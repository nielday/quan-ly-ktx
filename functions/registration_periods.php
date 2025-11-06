<?php
/**
 * Registration_Periods functions - Các hàm xử lý đợt đăng ký
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy danh sách tất cả đợt đăng ký
 * @param string|null $status Lọc theo status (null = tất cả)
 * @return array
 */
function getAllRegistrationPeriods($status = null) {
    $conn = getDbConnection();
    $periods = [];
    
    if ($status) {
        $sql = "SELECT rp.*, u.full_name as created_by_name 
                FROM registration_periods rp 
                LEFT JOIN users u ON rp.created_by = u.id 
                WHERE rp.status = ? 
                ORDER BY rp.start_date DESC, rp.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $status);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $periods[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT rp.*, u.full_name as created_by_name 
                FROM registration_periods rp 
                LEFT JOIN users u ON rp.created_by = u.id 
                ORDER BY rp.start_date DESC, rp.created_at DESC";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $periods[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $periods;
}

/**
 * Lấy đợt đăng ký đang mở (open)
 * @return array|null
 */
function getOpenRegistrationPeriod() {
    $conn = getDbConnection();
    $period = null;
    
    $sql = "SELECT rp.*, u.full_name as created_by_name 
            FROM registration_periods rp 
            LEFT JOIN users u ON rp.created_by = u.id 
            WHERE rp.status = 'open' 
            AND rp.start_date <= CURDATE() 
            AND rp.end_date >= CURDATE()
            ORDER BY rp.start_date DESC 
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $period = mysqli_fetch_assoc($result);
    }
    
    mysqli_close($conn);
    return $period;
}

/**
 * Lấy thông tin đợt đăng ký theo ID
 * @param int $id
 * @return array|null
 */
function getRegistrationPeriodById($id) {
    $conn = getDbConnection();
    $period = null;
    
    $sql = "SELECT rp.*, u.full_name as created_by_name 
            FROM registration_periods rp 
            LEFT JOIN users u ON rp.created_by = u.id 
            WHERE rp.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $period = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $period;
}

/**
 * Kiểm tra đợt đăng ký có đang được sử dụng không (có đơn đăng ký)
 * @param int $periodId
 * @return bool
 */
function isRegistrationPeriodInUse($periodId) {
    $conn = getDbConnection();
    $inUse = false;
    
    $sql = "SELECT COUNT(*) as count FROM applications WHERE registration_period_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $periodId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $inUse = ($row['count'] > 0);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $inUse;
}

/**
 * Tự động cập nhật status của đợt đăng ký dựa trên ngày
 * @param int $id
 * @return void
 */
function updateRegistrationPeriodStatus($id) {
    $conn = getDbConnection();
    $period = getRegistrationPeriodById($id);
    
    if (!$period) {
        mysqli_close($conn);
        return;
    }
    
    $today = date('Y-m-d');
    $startDate = $period['start_date'];
    $endDate = $period['end_date'];
    $currentStatus = $period['status'];
    
    $newStatus = $currentStatus;
    
    // Logic cập nhật status
    if ($today < $startDate) {
        $newStatus = 'upcoming';
    } elseif ($today >= $startDate && $today <= $endDate) {
        $newStatus = 'open';
    } else {
        $newStatus = 'closed';
    }
    
    // Chỉ update nếu status thay đổi
    if ($newStatus != $currentStatus) {
        $sql = "UPDATE registration_periods SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

/**
 * Tạo đợt đăng ký mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createRegistrationPeriod($data) {
    $conn = getDbConnection();
    
    $periodName = trim($data['period_name'] ?? '');
    $startDate = trim($data['start_date'] ?? '');
    $endDate = trim($data['end_date'] ?? '');
    $semester = trim($data['semester'] ?? '');
    $academicYear = trim($data['academic_year'] ?? '');
    $totalRoomsAvailable = !empty($data['total_rooms_available']) ? intval($data['total_rooms_available']) : null;
    $status = trim($data['status'] ?? 'upcoming');
    $createdBy = intval($data['created_by'] ?? 0);
    
    // Validation
    if (empty($periodName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên đợt đăng ký không được để trống!'];
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
    
    if (!in_array($status, ['upcoming', 'open', 'closed'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    if ($createdBy <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Người tạo không hợp lệ!'];
    }
    
    // Tự động cập nhật status dựa trên ngày khi tạo mới
    $today = date('Y-m-d');
    if ($today < $startDate) {
        $status = 'upcoming';
    } elseif ($today >= $startDate && $today <= $endDate) {
        $status = 'open';
    } else {
        $status = 'closed';
    }
    
    // Nếu người dùng chỉ định status, ưu tiên lựa chọn của họ (trừ khi đã qua ngày kết thúc)
    if (!empty($data['status']) && $data['status'] != $status) {
        // Cho phép set status thủ công, nhưng nếu đã qua ngày kết thúc thì bắt buộc phải closed
        if ($today > $endDate) {
            $status = 'closed';
        } else {
            $status = $data['status'];
        }
    }
    
    // Insert
    $sql = "INSERT INTO registration_periods (period_name, start_date, end_date, semester, academic_year, total_rooms_available, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssssisi", $periodName, $startDate, $endDate, $semester, $academicYear, $totalRoomsAvailable, $status, $createdBy);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo đợt đăng ký thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo đợt đăng ký: ' . $error];
    }
}

/**
 * Cập nhật đợt đăng ký
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateRegistrationPeriod($id, $data) {
    $conn = getDbConnection();
    
    $periodName = trim($data['period_name'] ?? '');
    $startDate = trim($data['start_date'] ?? '');
    $endDate = trim($data['end_date'] ?? '');
    $semester = trim($data['semester'] ?? '');
    $academicYear = trim($data['academic_year'] ?? '');
    $totalRoomsAvailable = !empty($data['total_rooms_available']) ? intval($data['total_rooms_available']) : null;
    $status = trim($data['status'] ?? 'upcoming');
    
    // Validation
    if (empty($periodName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Tên đợt đăng ký không được để trống!'];
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
    
    if (!in_array($status, ['upcoming', 'open', 'closed'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    // Kiểm tra đợt đăng ký có tồn tại không
    $existingPeriod = getRegistrationPeriodById($id);
    if (!$existingPeriod) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đợt đăng ký không tồn tại!'];
    }
    
    // Tôn trọng lựa chọn status của người dùng
    // Chỉ tự động cập nhật nếu đã qua ngày kết thúc (bắt buộc phải closed)
    $today = date('Y-m-d');
    
    // Nếu đã qua ngày kết thúc, bắt buộc phải là closed
    if ($today > $endDate) {
        $status = 'closed';
    }
    // Còn lại, giữ nguyên status mà người dùng chọn
    
    // Update
    $sql = "UPDATE registration_periods 
            SET period_name = ?, start_date = ?, end_date = ?, semester = ?, academic_year = ?, 
                total_rooms_available = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "sssssisi", $periodName, $startDate, $endDate, $semester, $academicYear, $totalRoomsAvailable, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật đợt đăng ký thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật đợt đăng ký: ' . $error];
    }
}

/**
 * Xóa đợt đăng ký
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function deleteRegistrationPeriod($id) {
    $conn = getDbConnection();
    
    // Kiểm tra đợt đăng ký có tồn tại không
    $period = getRegistrationPeriodById($id);
    if (!$period) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đợt đăng ký không tồn tại!'];
    }
    
    // Kiểm tra đợt đăng ký có đang được sử dụng không
    if (isRegistrationPeriodInUse($id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không thể xóa đợt đăng ký vì đã có đơn đăng ký sử dụng đợt này!'];
    }
    
    // Delete
    $sql = "DELETE FROM registration_periods WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa đợt đăng ký thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi xóa đợt đăng ký: ' . $error];
    }
}

/**
 * Lấy các status có thể chọn
 * @return array
 */
function getRegistrationPeriodStatuses() {
    return [
        'upcoming' => 'Sắp tới',
        'open' => 'Đang mở',
        'closed' => 'Đã đóng'
    ];
}

?>

