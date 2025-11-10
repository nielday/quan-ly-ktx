<?php
/**
 * Xử lý các action của Applications
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/applications.php';
require_once __DIR__ . '/../functions/students.php';

$action = getAction();
$currentUser = getCurrentUser();

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('../login.php');
}

switch ($action) {
    case 'create':
        handleCreateApplication();
        break;
    
    case 'approve':
        handleApproveApplication();
        break;
    
    case 'reject':
        handleRejectApplication();
        break;
    
    default:
        if ($currentUser['role'] == 'manager') {
            setErrorMessage('Action không hợp lệ!');
            redirect('../views/manager/applications.php');
        } else {
            setErrorMessage('Action không hợp lệ!');
            redirect('../views/student/applications/view.php');
        }
        break;
}

/**
 * Xử lý tạo đơn đăng ký mới (Student)
 */
function handleCreateApplication() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/student/applications/create.php');
    }
    
    $currentUser = getCurrentUser();
    
    // Chỉ student mới được tạo đơn
    if ($currentUser['role'] != 'student') {
        setErrorMessage('Chỉ sinh viên mới được tạo đơn đăng ký!');
        redirect('../views/student/applications/create.php');
    }
    
    // Lấy student_id từ user_id
    $student = getStudentByUserId($currentUser['id']);
    if (!$student) {
        setErrorMessage('Không tìm thấy thông tin sinh viên!');
        redirect('../views/student/applications/create.php');
    }
    
    $data = [
        'student_id' => $student['id'],
        'registration_period_id' => !empty($_POST['registration_period_id']) ? intval($_POST['registration_period_id']) : null,
        'application_date' => $_POST['application_date'] ?? date('Y-m-d'),
        'semester' => $_POST['semester'] ?? '',
        'academic_year' => $_POST['academic_year'] ?? '',
        'preferred_room_type' => $_POST['preferred_room_type'] ?? ''
    ];
    
    $result = createApplication($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        redirect('../views/student/applications/view.php');
    } else {
        setErrorMessage($result['message']);
        redirect('../views/student/applications/create.php');
    }
}

/**
 * Xử lý duyệt đơn đăng ký (Manager)
 */
function handleApproveApplication() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/applications.php');
    }
    
    $currentUser = getCurrentUser();
    
    // Chỉ manager mới được duyệt
    checkRole('manager', '../login.php');
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/applications.php');
    }
    
    $result = approveApplication($id, $currentUser['id']);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/applications/view.php?id=' . $id);
}

/**
 * Xử lý từ chối đơn đăng ký (Manager)
 */
function handleRejectApplication() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/applications.php');
    }
    
    $currentUser = getCurrentUser();
    
    // Chỉ manager mới được từ chối
    checkRole('manager', '../login.php');
    
    $id = intval($_POST['id'] ?? 0);
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/applications.php');
    }
    
    $result = rejectApplication($id, $currentUser['id'], $rejectionReason);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/applications/view.php?id=' . $id);
}

?>

