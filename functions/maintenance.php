<?php
/**
 * Maintenance Requests Functions
 * Quản lý yêu cầu sửa chữa
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy tất cả yêu cầu sửa chữa
 */
function getAllMaintenanceRequests($filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT mr.*, 
                   s.student_code, s.full_name as student_name,
                   r.room_code, b.building_code,
                   u.full_name as assigned_to_name
            FROM maintenance_requests mr
            INNER JOIN students s ON mr.student_id = s.id
            INNER JOIN rooms r ON mr.room_id = r.id
            INNER JOIN buildings b ON r.building_id = b.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Filter by status
    if (!empty($filters['status'])) {
        $sql .= " AND mr.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    // Filter by request_type
    if (!empty($filters['request_type'])) {
        $sql .= " AND mr.request_type = ?";
        $params[] = $filters['request_type'];
        $types .= "s";
    }
    
    // Filter by priority
    if (!empty($filters['priority'])) {
        $sql .= " AND mr.priority = ?";
        $params[] = $filters['priority'];
        $types .= "s";
    }
    
    // Filter by room
    if (!empty($filters['room_id'])) {
        $sql .= " AND mr.room_id = ?";
        $params[] = $filters['room_id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY 
              CASE mr.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
              END,
              mr.created_at DESC";
    
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
 * Lấy thông tin chi tiết yêu cầu sửa chữa
 */
function getMaintenanceRequestById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT mr.*, 
                   s.student_code, s.full_name as student_name, s.phone as student_phone,
                   r.room_code, r.floor, b.building_code, b.building_name,
                   u.full_name as assigned_to_name
            FROM maintenance_requests mr
            INNER JOIN students s ON mr.student_id = s.id
            INNER JOIN rooms r ON mr.room_id = r.id
            INNER JOIN buildings b ON r.building_id = b.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            WHERE mr.id = ?";
    
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
 * Cập nhật trạng thái yêu cầu sửa chữa
 */
function updateMaintenanceStatus($id, $status, $assignedTo = null, $notes = null) {
    $conn = getDbConnection();
    
    mysqli_begin_transaction($conn);
    
    try {
        // Kiểm tra yêu cầu tồn tại
        $request = getMaintenanceRequestById($id);
        if (!$request) {
            throw new Exception("Yêu cầu sửa chữa không tồn tại");
        }
        
        // Cập nhật status
        if ($status === 'completed') {
            $sql = "UPDATE maintenance_requests 
                    SET status = ?, completed_at = NOW(), updated_at = NOW()
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
        } else {
            $sql = "UPDATE maintenance_requests 
                    SET status = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi cập nhật trạng thái: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
        
        // Cập nhật assigned_to nếu có
        if ($assignedTo !== null) {
            $sql = "UPDATE maintenance_requests 
                    SET assigned_to = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $assignedTo, $id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Lỗi phân công: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        }
        
        mysqli_commit($conn);
        mysqli_close($conn);
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        throw $e;
    }
}

/**
 * Cập nhật thông tin yêu cầu sửa chữa (priority, assigned_to)
 */
function updateMaintenanceRequest($id, $data) {
    $conn = getDbConnection();
    
    $updates = [];
    $params = [];
    $types = "";
    
    if (isset($data['priority'])) {
        $updates[] = "priority = ?";
        $params[] = $data['priority'];
        $types .= "s";
    }
    
    if (isset($data['assigned_to'])) {
        $updates[] = "assigned_to = ?";
        $params[] = $data['assigned_to'];
        $types .= "i";
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = $data['description'];
        $types .= "s";
    }
    
    if (empty($updates)) {
        mysqli_close($conn);
        return true;
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $id;
    $types .= "i";
    
    $sql = "UPDATE maintenance_requests SET " . implode(", ", $updates) . " WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $result;
}

/**
 * Lấy thống kê yêu cầu sửa chữa
 */
function getMaintenanceStatistics() {
    $conn = getDbConnection();
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent
            FROM maintenance_requests";
    
    $result = mysqli_query($conn, $sql);
    
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'urgent' => 0
    ];
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats = [
            'total' => intval($row['total']),
            'pending' => intval($row['pending']),
            'in_progress' => intval($row['in_progress']),
            'completed' => intval($row['completed']),
            'cancelled' => intval($row['cancelled']),
            'urgent' => intval($row['urgent'])
        ];
    }
    
    mysqli_close($conn);
    
    return $stats;
}

/**
 * Lấy danh sách loại yêu cầu
 */
function getRequestTypes() {
    return [
        'electrical' => 'Điện',
        'plumbing' => 'Nước',
        'furniture' => 'Nội thất',
        'other' => 'Khác'
    ];
}

/**
 * Lấy danh sách mức độ ưu tiên
 */
function getPriorityLevels() {
    return [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp'
    ];
}

/**
 * Lấy danh sách trạng thái
 */
function getMaintenanceStatuses() {
    return [
        'pending' => 'Chờ xử lý',
        'in_progress' => 'Đang sửa',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
}

/**
 * Hủy yêu cầu sửa chữa
 */
function cancelMaintenanceRequest($id) {
    return updateMaintenanceStatus($id, 'cancelled');
}

/**
 * Tạo yêu cầu sửa chữa (Sinh viên tạo)
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'request_id' => int]
 */
function createMaintenanceRequest($data) {
    $conn = getDbConnection();
    
    // Validate
    $required = ['student_id', 'room_id', 'request_type', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            mysqli_close($conn);
            return ['success' => false, 'message' => "Thiếu trường bắt buộc: $field"];
        }
    }
    
    // Kiểm tra sinh viên đang ở phòng này
    require_once __DIR__ . '/room_assignments.php';
    $roomAssignment = getActiveRoomAssignmentByStudentId($data['student_id']);
    
    if (!$roomAssignment || $roomAssignment['room_id'] != $data['room_id']) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không ở phòng này!'];
    }
    
    // Chuẩn bị dữ liệu
    $studentId = intval($data['student_id']);
    $roomId = intval($data['room_id']);
    $requestType = trim($data['request_type']);
    $description = trim($data['description']);
    $priority = !empty($data['priority']) ? trim($data['priority']) : 'medium';
    
    // Validate request_type
    $validTypes = ['electrical', 'plumbing', 'furniture', 'other'];
    if (!in_array($requestType, $validTypes)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Loại yêu cầu không hợp lệ!'];
    }
    
    // Validate priority
    $validPriorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        $priority = 'medium';
    }
    
    // Insert
    $sql = "INSERT INTO maintenance_requests 
            (student_id, room_id, request_type, description, priority, status)
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisss", 
        $studentId,
        $roomId,
        $requestType,
        $description,
        $priority
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $request_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return [
            'success' => true, 
            'message' => 'Tạo yêu cầu sửa chữa thành công!',
            'request_id' => $request_id
        ];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

