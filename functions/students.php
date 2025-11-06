<?php
/**
 * Students functions - Các hàm xử lý sinh viên
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy danh sách tất cả sinh viên
 * @param string|null $status Lọc theo status (null = tất cả)
 * @return array
 */
function getAllStudents($status = null) {
    $conn = getDbConnection();
    $students = [];
    
    if ($status) {
        $sql = "SELECT s.*, u.username, u.role 
                FROM students s 
                INNER JOIN users u ON s.user_id = u.id 
                WHERE s.status = ? 
                ORDER BY s.student_code ASC";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $status);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $students[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT s.*, u.username, u.role 
                FROM students s 
                INNER JOIN users u ON s.user_id = u.id 
                ORDER BY s.student_code ASC";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $students[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $students;
}

/**
 * Lấy thông tin sinh viên theo ID
 * @param int $id
 * @return array|null
 */
function getStudentById($id) {
    $conn = getDbConnection();
    $student = null;
    
    $sql = "SELECT s.*, u.username, u.role 
            FROM students s 
            INNER JOIN users u ON s.user_id = u.id 
            WHERE s.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $student;
}

/**
 * Lấy thông tin sinh viên theo user_id
 * @param int $userId
 * @return array|null
 */
function getStudentByUserId($userId) {
    $conn = getDbConnection();
    $student = null;
    
    $sql = "SELECT s.*, u.username, u.role 
            FROM students s 
            INNER JOIN users u ON s.user_id = u.id 
            WHERE s.user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $student;
}

/**
 * Kiểm tra mã sinh viên đã tồn tại chưa (trừ ID hiện tại)
 * @param string $studentCode
 * @param int|null $excludeId ID cần loại trừ (khi sửa)
 * @return bool
 */
function isStudentCodeExists($studentCode, $excludeId = null) {
    $conn = getDbConnection();
    $exists = false;
    
    if ($excludeId) {
        $sql = "SELECT id FROM students WHERE student_code = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $studentCode, $excludeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = ($result && mysqli_num_rows($result) > 0);
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "SELECT id FROM students WHERE student_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $studentCode);
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
 * Tạo sinh viên mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createStudent($data) {
    $conn = getDbConnection();
    
    $userId = intval($data['user_id'] ?? 0);
    $studentCode = trim($data['student_code'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $dateOfBirth = !empty($data['date_of_birth']) ? trim($data['date_of_birth']) : null;
    $gender = !empty($data['gender']) ? trim($data['gender']) : null;
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');
    $university = trim($data['university'] ?? '');
    $major = trim($data['major'] ?? '');
    $year = trim($data['year'] ?? '');
    $idCard = trim($data['id_card'] ?? '');
    $status = trim($data['status'] ?? 'active');
    
    // Validation
    if ($userId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User ID không hợp lệ!'];
    }
    
    if (empty($studentCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã sinh viên không được để trống!'];
    }
    
    if (empty($fullName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Họ tên không được để trống!'];
    }
    
    if (!in_array($status, ['active', 'inactive', 'graduated'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    if ($gender && !in_array($gender, ['male', 'female', 'other'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Giới tính không hợp lệ!'];
    }
    
    // Kiểm tra mã sinh viên đã tồn tại chưa
    if (isStudentCodeExists($studentCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã sinh viên đã tồn tại!'];
    }
    
    // Kiểm tra user_id đã có student chưa
    $existingStudent = getStudentByUserId($userId);
    if ($existingStudent) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User này đã có thông tin sinh viên!'];
    }
    
    // Insert
    $sql = "INSERT INTO students (user_id, student_code, full_name, date_of_birth, gender, phone, email, address, university, major, year, id_card, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "issssssssssss", $userId, $studentCode, $fullName, $dateOfBirth, $gender, $phone, $email, $address, $university, $major, $year, $idCard, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo sinh viên thành công!', 'id' => $newId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo sinh viên: ' . $error];
    }
}

/**
 * Cập nhật thông tin sinh viên
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateStudent($id, $data) {
    $conn = getDbConnection();
    
    $studentCode = trim($data['student_code'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $dateOfBirth = !empty($data['date_of_birth']) ? trim($data['date_of_birth']) : null;
    $gender = !empty($data['gender']) ? trim($data['gender']) : null;
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');
    $university = trim($data['university'] ?? '');
    $major = trim($data['major'] ?? '');
    $year = trim($data['year'] ?? '');
    $idCard = trim($data['id_card'] ?? '');
    $status = trim($data['status'] ?? 'active');
    
    // Validation
    if (empty($studentCode)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã sinh viên không được để trống!'];
    }
    
    if (empty($fullName)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Họ tên không được để trống!'];
    }
    
    if (!in_array($status, ['active', 'inactive', 'graduated'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    if ($gender && !in_array($gender, ['male', 'female', 'other'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Giới tính không hợp lệ!'];
    }
    
    // Kiểm tra sinh viên có tồn tại không
    $existingStudent = getStudentById($id);
    if (!$existingStudent) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không tồn tại!'];
    }
    
    // Kiểm tra mã sinh viên đã tồn tại chưa (trừ ID hiện tại)
    if (isStudentCodeExists($studentCode, $id)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mã sinh viên đã tồn tại!'];
    }
    
    // Update
    $sql = "UPDATE students 
            SET student_code = ?, full_name = ?, date_of_birth = ?, gender = ?, phone = ?, email = ?, 
                address = ?, university = ?, major = ?, year = ?, id_card = ?, status = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "ssssssssssssi", $studentCode, $fullName, $dateOfBirth, $gender, $phone, $email, $address, $university, $major, $year, $idCard, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật thông tin sinh viên thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi cập nhật sinh viên: ' . $error];
    }
}

/**
 * Lấy phòng đang ở của sinh viên
 * @param int $studentId
 * @return array|null
 */
function getStudentCurrentRoom($studentId) {
    $conn = getDbConnection();
    $room = null;
    
    $sql = "SELECT r.*, b.building_code, b.building_name, ra.assigned_date
            FROM room_assignments ra
            INNER JOIN rooms r ON ra.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE ra.student_id = ? 
            AND ra.status = 'active' 
            AND ra.end_date IS NULL
            ORDER BY ra.assigned_date DESC
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
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
 * Lấy các status có thể chọn
 * @return array
 */
function getStudentStatuses() {
    return [
        'active' => 'Đang hoạt động',
        'inactive' => 'Ngừng hoạt động',
        'graduated' => 'Đã tốt nghiệp'
    ];
}

/**
 * Lấy các giới tính có thể chọn
 * @return array
 */
function getGenders() {
    return [
        'male' => 'Nam',
        'female' => 'Nữ',
        'other' => 'Khác'
    ];
}

?>

