<?php
/**
 * Xử lý các action của Pricing
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/pricing.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../login.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreatePricing();
        break;
    
    case 'update':
        handleUpdatePricing();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/pricing.php');
        break;
}

/**
 * Xử lý tạo đơn giá mới
 */
function handleCreatePricing() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/pricing.php');
    }
    
    $currentUser = getCurrentUser();
    
    $data = [
        'price_type' => $_POST['price_type'] ?? '',
        'price_value' => $_POST['price_value'] ?? 0,
        'unit' => $_POST['unit'] ?? '',
        'effective_from' => $_POST['effective_from'] ?? date('Y-m-d'),
        'effective_to' => !empty($_POST['effective_to']) ? $_POST['effective_to'] : null,
        'description' => $_POST['description'] ?? '',
        'created_by' => $currentUser['id']
    ];
    
    $result = createPricing($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/pricing.php');
}

/**
 * Xử lý cập nhật đơn giá
 */
function handleUpdatePricing() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/pricing.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/pricing.php');
    }
    
    $data = [
        'description' => $_POST['description'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = updatePricing($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/pricing.php');
}

?>

