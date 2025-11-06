<?php
/**
 * Contracts functions - Các hàm xử lý hợp đồng
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/rooms.php';
require_once __DIR__ . '/pricing.php';

/**
 * Lấy danh sách tất cả hợp đồng
 * @param string|null $status Lọc theo status (null = tất cả)
 * @return array
 */
function getAllContracts($status = null) {
    $conn = getDbConnection();
    $contracts = [];
    
    if ($status) {
        $sql = "SELECT c.*, 
                       s.student_code, s.full_name as student_name,
                       r.room_code, r.room_number, r.room_type,
                       b.building_code, b.building_name
                FROM contracts c 
                INNER JOIN students s ON c.student_id = s.id
                INNER JOIN rooms r ON c.room_id = r.id
                LEFT JOIN buildings b ON r.building_id = b.id
                WHERE c.status = ? 
                ORDER BY c.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $status);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $contracts[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT c.*, 
                       s.student_code, s.full_name as student_name,
                       r.room_code, r.room_number, r.room_type,
                       b.building_code, b.building_name
                FROM contracts c 
                INNER JOIN students s ON c.student_id = s.id
                INNER JOIN rooms r ON c.room_id = r.id
                LEFT JOIN buildings b ON r.building_id = b.id
                ORDER BY c.created_at DESC";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $contracts[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $contracts;
}

/**
 * Lấy hợp đồng theo ID
 * @param int $id
 * @return array|null
 */
function getContractById($id) {
    $conn = getDbConnection();
    $contract = null;
    
    $sql = "SELECT c.*, 
                   s.student_code, s.full_name as student_name, s.phone, s.email,
                   r.room_code, r.room_number, r.room_type, r.capacity,
                   b.building_code, b.building_name
            FROM contracts c 
            INNER JOIN students s ON c.student_id = s.id
            INNER JOIN rooms r ON c.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE c.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $contract = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $contract;
}

/**
 * Lấy hợp đồng của sinh viên
 * @param int $studentId
 * @return array|null
 */
function getContractByStudentId($studentId) {
    $conn = getDbConnection();
    $contract = null;
    
    $sql = "SELECT c.*, 
                   r.room_code, r.room_number, r.room_type,
                   b.building_code, b.building_name
            FROM contracts c 
            INNER JOIN rooms r ON c.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE c.student_id = ? 
            AND c.status = 'active'
            ORDER BY c.created_at DESC
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $contract = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $contract;
}

/**
 * Kiểm tra xem application đã có hợp đồng chưa
 * (Kiểm tra xem có hợp đồng nào được tạo sau khi application được approved không)
 * @param int $applicationId
 * @param int $studentId
 * @param string $approvedAt Ngày duyệt đơn (approved_at)
 * @return array|null Hợp đồng nếu có, null nếu chưa có
 */
function getContractByApplication($applicationId, $studentId, $approvedAt = null) {
    $conn = getDbConnection();
    $contract = null;
    
    // Nếu có approved_at, chỉ tìm hợp đồng được tạo sau khi duyệt đơn
    if ($approvedAt) {
        $sql = "SELECT c.*, 
                       r.room_code, r.room_number, r.room_type,
                       b.building_code, b.building_name
                FROM contracts c 
                INNER JOIN rooms r ON c.room_id = r.id
                LEFT JOIN buildings b ON r.building_id = b.id
                WHERE c.student_id = ? 
                AND c.created_at >= ?
                ORDER BY c.created_at ASC
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $studentId, $approvedAt);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $contract = mysqli_fetch_assoc($result);
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        // Nếu không có approved_at, tìm hợp đồng active gần nhất
        $contract = getContractByStudentId($studentId);
    }
    
    mysqli_close($conn);
    return $contract;
}

/**
 * Tạo mã hợp đồng tự động
 * @return string
 */
function generateContractCode() {
    $conn = getDbConnection();
    $prefix = 'HD' . date('Y');
    $sql = "SELECT COUNT(*) as count FROM contracts WHERE contract_code LIKE ?";
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
    return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
}

/**
 * Kiểm tra mã hợp đồng đã tồn tại chưa
 * @param string $contractCode
 * @param int|null $excludeId
 * @return bool
 */
function isContractCodeExists($contractCode, $excludeId = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($excludeId) {
        $sql = "SELECT id FROM contracts WHERE contract_code = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $contractCode, $excludeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT id FROM contracts WHERE contract_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $contractCode);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
    return $exists;
}

/**
 * Tạo hợp đồng mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createContract($data) {
    $conn = getDbConnection();
    
    $studentId = intval($data['student_id'] ?? 0);
    $roomId = intval($data['room_id'] ?? 0);
    $contractCode = trim($data['contract_code'] ?? '');
    $startDate = trim($data['start_date'] ?? date('Y-m-d'));
    $endDate = trim($data['end_date'] ?? '');
    $monthlyFee = !empty($data['monthly_fee']) ? floatval($data['monthly_fee']) : null;
    $deposit = !empty($data['deposit']) ? floatval($data['deposit']) : 0;
    $status = trim($data['status'] ?? 'active');
    $applicationId = !empty($data['application_id']) ? intval($data['application_id']) : null;
    $applicationApprovedAt = !empty($data['application_approved_at']) ? trim($data['application_approved_at']) : null;
    
    // Validation
    if ($studentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn sinh viên!'];
    }
    
    if ($roomId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn phòng!'];
    }
    
    if (empty($startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày bắt đầu không được để trống!'];
    }
    
    if (empty($endDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc không được để trống!'];
    }
    
    if (strtotime($endDate) < strtotime($startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc phải sau ngày bắt đầu!'];
    }
    
    if (!in_array($status, ['active', 'expired', 'terminated'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    // Kiểm tra sinh viên có tồn tại không
    $student = getStudentById($studentId);
    if (!$student) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không tồn tại!'];
    }
    
    // Kiểm tra phòng có tồn tại không
    $room = getRoomById($roomId);
    if (!$room) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    // Kiểm tra phòng còn chỗ không
    if ($room['current_occupancy'] >= $room['capacity']) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng đã đầy!'];
    }
    
    // Kiểm tra nếu có application_id: đơn đăng ký này đã có hợp đồng chưa
    if ($applicationId && $applicationApprovedAt) {
        $existingContract = getContractByApplication($applicationId, $studentId, $applicationApprovedAt);
        if ($existingContract) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Đơn đăng ký này đã có hợp đồng rồi! Mỗi đơn đăng ký chỉ được tạo 1 hợp đồng.'];
        }
    } else {
        // Kiểm tra sinh viên đã có hợp đồng active chưa (nếu không có application_id)
        $existingContract = getContractByStudentId($studentId);
        if ($existingContract) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Sinh viên đã có hợp đồng đang active!'];
        }
    }
    
    // Tạo mã hợp đồng nếu chưa có
    if (empty($contractCode)) {
        $contractCode = generateContractCode();
    }
    
    // Kiểm tra mã hợp đồng đã tồn tại chưa
    if (isContractCodeExists($contractCode)) {
        $contractCode = generateContractCode(); // Tạo lại nếu trùng
    }
    
    // Lấy giá phòng nếu chưa có monthly_fee
    if ($monthlyFee === null) {
        $roomPrice = getRoomPriceFromPricing($room['room_type']);
        $monthlyFee = $roomPrice ? $roomPrice : 0;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày bắt đầu không đúng định dạng (Y-m-d)!'];
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc không đúng định dạng (Y-m-d)!'];
    }
    
    // Insert contract
    $sql = "INSERT INTO contracts (student_id, room_id, contract_code, start_date, end_date, monthly_fee, deposit, status, signed_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "iisssdds", $studentId, $roomId, $contractCode, $startDate, $endDate, $monthlyFee, $deposit, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $contractId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Tự động tạo room_assignment và cập nhật current_occupancy
        require_once __DIR__ . '/room_assignments.php';
        $assignmentResult = createRoomAssignment([
            'contract_id' => $contractId,
            'student_id' => $studentId,
            'room_id' => $roomId,
            'assigned_date' => $startDate
        ]);
        
        if (!$assignmentResult['success']) {
            // Nếu tạo assignment thất bại, xóa contract
            mysqli_query($conn, "DELETE FROM contracts WHERE id = $contractId");
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Lỗi phân phòng: ' . $assignmentResult['message']];
        }
        
        // Nếu có tiền đặt cọc, tạo payment đặt cọc
        if ($deposit > 0) {
            require_once __DIR__ . '/payments.php';
            // Lấy created_by từ session hoặc từ data nếu có
            $createdBy = !empty($data['created_by']) ? intval($data['created_by']) : null;
            
            $depositResult = createDepositPayment(
                $contractId, 
                $studentId, 
                $deposit, 
                $createdBy, // Tự động confirmed nếu có created_by
                $startDate, // Ngày thanh toán = ngày bắt đầu hợp đồng
                'cash', // Mặc định là tiền mặt
                'Tiền đặt cọc khi ký hợp đồng'
            );
            
            // Nếu tạo payment thất bại, chỉ log warning, không rollback contract
            // Vì hợp đồng vẫn hợp lệ dù chưa có payment record
            // (Có thể do database chưa được migration)
            if (!$depositResult['success']) {
                // Log warning nhưng không fail contract creation
                // Hợp đồng vẫn được tạo thành công, chỉ là chưa có payment record
                error_log('Warning: Không thể tạo payment đặt cọc: ' . $depositResult['message']);
            }
        }
        
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo hợp đồng và phân phòng thành công!', 'id' => $contractId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo hợp đồng: ' . $error];
    }
}

/**
 * Cập nhật hợp đồng
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateContract($id, $data) {
    $conn = getDbConnection();
    
    $contractCode = trim($data['contract_code'] ?? '');
    $startDate = trim($data['start_date'] ?? '');
    $endDate = trim($data['end_date'] ?? '');
    $monthlyFee = !empty($data['monthly_fee']) ? floatval($data['monthly_fee']) : null;
    $deposit = !empty($data['deposit']) ? floatval($data['deposit']) : 0;
    $status = trim($data['status'] ?? 'active');
    
    // Validation
    if (empty($contractCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã hợp đồng không được để trống!'];
    }
    
    if (empty($startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày bắt đầu không được để trống!'];
    }
    
    if (empty($endDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc không được để trống!'];
    }
    
    if (strtotime($endDate) < strtotime($startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc phải sau ngày bắt đầu!'];
    }
    
    if (!in_array($status, ['active', 'expired', 'terminated'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    // Kiểm tra hợp đồng có tồn tại không
    $existingContract = getContractById($id);
    if (!$existingContract) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hợp đồng không tồn tại!'];
    }
    
    // Không cho phép sửa hợp đồng đã thanh lý
    if ($existingContract['status'] == 'terminated') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không thể sửa hợp đồng đã thanh lý!'];
    }
    
    // Kiểm tra mã hợp đồng đã tồn tại chưa (trừ ID hiện tại)
    if (isContractCodeExists($contractCode, $id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã hợp đồng đã tồn tại!'];
    }
    
    // Nếu monthly_fee null, giữ nguyên giá trị cũ
    if ($monthlyFee === null) {
        $monthlyFee = $existingContract['monthly_fee'];
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày bắt đầu không đúng định dạng (Y-m-d)!'];
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc không đúng định dạng (Y-m-d)!'];
    }
    
    // Update
    $sql = "UPDATE contracts 
            SET contract_code = ?, start_date = ?, end_date = ?, monthly_fee = ?, deposit = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "ssssddsi", $contractCode, $startDate, $endDate, $monthlyFee, $deposit, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật hợp đồng thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật hợp đồng: ' . $error];
    }
}

/**
 * Gia hạn hợp đồng
 * @param int $id
 * @param string $newEndDate
 * @return array ['success' => bool, 'message' => string]
 */
function extendContract($id, $newEndDate) {
    $conn = getDbConnection();
    
    $contract = getContractById($id);
    if (!$contract) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hợp đồng không tồn tại!'];
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newEndDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc không đúng định dạng (Y-m-d)!'];
    }
    
    if (strtotime($newEndDate) <= strtotime($contract['end_date'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại!'];
    }
    
    $sql = "UPDATE contracts SET end_date = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "si", $newEndDate, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Gia hạn hợp đồng thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi gia hạn hợp đồng: ' . $error];
    }
}

/**
 * Thanh lý hợp đồng (chấm dứt)
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function terminateContract($id) {
    $conn = getDbConnection();
    
    $contract = getContractById($id);
    if (!$contract) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hợp đồng không tồn tại!'];
    }
    
    if ($contract['status'] == 'terminated') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hợp đồng đã được thanh lý!'];
    }
    
    // Update contract status
    $sql = "UPDATE contracts 
            SET status = 'terminated', terminated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        
        // Cập nhật room_assignment: end_date và status = moved_out
        require_once __DIR__ . '/room_assignments.php';
        $today = date('Y-m-d');
        $assignmentResult = endRoomAssignment($contract['student_id'], $contract['room_id'], $today);
        
        if (!$assignmentResult['success']) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Lỗi cập nhật phân phòng: ' . $assignmentResult['message']];
        }
        
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Thanh lý hợp đồng thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi thanh lý hợp đồng: ' . $error];
    }
}

/**
 * Lấy các status có thể chọn
 * @return array
 */
function getContractStatuses() {
    return [
        'active' => 'Đang hoạt động',
        'expired' => 'Hết hạn',
        'terminated' => 'Đã thanh lý'
    ];
}

?>

