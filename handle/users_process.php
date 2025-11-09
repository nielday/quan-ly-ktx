<?php
/**
 * Users Process Handler - Xử lý các action quản lý users (Admin)
 */

require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/users.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

startSession();
$currentUser = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreateUser();
        break;
    case 'update':
        handleUpdateUser();
        break;
    case 'delete':
        handleDeleteUser();
        break;
    case 'reset_password':
        handleResetPassword();
        break;
    case 'toggle_status':
        handleToggleStatus();
        break;
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/admin/users.php');
        break;
}

/**
 * Xử lý tạo user mới
 */
function handleCreateUser() {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Validate
    if (empty($username) || empty($password) || empty($fullName) || empty($role)) {
        setErrorMessage('Vui lòng điền đầy đủ thông tin bắt buộc!');
        redirect('../views/admin/users/create_user.php');
        exit;
    }
    
    if (strlen($password) < 6) {
        setErrorMessage('Mật khẩu phải có ít nhất 6 ký tự!');
        redirect('../views/admin/users/create_user.php');
        exit;
    }
    
    $data = [
        'username' => $username,
        'password' => $password,
        'full_name' => $fullName,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'role' => $role
    ];
    
    $result = createUser($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        redirect('../views/admin/users.php');
    } else {
        setErrorMessage($result['message']);
        redirect('../views/admin/users/create_user.php');
    }
}

/**
 * Xử lý cập nhật user
 */
function handleUpdateUser() {
    $userId = intval($_POST['user_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Validate
    if ($userId <= 0) {
        setErrorMessage('ID user không hợp lệ!');
        redirect('../views/admin/users.php');
        exit;
    }
    
    if (empty($fullName) || empty($role)) {
        setErrorMessage('Vui lòng điền đầy đủ thông tin bắt buộc!');
        redirect('../views/admin/users/edit_user.php?id=' . $userId);
        exit;
    }
    
    $data = [
        'full_name' => $fullName,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'role' => $role,
        'status' => $status
    ];
    
    $result = updateUser($userId, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        redirect('../views/admin/users.php');
    } else {
        setErrorMessage($result['message']);
        redirect('../views/admin/users/edit_user.php?id=' . $userId);
    }
}

/**
 * Xử lý xóa user
 */
function handleDeleteUser() {
    $userId = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
    $currentUserId = $_SESSION['user_id'];
    
    if ($userId <= 0) {
        setErrorMessage('ID user không hợp lệ!');
        redirect('../views/admin/users.php');
        exit;
    }
    
    $result = deleteUser($userId, $currentUserId);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/admin/users.php');
}

/**
 * Xử lý reset mật khẩu
 */
function handleResetPassword() {
    $userId = intval($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    
    if ($userId <= 0) {
        setErrorMessage('ID user không hợp lệ!');
        redirect('../views/admin/users.php');
        exit;
    }
    
    if (empty($newPassword) || strlen($newPassword) < 6) {
        setErrorMessage('Mật khẩu phải có ít nhất 6 ký tự!');
        redirect('../views/admin/users/edit_user.php?id=' . $userId);
        exit;
    }
    
    $result = resetUserPassword($userId, $newPassword);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        redirect('../views/admin/users.php');
    } else {
        setErrorMessage($result['message']);
        redirect('../views/admin/users/edit_user.php?id=' . $userId);
    }
}

/**
 * Xử lý toggle status
 */
function handleToggleStatus() {
    $userId = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
    $currentUserId = $_SESSION['user_id'];
    
    if ($userId <= 0) {
        setErrorMessage('ID user không hợp lệ!');
        redirect('../views/admin/users.php');
        exit;
    }
    
    $result = toggleUserStatus($userId, $currentUserId);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/admin/users.php');
}

