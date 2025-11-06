<?php
/**
 * Buildings functions - Các hàm xử lý tòa nhà
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy danh sách tất cả tòa nhà
 * @return array
 */
function getAllBuildings() {
    $conn = getDbConnection();
    $buildings = [];
    
    $sql = "SELECT * FROM buildings ORDER BY building_code ASC";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $buildings[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $buildings;
}

/**
 * Lấy thông tin tòa nhà theo ID
 * @param int $id
 * @return array|null
 */
function getBuildingById($id) {
    $conn = getDbConnection();
    $building = null;
    
    $sql = "SELECT * FROM buildings WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $building = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $building;
}

/**
 * Lấy thông tin tòa nhà theo mã tòa
 * @param string $buildingCode
 * @return array|null
 */
function getBuildingByCode($buildingCode) {
    $conn = getDbConnection();
    $building = null;
    
    $sql = "SELECT * FROM buildings WHERE building_code = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $buildingCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $building = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $building;
}

/**
 * Kiểm tra mã tòa đã tồn tại chưa (trừ ID hiện tại)
 * @param string $buildingCode
 * @param int|null $excludeId ID cần loại trừ (khi sửa)
 * @return bool
 */
function isBuildingCodeExists($buildingCode, $excludeId = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($excludeId) {
        $sql = "SELECT id FROM buildings WHERE building_code = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $buildingCode, $excludeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT id FROM buildings WHERE building_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $buildingCode);
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
 * Tạo tòa nhà mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createBuilding($data) {
    $conn = getDbConnection();
    
    $buildingCode = trim($data['building_code'] ?? '');
    $buildingName = trim($data['building_name'] ?? '');
    $address = trim($data['address'] ?? '');
    $floors = intval($data['floors'] ?? 1);
    $description = trim($data['description'] ?? '');
    
    // Validation
    if (empty($buildingCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã tòa nhà không được để trống!'];
    }
    
    if (empty($buildingName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên tòa nhà không được để trống!'];
    }
    
    // Kiểm tra mã tòa đã tồn tại chưa
    if (isBuildingCodeExists($buildingCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã tòa nhà đã tồn tại!'];
    }
    
    // Insert
    $sql = "INSERT INTO buildings (building_code, building_name, address, floors, description) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssis", $buildingCode, $buildingName, $address, $floors, $description);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo tòa nhà thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo tòa nhà: ' . $error];
    }
}

/**
 * Cập nhật tòa nhà
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateBuilding($id, $data) {
    $conn = getDbConnection();
    
    $buildingCode = trim($data['building_code'] ?? '');
    $buildingName = trim($data['building_name'] ?? '');
    $address = trim($data['address'] ?? '');
    $floors = intval($data['floors'] ?? 1);
    $description = trim($data['description'] ?? '');
    
    // Validation
    if (empty($buildingCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã tòa nhà không được để trống!'];
    }
    
    if (empty($buildingName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên tòa nhà không được để trống!'];
    }
    
    // Kiểm tra mã tòa đã tồn tại chưa (trừ ID hiện tại)
    if (isBuildingCodeExists($buildingCode, $id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã tòa nhà đã tồn tại!'];
    }
    
    // Kiểm tra tòa nhà có tồn tại không
    $existingBuilding = getBuildingById($id);
    if (!$existingBuilding) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tòa nhà không tồn tại!'];
    }
    
    // Update
    $sql = "UPDATE buildings 
            SET building_code = ?, building_name = ?, address = ?, floors = ?, description = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssisi", $buildingCode, $buildingName, $address, $floors, $description, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật tòa nhà thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật tòa nhà: ' . $error];
    }
}

/**
 * Xóa tòa nhà
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function deleteBuilding($id) {
    $conn = getDbConnection();
    
    // Kiểm tra tòa nhà có tồn tại không
    $building = getBuildingById($id);
    if (!$building) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tòa nhà không tồn tại!'];
    }
    
    // Kiểm tra tòa nhà có phòng nào không (có thể không cho xóa nếu có phòng)
    $sql = "SELECT COUNT(*) as count FROM rooms WHERE building_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Không thể xóa tòa nhà vì đang có ' . $row['count'] . ' phòng!'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Delete
    $sql = "DELETE FROM buildings WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa tòa nhà thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi xóa tòa nhà: ' . $error];
    }
}

/**
 * Đếm số phòng trong tòa nhà
 * @param int $buildingId
 * @return int
 */
function countRoomsInBuilding($buildingId) {
    $conn = getDbConnection();
    $count = 0;
    
    $sql = "SELECT COUNT(*) as count FROM rooms WHERE building_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $buildingId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $count = intval($row['count']);
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $count;
}

?>

