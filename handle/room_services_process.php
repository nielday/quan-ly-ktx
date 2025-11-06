<?php
/**
 * Xử lý các action của Room_Services
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/room_services.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager', '../index.php');

$action = getAction();

switch ($action) {
    case 'assign':
        handleAssignService();
        break;
    
    case 'remove':
        handleRemoveService();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        redirect('../views/manager/room_services.php');
        break;
}

/**
 * Xử lý gán dịch vụ cho phòng (có thể gán nhiều dịch vụ cùng lúc)
 */
function handleAssignService() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/room_services.php');
    }
    
    $roomId = intval($_POST['room_id'] ?? 0);
    $serviceIds = $_POST['service_ids'] ?? [];
    $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
    $endDate = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
    
    if ($roomId <= 0) {
        setErrorMessage('Vui lòng chọn phòng!');
        redirect('../views/manager/room_services.php');
    }
    
    if (empty($serviceIds) || !is_array($serviceIds)) {
        setErrorMessage('Vui lòng chọn ít nhất một dịch vụ!');
        redirect('../views/manager/room_services.php');
    }
    
    $successCount = 0;
    $errorMessages = [];
    $successServiceIds = [];
    
    // Loại bỏ các dịch vụ đã được gán trước đó
    require_once __DIR__ . '/../functions/room_services.php';
    $existingRoomServices = getRoomServices($roomId);
    $existingServiceIds = array_column($existingRoomServices, 'service_id');
    $serviceIds = array_filter($serviceIds, function($id) use ($existingServiceIds) {
        return !in_array(intval($id), $existingServiceIds);
    });
    
    if (empty($serviceIds)) {
        setErrorMessage('Tất cả các dịch vụ đã được gán cho phòng này!');
        redirect('../views/manager/room_services.php');
    }
    
    // Gán từng dịch vụ
    foreach ($serviceIds as $serviceId) {
        $serviceId = intval($serviceId);
        if ($serviceId <= 0) continue;
        
        // Kiểm tra lại trước khi gán (tránh duplicate trong cùng request)
        if (in_array($serviceId, $successServiceIds)) {
            continue; // Đã gán thành công trong request này
        }
        
        $data = [
            'room_id' => $roomId,
            'service_id' => $serviceId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        $result = assignServiceToRoom($data);
        
        if ($result['success']) {
            $successCount++;
            $successServiceIds[] = $serviceId;
        } else {
            $errorMessages[] = $result['message'];
        }
    }
    
    // Thông báo kết quả
    if ($successCount > 0) {
        if ($successCount == count($serviceIds)) {
            setSuccessMessage("Đã gán thành công {$successCount} dịch vụ!");
        } else {
            setSuccessMessage("Đã gán thành công {$successCount}/" . count($serviceIds) . " dịch vụ!");
            if (!empty($errorMessages)) {
                setErrorMessage(implode('; ', array_unique($errorMessages)));
            }
        }
    } else {
        if (!empty($errorMessages)) {
            setErrorMessage(implode('; ', array_unique($errorMessages)));
        } else {
            setErrorMessage('Không thể gán dịch vụ!');
        }
    }
    
    redirect('../views/manager/room_services.php');
}

/**
 * Xử lý hủy dịch vụ của phòng
 */
function handleRemoveService() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/manager/room_services.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setErrorMessage('ID không hợp lệ!');
        redirect('../views/manager/room_services.php');
    }
    
    $result = removeServiceFromRoom($id);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    redirect('../views/manager/room_services.php');
}

?>

