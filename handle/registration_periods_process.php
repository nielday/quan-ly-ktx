<?php
/**
 * Xử lý các action của Registration_Periods
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/registration_periods.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../index.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreateRegistrationPeriod();
        break;
    
    case 'update':
        handleUpdateRegistrationPeriod();
        break;
    
    case 'delete':
        handleDeleteRegistrationPeriod();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
        break;
}

/**
 * Xử lý tạo đợt đăng ký mới
 */
function handleCreateRegistrationPeriod() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
    }
    
    $currentUser = getCurrentUser();
    
    $data = [
        'period_name' => $_POST['period_name'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'semester' => $_POST['semester'] ?? '',
        'academic_year' => $_POST['academic_year'] ?? '',
        'total_rooms_available' => $_POST['total_rooms_available'] ?? null,
        'status' => $_POST['status'] ?? 'upcoming',
        'created_by' => $currentUser['id']
    ];
    
    $result = createRegistrationPeriod($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/registration_periods.php');
}

/**
 * Xử lý cập nhật đợt đăng ký
 */
function handleUpdateRegistrationPeriod() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
    }
    
    $data = [
        'period_name' => $_POST['period_name'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'semester' => $_POST['semester'] ?? '',
        'academic_year' => $_POST['academic_year'] ?? '',
        'total_rooms_available' => $_POST['total_rooms_available'] ?? null,
        'status' => $_POST['status'] ?? 'upcoming'
    ];
    
    $result = updateRegistrationPeriod($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/registration_periods.php');
}

/**
 * Xử lý xóa đợt đăng ký
 */
function handleDeleteRegistrationPeriod() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/registration_periods.php');
    }
    
    $result = deleteRegistrationPeriod($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/registration_periods.php');
}

?>

