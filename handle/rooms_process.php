<?php
/**
 * Xử lý các action của Rooms
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/rooms.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../login.php');

$action = getAction();

switch ($action) {
    case 'create':
        handleCreateRoom();
        break;
    
    case 'update':
        handleUpdateRoom();
        break;
    
    case 'delete':
        handleDeleteRoom();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/rooms.php');
        break;
}

/**
 * Xử lý tạo phòng mới
 */
function handleCreateRoom() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/rooms.php');
    }
    
    $data = [
        'building_id' => $_POST['building_id'] ?? 0,
        'room_code' => $_POST['room_code'] ?? '',
        'room_number' => $_POST['room_number'] ?? '',
        'floor' => $_POST['floor'] ?? 1,
        'capacity' => $_POST['capacity'] ?? 4,
        'room_type' => $_POST['room_type'] ?? '',
        'amenities' => $_POST['amenities'] ?? '',
        'status' => $_POST['status'] ?? 'available'
    ];
    
    $result = createRoom($data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/rooms.php');
}

/**
 * Xử lý cập nhật phòng
 */
function handleUpdateRoom() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/rooms.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/rooms.php');
    }
    
    $data = [
        'building_id' => $_POST['building_id'] ?? 0,
        'room_code' => $_POST['room_code'] ?? '',
        'room_number' => $_POST['room_number'] ?? '',
        'floor' => $_POST['floor'] ?? 1,
        'capacity' => $_POST['capacity'] ?? 4,
        'room_type' => $_POST['room_type'] ?? '',
        'amenities' => $_POST['amenities'] ?? '',
        'status' => $_POST['status'] ?? 'available'
    ];
    
    $result = updateRoom($id, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/rooms.php');
}

/**
 * Xử lý xóa phòng
 */
function handleDeleteRoom() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/rooms.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/rooms.php');
    }
    
    $result = deleteRoom($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/rooms.php');
}

?>

