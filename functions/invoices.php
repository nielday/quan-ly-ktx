<?php
/**
 * Invoices functions - Các hàm xử lý hóa đơn
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/contracts.php';
require_once __DIR__ . '/rooms.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/room_services.php';
require_once __DIR__ . '/room_assignments.php';
require_once __DIR__ . '/students.php';

/**
 * Tạo mã hóa đơn tự động
 * @param string $invoiceMonth Tháng hóa đơn (YYYY-MM)
 * @return string
 */
function generateInvoiceCode($invoiceMonth) {
    $conn = getDbConnection();
    $year = substr($invoiceMonth, 0, 4);
    $month = substr($invoiceMonth, 5, 2);
    $prefix = 'HD' . $year . $month;
    
    $sql = "SELECT COUNT(*) as count FROM invoices WHERE invoice_code LIKE ?";
    $likePattern = $prefix . '%';
    $stmt = mysqli_prepare($conn, $sql);
    $count = 0;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $likePattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $count = $row['count'];
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    $count++;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Lấy đơn giá theo tháng hóa đơn
 * @param string $priceType Loại đơn giá
 * @param string $invoiceMonth Tháng hóa đơn (YYYY-MM)
 * @return array|null
 */
function getPricingForInvoiceMonth($priceType, $invoiceMonth) {
    // Lấy ngày đầu tháng để kiểm tra đơn giá
    $checkDate = $invoiceMonth . '-01';
    return getCurrentPricing($priceType, $checkDate);
}

/**
 * Tính toán chi tiết hóa đơn cho một phòng
 * @param int $roomId
 * @param string $invoiceMonth Tháng hóa đơn (YYYY-MM)
 * @param float $electricityAmount Số kWh điện của cả phòng
 * @param float $waterAmount Số m³ nước của cả phòng
 * @return array|null ['success' => bool, 'data' => array, 'message' => string]
 */
function calculateInvoiceForRoom($roomId, $invoiceMonth, $electricityAmount, $waterAmount) {
    // Lấy thông tin phòng
    $room = getRoomById($roomId);
    if (!$room) {
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    // Lấy danh sách sinh viên đang ở trong phòng (active)
    $studentsInRoom = getStudentsInRoom($roomId);
    if (empty($studentsInRoom)) {
        return ['success' => false, 'message' => 'Phòng không có sinh viên nào đang ở!'];
    }
    
    $occupancyCount = count($studentsInRoom);
    
    // Lấy đơn giá theo tháng hóa đơn
    $checkDate = $invoiceMonth . '-01';
    
    // 1. Lấy giá phòng
    $roomPriceType = null;
    $roomTypeMap = [
        'đơn' => 'room_single',
        'đôi' => 'room_double',
        '4 người' => 'room_4people',
        '6 người' => 'room_6people'
    ];
    $roomPriceType = $roomTypeMap[$room['room_type']] ?? null;
    
    if (!$roomPriceType) {
        return ['success' => false, 'message' => 'Loại phòng không hợp lệ!'];
    }
    
    $roomPricing = getCurrentPricing($roomPriceType, $checkDate);
    if (!$roomPricing) {
        return ['success' => false, 'message' => 'Chưa có đơn giá cho loại phòng này trong tháng ' . $invoiceMonth . '!'];
    }
    $roomTotalFee = floatval($roomPricing['price_value']);
    $roomFeePerPerson = $roomTotalFee / $occupancyCount;
    
    // 2. Lấy đơn giá điện
    $electricityPricing = getCurrentPricing('electricity', $checkDate);
    if (!$electricityPricing) {
        return ['success' => false, 'message' => 'Chưa có đơn giá điện trong tháng ' . $invoiceMonth . '!'];
    }
    $electricityUnitPrice = floatval($electricityPricing['price_value']);
    $electricityTotalRoom = $electricityAmount * $electricityUnitPrice;
    $electricityAmountPerPerson = $electricityTotalRoom / $occupancyCount;
    
    // 3. Lấy đơn giá nước
    $waterPricing = getCurrentPricing('water', $checkDate);
    if (!$waterPricing) {
        return ['success' => false, 'message' => 'Chưa có đơn giá nước trong tháng ' . $invoiceMonth . '!'];
    }
    $waterUnitPrice = floatval($waterPricing['price_value']);
    $waterTotalRoom = $waterAmount * $waterUnitPrice;
    $waterAmountPerPerson = $waterTotalRoom / $occupancyCount;
    
    // 4. Lấy dịch vụ của phòng
    $roomServices = getRoomServices($roomId);
    $serviceTotalRoom = 0;
    $serviceDetails = [];
    
    foreach ($roomServices as $service) {
        $servicePrice = floatval($service['price']);
        $serviceTotalRoom += $servicePrice;
        $serviceDetails[] = [
            'service_name' => $service['service_name'],
            'price' => $servicePrice
        ];
    }
    
    $serviceAmountPerPerson = $serviceTotalRoom / $occupancyCount;
    
    // 5. Tính subtotal (chưa có vi phạm)
    $subtotalPerPerson = $roomFeePerPerson + $electricityAmountPerPerson + $waterAmountPerPerson + $serviceAmountPerPerson;
    
    // Trả về dữ liệu tính toán
    return [
        'success' => true,
        'data' => [
            'room' => $room,
            'students' => $studentsInRoom,
            'occupancy_count' => $occupancyCount,
            'room_total_fee' => $roomTotalFee,
            'room_fee_per_person' => $roomFeePerPerson,
            'electricity_amount' => $electricityAmount,
            'electricity_unit_price' => $electricityUnitPrice,
            'electricity_total_room' => $electricityTotalRoom,
            'electricity_amount_per_person' => $electricityAmountPerPerson,
            'water_amount' => $waterAmount,
            'water_unit_price' => $waterUnitPrice,
            'water_total_room' => $waterTotalRoom,
            'water_amount_per_person' => $waterAmountPerPerson,
            'service_total_room' => $serviceTotalRoom,
            'service_amount_per_person' => $serviceAmountPerPerson,
            'service_details' => $serviceDetails,
            'subtotal_per_person' => $subtotalPerPerson
        ]
    ];
}

/**
 * Lấy phí vi phạm của sinh viên trong tháng
 * @param int $studentId
 * @param string $invoiceMonth Tháng hóa đơn (YYYY-MM)
 * @return float
 */
function getStudentViolationFeeForMonth($studentId, $invoiceMonth) {
    $conn = getDbConnection();
    $totalFee = 0;
    
    // Lấy ngày đầu và cuối tháng
    $startDate = $invoiceMonth . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "SELECT SUM(penalty_amount) as total 
            FROM violations 
            WHERE student_id = ? 
            AND violation_date >= ? 
            AND violation_date <= ?
            AND penalty_type = 'fine'";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $studentId, $startDate, $endDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $totalFee = floatval($row['total'] ?? 0);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $totalFee;
}

/**
 * Kiểm tra hóa đơn đã tồn tại cho phòng trong tháng
 * @param int $roomId
 * @param string $invoiceMonth
 * @return bool
 */
function hasInvoiceForRoomInMonth($roomId, $invoiceMonth) {
    $conn = getDbConnection();
    $exists = false;
    
    $sql = "SELECT COUNT(*) as count 
            FROM invoices 
            WHERE room_id = ? 
            AND invoice_month = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $roomId, $invoiceMonth);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $exists = ($row['count'] > 0);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $exists;
}

/**
 * Kiểm tra hóa đơn đã tồn tại cho sinh viên trong tháng
 * @param int $studentId
 * @param string $invoiceMonth
 * @return bool
 */
function hasInvoiceForStudentInMonth($studentId, $invoiceMonth) {
    $conn = getDbConnection();
    $exists = false;
    
    $sql = "SELECT id FROM invoices 
            WHERE student_id = ? 
            AND invoice_month = ? 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $studentId, $invoiceMonth);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = ($result && mysqli_num_rows($result) > 0);
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $exists;
}

/**
 * Tạo hóa đơn cho tất cả sinh viên trong phòng
 * @param int $roomId
 * @param string $invoiceMonth Tháng hóa đơn (YYYY-MM)
 * @param float $electricityAmount Số kWh điện
 * @param float $waterAmount Số m³ nước
 * @param int $createdBy ID của Manager tạo
 * @param string|null $dueDate Hạn thanh toán (null = tự động tính)
 * @return array ['success' => bool, 'message' => string, 'invoice_ids' => array]
 */
function createInvoicesForRoom($roomId, $invoiceMonth, $electricityAmount, $waterAmount, $createdBy, $dueDate = null) {
    $conn = getDbConnection();
    
    // Validation
    if ($roomId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Room ID không hợp lệ!'];
    }
    
    if (empty($invoiceMonth) || !preg_match('/^\d{4}-\d{2}$/', $invoiceMonth)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tháng hóa đơn không đúng định dạng (YYYY-MM)!'];
    }
    
    if ($electricityAmount < 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số kWh điện không hợp lệ!'];
    }
    
    if ($waterAmount < 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số m³ nước không hợp lệ!'];
    }
    
    // Kiểm tra đã có hóa đơn cho phòng trong tháng này chưa
    if (hasInvoiceForRoomInMonth($roomId, $invoiceMonth)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đã có hóa đơn cho phòng này trong tháng ' . $invoiceMonth . '!'];
    }
    
    // Tính toán chi tiết
    $calculation = calculateInvoiceForRoom($roomId, $invoiceMonth, $electricityAmount, $waterAmount);
    if (!$calculation['success']) {
        mysqli_close($conn);
        return $calculation;
    }
    
    $data = $calculation['data'];
    $students = $data['students'];
    
    // Tính hạn thanh toán (mặc định: cuối tháng sau)
    if (!$dueDate) {
        $nextMonth = date('Y-m-d', strtotime($invoiceMonth . '-01 +1 month'));
        $dueDate = date('Y-m-t', strtotime($nextMonth));
    }
    
    // Chuyển service_details thành JSON
    $serviceDetailsJson = json_encode($data['service_details'], JSON_UNESCAPED_UNICODE);
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    $invoiceIds = [];
    
    try {
        // Lấy số lượng hóa đơn hiện có trong tháng để tạo mã tăng dần
        $year = substr($invoiceMonth, 0, 4);
        $month = substr($invoiceMonth, 5, 2);
        $prefix = 'HD' . $year . $month;
        
        $sqlCount = "SELECT COUNT(*) as count FROM invoices WHERE invoice_code LIKE ?";
        $likePattern = $prefix . '%';
        $stmtCount = mysqli_prepare($conn, $sqlCount);
        $baseCount = 0;
        
        if ($stmtCount) {
            mysqli_stmt_bind_param($stmtCount, "s", $likePattern);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
            if ($resultCount && mysqli_num_rows($resultCount) > 0) {
                $rowCount = mysqli_fetch_assoc($resultCount);
                $baseCount = intval($rowCount['count']);
            }
            mysqli_stmt_close($stmtCount);
        }
        
        // Tạo hóa đơn cho từng sinh viên
        $invoiceCounter = 0;
        foreach ($students as $student) {
            $studentId = $student['student_id'];
            $contractId = $student['contract_id'];
            
            // Kiểm tra sinh viên đã có hóa đơn trong tháng này chưa
            if (hasInvoiceForStudentInMonth($studentId, $invoiceMonth)) {
                continue; // Bỏ qua nếu đã có
            }
            
            // Lấy phí vi phạm của sinh viên
            $violationFee = getStudentViolationFeeForMonth($studentId, $invoiceMonth);
            
            // Tính tổng tiền
            $subtotal = $data['subtotal_per_person'];
            $totalAmount = $subtotal + $violationFee;
            
            // Tạo mã hóa đơn tăng dần trong transaction
            $invoiceCounter++;
            $invoiceCode = $prefix . str_pad($baseCount + $invoiceCounter, 4, '0', STR_PAD_LEFT);
            
            // Insert hóa đơn
            // Đếm: 24 tham số
            // i i i i s d i d d d d d d d d d d d d s d d d s i
            // 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24
            $sql = "INSERT INTO invoices (
                        student_id, contract_id, room_id, invoice_code, invoice_month,
                        room_total_fee, room_occupancy_count, room_fee_per_person,
                        electricity_total_room, electricity_amount_per_person, electricity_amount, electricity_unit_price,
                        water_total_room, water_amount_per_person, water_amount, water_unit_price,
                        service_total_room, service_amount_per_person, service_details,
                        violation_fee, subtotal, total_amount, due_date, status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                throw new Exception('Lỗi chuẩn bị câu lệnh SQL: ' . mysqli_error($conn));
            }
            
            // Format: iiiisdiidddddddddddsdsdi (24 ký tự)
            mysqli_stmt_bind_param($stmt, "iiiisdiidddddddddddsdsdi",
                $studentId, $contractId, $roomId, $invoiceCode, $invoiceMonth,
                $data['room_total_fee'], $data['occupancy_count'], $data['room_fee_per_person'],
                $data['electricity_total_room'], $data['electricity_amount_per_person'], $data['electricity_amount'], $data['electricity_unit_price'],
                $data['water_total_room'], $data['water_amount_per_person'], $data['water_amount'], $data['water_unit_price'],
                $data['service_total_room'], $data['service_amount_per_person'], $serviceDetailsJson,
                $violationFee, $subtotal, $totalAmount, $dueDate, $createdBy
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Lỗi tạo hóa đơn: ' . mysqli_error($conn));
            }
            
            $invoiceIds[] = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
        
        if (empty($invoiceIds)) {
            throw new Exception('Tất cả sinh viên đã có hóa đơn trong tháng này!');
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return [
            'success' => true,
            'message' => 'Tạo ' . count($invoiceIds) . ' hóa đơn thành công!',
            'invoice_ids' => $invoiceIds
        ];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy danh sách tất cả hóa đơn
 * @param string|null $status Lọc theo status
 * @param string|null $invoiceMonth Lọc theo tháng
 * @return array
 */
function getAllInvoices($status = null, $invoiceMonth = null) {
    $conn = getDbConnection();
    $invoices = [];
    
    $sql = "SELECT i.*, 
                   s.student_code, s.full_name as student_name,
                   r.room_code, r.room_number,
                   b.building_code, b.building_name,
                   c.contract_code,
                   u.full_name as created_by_name
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.id
            INNER JOIN rooms r ON i.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN contracts c ON i.contract_id = c.id
            LEFT JOIN users u ON i.created_by = u.id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($status) {
        $conditions[] = "i.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($invoiceMonth) {
        $conditions[] = "i.invoice_month = ?";
        $params[] = $invoiceMonth;
        $types .= "s";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY i.invoice_month DESC, i.created_at DESC";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $invoices[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $invoices[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $invoices;
}

/**
 * Lấy hóa đơn theo ID
 * @param int $id
 * @return array|null
 */
function getInvoiceById($id) {
    $conn = getDbConnection();
    $invoice = null;
    
    $sql = "SELECT i.*, 
                   s.student_code, s.full_name as student_name, s.phone, s.email,
                   r.room_code, r.room_number, r.room_type,
                   b.building_code, b.building_name,
                   c.contract_code,
                   u.full_name as created_by_name
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.id
            INNER JOIN rooms r ON i.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN contracts c ON i.contract_id = c.id
            LEFT JOIN users u ON i.created_by = u.id
            WHERE i.id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $invoice = mysqli_fetch_assoc($result);
            // Parse service_details từ JSON
            if (!empty($invoice['service_details'])) {
                $invoice['service_details_array'] = json_decode($invoice['service_details'], true);
            } else {
                $invoice['service_details_array'] = [];
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $invoice;
}

/**
 * Lấy hóa đơn của sinh viên
 * @param int $studentId
 * @return array
 */
function getInvoicesByStudentId($studentId) {
    $conn = getDbConnection();
    $invoices = [];
    
    $sql = "SELECT i.*, 
                   r.room_code, r.room_number,
                   b.building_code, b.building_name
            FROM invoices i
            INNER JOIN rooms r ON i.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE i.student_id = ?
            ORDER BY i.invoice_month DESC, i.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $invoices[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $invoices;
}

/**
 * Lấy các status có thể chọn
 * @return array
 */
function getInvoiceStatuses() {
    return [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'overdue' => 'Quá hạn',
        'cancelled' => 'Đã hủy'
    ];
}

?>

