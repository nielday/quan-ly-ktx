<?php
/**
 * Pricing functions - Các hàm xử lý đơn giá
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy danh sách tất cả đơn giá (active)
 * @param bool $includeInactive Có bao gồm inactive không
 * @return array
 */
function getAllPricing($includeInactive = false) {
    $conn = getDbConnection();
    $pricing = [];
    
    if ($includeInactive) {
        $sql = "SELECT p.*, u.full_name as created_by_name 
                FROM pricing p 
                LEFT JOIN users u ON p.created_by = u.id 
                ORDER BY p.price_type ASC, p.effective_from DESC";
    } else {
        $sql = "SELECT p.*, u.full_name as created_by_name 
                FROM pricing p 
                LEFT JOIN users u ON p.created_by = u.id 
                WHERE p.status = 'active' 
                ORDER BY p.price_type ASC, p.effective_from DESC";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pricing[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $pricing;
}

/**
 * Lấy đơn giá hiện tại (active và đang áp dụng)
 * @param string $priceType Loại đơn giá (electricity, water, room_single, etc.)
 * @param string|null $date Ngày kiểm tra (null = ngày hiện tại)
 * @return array|null
 */
function getCurrentPricing($priceType, $date = null) {
    $conn = getDbConnection();
    
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM pricing p 
            LEFT JOIN users u ON p.created_by = u.id 
            WHERE p.price_type = ? 
            AND p.status = 'active' 
            AND p.effective_from <= ? 
            AND (p.effective_to IS NULL OR p.effective_to >= ?)
            ORDER BY p.effective_from DESC 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    $pricing = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $priceType, $date, $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $pricing = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $pricing;
}

/**
 * Lấy thông tin đơn giá theo ID
 * @param int $id
 * @return array|null
 */
function getPricingById($id) {
    $conn = getDbConnection();
    $pricing = null;
    
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM pricing p 
            LEFT JOIN users u ON p.created_by = u.id 
            WHERE p.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $pricing = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $pricing;
}

/**
 * Lấy lịch sử đơn giá theo loại
 * @param string $priceType
 * @return array
 */
function getPricingHistory($priceType) {
    $conn = getDbConnection();
    $history = [];
    
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM pricing p 
            LEFT JOIN users u ON p.created_by = u.id 
            WHERE p.price_type = ? 
            ORDER BY p.effective_from DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $priceType);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $history;
}

/**
 * Tạo đơn giá mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createPricing($data) {
    $conn = getDbConnection();
    
    $priceType = trim($data['price_type'] ?? '');
    $priceValue = floatval($data['price_value'] ?? 0);
    $unit = trim($data['unit'] ?? '');
    $effectiveFrom = trim($data['effective_from'] ?? date('Y-m-d'));
    $effectiveTo = !empty($data['effective_to']) ? trim($data['effective_to']) : null;
    $description = trim($data['description'] ?? '');
    $createdBy = intval($data['created_by'] ?? 0);
    
    // Validation
    if (empty($priceType)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Loại đơn giá không được để trống!'];
    }
    
    if ($priceValue <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Giá trị đơn giá phải lớn hơn 0!'];
    }
    
    if (empty($unit)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn vị tính không được để trống!'];
    }
    
    if (empty($effectiveFrom)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày có hiệu lực không được để trống!'];
    }
    
    if ($createdBy <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Người tạo không hợp lệ!'];
    }
    
    // Nếu có đơn giá active cùng loại, inactive đơn giá cũ
    $currentPricing = getCurrentPricing($priceType, $effectiveFrom);
    if ($currentPricing) {
        // Cập nhật effective_to của đơn giá cũ = ngày trước effective_from mới
        $oldEffectiveTo = date('Y-m-d', strtotime($effectiveFrom . ' -1 day'));
        // Set status = 'inactive' để không hiển thị trong danh sách chính
        $updateSql = "UPDATE pricing SET effective_to = ?, status = 'inactive' WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "si", $oldEffectiveTo, $currentPricing['id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }
    
    // Insert
    $sql = "INSERT INTO pricing (price_type, price_value, unit, effective_from, effective_to, description, created_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sdssssi", $priceType, $priceValue, $unit, $effectiveFrom, $effectiveTo, $description, $createdBy);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo đơn giá thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo đơn giá: ' . $error];
    }
}

/**
 * Cập nhật đơn giá (chỉ cập nhật description, status)
 * Lưu ý: Không nên cập nhật giá trị và ngày hiệu lực, nên tạo đơn giá mới
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updatePricing($id, $data) {
    $conn = getDbConnection();
    
    $description = trim($data['description'] ?? '');
    $status = trim($data['status'] ?? 'active');
    
    // Kiểm tra đơn giá có tồn tại không
    $existingPricing = getPricingById($id);
    if (!$existingPricing) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn giá không tồn tại!'];
    }
    
    // Update
    $sql = "UPDATE pricing 
            SET description = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "ssi", $description, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật đơn giá thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật đơn giá: ' . $error];
    }
}

/**
 * Lấy danh sách các loại đơn giá
 * @return array
 */
function getPriceTypes() {
    return [
        'electricity' => 'Điện (VNĐ/kWh)',
        'water' => 'Nước (VNĐ/m³)',
        'room_single' => 'Phòng đơn (VNĐ/tháng)',
        'room_double' => 'Phòng đôi (VNĐ/tháng)',
        'room_4people' => 'Phòng 4 người (VNĐ/tháng)',
        'room_6people' => 'Phòng 6 người (VNĐ/tháng)'
    ];
}

/**
 * Lấy đơn vị tính mặc định theo loại đơn giá
 * @param string $priceType
 * @return string
 */
function getDefaultUnit($priceType) {
    $units = [
        'electricity' => 'kWh',
        'water' => 'm³',
        'room_single' => 'tháng',
        'room_double' => 'tháng',
        'room_4people' => 'tháng',
        'room_6people' => 'tháng'
    ];
    
    return $units[$priceType] ?? '';
}

?>

