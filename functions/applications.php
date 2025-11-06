<?php
/**
 * Applications functions - Các hàm xử lý đơn đăng ký
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/registration_periods.php';
require_once __DIR__ . '/students.php';

/**
 * Lấy danh sách tất cả đơn đăng ký
 * @param string|null $status Lọc theo status (null = tất cả)
 * @param int|null $registrationPeriodId Lọc theo đợt đăng ký
 * @return array
 */
function getAllApplications($status = null, $registrationPeriodId = null) {
    $conn = getDbConnection();
    $applications = [];
    
    $sql = "SELECT a.*, 
                   s.student_code, s.full_name as student_name, s.phone, s.email,
                   rp.period_name, rp.start_date as period_start, rp.end_date as period_end,
                   u.full_name as approved_by_name
            FROM applications a 
            INNER JOIN students s ON a.student_id = s.id
            LEFT JOIN registration_periods rp ON a.registration_period_id = rp.id
            LEFT JOIN users u ON a.approved_by = u.id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($status) {
        $conditions[] = "a.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($registrationPeriodId) {
        $conditions[] = "a.registration_period_id = ?";
        $params[] = $registrationPeriodId;
        $types .= "i";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $applications[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $applications[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $applications;
}

/**
 * Lấy đơn đăng ký theo ID
 * @param int $id
 * @return array|null
 */
function getApplicationById($id) {
    $conn = getDbConnection();
    $application = null;
    
    $sql = "SELECT a.*, 
                   s.student_code, s.full_name as student_name, s.phone, s.email, s.university, s.major, s.year,
                   rp.period_name, rp.start_date as period_start, rp.end_date as period_end,
                   u.full_name as approved_by_name
            FROM applications a 
            INNER JOIN students s ON a.student_id = s.id
            LEFT JOIN registration_periods rp ON a.registration_period_id = rp.id
            LEFT JOIN users u ON a.approved_by = u.id
            WHERE a.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $application = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $application;
}

/**
 * Lấy đơn đăng ký của sinh viên
 * @param int $studentId
 * @return array
 */
function getApplicationsByStudentId($studentId) {
    $conn = getDbConnection();
    $applications = [];
    
    $sql = "SELECT a.*, 
                   rp.period_name, rp.start_date as period_start, rp.end_date as period_end,
                   u.full_name as approved_by_name
            FROM applications a 
            LEFT JOIN registration_periods rp ON a.registration_period_id = rp.id
            LEFT JOIN users u ON a.approved_by = u.id
            WHERE a.student_id = ? 
            ORDER BY a.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $applications[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $applications;
}

/**
 * Kiểm tra sinh viên đã có đơn đăng ký trong đợt này chưa
 * @param int $studentId
 * @param int $registrationPeriodId
 * @return bool
 */
function hasApplicationInPeriod($studentId, $registrationPeriodId) {
    $conn = getDbConnection();
    $exists = false;
    
    $sql = "SELECT id FROM applications 
            WHERE student_id = ? 
            AND registration_period_id = ? 
            AND status IN ('pending', 'approved', 'waiting_list')
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $registrationPeriodId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = ($result && mysqli_num_rows($result) > 0);
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $exists;
}

/**
 * Tạo đơn đăng ký mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createApplication($data) {
    $conn = getDbConnection();
    
    $studentId = intval($data['student_id'] ?? 0);
    $registrationPeriodId = !empty($data['registration_period_id']) ? intval($data['registration_period_id']) : null;
    $applicationDate = trim($data['application_date'] ?? date('Y-m-d'));
    $semester = trim($data['semester'] ?? '');
    $academicYear = trim($data['academic_year'] ?? '');
    $preferredRoomType = trim($data['preferred_room_type'] ?? '');
    $status = 'pending';
    
    // Validation
    if ($studentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Student ID không hợp lệ!'];
    }
    
    if (empty($applicationDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày đăng ký không được để trống!'];
    }
    
    if ($preferredRoomType && !in_array($preferredRoomType, ['đơn', 'đôi', '4 người', '6 người'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Loại phòng không hợp lệ!'];
    }
    
    // Kiểm tra sinh viên có tồn tại không
    $student = getStudentById($studentId);
    if (!$student) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không tồn tại!'];
    }
    
    // Kiểm tra đợt đăng ký có tồn tại và đang mở không
    if ($registrationPeriodId) {
        $period = getRegistrationPeriodById($registrationPeriodId);
        if (!$period) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Đợt đăng ký không tồn tại!'];
        }
        
        // Kiểm tra đợt đăng ký có đang mở không
        $today = date('Y-m-d');
        if ($period['status'] != 'open' || $today < $period['start_date'] || $today > $period['end_date']) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Đợt đăng ký không đang mở!'];
        }
        
        // Kiểm tra sinh viên đã có đơn trong đợt này chưa
        if (hasApplicationInPeriod($studentId, $registrationPeriodId)) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Bạn đã có đơn đăng ký trong đợt này!'];
        }
    }
    
    // Insert
    $sql = "INSERT INTO applications (student_id, registration_period_id, application_date, semester, academic_year, preferred_room_type, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "iisssss", $studentId, $registrationPeriodId, $applicationDate, $semester, $academicYear, $preferredRoomType, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo đơn đăng ký thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo đơn đăng ký: ' . $error];
    }
}

/**
 * Duyệt đơn đăng ký
 * @param int $id
 * @param int $approvedBy
 * @return array ['success' => bool, 'message' => string]
 */
function approveApplication($id, $approvedBy) {
    $conn = getDbConnection();
    
    // Kiểm tra đơn có tồn tại không
    $application = getApplicationById($id);
    if (!$application) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn đăng ký không tồn tại!'];
    }
    
    if ($application['status'] != 'pending' && $application['status'] != 'waiting_list') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn đăng ký không ở trạng thái chờ duyệt!'];
    }
    
    // Update
    $sql = "UPDATE applications 
            SET status = 'approved', approved_by = ?, approved_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $approvedBy, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Duyệt đơn đăng ký thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi duyệt đơn: ' . $error];
    }
}

/**
 * Từ chối đơn đăng ký
 * @param int $id
 * @param int $approvedBy
 * @param string $rejectionReason
 * @return array ['success' => bool, 'message' => string]
 */
function rejectApplication($id, $approvedBy, $rejectionReason = '') {
    $conn = getDbConnection();
    
    // Kiểm tra đơn có tồn tại không
    $application = getApplicationById($id);
    if (!$application) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn đăng ký không tồn tại!'];
    }
    
    if ($application['status'] != 'pending' && $application['status'] != 'waiting_list') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đơn đăng ký không ở trạng thái chờ duyệt!'];
    }
    
    // Update
    $sql = "UPDATE applications 
            SET status = 'rejected', approved_by = ?, approved_at = CURRENT_TIMESTAMP, rejection_reason = ? 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "isi", $approvedBy, $rejectionReason, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Từ chối đơn đăng ký thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi từ chối đơn: ' . $error];
    }
}

/**
 * Lấy các status có thể chọn
 * @return array
 */
function getApplicationStatuses() {
    return [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Bị từ chối',
        'waiting_list' => 'Danh sách chờ'
    ];
}

// Hàm getRoomTypes() đã được định nghĩa trong functions/rooms.php
// Không cần định nghĩa lại ở đây

?>

