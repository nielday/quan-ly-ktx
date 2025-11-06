<?php
/**
 * Services functions - Các hàm xử lý dịch vụ
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy danh sách tất cả dịch vụ
 * @param bool $includeInactive Có bao gồm inactive không
 * @return array
 */
function getAllServices($includeInactive = false) {
    $conn = getDbConnection();
    $services = [];
    
    if ($includeInactive) {
        $sql = "SELECT * FROM services ORDER BY service_code ASC";
    } else {
        $sql = "SELECT * FROM services WHERE status = 'active' ORDER BY service_code ASC";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $services[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $services;
}

/**
 * Lấy thông tin dịch vụ theo ID
 * @param int $id
 * @return array|null
 */
function getServiceById($id) {
    $conn = getDbConnection();
    $service = null;
    
    $sql = "SELECT * FROM services WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $service = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $service;
}

/**
 * Kiểm tra mã dịch vụ đã tồn tại chưa (trừ ID hiện tại)
 * @param string $serviceCode
 * @param int|null $excludeId ID cần loại trừ (khi sửa)
 * @return bool
 */
function isServiceCodeExists($serviceCode, $excludeId = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($excludeId) {
        $sql = "SELECT id FROM services WHERE service_code = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $serviceCode, $excludeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT id FROM services WHERE service_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $serviceCode);
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
 * Tạo dịch vụ mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createService($data) {
    $conn = getDbConnection();
    
    $serviceCode = trim($data['service_code'] ?? '');
    $serviceName = trim($data['service_name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $unit = trim($data['unit'] ?? 'tháng');
    $status = trim($data['status'] ?? 'active');
    
    // Validation
    if (empty($serviceCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã dịch vụ không được để trống!'];
    }
    
    if (empty($serviceName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên dịch vụ không được để trống!'];
    }
    
    if ($price <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Giá dịch vụ phải lớn hơn 0!'];
    }
    
    if (empty($unit)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn vị tính không được để trống!'];
    }
    
    // Kiểm tra mã dịch vụ đã tồn tại chưa
    if (isServiceCodeExists($serviceCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã dịch vụ đã tồn tại!'];
    }
    
    // Insert
    $sql = "INSERT INTO services (service_code, service_name, description, price, unit, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssdss", $serviceCode, $serviceName, $description, $price, $unit, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo dịch vụ thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo dịch vụ: ' . $error];
    }
}

/**
 * Cập nhật dịch vụ
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateService($id, $data) {
    $conn = getDbConnection();
    
    $serviceCode = trim($data['service_code'] ?? '');
    $serviceName = trim($data['service_name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $unit = trim($data['unit'] ?? 'tháng');
    $status = trim($data['status'] ?? 'active');
    
    // Validation
    if (empty($serviceCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã dịch vụ không được để trống!'];
    }
    
    if (empty($serviceName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên dịch vụ không được để trống!'];
    }
    
    if ($price <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Giá dịch vụ phải lớn hơn 0!'];
    }
    
    if (empty($unit)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn vị tính không được để trống!'];
    }
    
    // Kiểm tra mã dịch vụ đã tồn tại chưa (trừ ID hiện tại)
    if (isServiceCodeExists($serviceCode, $id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã dịch vụ đã tồn tại!'];
    }
    
    // Kiểm tra dịch vụ có tồn tại không
    $existingService = getServiceById($id);
    if (!$existingService) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Dịch vụ không tồn tại!'];
    }
    
    // Update
    $sql = "UPDATE services 
            SET service_code = ?, service_name = ?, description = ?, price = ?, unit = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssdssi", $serviceCode, $serviceName, $description, $price, $unit, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật dịch vụ thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật dịch vụ: ' . $error];
    }
}

/**
 * Xóa dịch vụ
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function deleteService($id) {
    $conn = getDbConnection();
    
    // Kiểm tra dịch vụ có tồn tại không
    $service = getServiceById($id);
    if (!$service) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Dịch vụ không tồn tại!'];
    }
    
    // Kiểm tra dịch vụ có đang được sử dụng trong phòng không
    $sql = "SELECT COUNT(*) as count FROM room_services WHERE service_id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Không thể xóa dịch vụ vì đang có ' . $row['count'] . ' phòng đang sử dụng!'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Delete
    $sql = "DELETE FROM services WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa dịch vụ thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi xóa dịch vụ: ' . $error];
    }
}

?>

