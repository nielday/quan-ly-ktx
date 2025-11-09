<?php
/**
 * Users functions - Các hàm xử lý quản lý tài khoản (Admin)
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

/**
 * Lấy tất cả users với filter
 * @param array $filters ['role' => 'admin|manager|student', 'status' => 'active|inactive']
 * @return array
 */
function getAllUsers($filters = []) {
    $conn = getDbConnection();
    $users = [];
    
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['role'])) {
        $sql .= " AND role = ?";
        $params[] = $filters['role'];
        $types .= "s";
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        $search = "%" . $filters['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Xóa password khỏi kết quả
                unset($row['password']);
                $users[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $users;
}

/**
 * Lấy thông tin user theo ID
 * @param int $userId
 * @return array|null
 */
function getUserById($userId) {
    $conn = getDbConnection();
    $user = null;
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            // Xóa password
            unset($user['password']);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $user;
}

/**
 * Lấy user theo username
 * @param string $username
 * @return array|null
 */
function getUserByUsername($username) {
    $conn = getDbConnection();
    $user = null;
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $user;
}

/**
 * Tạo user mới
 * @param array $data ['username', 'password', 'full_name', 'email', 'phone', 'role']
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function createUser($data) {
    $conn = getDbConnection();
    
    // Validate
    if (empty($data['username']) || empty($data['password']) || empty($data['full_name']) || empty($data['role'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'];
    }
    
    // Kiểm tra username đã tồn tại chưa
    $existingUser = getUserByUsername($data['username']);
    if ($existingUser) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Username đã tồn tại!'];
    }
    
    // Validate role
    $validRoles = ['admin', 'manager', 'student'];
    if (!in_array($data['role'], $validRoles)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Role không hợp lệ!'];
    }
    
    // Validate email format
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Email không hợp lệ!'];
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (username, password, full_name, email, phone, role, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    
    mysqli_stmt_bind_param($stmt, "ssssss", 
        $data['username'], 
        $hashedPassword, 
        $data['full_name'], 
        $email, 
        $phone, 
        $data['role']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $userId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Tạo tài khoản thành công!', 'user_id' => $userId];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo tài khoản: ' . $error];
    }
}

/**
 * Cập nhật user
 * @param int $userId
 * @param array $data ['full_name', 'email', 'phone', 'role', 'status']
 * @return array ['success' => bool, 'message' => string]
 */
function updateUser($userId, $data) {
    $conn = getDbConnection();
    
    // Validate
    if (empty($data['full_name']) || empty($data['role'])) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'];
    }
    
    // Validate role
    $validRoles = ['admin', 'manager', 'student'];
    if (!in_array($data['role'], $validRoles)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Role không hợp lệ!'];
    }
    
    // Validate status
    $validStatuses = ['active', 'inactive'];
    if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Status không hợp lệ!'];
    }
    
    // Validate email format
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Email không hợp lệ!'];
    }
    
    // Kiểm tra user có tồn tại không
    $existingUser = getUserById($userId);
    if (!$existingUser) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User không tồn tại!'];
    }
    
    // Không cho phép khóa tài khoản admin
    if (isset($data['status']) && $data['status'] === 'inactive') {
        // Kiểm tra user có phải admin không
        if ($existingUser['role'] === 'admin') {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Không thể khóa tài khoản Admin!'];
        }
        
        // Không cho phép admin tự khóa mình (nếu là manager/student)
        startSession();
        $currentUserId = $_SESSION['user_id'] ?? null;
        if ($currentUserId == $userId) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Bạn không thể khóa tài khoản của chính mình!'];
        }
    }
    
    // Đảm bảo admin luôn ở trạng thái active
    if ($existingUser['role'] === 'admin') {
        $data['status'] = 'active';
    }
    
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $status = $data['status'] ?? 'active';
    
    // Kiểm tra status có thay đổi không (chỉ cần đồng bộ khi thay đổi status và là student)
    $statusChanged = ($existingUser['status'] !== $status);
    $isStudent = ($existingUser['role'] === 'student' || $data['role'] === 'student');
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update user
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL cho users!');
        }
        
        mysqli_stmt_bind_param($stmt, "sssssi", 
            $data['full_name'], 
            $email, 
            $phone, 
            $data['role'], 
            $status,
            $userId
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật tài khoản users: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        
        // Nếu là student và status thay đổi, đồng bộ với bảng students
        if ($isStudent && $statusChanged) {
            // Chuyển đổi status: users (active/inactive) -> students (active/inactive)
            // Không thay đổi nếu student đã graduated
            $studentStatus = $status; // 'active' hoặc 'inactive'
            
            $sqlStudent = "UPDATE students SET status = ?, updated_at = CURRENT_TIMESTAMP 
                          WHERE user_id = ? AND status != 'graduated'";
            $stmtStudent = mysqli_prepare($conn, $sqlStudent);
            
            if (!$stmtStudent) {
                throw new Exception('Lỗi chuẩn bị câu lệnh SQL cho students!');
            }
            
            mysqli_stmt_bind_param($stmtStudent, "si", $studentStatus, $userId);
            
            if (!mysqli_stmt_execute($stmtStudent)) {
                throw new Exception('Lỗi cập nhật trạng thái students: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmtStudent);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['success' => true, 'message' => 'Cập nhật tài khoản thành công!'];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Xóa user
 * @param int $userId
 * @param int $currentUserId ID của admin đang thực hiện
 * @return array ['success' => bool, 'message' => string]
 */
function deleteUser($userId, $currentUserId) {
    $conn = getDbConnection();
    
    // Không cho phép xóa chính mình
    if ($userId == $currentUserId) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Bạn không thể xóa tài khoản của chính mình!'];
    }
    
    // Kiểm tra user có tồn tại không
    $user = getUserById($userId);
    if (!$user) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User không tồn tại!'];
    }
    
    // Không cho phép xóa tài khoản admin
    if ($user['role'] === 'admin') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không thể xóa tài khoản Admin!'];
    }
    
    // Kiểm tra user có liên kết với bảng students không (nếu là student)
    if ($user['role'] === 'student') {
        $sqlCheck = "SELECT COUNT(*) as count FROM students WHERE user_id = ?";
        $stmtCheck = mysqli_prepare($conn, $sqlCheck);
        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "i", $userId);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $rowCheck = mysqli_fetch_assoc($resultCheck);
            mysqli_stmt_close($stmtCheck);
            
            if ($rowCheck['count'] > 0) {
                mysqli_close($conn);
                return ['success' => false, 'message' => 'Không thể xóa user này vì đã có dữ liệu liên quan (students)!'];
            }
        }
    }
    
    // Xóa user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa tài khoản thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi xóa tài khoản: ' . $error];
    }
}

/**
 * Reset mật khẩu user
 * @param int $userId
 * @param string $newPassword
 * @return array ['success' => bool, 'message' => string]
 */
function resetUserPassword($userId, $newPassword) {
    $conn = getDbConnection();
    
    // Validate
    if (empty($newPassword) || strlen($newPassword) < 6) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự!'];
    }
    
    // Kiểm tra user có tồn tại không
    $user = getUserById($userId);
    if (!$user) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User không tồn tại!'];
    }
    
    // Hash password mới
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL!'];
    }
    
    mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Reset mật khẩu thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi reset mật khẩu: ' . $error];
    }
}

/**
 * Toggle user status (active/inactive)
 * @param int $userId
 * @param int $currentUserId ID của admin đang thực hiện
 * @return array ['success' => bool, 'message' => string, 'new_status' => string|null]
 */
function toggleUserStatus($userId, $currentUserId) {
    $conn = getDbConnection();
    
    // Không cho phép khóa chính mình
    if ($userId == $currentUserId) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Bạn không thể khóa/mở khóa tài khoản của chính mình!'];
    }
    
    // Lấy thông tin user trực tiếp từ database (trong cùng connection)
    $sqlGetUser = "SELECT id, role, status FROM users WHERE id = ?";
    $stmtGetUser = mysqli_prepare($conn, $sqlGetUser);
    
    if (!$stmtGetUser) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi lấy thông tin user!'];
    }
    
    mysqli_stmt_bind_param($stmtGetUser, "i", $userId);
    mysqli_stmt_execute($stmtGetUser);
    $resultGetUser = mysqli_stmt_get_result($stmtGetUser);
    
    if (!$resultGetUser || mysqli_num_rows($resultGetUser) === 0) {
        mysqli_stmt_close($stmtGetUser);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'User không tồn tại!'];
    }
    
    $user = mysqli_fetch_assoc($resultGetUser);
    mysqli_stmt_close($stmtGetUser);
    
    // Không cho phép khóa tài khoản admin
    if ($user['role'] === 'admin') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không thể khóa tài khoản Admin!'];
    }
    
    // Toggle status
    $newStatus = ($user['status'] === 'inactive') ? 'active' : 'inactive';
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update users table
        $sql = "UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL cho users!');
        }
        
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $userId);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật trạng thái users: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        
        // Nếu là student, đồng bộ status với bảng students
        if ($user['role'] === 'student') {
            // Chuyển đổi status: users (active/inactive) -> students (active/inactive)
            // Nếu user inactive thì student cũng inactive (trừ khi student đã graduated)
            $studentStatus = $newStatus; // 'active' hoặc 'inactive'
            
            $sqlStudent = "UPDATE students SET status = ?, updated_at = CURRENT_TIMESTAMP 
                          WHERE user_id = ? AND status != 'graduated'";
            $stmtStudent = mysqli_prepare($conn, $sqlStudent);
            
            if (!$stmtStudent) {
                throw new Exception('Lỗi chuẩn bị câu lệnh SQL cho students!');
            }
            
            mysqli_stmt_bind_param($stmtStudent, "si", $studentStatus, $userId);
            
            if (!mysqli_stmt_execute($stmtStudent)) {
                throw new Exception('Lỗi cập nhật trạng thái students: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmtStudent);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        $statusLabel = ($newStatus === 'active') ? 'kích hoạt' : 'khóa';
        return ['success' => true, 'message' => "Đã {$statusLabel} tài khoản thành công!", 'new_status' => $newStatus];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy thống kê users
 * @return array
 */
function getUserStatistics() {
    $conn = getDbConnection();
    $stats = [
        'total' => 0,
        'admin' => 0,
        'manager' => 0,
        'student' => 0,
        'active' => 0,
        'inactive' => 0
    ];
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager,
                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
            FROM users";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats = [
            'total' => intval($row['total']),
            'admin' => intval($row['admin']),
            'manager' => intval($row['manager']),
            'student' => intval($row['student']),
            'active' => intval($row['active']),
            'inactive' => intval($row['inactive'])
        ];
    }
    
    mysqli_close($conn);
    return $stats;
}

/**
 * Lấy danh sách roles
 * @return array
 */
function getUserRoles() {
    return [
        'admin' => 'Quản trị viên',
        'manager' => 'Quản lý KTX',
        'student' => 'Sinh viên'
    ];
}

/**
 * Lấy danh sách statuses
 * @return array
 */
function getUserStatuses() {
    return [
        'active' => 'Hoạt động',
        'inactive' => 'Không hoạt động'
    ];
}

