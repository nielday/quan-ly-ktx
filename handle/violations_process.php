<?php
/**
 * Violations Process - Xử lý các action liên quan đến vi phạm
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/violations.php';
require_once __DIR__ . '/../functions/helpers.php';

// Kiểm tra đăng nhập và quyền Manager
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$user = getCurrentUser();
if ($user['role'] !== 'manager') {
    $_SESSION['error'] = 'Bạn không có quyền truy cập!';
    header('Location: ../views/manager/dashboard.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreateViolation();
        break;
    
    case 'update':
        handleUpdateViolation();
        break;
    
    case 'delete':
        handleDeleteViolation();
        break;
    
    default:
        $_SESSION['error'] = 'Action không hợp lệ!';
        header('Location: ../views/manager/violations.php');
        exit;
}

/**
 * Xử lý tạo vi phạm mới
 */
function handleCreateViolation() {
    // Validate dữ liệu đầu vào
    $studentId = intval($_POST['student_id'] ?? 0);
    $roomId = intval($_POST['room_id'] ?? 0);
    $violationType = trim($_POST['violation_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $violationDate = trim($_POST['violation_date'] ?? '');
    $penaltyAmount = floatval($_POST['penalty_amount'] ?? 0);
    $penaltyType = trim($_POST['penalty_type'] ?? 'warning');
    $evidence = trim($_POST['evidence'] ?? '');
    
    // Validate
    if ($studentId <= 0) {
        $_SESSION['error'] = 'Vui lòng chọn sinh viên!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    if ($roomId <= 0) {
        $_SESSION['error'] = 'Vui lòng chọn phòng!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    if (empty($violationType)) {
        $_SESSION['error'] = 'Vui lòng chọn loại vi phạm!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    if (empty($violationDate)) {
        $_SESSION['error'] = 'Vui lòng chọn ngày vi phạm!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    // Validate date
    $dateParts = explode('-', $violationDate);
    if (count($dateParts) !== 3 || !checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
        $_SESSION['error'] = 'Ngày vi phạm không hợp lệ!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    // Validate penalty amount nếu penalty_type = fine
    if ($penaltyType == 'fine' && $penaltyAmount <= 0) {
        $_SESSION['error'] = 'Vui lòng nhập số tiền phạt!';
        header('Location: ../views/manager/violations/create.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Chuẩn bị dữ liệu
    $data = [
        'student_id' => $studentId,
        'room_id' => $roomId,
        'violation_type' => $violationType,
        'description' => $description,
        'violation_date' => $violationDate,
        'reported_by' => $userId,
        'penalty_amount' => $penaltyAmount,
        'penalty_type' => $penaltyType,
        'status' => 'pending',
        'evidence' => $evidence
    ];
    
    // Tạo vi phạm
    $result = createViolation($data);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../views/manager/violations.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/manager/violations/create.php');
    }
    exit;
}

/**
 * Xử lý cập nhật vi phạm
 */
function handleUpdateViolation() {
    $violationId = intval($_POST['violation_id'] ?? 0);
    
    if ($violationId <= 0) {
        $_SESSION['error'] = 'ID vi phạm không hợp lệ!';
        header('Location: ../views/manager/violations.php');
        exit;
    }
    
    // Lấy dữ liệu từ form
    $data = [];
    
    if (isset($_POST['violation_type'])) {
        $data['violation_type'] = trim($_POST['violation_type']);
    }
    
    if (isset($_POST['description'])) {
        $data['description'] = trim($_POST['description']);
    }
    
    if (isset($_POST['violation_date'])) {
        $violationDate = trim($_POST['violation_date']);
        // Validate date
        $dateParts = explode('-', $violationDate);
        if (count($dateParts) !== 3 || !checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
            $_SESSION['error'] = 'Ngày vi phạm không hợp lệ!';
            header('Location: ../views/manager/violations/edit.php?id=' . $violationId);
            exit;
        }
        $data['violation_date'] = $violationDate;
    }
    
    if (isset($_POST['penalty_type'])) {
        $data['penalty_type'] = trim($_POST['penalty_type']);
    }
    
    if (isset($_POST['penalty_amount'])) {
        $penaltyAmount = floatval($_POST['penalty_amount']);
        // Validate penalty amount nếu penalty_type = fine
        if (isset($data['penalty_type']) && $data['penalty_type'] == 'fine' && $penaltyAmount <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập số tiền phạt!';
            header('Location: ../views/manager/violations/edit.php?id=' . $violationId);
            exit;
        }
        $data['penalty_amount'] = $penaltyAmount;
    }
    
    if (isset($_POST['status'])) {
        $data['status'] = trim($_POST['status']);
    }
    
    if (isset($_POST['evidence'])) {
        $data['evidence'] = trim($_POST['evidence']);
    }
    
    // Cập nhật vi phạm
    $result = updateViolation($violationId, $data);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        // Redirect URL từ form hoặc về danh sách
        $redirectUrl = $_POST['redirect_url'] ?? '../views/manager/violations.php';
        header('Location: ' . $redirectUrl);
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/manager/violations/edit.php?id=' . $violationId);
    }
    exit;
}

/**
 * Xử lý xóa vi phạm
 */
function handleDeleteViolation() {
    $violationId = intval($_GET['id'] ?? 0);
    
    if ($violationId <= 0) {
        $_SESSION['error'] = 'ID vi phạm không hợp lệ!';
        header('Location: ../views/manager/violations.php');
        exit;
    }
    
    // Xóa vi phạm
    $result = deleteViolation($violationId);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ../views/manager/violations.php');
    exit;
}

