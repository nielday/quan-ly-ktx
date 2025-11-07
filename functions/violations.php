<?php
/**
 * Violations functions - Các hàm xử lý vi phạm
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

/**
 * Lấy danh sách tất cả vi phạm với thông tin sinh viên, phòng
 * @param array $filters Mảng điều kiện lọc (student_id, room_id, status, etc.)
 * @param int $limit Số lượng bản ghi
 * @param int $offset Vị trí bắt đầu
 * @return array
 */
function getAllViolations($filters = [], $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    $sql = "SELECT v.*, 
                   s.student_code, s.full_name as student_name, s.phone as student_phone,
                   r.room_code, r.room_number, b.building_code,
                   u.full_name as reported_by_name
            FROM violations v
            INNER JOIN students s ON v.student_id = s.id
            INNER JOIN rooms r ON v.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN users u ON v.reported_by = u.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Áp dụng filters
    if (!empty($filters['student_id'])) {
        $sql .= " AND v.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['room_id'])) {
        $sql .= " AND v.room_id = ?";
        $params[] = $filters['room_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND v.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['violation_type'])) {
        $sql .= " AND v.violation_type = ?";
        $params[] = $filters['violation_type'];
        $types .= 's';
    }
    
    if (!empty($filters['month'])) {
        $sql .= " AND DATE_FORMAT(v.violation_date, '%Y-%m') = ?";
        $params[] = $filters['month'];
        $types .= 's';
    }
    
    // Sắp xếp
    $sql .= " ORDER BY v.violation_date DESC, v.created_at DESC";
    
    // Phân trang
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $violations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $violations[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $violations;
}

/**
 * Đếm tổng số vi phạm theo filters
 * @param array $filters
 * @return int
 */
function countViolations($filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM violations v WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($filters['student_id'])) {
        $sql .= " AND v.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['room_id'])) {
        $sql .= " AND v.room_id = ?";
        $params[] = $filters['room_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND v.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['violation_type'])) {
        $sql .= " AND v.violation_type = ?";
        $params[] = $filters['violation_type'];
        $types .= 's';
    }
    
    if (!empty($filters['month'])) {
        $sql .= " AND DATE_FORMAT(v.violation_date, '%Y-%m') = ?";
        $params[] = $filters['month'];
        $types .= 's';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total = $row['total'];
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $total;
}

/**
 * Lấy chi tiết một vi phạm theo ID
 * @param int $id
 * @return array|null
 */
function getViolationById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT v.*, 
                   s.student_code, s.full_name as student_name, s.phone as student_phone, s.email as student_email,
                   r.room_code, r.room_number, r.floor, b.building_code, b.building_name,
                   u.full_name as reported_by_name, u.username as reported_by_username
            FROM violations v
            INNER JOIN students s ON v.student_id = s.id
            INNER JOIN rooms r ON v.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN users u ON v.reported_by = u.id
            WHERE v.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $violation = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $violation = mysqli_fetch_assoc($result);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $violation;
}

/**
 * Tạo vi phạm mới
 * @param array $data Dữ liệu vi phạm
 * @return array ['success' => bool, 'message' => string, 'violation_id' => int]
 */
function createViolation($data) {
    $conn = getDbConnection();
    
    // Validate dữ liệu
    $required = ['student_id', 'room_id', 'violation_type', 'violation_date', 'reported_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Thiếu trường bắt buộc: $field"];
        }
    }
    
    // Kiểm tra sinh viên có tồn tại
    $sqlCheck = "SELECT id FROM students WHERE id = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "i", $data['student_id']);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        mysqli_stmt_close($stmtCheck);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Sinh viên không tồn tại!'];
    }
    mysqli_stmt_close($stmtCheck);
    
    // Chuẩn bị dữ liệu
    $description = $data['description'] ?? '';
    $penalty_amount = isset($data['penalty_amount']) ? floatval($data['penalty_amount']) : 0;
    $penalty_type = $data['penalty_type'] ?? 'warning';
    $status = $data['status'] ?? 'pending';
    $evidence = $data['evidence'] ?? '';
    
    // Insert vào database
    $sql = "INSERT INTO violations 
            (student_id, room_id, violation_type, description, violation_date, 
             reported_by, penalty_amount, penalty_type, status, evidence)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisssidsss",
        $data['student_id'],
        $data['room_id'],
        $data['violation_type'],
        $description,
        $data['violation_date'],
        $data['reported_by'],
        $penalty_amount,
        $penalty_type,
        $status,
        $evidence
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $violation_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return [
            'success' => true, 
            'message' => 'Ghi nhận vi phạm thành công!',
            'violation_id' => $violation_id
        ];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

/**
 * Cập nhật vi phạm
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateViolation($id, $data) {
    $conn = getDbConnection();
    
    // Kiểm tra vi phạm có tồn tại
    $sqlCheck = "SELECT id FROM violations WHERE id = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "i", $id);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        mysqli_stmt_close($stmtCheck);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vi phạm không tồn tại!'];
    }
    mysqli_stmt_close($stmtCheck);
    
    // Chuẩn bị dữ liệu
    $fields = [];
    $params = [];
    $types = '';
    
    if (isset($data['violation_type'])) {
        $fields[] = "violation_type = ?";
        $params[] = $data['violation_type'];
        $types .= 's';
    }
    
    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $params[] = $data['description'];
        $types .= 's';
    }
    
    if (isset($data['violation_date'])) {
        $fields[] = "violation_date = ?";
        $params[] = $data['violation_date'];
        $types .= 's';
    }
    
    if (isset($data['penalty_amount'])) {
        $fields[] = "penalty_amount = ?";
        $params[] = floatval($data['penalty_amount']);
        $types .= 'd';
    }
    
    if (isset($data['penalty_type'])) {
        $fields[] = "penalty_type = ?";
        $params[] = $data['penalty_type'];
        $types .= 's';
    }
    
    if (isset($data['status'])) {
        $fields[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
        
        // Nếu status = resolved, cập nhật resolved_at
        if ($data['status'] == 'resolved') {
            $fields[] = "resolved_at = NOW()";
        }
    }
    
    if (isset($data['evidence'])) {
        $fields[] = "evidence = ?";
        $params[] = $data['evidence'];
        $types .= 's';
    }
    
    if (empty($fields)) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật!'];
    }
    
    // Thêm ID vào params
    $params[] = $id;
    $types .= 'i';
    
    $sql = "UPDATE violations SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Cập nhật vi phạm thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

/**
 * Xóa vi phạm
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function deleteViolation($id) {
    $conn = getDbConnection();
    
    // Kiểm tra vi phạm có tồn tại
    $sqlCheck = "SELECT id FROM violations WHERE id = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "i", $id);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        mysqli_stmt_close($stmtCheck);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Vi phạm không tồn tại!'];
    }
    mysqli_stmt_close($stmtCheck);
    
    // Xóa vi phạm
    $sql = "DELETE FROM violations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xóa vi phạm thành công!'];
    } else {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi: ' . $error];
    }
}

/**
 * Lấy tổng phí vi phạm của sinh viên trong một tháng
 * Dùng khi tạo hóa đơn
 * @param int $studentId
 * @param string $month Format: YYYY-MM
 * @return float
 */
function getStudentViolationFeeForMonth($studentId, $month) {
    $conn = getDbConnection();
    
    $sql = "SELECT COALESCE(SUM(penalty_amount), 0) as total_fee
            FROM violations
            WHERE student_id = ? 
            AND DATE_FORMAT(violation_date, '%Y-%m') = ?
            AND penalty_type = 'fine'
            AND status = 'pending'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $studentId, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $total_fee = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $total_fee = floatval($row['total_fee']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $total_fee;
}

/**
 * Lấy danh sách loại vi phạm
 * @return array
 */
function getViolationTypes() {
    return [
        'noise' => 'Gây ồn',
        'alcohol' => 'Sử dụng rượu bia',
        'late_night' => 'Về muộn',
        'damage' => 'Làm hỏng tài sản',
        'smoking' => 'Hút thuốc',
        'unauthorized_guest' => 'Đưa người lạ vào phòng',
        'hygiene' => 'Vệ sinh kém',
        'other' => 'Vi phạm khác'
    ];
}

/**
 * Lấy danh sách loại hình phạt
 * @return array
 */
function getPenaltyTypes() {
    return [
        'warning' => 'Cảnh cáo',
        'fine' => 'Phạt tiền',
        'suspension' => 'Đình chỉ'
    ];
}

/**
 * Lấy danh sách trạng thái vi phạm
 * @return array
 */
function getViolationStatuses() {
    return [
        'pending' => 'Chưa xử lý',
        'resolved' => 'Đã xử lý'
    ];
}

/**
 * Lấy thống kê vi phạm
 * @return array
 */
function getViolationStatistics() {
    $conn = getDbConnection();
    
    $stats = [
        'total' => 0,
        'pending' => 0,
        'resolved' => 0,
        'total_fine_amount' => 0,
        'by_type' => []
    ];
    
    // Tổng số vi phạm
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN penalty_type = 'fine' THEN penalty_amount ELSE 0 END) as total_fine
            FROM violations";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['total'] = intval($row['total']);
        $stats['pending'] = intval($row['pending']);
        $stats['resolved'] = intval($row['resolved']);
        $stats['total_fine_amount'] = floatval($row['total_fine']);
    }
    
    // Thống kê theo loại vi phạm
    $sql2 = "SELECT violation_type, COUNT(*) as count
             FROM violations
             GROUP BY violation_type
             ORDER BY count DESC";
    
    $result2 = mysqli_query($conn, $sql2);
    if ($result2) {
        while ($row = mysqli_fetch_assoc($result2)) {
            $stats['by_type'][$row['violation_type']] = intval($row['count']);
        }
    }
    
    mysqli_close($conn);
    
    return $stats;
}

