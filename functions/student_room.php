<?php
/**
 * Student Room Functions
 * Các hàm xử lý thông tin phòng và bạn cùng phòng cho sinh viên
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/room_assignments.php';
require_once __DIR__ . '/rooms.php';

/**
 * Lấy thông tin phòng đang ở và bạn cùng phòng của sinh viên
 * @param int $studentId
 * @return array|null
 */
function getStudentRoomInfo($studentId) {
    // Lấy phòng đang ở
    $roomAssignment = getActiveRoomAssignmentByStudentId($studentId);
    
    if (!$roomAssignment) {
        return null;
    }
    
    $roomId = $roomAssignment['room_id'];
    
    // Lấy thông tin chi tiết phòng
    $room = getRoomById($roomId);
    if (!$room) {
        return null;
    }
    
    // Lấy danh sách bạn cùng phòng
    $roommates = getStudentsInRoom($roomId);
    
    // Lọc bỏ chính sinh viên đó
    $roommates = array_filter($roommates, function($mate) use ($studentId) {
        return $mate['student_id'] != $studentId;
    });
    $roommates = array_values($roommates); // Reset keys
    
    // Lấy dịch vụ của phòng
    $services = getRoomServices($roomId);
    
    return [
        'room' => $room,
        'room_assignment' => $roomAssignment,
        'roommates' => $roommates,
        'services' => $services,
        'total_occupancy' => count($roommates) + 1 // +1 cho chính sinh viên đó
    ];
}

/**
 * Lấy dịch vụ của phòng
 * @param int $roomId
 * @return array
 */
function getRoomServices($roomId) {
    $conn = getDbConnection();
    $services = [];
    
    $sql = "SELECT rs.*, 
                   s.service_code, s.service_name, s.description, s.price, s.unit
            FROM room_services rs
            INNER JOIN services s ON rs.service_id = s.id
            WHERE rs.room_id = ? 
            AND rs.status = 'active'
            AND (rs.end_date IS NULL OR rs.end_date >= CURDATE())
            ORDER BY s.service_name ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $services[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $services;
}

