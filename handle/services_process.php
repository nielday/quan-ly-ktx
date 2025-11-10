<?php
/**
 * Xử lý các action của Services
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/services.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../login.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreateService();
        break;
    
    case 'update':
        handleUpdateService();
        break;
    
    case 'delete':
        handleDeleteService();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/services.php');
        break;
}

/**
 * Xử lý tạo dịch vụ mới
 */
function handleCreateService() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/services.php');
    }
    
    $data = [
        'service_code' => $_POST['service_code'] ?? '',
        'service_name' => $_POST['service_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'unit' => $_POST['unit'] ?? 'tháng',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = createService($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/services.php');
}

/**
 * Xử lý cập nhật dịch vụ
 */
function handleUpdateService() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/services.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/services.php');
    }
    
    $data = [
        'service_code' => $_POST['service_code'] ?? '',
        'service_name' => $_POST['service_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'unit' => $_POST['unit'] ?? 'tháng',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = updateService($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/services.php');
}

/**
 * Xử lý xóa dịch vụ
 */
function handleDeleteService() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/services.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/services.php');
    }
    
    $result = deleteService($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/services.php');
}

?>

