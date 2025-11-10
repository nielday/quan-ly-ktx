<?php
/**
 * Room Transfers Process - Xử lý các action liên quan đến yêu cầu chuyển phòng
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/room_transfers.php';
require_once __DIR__ . '/../functions/helpers.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Phân quyền theo role
if ($user['role'] === 'manager') {
    // Manager có thể approve, reject
    switch ($action) {
        case 'approve':
            handleApproveRequest();
            break;
        
        case 'reject':
            handleRejectRequest();
            break;
        
        default:
            $_SESSION['error'] = 'Action không hợp lệ!';
            header('Location: ../views/manager/room_transfers.php');
            exit;
    }
} elseif ($user['role'] === 'student') {
    // Student có thể create
    switch ($action) {
        case 'create':
            handleCreateRequest();
            break;
        
        default:
            $_SESSION['error'] = 'Action không hợp lệ!';
            header('Location: ../views/student/room_transfers.php');
            exit;
    }
} else {
    $_SESSION['error'] = 'Bạn không có quyền truy cập!';
    header('Location: ../login.php');
    exit;
}

/**
 * Xử lý duyệt yêu cầu chuyển phòng
 */
function handleApproveRequest() {
    $requestId = intval($_POST['request_id'] ?? 0);
    $newRoomId = intval($_POST['new_room_id'] ?? 0);
    
    if ($requestId <= 0) {
        $_SESSION['error'] = 'ID yêu cầu không hợp lệ!';
        header('Location: ../views/manager/room_transfers.php');
        exit;
    }
    
    if ($newRoomId <= 0) {
        $_SESSION['error'] = 'Vui lòng chọn phòng mới!';
        header('Location: ../views/manager/room_transfers/view.php?id=' . $requestId);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Duyệt yêu cầu
    $result = approveRoomTransferRequest($requestId, $newRoomId, $userId);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../views/manager/room_transfers.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/manager/room_transfers/view.php?id=' . $requestId);
    }
    exit;
}

/**
 * Xử lý từ chối yêu cầu chuyển phòng
 */
function handleRejectRequest() {
    $requestId = intval($_POST['request_id'] ?? 0);
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    
    if ($requestId <= 0) {
        $_SESSION['error'] = 'ID yêu cầu không hợp lệ!';
        header('Location: ../views/manager/room_transfers.php');
        exit;
    }
    
    if (empty($rejectionReason)) {
        $_SESSION['error'] = 'Vui lòng nhập lý do từ chối!';
        header('Location: ../views/manager/room_transfers/view.php?id=' . $requestId);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Từ chối yêu cầu
    $result = rejectRoomTransferRequest($requestId, $rejectionReason, $userId);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../views/manager/room_transfers.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/manager/room_transfers/view.php?id=' . $requestId);
    }
    exit;
}

/**
 * Xử lý tạo yêu cầu chuyển phòng (Student)
 */
function handleCreateRequest() {
    require_once __DIR__ . '/../functions/students.php';
    
    $student = getStudentByUserId($_SESSION['user_id']);
    if (!$student) {
        $_SESSION['error'] = 'Không tìm thấy thông tin sinh viên!';
        header('Location: ../views/student/dashboard.php');
        exit;
    }
    
    $currentRoomId = intval($_POST['current_room_id'] ?? 0);
    $requestedRoomId = !empty($_POST['requested_room_id']) ? intval($_POST['requested_room_id']) : null;
    $reason = trim($_POST['reason'] ?? '');
    
    if ($currentRoomId <= 0) {
        $_SESSION['error'] = 'Vui lòng chọn phòng hiện tại!';
        header('Location: ../views/student/room_transfers/create.php');
        exit;
    }
    
    if (empty($reason)) {
        $_SESSION['error'] = 'Vui lòng nhập lý do chuyển phòng!';
        header('Location: ../views/student/room_transfers/create.php');
        exit;
    }
    
    $data = [
        'student_id' => $student['id'],
        'current_room_id' => $currentRoomId,
        'requested_room_id' => $requestedRoomId,
        'reason' => $reason
    ];
    
    $result = createRoomTransferRequest($data);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../views/student/room_transfers.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/student/room_transfers/create.php');
    }
    exit;
}

