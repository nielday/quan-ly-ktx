<?php
/**
 * Maintenance Requests Process Handler
 * Xử lý các action liên quan đến yêu cầu sửa chữa
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/maintenance.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Phân quyền theo role
if ($user['role'] === 'manager') {
    try {
        switch ($action) {
            case 'update_status':
                handleUpdateStatus();
                break;
                
            case 'assign':
                handleAssign();
                break;
                
            case 'update_priority':
                handleUpdatePriority();
                break;
                
            case 'cancel':
                handleCancel();
                break;
                
            default:
                setErrorMessage("Action không hợp lệ");
                redirect('../views/manager/maintenance.php');
                break;
        }
    } catch (Exception $e) {
        setErrorMessage("Lỗi: " . $e->getMessage());
        
        // Redirect về trang phù hợp
        $requestId = $_POST['request_id'] ?? $_GET['request_id'] ?? null;
        if ($requestId) {
            redirect('../views/manager/maintenance/view.php?id=' . $requestId);
        } else {
            redirect('../views/manager/maintenance.php');
        }
    }
} elseif ($user['role'] === 'student') {
    // Student có thể create
    switch ($action) {
        case 'create':
            handleCreateRequest();
            break;
        
        default:
            setErrorMessage('Action không hợp lệ!');
            redirect('../views/student/maintenance.php');
            break;
    }
} else {
    setErrorMessage('Bạn không có quyền truy cập!');
    redirect('../login.php');
}

/**
 * Cập nhật trạng thái yêu cầu
 */
function handleUpdateStatus() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method không hợp lệ");
    }
    
    $requestId = $_POST['request_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$requestId || !$status) {
        throw new Exception("Thiếu thông tin bắt buộc");
    }
    
    // Validate status
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Trạng thái không hợp lệ");
    }
    
    // Cập nhật
    $result = updateMaintenanceStatus($requestId, $status);
    
    if ($result) {
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'in_progress' => 'Đang sửa',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];
        setSuccessMessage("Đã cập nhật trạng thái thành: " . $statusLabels[$status]);
    } else {
        throw new Exception("Không thể cập nhật trạng thái");
    }
    
    redirect('../views/manager/maintenance/view.php?id=' . $requestId);
}

/**
 * Phân công người sửa chữa
 */
function handleAssign() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method không hợp lệ");
    }
    
    $requestId = $_POST['request_id'] ?? null;
    $assignedTo = $_POST['assigned_to'] ?? null;
    $priority = $_POST['priority'] ?? null;
    
    if (!$requestId) {
        throw new Exception("Thiếu thông tin yêu cầu");
    }
    
    $data = [];
    
    if ($assignedTo !== null && $assignedTo !== '') {
        $data['assigned_to'] = intval($assignedTo);
    }
    
    if ($priority) {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities)) {
            throw new Exception("Mức độ ưu tiên không hợp lệ");
        }
        $data['priority'] = $priority;
    }
    
    if (empty($data)) {
        throw new Exception("Không có thông tin để cập nhật");
    }
    
    $result = updateMaintenanceRequest($requestId, $data);
    
    if ($result) {
        // Tự động chuyển sang in_progress nếu có phân công
        if (isset($data['assigned_to']) && $data['assigned_to'] > 0) {
            $request = getMaintenanceRequestById($requestId);
            if ($request && $request['status'] === 'pending') {
                updateMaintenanceStatus($requestId, 'in_progress');
            }
        }
        
        setSuccessMessage("Đã cập nhật thông tin yêu cầu");
    } else {
        throw new Exception("Không thể cập nhật thông tin");
    }
    
    redirect('../views/manager/maintenance/view.php?id=' . $requestId);
}

/**
 * Cập nhật mức độ ưu tiên
 */
function handleUpdatePriority() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method không hợp lệ");
    }
    
    $requestId = $_POST['request_id'] ?? null;
    $priority = $_POST['priority'] ?? null;
    
    if (!$requestId || !$priority) {
        throw new Exception("Thiếu thông tin bắt buộc");
    }
    
    $validPriorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        throw new Exception("Mức độ ưu tiên không hợp lệ");
    }
    
    $result = updateMaintenanceRequest($requestId, ['priority' => $priority]);
    
    if ($result) {
        setSuccessMessage("Đã cập nhật mức độ ưu tiên");
    } else {
        throw new Exception("Không thể cập nhật mức độ ưu tiên");
    }
    
    redirect('../views/manager/maintenance/view.php?id=' . $requestId);
}

/**
 * Hủy yêu cầu
 */
function handleCancel() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method không hợp lệ");
    }
    
    $requestId = $_POST['request_id'] ?? null;
    
    if (!$requestId) {
        throw new Exception("Thiếu thông tin yêu cầu");
    }
    
    $result = cancelMaintenanceRequest($requestId);
    
    if ($result) {
        setSuccessMessage("Đã hủy yêu cầu sửa chữa");
    } else {
        throw new Exception("Không thể hủy yêu cầu");
    }
    
    redirect('../views/manager/maintenance/view.php?id=' . $requestId);
}

/**
 * Xử lý tạo yêu cầu sửa chữa (Student)
 */
function handleCreateRequest() {
    require_once __DIR__ . '/../functions/students.php';
    
    $student = getStudentByUserId($_SESSION['user_id']);
    if (!$student) {
        setErrorMessage('Không tìm thấy thông tin sinh viên!');
        redirect('../views/student/dashboard.php');
        return;
    }
    
    $roomId = intval($_POST['room_id'] ?? 0);
    $requestType = trim($_POST['request_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    
    if ($roomId <= 0) {
        setErrorMessage('Vui lòng chọn phòng!');
        redirect('../views/student/maintenance/create.php');
        return;
    }
    
    if (empty($requestType)) {
        setErrorMessage('Vui lòng chọn loại sửa chữa!');
        redirect('../views/student/maintenance/create.php');
        return;
    }
    
    if (empty($description)) {
        setErrorMessage('Vui lòng nhập mô tả vấn đề!');
        redirect('../views/student/maintenance/create.php');
        return;
    }
    
    $data = [
        'student_id' => $student['id'],
        'room_id' => $roomId,
        'request_type' => $requestType,
        'description' => $description,
        'priority' => $priority
    ];
    
    $result = createMaintenanceRequest($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        redirect('../views/student/maintenance.php');
    } else {
        setErrorMessage($result['message']);
        redirect('../views/student/maintenance/create.php');
    }
}

