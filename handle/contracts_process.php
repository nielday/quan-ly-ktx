<?php
/**
 * Xử lý các action của Contracts
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/contracts.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../index.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreateContract();
        break;
    
    case 'update':
        handleUpdateContract();
        break;
    
    case 'extend':
        handleExtendContract();
        break;
    
    case 'terminate':
        handleTerminateContract();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/contracts.php');
        break;
}

/**
 * Xử lý tạo hợp đồng mới
 */
function handleCreateContract() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    // Lấy thông tin application nếu có
    $applicationId = !empty($_POST['application_id']) ? intval($_POST['application_id']) : null;
    $applicationApprovedAt = null;
    
    if ($applicationId) {
        require_once __DIR__ . '/../functions/applications.php';
        $application = getApplicationById($applicationId);
        if ($application && $application['approved_at']) {
            $applicationApprovedAt = $application['approved_at'];
        }
    }
    
    $data = [
        'student_id' => $_POST['student_id'] ?? 0,
        'room_id' => $_POST['room_id'] ?? 0,
        'contract_code' => trim($_POST['contract_code'] ?? ''),
        'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
        'end_date' => $_POST['end_date'] ?? '',
        'monthly_fee' => $_POST['monthly_fee'] ?? null,
        'deposit' => $_POST['deposit'] ?? 0,
        'status' => $_POST['status'] ?? 'active',
        'application_id' => $applicationId,
        'application_approved_at' => $applicationApprovedAt,
        'created_by' => $_SESSION['user_id'] ?? null // Thêm created_by để tự động confirmed payment
    ];
    
    $result = createContract($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    // Nếu có application_id, redirect về applications
    if (isset($_POST['application_id']) && !empty($_POST['application_id'])) {
        redirect('../views/manager/applications/view.php?id=' . intval($_POST['application_id']));
    } else {
        redirect('../views/manager/contracts.php');
    }
}

/**
 * Xử lý cập nhật hợp đồng
 */
function handleUpdateContract() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    $data = [
        'contract_code' => trim($_POST['contract_code'] ?? ''),
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'monthly_fee' => $_POST['monthly_fee'] ?? null,
        'deposit' => $_POST['deposit'] ?? 0,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = updateContract($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/contracts/view_contract.php?id=' . $id);
}

/**
 * Xử lý gia hạn hợp đồng
 */
function handleExtendContract() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $newEndDate = trim($_POST['new_end_date'] ?? '');
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    if (empty($newEndDate)) {
        setErrorMessage('Ngày kết thúc mới không được để trống!');
        redirect('../views/manager/contracts/view_contract.php?id=' . $id);
    }
    
    $result = extendContract($id, $newEndDate);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/contracts/view_contract.php?id=' . $id);
}

/**
 * Xử lý thanh lý hợp đồng
 */
function handleTerminateContract() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/contracts.php');
    }
    
    $result = terminateContract($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/contracts/view_contract.php?id=' . $id);
}

?>

