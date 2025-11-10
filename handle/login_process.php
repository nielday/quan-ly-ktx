<?php
/**
 * Xử lý đăng nhập
 */

session_start();
require_once __DIR__ . '/../functions/db_connection.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    handleLogin();
}

function handleLogin() {
    $conn = getDbConnection();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (empty($username) || empty($password)) {
        setErrorMessage('Vui lòng nhập đầy đủ username và password!');
        mysqli_close($conn);
        redirect('../login.php');
    }

    // Xác thực user
    $user = authenticateUser($conn, $username, $password);
    
    if ($user) {
        // Lưu thông tin vào session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'] ?? null;
        $_SESSION['phone'] = $user['phone'] ?? null;
        
        setSuccessMessage('Đăng nhập thành công!');
        
        // Chuyển hướng theo role
        $dashboardPath = getDashboardPath($user['role']);
        mysqli_close($conn);
        redirect('../' . $dashboardPath);
    }

    // Đăng nhập thất bại
    setErrorMessage('Tên đăng nhập hoặc mật khẩu không đúng!');
    mysqli_close($conn);
    redirect('../login.php');
}

?>

