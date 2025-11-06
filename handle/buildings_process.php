<?php
/**
 * Xử lý các action của Buildings
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../index.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreateBuilding();
        break;
    
    case 'update':
        handleUpdateBuilding();
        break;
    
    case 'delete':
        handleDeleteBuilding();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/buildings.php');
        break;
}

/**
 * Xử lý tạo tòa nhà mới
 */
function handleCreateBuilding() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/buildings.php');
    }
    
    $data = [
        'building_code' => $_POST['building_code'] ?? '',
        'building_name' => $_POST['building_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'floors' => $_POST['floors'] ?? 1,
        'description' => $_POST['description'] ?? ''
    ];
    
    $result = createBuilding($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/buildings.php');
}

/**
 * Xử lý cập nhật tòa nhà
 */
function handleUpdateBuilding() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/buildings.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/buildings.php');
    }
    
    $data = [
        'building_code' => $_POST['building_code'] ?? '',
        'building_name' => $_POST['building_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'floors' => $_POST['floors'] ?? 1,
        'description' => $_POST['description'] ?? ''
    ];
    
    $result = updateBuilding($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/buildings.php');
}

/**
 * Xử lý xóa tòa nhà
 */
function handleDeleteBuilding() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/buildings.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/buildings.php');
    }
    
    $result = deleteBuilding($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/buildings.php');
}

?>

