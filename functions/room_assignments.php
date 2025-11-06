<?php
/**
 * Room_Assignments functions - Các hàm xử lý phân phòng
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/rooms.php';

/**
 * Tạo phân phòng mới
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createRoomAssignment($data) {
    $conn = getDbConnection();
    
    $contractId = intval($data['contract_id'] ?? 0);
    $studentId = intval($data['student_id'] ?? 0);
    $roomId = intval($data['room_id'] ?? 0);
    $assignedDate = trim($data['assigned_date'] ?? date('Y-m-d'));
    
    // Validation
    if ($contractId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Contract ID không hợp lệ!'];
    }
    
    if ($studentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Student ID không hợp lệ!'];
    }
    
    if ($roomId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Room ID không hợp lệ!'];
    }
    
    if (empty($assignedDate)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Ngày phân phòng không được để trống!'];
    }
    
    // Kiểm tra phòng còn chỗ không
    $room = getRoomById($roomId);
    if (!$room) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng không tồn tại!'];
    }
    
    if ($room['current_occupancy'] >= $room['capacity']) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Phòng đã đầy!'];
    }
    
    // Kiểm tra sinh viên đã có phòng active chưa
    $existingAssignment = getActiveRoomAssignmentByStudentId($studentId);
    if ($existingAssignment) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên đã có phòng đang active!'];
    }
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert room_assignment
        $sql = "INSERT INTO room_assignments (contract_id, student_id, room_id, assigned_date, status) 
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL!');
        }
        
        mysqli_stmt_bind_param($stmt, "iiis", $contractId, $studentId, $roomId, $assignedDate);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi tạo phân phòng: ' . mysqli_error($conn));
        }
        
        $assignmentId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Cập nhật current_occupancy của phòng
        $updateSql = "UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        
        if (!$updateStmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL cập nhật phòng!');
        }
        
        mysqli_stmt_bind_param($updateStmt, "i", $roomId);
        
        if (!mysqli_stmt_execute($updateStmt)) {
            throw new Exception('Lỗi cập nhật phòng: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($updateStmt);
        
        // Cập nhật status phòng nếu cần
        $newOccupancy = $room['current_occupancy'] + 1;
        if ($newOccupancy >= $room['capacity']) {
            $statusSql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
            $statusStmt = mysqli_prepare($conn, $statusSql);
            if ($statusStmt) {
                mysqli_stmt_bind_param($statusStmt, "i", $roomId);
                mysqli_stmt_execute($statusStmt);
                mysqli_stmt_close($statusStmt);
            }
        } else {
            $statusSql = "UPDATE rooms SET status = 'available' WHERE id = ?";
            $statusStmt = mysqli_prepare($conn, $statusSql);
            if ($statusStmt) {
                mysqli_stmt_bind_param($statusStmt, "i", $roomId);
                mysqli_stmt_execute($statusStmt);
                mysqli_stmt_close($statusStmt);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['success' => true, 'message' => 'Phân phòng thành công!', 'id' => $assignmentId];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Kết thúc phân phòng (sinh viên chuyển đi)
 * @param int $studentId
 * @param int $roomId
 * @param string $endDate
 * @return array ['success' => bool, 'message' => string]
 */
function endRoomAssignment($studentId, $roomId, $endDate) {
    $conn = getDbConnection();
    
    // Tìm room_assignment active
    $sql = "SELECT id FROM room_assignments 
            WHERE student_id = ? 
            AND room_id = ? 
            AND status = 'active' 
            AND end_date IS NULL
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $assignmentId = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $assignmentId = $row['id'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!$assignmentId) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không tìm thấy phân phòng active!'];
    }
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update room_assignment
        $updateSql = "UPDATE room_assignments 
                     SET end_date = ?, status = 'moved_out' 
                     WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        
        if (!$updateStmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL!');
        }
        
        mysqli_stmt_bind_param($updateStmt, "si", $endDate, $assignmentId);
        
        if (!mysqli_stmt_execute($updateStmt)) {
            throw new Exception('Lỗi cập nhật phân phòng: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($updateStmt);
        
        // Giảm current_occupancy của phòng
        $roomSql = "UPDATE rooms SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?";
        $roomStmt = mysqli_prepare($conn, $roomSql);
        
        if (!$roomStmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL cập nhật phòng!');
        }
        
        mysqli_stmt_bind_param($roomStmt, "i", $roomId);
        
        if (!mysqli_stmt_execute($roomStmt)) {
            throw new Exception('Lỗi cập nhật phòng: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($roomStmt);
        
        // Cập nhật status phòng
        $room = getRoomById($roomId);
        if ($room && $room['current_occupancy'] > 0) {
            $newOccupancy = $room['current_occupancy'] - 1;
            if ($newOccupancy == 0) {
                $statusSql = "UPDATE rooms SET status = 'available' WHERE id = ?";
            } else {
                $statusSql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
            }
            $statusStmt = mysqli_prepare($conn, $statusSql);
            if ($statusStmt) {
                mysqli_stmt_bind_param($statusStmt, "i", $roomId);
                mysqli_stmt_execute($statusStmt);
                mysqli_stmt_close($statusStmt);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['success' => true, 'message' => 'Cập nhật phân phòng thành công!'];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy phân phòng active của sinh viên
 * @param int $studentId
 * @return array|null
 */
function getActiveRoomAssignmentByStudentId($studentId) {
    $conn = getDbConnection();
    $assignment = null;
    
    $sql = "SELECT ra.*, 
                   r.room_code, r.room_number, r.room_type,
                   b.building_code, b.building_name
            FROM room_assignments ra
            INNER JOIN rooms r ON ra.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE ra.student_id = ? 
            AND ra.status = 'active' 
            AND ra.end_date IS NULL
            ORDER BY ra.assigned_date DESC
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $assignment = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $assignment;
}

/**
 * Lấy danh sách sinh viên trong phòng
 * @param int $roomId
 * @return array
 */
function getStudentsInRoom($roomId) {
    $conn = getDbConnection();
    $students = [];
    
    $sql = "SELECT ra.*, 
                   s.student_code, s.full_name, s.phone, s.email,
                   c.contract_code
            FROM room_assignments ra
            INNER JOIN students s ON ra.student_id = s.id
            LEFT JOIN contracts c ON ra.contract_id = c.id
            WHERE ra.room_id = ? 
            AND ra.status = 'active' 
            AND ra.end_date IS NULL
            ORDER BY ra.assigned_date ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $students[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $students;
}

/**
 * Lấy phân phòng theo ID
 * @param int $id
 * @return array|null
 */
function getRoomAssignmentById($id) {
    $conn = getDbConnection();
    $assignment = null;
    
    $sql = "SELECT ra.*, 
                   s.student_code, s.full_name,
                   r.room_code, r.room_number,
                   b.building_code, b.building_name,
                   c.contract_code
            FROM room_assignments ra
            INNER JOIN students s ON ra.student_id = s.id
            INNER JOIN rooms r ON ra.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN contracts c ON ra.contract_id = c.id
            WHERE ra.id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $assignment = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $assignment;
}

?>

