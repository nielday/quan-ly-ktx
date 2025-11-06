<?php
/**
 * Authentication functions - Các hàm xử lý xác thực
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';

/**
 * Hàm kiểm tra xem user đã đăng nhập chưa
 * Nếu chưa đăng nhập, chuyển hướng về trang login
 * 
 * @param string $redirectPath Đường dẫn để chuyển hướng về trang login (mặc định: '../index.php')
 */
function checkLogin($redirectPath = '../index.php') {
    startSession();
    
    // Kiểm tra xem user đã đăng nhập chưa
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        // Nếu chưa đăng nhập, set thông báo lỗi và chuyển hướng
        setErrorMessage('Bạn cần đăng nhập để truy cập trang này!');
        redirect($redirectPath);
    }
}

/**
 * Hàm kiểm tra quyền truy cập theo role
 * @param array|string $allowedRoles Danh sách role được phép (ví dụ: ['admin', 'manager'] hoặc 'admin')
 * @param string $redirectPath Đường dẫn chuyển hướng nếu không có quyền
 */
function checkRole($allowedRoles, $redirectPath = '../index.php') {
    startSession();
    checkLogin($redirectPath);
    
    $userRole = $_SESSION['role'] ?? null;
    
    // Nếu allowedRoles là string, chuyển thành array
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    // Kiểm tra role
    if (!in_array($userRole, $allowedRoles)) {
        setErrorMessage('Bạn không có quyền truy cập trang này!');
        redirect($redirectPath);
    }
}

/**
 * Hàm đăng xuất user
 * Xóa tất cả session và chuyển hướng về trang login
 * 
 * @param string $redirectPath Đường dẫn để chuyển hướng sau khi logout (mặc định: '../index.php')
 */
function logout($redirectPath = '../index.php') {
    startSession();
    
    // Hủy tất cả session
    session_unset();
    session_destroy();
    
    // Khởi tạo session mới để lưu thông báo
    session_start();
    setSuccessMessage('Đăng xuất thành công!');
    
    // Chuyển hướng về trang đăng nhập
    redirect($redirectPath);
}

/**
 * Hàm lấy thông tin user hiện tại
 * 
 * @return array|null Trả về thông tin user nếu đã đăng nhập, null nếu chưa đăng nhập
 */
function getCurrentUser() {
    startSession();
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'phone' => $_SESSION['phone'] ?? null
        ];
    }
    
    return null;
}

/**
 * Hàm kiểm tra xem user đã đăng nhập chưa (không redirect)
 * 
 * @return bool True nếu đã đăng nhập, False nếu chưa
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Hàm xác thực đăng nhập
 * @param mysqli $conn
 * @param string $username
 * @param string $password
 * @return array|false Trả về thông tin user nếu đúng, false nếu sai
 */
function authenticateUser($conn, $username, $password) {
    $sql = "SELECT id, username, password, full_name, role, email, phone, status 
            FROM users 
            WHERE username = ? AND status = 'active' 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Kiểm tra mật khẩu (hỗ trợ cả plain text và hashed password)
        $passwordMatch = false;
        
        // Nếu password trong DB là hash (bắt đầu bằng $2y$), dùng password_verify
        if (strpos($user['password'], '$2y$') === 0) {
            $passwordMatch = password_verify($password, $user['password']);
        } else {
            // Nếu là plain text, so sánh trực tiếp
            $passwordMatch = ($password === $user['password']);
        }
        
        if ($passwordMatch) {
            mysqli_stmt_close($stmt);
            // Xóa password khỏi kết quả trả về
            unset($user['password']);
            return $user;
        }
    }
    
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

/**
 * Hàm lấy đường dẫn dashboard theo role
 * @param string $role
 * @return string
 */
function getDashboardPath($role) {
    switch ($role) {
        case 'admin':
            return 'views/admin/dashboard.php';
        case 'manager':
            return 'views/manager/dashboard.php';
        case 'student':
            return 'views/student/dashboard.php';
        default:
            return 'index.php';
    }
}

?>

