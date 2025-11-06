<?php
/**
 * Rooms functions - Các hàm xử lý phòng
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/pricing.php';

/**
 * Lấy danh sách tất cả phòng
 * @param int|null $buildingId Lọc theo tòa nhà (null = tất cả)
 * @param string|null $status Lọc theo trạng thái (null = tất cả)
 * @return array
 */
function getAllRooms($buildingId = null, $status = null) {
    $conn = getDbConnection();
    $rooms = [];
    
    $sql = "SELECT r.*, b.building_name, b.building_code 
            FROM rooms r 
            LEFT JOIN buildings b ON r.building_id = b.id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($buildingId !== null) {
        $conditions[] = "r.building_id = ?";
        $params[] = $buildingId;
        $types .= "i";
    }
    
    if ($status !== null) {
        $conditions[] = "r.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY b.building_code ASC, r.room_number ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rooms[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $rooms;
}

/**
 * Lấy thông tin phòng theo ID
 * @param int $id
 * @return array|null
 */
function getRoomById($id) {
    $conn = getDbConnection();
    $room = null;
    
    $sql = "SELECT r.*, b.building_name, b.building_code 
            FROM rooms r 
            LEFT JOIN buildings b ON r.building_id = b.id 
            WHERE r.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $room = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $room;
}

/**
 * Kiểm tra mã phòng đã tồn tại chưa (trừ ID hiện tại)
 * @param string $roomCode
 * @param int|null $excludeId ID cần loại trừ (khi sửa)
 * @return bool
 */
function isRoomCodeExists($roomCode, $excludeId = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($excludeId) {
        $sql = "SELECT id FROM rooms WHERE room_code = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $roomCode, $excludeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT id FROM rooms WHERE room_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $roomCode);
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
 * Lấy giá phòng từ bảng Pricing theo room_type
 * @param string $roomType
 * @return float|null
 */
function getRoomPriceFromPricing($roomType) {
    // Map room_type sang price_type trong bảng Pricing
    $roomTypeMap = [
        'đơn' => 'room_single',
        'đôi' => 'room_double',
        '4 người' => 'room_4people',
        '6 người' => 'room_6people'
    ];
    
    // Lấy price_type tương ứng
    $priceType = $roomTypeMap[$roomType] ?? null;
    
    if (!$priceType) {
        return null;
    }
    
    $pricing = getCurrentPricing($priceType);
    
    if ($pricing) {
        return floatval($pricing['price_value']);
    }
    
    return null;
}

/**
 * Tạo phòng mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createRoom($data) {
    $conn = getDbConnection();
    
    $buildingId = intval($data['building_id'] ?? 0);
    $roomCode = trim($data['room_code'] ?? '');
    $roomNumber = trim($data['room_number'] ?? '');
    $floor = intval($data['floor'] ?? 1);
    $capacity = intval($data['capacity'] ?? 4);
    $roomType = trim($data['room_type'] ?? '');
    $amenities = trim($data['amenities'] ?? '');
    $status = trim($data['status'] ?? 'available');
    
    // Validation
    if ($buildingId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn tòa nhà!'];
    }
    
    if (empty($roomCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã phòng không được để trống!'];
    }
    
    if (empty($roomNumber)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số phòng không được để trống!'];
    }
    
    if ($capacity <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sức chứa phải lớn hơn 0!'];
    }
    
    if (empty($roomType)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn loại phòng!'];
    }
    
    // Kiểm tra mã phòng đã tồn tại chưa
    if (isRoomCodeExists($roomCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã phòng đã tồn tại!'];
    }
    
    // Lấy giá phòng từ Pricing
    $pricePerMonth = getRoomPriceFromPricing($roomType);
    if ($pricePerMonth === null) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Chưa có đơn giá cho loại phòng này! Vui lòng tạo đơn giá trước.'];
    }
    
    // Insert
    $sql = "INSERT INTO rooms (building_id, room_code, room_number, floor, capacity, current_occupancy, price_per_month, room_type, amenities, status) 
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "issiidsss", $buildingId, $roomCode, $roomNumber, $floor, $capacity, $pricePerMonth, $roomType, $amenities, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo phòng thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo phòng: ' . $error];
    }
}

/**
 * Cập nhật phòng
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateRoom($id, $data) {
    $conn = getDbConnection();
    
    $buildingId = intval($data['building_id'] ?? 0);
    $roomCode = trim($data['room_code'] ?? '');
    $roomNumber = trim($data['room_number'] ?? '');
    $floor = intval($data['floor'] ?? 1);
    $capacity = intval($data['capacity'] ?? 4);
    $roomType = trim($data['room_type'] ?? '');
    $amenities = trim($data['amenities'] ?? '');
    $status = trim($data['status'] ?? 'available');
    
    // Validation
    if ($buildingId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn tòa nhà!'];
    }
    
    if (empty($roomCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã phòng không được để trống!'];
    }
    
    if (empty($roomNumber)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số phòng không được để trống!'];
    }
    
    if ($capacity <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sức chứa phải lớn hơn 0!'];
    }
    
    if (empty($roomType)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng chọn loại phòng!'];
    }
    
    // Kiểm tra mã phòng đã tồn tại chưa (trừ ID hiện tại)
    if (isRoomCodeExists($roomCode, $id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã phòng đã tồn tại!'];
    }
    
    // Kiểm tra phòng có tồn tại không
    $existingRoom = getRoomById($id);
    if (!$existingRoom) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    // Kiểm tra current_occupancy không vượt quá capacity mới
    if ($existingRoom['current_occupancy'] > $capacity) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sức chứa mới không được nhỏ hơn số người đang ở (' . $existingRoom['current_occupancy'] . ' người)!'];
    }
    
    // Lấy giá phòng từ Pricing
    $pricePerMonth = getRoomPriceFromPricing($roomType);
    if ($pricePerMonth === null) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Chưa có đơn giá cho loại phòng này! Vui lòng tạo đơn giá trước.'];
    }
    
    // Update
    $sql = "UPDATE rooms 
            SET building_id = ?, room_code = ?, room_number = ?, floor = ?, capacity = ?, 
                price_per_month = ?, room_type = ?, amenities = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "issiidsssi", $buildingId, $roomCode, $roomNumber, $floor, $capacity, $pricePerMonth, $roomType, $amenities, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật phòng thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật phòng: ' . $error];
    }
}

/**
 * Xóa phòng
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function deleteRoom($id) {
    $conn = getDbConnection();
    
    // Kiểm tra phòng có tồn tại không
    $room = getRoomById($id);
    if (!$room) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    // Kiểm tra phòng có người ở không
    if ($room['current_occupancy'] > 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không thể xóa phòng vì đang có ' . $room['current_occupancy'] . ' người ở!'];
    }
    
    // Delete
    $sql = "DELETE FROM rooms WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa phòng thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi xóa phòng: ' . $error];
    }
}

/**
 * Đếm số phòng theo trạng thái
 * @param string|null $status
 * @return int
 */
function countRoomsByStatus($status = null) {
    $conn = getDbConnection();
    $count = 0;
    
    if ($status) {
        $sql = "SELECT COUNT(*) as count FROM rooms WHERE status = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $status);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $count = intval($row['count']);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT COUNT(*) as count FROM rooms";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $count = intval($row['count']);
        }
    }
    
    mysqli_close($conn);
    return $count;
}

/**
 * Lấy danh sách loại phòng
 * @return array
 */
function getRoomTypes() {
    return [
        'đơn' => 'Phòng đơn',
        'đôi' => 'Phòng đôi',
        '4 người' => 'Phòng 4 người',
        '6 người' => 'Phòng 6 người'
    ];
}

/**
 * Lấy danh sách trạng thái phòng
 * @return array
 */
function getRoomStatuses() {
    return [
        'available' => 'Trống',
        'occupied' => 'Đã có người ở',
        'maintenance' => 'Đang sửa chữa'
    ];
}

?>

