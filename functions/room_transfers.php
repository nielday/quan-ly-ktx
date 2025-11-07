<?php
/**
 * Room Transfers functions - Các hàm xử lý yêu cầu chuyển phòng
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

/**
 * Lấy danh sách tất cả yêu cầu chuyển phòng
 * @param array $filters Mảng điều kiện lọc
 * @param int $limit Số lượng bản ghi
 * @param int $offset Vị trí bắt đầu
 * @return array
 */
function getAllRoomTransferRequests($filters = [], $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    $sql = "SELECT rtr.*, 
                   s.student_code, s.full_name as student_name, s.phone as student_phone,
                   cr.room_code as current_room_code, cb.building_code as current_building_code,
                   rr.room_code as requested_room_code, rb.building_code as requested_building_code,
                   u.full_name as reviewed_by_name
            FROM room_transfer_requests rtr
            INNER JOIN students s ON rtr.student_id = s.id
            INNER JOIN rooms cr ON rtr.current_room_id = cr.id
            LEFT JOIN buildings cb ON cr.building_id = cb.id
            LEFT JOIN rooms rr ON rtr.requested_room_id = rr.id
            LEFT JOIN buildings rb ON rr.building_id = rb.id
            LEFT JOIN users u ON rtr.reviewed_by = u.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Áp dụng filters
    if (!empty($filters['student_id'])) {
        $sql .= " AND rtr.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND rtr.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['current_room_id'])) {
        $sql .= " AND rtr.current_room_id = ?";
        $params[] = $filters['current_room_id'];
        $types .= 'i';
    }
    
    // Sắp xếp: pending trước, sau đó theo ngày tạo mới nhất
    $sql .= " ORDER BY 
              CASE rtr.status 
                WHEN 'pending' THEN 1 
                WHEN 'approved' THEN 2 
                WHEN 'rejected' THEN 3 
              END,
              rtr.created_at DESC";
    
    // Phân trang
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $requests = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $requests;
}

/**
 * Đếm tổng số yêu cầu chuyển phòng
 * @param array $filters
 * @return int
 */
function countRoomTransferRequests($filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM room_transfer_requests rtr WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($filters['student_id'])) {
        $sql .= " AND rtr.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND rtr.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total = $row['total'];
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $total;
}

/**
 * Lấy chi tiết một yêu cầu chuyển phòng
 * @param int $id
 * @return array|null
 */
function getRoomTransferRequestById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT rtr.*, 
                   s.student_code, s.full_name as student_name, s.phone as student_phone, s.email as student_email,
                   cr.room_code as current_room_code, cr.room_number as current_room_number, 
                   cr.capacity as current_room_capacity, cr.current_occupancy as current_room_occupancy,
                   cb.building_code as current_building_code, cb.building_name as current_building_name,
                   rr.room_code as requested_room_code, rr.room_number as requested_room_number,
                   rr.capacity as requested_room_capacity, rr.current_occupancy as requested_room_occupancy,
                   rb.building_code as requested_building_code, rb.building_name as requested_building_name,
                   u.full_name as reviewed_by_name
            FROM room_transfer_requests rtr
            INNER JOIN students s ON rtr.student_id = s.id
            INNER JOIN rooms cr ON rtr.current_room_id = cr.id
            LEFT JOIN buildings cb ON cr.building_id = cb.id
            LEFT JOIN rooms rr ON rtr.requested_room_id = rr.id
            LEFT JOIN buildings rb ON rr.building_id = rb.id
            LEFT JOIN users u ON rtr.reviewed_by = u.id
            WHERE rtr.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $request = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $request = mysqli_fetch_assoc($result);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $request;
}

/**
 * Tạo yêu cầu chuyển phòng (Sinh viên tạo)
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'request_id' => int]
 */
function createRoomTransferRequest($data) {
    $conn = getDbConnection();
    
    // Validate
    $required = ['student_id', 'current_room_id', 'reason'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Thiếu trường bắt buộc: $field"];
        }
    }
    
    // Kiểm tra sinh viên đang ở phòng hiện tại
    $sqlCheck = "SELECT ra.id 
                 FROM room_assignments ra
                 WHERE ra.student_id = ? 
                 AND ra.room_id = ? 
                 AND ra.status = 'active'";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "ii", $data['student_id'], $data['current_room_id']);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        mysqli_stmt_close($stmtCheck);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không ở phòng này!'];
    }
    mysqli_stmt_close($stmtCheck);
    
    // Kiểm tra đã có yêu cầu pending chưa
    $sqlPending = "SELECT id FROM room_transfer_requests 
                   WHERE student_id = ? AND status = 'pending'";
    $stmtPending = mysqli_prepare($conn, $sqlPending);
    mysqli_stmt_bind_param($stmtPending, "i", $data['student_id']);
    mysqli_stmt_execute($stmtPending);
    $resultPending = mysqli_stmt_get_result($stmtPending);
    
    if ($resultPending && mysqli_num_rows($resultPending) > 0) {
        mysqli_stmt_close($stmtPending);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Bạn đã có yêu cầu chuyển phòng đang chờ xử lý!'];
    }
    mysqli_stmt_close($stmtPending);
    
    // Chuẩn bị dữ liệu
    $requestedRoomId = !empty($data['requested_room_id']) ? $data['requested_room_id'] : null;
    $reason = $data['reason'];
    
    // Insert
    $sql = "INSERT INTO room_transfer_requests 
            (student_id, current_room_id, requested_room_id, reason, status)
            VALUES (?, ?, ?, ?, 'pending')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiis", 
        $data['student_id'],
        $data['current_room_id'],
        $requestedRoomId,
        $reason
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $request_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return [
            'success' => true, 
            'message' => 'Tạo yêu cầu chuyển phòng thành công!',
            'request_id' => $request_id
        ];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

/**
 * Duyệt yêu cầu chuyển phòng (Manager)
 * QUAN TRỌNG: Terminate hợp đồng cũ, tạo hợp đồng mới, cập nhật room_assignments, rooms NHƯNG KHÔNG cập nhật invoices
 * @param int $requestId
 * @param int $newRoomId Phòng mới (có thể khác phòng sinh viên yêu cầu)
 * @param int $reviewedBy Manager ID
 * @return array ['success' => bool, 'message' => string]
 */
function approveRoomTransferRequest($requestId, $newRoomId, $reviewedBy) {
    require_once __DIR__ . '/contracts.php';
    
    $conn = getDbConnection();
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Lấy thông tin yêu cầu
        $request = getRoomTransferRequestById($requestId);
        
        if (!$request) {
            throw new Exception('Yêu cầu không tồn tại!');
        }
        
        if ($request['status'] !== 'pending') {
            throw new Exception('Yêu cầu đã được xử lý rồi!');
        }
        
        $studentId = $request['student_id'];
        $oldRoomId = $request['current_room_id'];
        
        // 2. Kiểm tra phòng mới có đủ chỗ không và lấy thông tin phòng (bao gồm room_type)
        require_once __DIR__ . '/rooms.php';
        require_once __DIR__ . '/pricing.php';
        
        $newRoom = getRoomById($newRoomId);
        
        if (!$newRoom) {
            throw new Exception('Phòng mới không tồn tại!');
        }
        
        if ($newRoom['current_occupancy'] >= $newRoom['capacity']) {
            throw new Exception('Phòng mới đã đầy!');
        }
        
        // 2.1. Lấy giá phòng mới từ Pricing dựa trên room_type
        $newRoomType = $newRoom['room_type'];
        $newMonthlyFee = getRoomPriceFromPricing($newRoomType);
        
        if ($newMonthlyFee === null || $newMonthlyFee <= 0) {
            throw new Exception('Không tìm thấy đơn giá cho loại phòng: ' . $newRoomType . '! Vui lòng cấu hình đơn giá trước.');
        }
        
        // 3. Lấy hợp đồng cũ
        $sqlContract = "SELECT id, end_date, monthly_fee, deposit FROM contracts 
                        WHERE student_id = ? AND status = 'active' 
                        LIMIT 1";
        $stmtContract = mysqli_prepare($conn, $sqlContract);
        mysqli_stmt_bind_param($stmtContract, "i", $studentId);
        mysqli_stmt_execute($stmtContract);
        $resultContract = mysqli_stmt_get_result($stmtContract);
        $oldContract = mysqli_fetch_assoc($resultContract);
        mysqli_stmt_close($stmtContract);
        
        if (!$oldContract) {
            throw new Exception('Không tìm thấy hợp đồng active của sinh viên!');
        }
        
        $oldContractId = $oldContract['id'];
        $oldEndDate = $oldContract['end_date'];
        $deposit = $oldContract['deposit'] ?? 0;
        // Lưu ý: monthly_fee sẽ lấy từ giá phòng mới, không dùng giá cũ
        
        // 4. Terminate hợp đồng cũ (trong cùng transaction)
        $today = date('Y-m-d');
        $terminatedAt = date('Y-m-d H:i:s');
        
        // 4.1. Cập nhật contract status = terminated
        $sqlTerminate = "UPDATE contracts 
                        SET status = 'terminated', terminated_at = ? 
                        WHERE id = ?";
        $stmtTerminate = mysqli_prepare($conn, $sqlTerminate);
        mysqli_stmt_bind_param($stmtTerminate, "si", $terminatedAt, $oldContractId);
        if (!mysqli_stmt_execute($stmtTerminate)) {
            throw new Exception('Lỗi terminate hợp đồng cũ: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtTerminate);
        
        // 4.2. End room_assignment cũ (end_date = today, status = moved_out)
        $sqlEndAssignment = "UPDATE room_assignments 
                            SET end_date = ?, status = 'moved_out' 
                            WHERE student_id = ? AND room_id = ? AND status = 'active'";
        $stmtEndAssignment = mysqli_prepare($conn, $sqlEndAssignment);
        mysqli_stmt_bind_param($stmtEndAssignment, "sii", $today, $studentId, $oldRoomId);
        if (!mysqli_stmt_execute($stmtEndAssignment)) {
            throw new Exception('Lỗi end room_assignment cũ: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtEndAssignment);
        
        // 4.3. Giảm occupancy phòng cũ
        $sqlDecreaseOld = "UPDATE rooms SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?";
        $stmtDecreaseOld = mysqli_prepare($conn, $sqlDecreaseOld);
        mysqli_stmt_bind_param($stmtDecreaseOld, "i", $oldRoomId);
        if (!mysqli_stmt_execute($stmtDecreaseOld)) {
            throw new Exception('Lỗi giảm occupancy phòng cũ: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtDecreaseOld);
        
        // 4.4. Cập nhật status phòng cũ (nếu occupancy = 0 thì available)
        $sqlStatusOld = "UPDATE rooms SET status = CASE 
                         WHEN current_occupancy = 0 THEN 'available' 
                         ELSE 'occupied' 
                         END 
                         WHERE id = ?";
        $stmtStatusOld = mysqli_prepare($conn, $sqlStatusOld);
        mysqli_stmt_bind_param($stmtStatusOld, "i", $oldRoomId);
        mysqli_stmt_execute($stmtStatusOld);
        mysqli_stmt_close($stmtStatusOld);
        
        // 5. Tạo hợp đồng mới cho phòng mới
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // 5.1. Tạo mã hợp đồng (trong cùng transaction)
        $prefix = 'HD' . date('Y');
        $sqlCount = "SELECT COUNT(*) as count FROM contracts WHERE contract_code LIKE ?";
        $likePattern = $prefix . '%';
        $stmtCount = mysqli_prepare($conn, $sqlCount);
        $count = 0;
        if ($stmtCount) {
            mysqli_stmt_bind_param($stmtCount, "s", $likePattern);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
            if ($resultCount && mysqli_num_rows($resultCount) > 0) {
                $rowCount = mysqli_fetch_assoc($resultCount);
                $count = intval($rowCount['count']);
            }
            mysqli_stmt_close($stmtCount);
        }
        $count++;
        $contractCode = $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
        
        // Kiểm tra mã đã tồn tại chưa (trong transaction)
        $sqlCheckCode = "SELECT id FROM contracts WHERE contract_code = ?";
        $stmtCheckCode = mysqli_prepare($conn, $sqlCheckCode);
        mysqli_stmt_bind_param($stmtCheckCode, "s", $contractCode);
        mysqli_stmt_execute($stmtCheckCode);
        $resultCheckCode = mysqli_stmt_get_result($stmtCheckCode);
        if ($resultCheckCode && mysqli_num_rows($resultCheckCode) > 0) {
            // Tạo lại mã nếu trùng (tăng count)
            $count++;
            $contractCode = $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
        }
        mysqli_stmt_close($stmtCheckCode);
        
        // 5.2. Insert hợp đồng mới (monthly_fee = giá phòng mới)
        $sqlNewContract = "INSERT INTO contracts 
                          (student_id, room_id, contract_code, start_date, end_date, monthly_fee, deposit, status, signed_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)";
        $stmtNewContract = mysqli_prepare($conn, $sqlNewContract);
        mysqli_stmt_bind_param($stmtNewContract, "iisssdd", $studentId, $newRoomId, $contractCode, $tomorrow, $oldEndDate, $newMonthlyFee, $deposit);
        if (!mysqli_stmt_execute($stmtNewContract)) {
            throw new Exception('Lỗi tạo hợp đồng mới: ' . mysqli_error($conn));
        }
        $newContractId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtNewContract);
        
        // 5.3. Tạo room_assignment mới
        $sqlNewAssignment = "INSERT INTO room_assignments 
                            (contract_id, student_id, room_id, assigned_date, status) 
                            VALUES (?, ?, ?, ?, 'active')";
        $stmtNewAssignment = mysqli_prepare($conn, $sqlNewAssignment);
        mysqli_stmt_bind_param($stmtNewAssignment, "iiis", $newContractId, $studentId, $newRoomId, $tomorrow);
        if (!mysqli_stmt_execute($stmtNewAssignment)) {
            throw new Exception('Lỗi tạo room_assignment mới: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtNewAssignment);
        
        // 5.4. Tăng occupancy phòng mới
        $sqlIncreaseNew = "UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?";
        $stmtIncreaseNew = mysqli_prepare($conn, $sqlIncreaseNew);
        mysqli_stmt_bind_param($stmtIncreaseNew, "i", $newRoomId);
        if (!mysqli_stmt_execute($stmtIncreaseNew)) {
            throw new Exception('Lỗi tăng occupancy phòng mới: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtIncreaseNew);
        
        // 5.5. Cập nhật status phòng mới (nếu đầy thì occupied)
        $sqlStatusNew = "UPDATE rooms SET status = CASE 
                         WHEN current_occupancy >= capacity THEN 'occupied' 
                         ELSE 'available' 
                         END 
                         WHERE id = ?";
        $stmtStatusNew = mysqli_prepare($conn, $sqlStatusNew);
        mysqli_stmt_bind_param($stmtStatusNew, "i", $newRoomId);
        mysqli_stmt_execute($stmtStatusNew);
        mysqli_stmt_close($stmtStatusNew);
        
        // 6. Cập nhật status yêu cầu thành approved
        $reviewedAt = date('Y-m-d H:i:s');
        $sqlApprove = "UPDATE room_transfer_requests 
                       SET status = 'approved', reviewed_by = ?, reviewed_at = ? 
                       WHERE id = ?";
        $stmtApprove = mysqli_prepare($conn, $sqlApprove);
        mysqli_stmt_bind_param($stmtApprove, "isi", $reviewedBy, $reviewedAt, $requestId);
        if (!mysqli_stmt_execute($stmtApprove)) {
            throw new Exception('Lỗi cập nhật status yêu cầu: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmtApprove);
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return [
            'success' => true,
            'message' => 'Duyệt yêu cầu chuyển phòng thành công! Hợp đồng cũ đã được thanh lý, hợp đồng mới đã được tạo với giá phòng mới (' . number_format($newMonthlyFee, 0, ',', '.') . ' VNĐ/tháng). Sinh viên sẽ chuyển từ ngày mai.'
        ];
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Từ chối yêu cầu chuyển phòng
 * @param int $requestId
 * @param string $rejectionReason
 * @param int $reviewedBy Manager ID
 * @return array ['success' => bool, 'message' => string]
 */
function rejectRoomTransferRequest($requestId, $rejectionReason, $reviewedBy) {
    $conn = getDbConnection();
    
    // Kiểm tra yêu cầu
    $request = getRoomTransferRequestById($requestId);
    
    if (!$request) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Yêu cầu không tồn tại!'];
    }
    
    if ($request['status'] !== 'pending') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Yêu cầu đã được xử lý rồi!'];
    }
    
    // Cập nhật status thành rejected
    $reviewedAt = date('Y-m-d H:i:s');
    $sql = "UPDATE room_transfer_requests 
            SET status = 'rejected', reviewed_by = ?, reviewed_at = ?, reason = CONCAT(reason, '\n\n[LÝ DO TỪ CHỐI]: ', ?)
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $reviewedBy, $reviewedAt, $rejectionReason, $requestId);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Đã từ chối yêu cầu chuyển phòng!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

/**
 * Lấy danh sách trạng thái
 * @return array
 */
function getRoomTransferStatuses() {
    return [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Đã từ chối'
    ];
}

/**
 * Lấy thống kê yêu cầu chuyển phòng
 * @return array
 */
function getRoomTransferStatistics() {
    $conn = getDbConnection();
    
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM room_transfer_requests";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['total'] = intval($row['total']);
        $stats['pending'] = intval($row['pending']);
        $stats['approved'] = intval($row['approved']);
        $stats['rejected'] = intval($row['rejected']);
    }
    
    mysqli_close($conn);
    
    return $stats;
}

