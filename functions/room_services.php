<?php
/**
 * Room_Services functions - Các hàm xử lý dịch vụ của phòng
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/rooms.php';
require_once __DIR__ . '/services.php';

/**
 * Lấy danh sách dịch vụ của một phòng (active)
 * @param int $roomId
 * @return array
 */
function getRoomServices($roomId) {
    $conn = getDbConnection();
    $services = [];
    
    $sql = "SELECT rs.*, s.service_code, s.service_name, s.price, s.unit 
            FROM room_services rs 
            INNER JOIN services s ON rs.service_id = s.id 
            WHERE rs.room_id = ? 
            AND rs.status = 'active' 
            AND (rs.end_date IS NULL OR rs.end_date >= CURDATE())
            ORDER BY s.service_name ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $services[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $services;
}

/**
 * Tính tổng tiền dịch vụ của phòng
 * @param int $roomId
 * @return float
 */
function getTotalRoomServicePrice($roomId) {
    $services = getRoomServices($roomId);
    $total = 0;
    
    foreach ($services as $service) {
        $total += floatval($service['price']);
    }
    
    return $total;
}

/**
 * Kiểm tra phòng đã có dịch vụ này chưa (active)
 * @param int $roomId
 * @param int $serviceId
 * @param string|null $startDate Kiểm tra với start_date cụ thể (để tránh duplicate)
 * @return bool
 */
function isRoomHasService($roomId, $serviceId, $startDate = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($startDate) {
        // Kiểm tra với start_date cụ thể (tránh duplicate unique constraint)
        $sql = "SELECT id FROM room_services 
                WHERE room_id = ? 
                AND service_id = ? 
                AND start_date = ?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $roomId, $serviceId, $startDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        // Kiểm tra active service
        $sql = "SELECT id FROM room_services 
                WHERE room_id = ? 
                AND service_id = ? 
                AND status = 'active' 
                AND (end_date IS NULL OR end_date >= CURDATE())
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $roomId, $serviceId);
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
 * Gán dịch vụ cho phòng
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function assignServiceToRoom($data) {
    $conn = getDbConnection();
    
    $roomId = intval($data['room_id'] ?? 0);
    $serviceId = intval($data['service_id'] ?? 0);
    $startDate = trim($data['start_date'] ?? date('Y-m-d'));
    $endDate = !empty($data['end_date']) ? trim($data['end_date']) : null;
    
    // Validation
    if ($roomId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn phòng!'];
    }
    
    if ($serviceId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn dịch vụ!'];
    }
    
    if (empty($startDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày bắt đầu không được để trống!'];
    }
    
    // Kiểm tra phòng có tồn tại không
    $room = getRoomById($roomId);
    if (!$room) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    // Kiểm tra dịch vụ có tồn tại không
    $service = getServiceById($serviceId);
    if (!$service) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Dịch vụ không tồn tại!'];
    }
    
    // Kiểm tra phòng đã có dịch vụ này chưa (active)
    if (isRoomHasService($roomId, $serviceId)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng đã có dịch vụ này đang active!'];
    }
    
    // Kiểm tra xem đã có record với cùng (room_id, service_id, start_date) chưa (kể cả inactive)
    // Nếu có, update lại thay vì insert mới
    $checkSql = "SELECT id, status FROM room_services 
                 WHERE room_id = ? 
                 AND service_id = ? 
                 AND start_date = ?
                 LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    $existingId = null;
    $existingStatus = null;
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "iis", $roomId, $serviceId, $startDate);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $existing = mysqli_fetch_assoc($checkResult);
            $existingId = $existing['id'];
            $existingStatus = $existing['status'];
        }
        
        mysqli_stmt_close($checkStmt);
    }
    
    // Nếu đã có record (có thể là inactive), update lại
    if ($existingId) {
        if ($existingStatus == 'active') {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Dịch vụ này đã được gán cho phòng với ngày bắt đầu này!'];
        }
        
        // Update lại record inactive thành active
        // Khi gán lại, end_date = null (đang áp dụng, chưa có ngày kết thúc)
        $updateSql = "UPDATE room_services 
                      SET status = 'active', end_date = NULL, updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        
        if (!$updateStmt) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
        }
        
        mysqli_stmt_bind_param($updateStmt, "i", $existingId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            mysqli_close($conn);
            return ['success' => true, 'message' => 'Gán lại dịch vụ cho phòng thành công!', 'id' => $existingId];
        } else {
            $error = mysqli_error($conn);
            mysqli_stmt_close($updateStmt);
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Lỗi cập nhật dịch vụ: ' . $error];
        }
    }
    
    // Nếu chưa có record, insert mới
    $sql = "INSERT INTO room_services (room_id, service_id, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, 'active')";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "iiss", $roomId, $serviceId, $startDate, $endDate);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Gán dịch vụ cho phòng thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        $errorCode = mysqli_errno($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        // Xử lý duplicate entry error (1062) - fallback
        if ($errorCode == 1062) {
            return ['success' => false, 'message' => 'Dịch vụ này đã được gán cho phòng với ngày bắt đầu này!'];
        }
        
        return ['success' => false, 'message' => 'Lỗi gán dịch vụ: ' . $error];
    }
}

/**
 * Hủy dịch vụ của phòng (set inactive)
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function removeServiceFromRoom($id) {
    $conn = getDbConnection();
    
    // Kiểm tra room_service có tồn tại không
    $sql = "SELECT * FROM room_services WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $exists = false;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = ($result && mysqli_num_rows($result) > 0);
        mysqli_stmt_close($stmt);
    }
    
    if (!$exists) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Dịch vụ phòng không tồn tại!'];
    }
    
    // Update status = inactive và end_date = hôm nay
    $endDate = date('Y-m-d');
    $sql = "UPDATE room_services 
            SET status = 'inactive', end_date = ? 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "si", $endDate, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Hủy dịch vụ thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi hủy dịch vụ: ' . $error];
    }
}

/**
 * Lấy danh sách tất cả phòng và dịch vụ của chúng
 * @return array
 */
function getAllRoomsWithServices() {
    $conn = getDbConnection();
    $rooms = [];
    
    $sql = "SELECT r.*, b.building_code, b.building_name,
                   GROUP_CONCAT(DISTINCT s.service_name ORDER BY s.service_name SEPARATOR ', ') as services_list,
                   COUNT(DISTINCT rs.service_id) as service_count
            FROM rooms r 
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN room_services rs ON r.id = rs.room_id AND rs.status = 'active' AND (rs.end_date IS NULL OR rs.end_date >= CURDATE())
            LEFT JOIN services s ON rs.service_id = s.id
            GROUP BY r.id
            ORDER BY b.building_code ASC, r.room_number ASC";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rooms[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $rooms;
}

/**
 * Lấy thông tin room_service theo ID
 * @param int $id
 * @return array|null
 */
function getRoomServiceById($id) {
    $conn = getDbConnection();
    $roomService = null;
    
    $sql = "SELECT rs.*, s.service_code, s.service_name, s.price, s.unit, 
                   r.room_code, r.room_number, b.building_code
            FROM room_services rs 
            INNER JOIN services s ON rs.service_id = s.id
            INNER JOIN rooms r ON rs.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE rs.id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $roomService = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $roomService;
}

?>

