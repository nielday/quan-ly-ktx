<?php
/**
 * Payments functions - Các hàm xử lý thanh toán
 */

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

/**
 * Tạo mã thanh toán tự động
 * @param string $type Loại thanh toán (deposit, invoice_payment, refund)
 * @return string
 */
function generatePaymentCode($type = 'invoice_payment') {
    $conn = getDbConnection();
    $prefix = 'TT';
    
    switch ($type) {
        case 'deposit':
            $prefix = 'DC'; // Đặt cọc
            break;
        case 'refund':
            $prefix = 'HT'; // Hoàn tiền
            break;
        default:
            $prefix = 'TT'; // Thanh toán
    }
    
    $year = date('Y');
    $month = date('m');
    $prefix = $prefix . $year . $month;
    
    $sql = "SELECT COUNT(*) as count FROM payments WHERE payment_code LIKE ?";
    $likePattern = $prefix . '%';
    $stmt = mysqli_prepare($conn, $sql);
    $count = 0;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $likePattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $count = $row['count'];
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    $count++;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Tạo payment cho tiền đặt cọc
 * @param int $contractId ID hợp đồng
 * @param int $studentId ID sinh viên
 * @param float $amount Số tiền đặt cọc
 * @param int $confirmedBy ID Manager xác nhận (null = tự động confirmed)
 * @param string|null $paymentDate Ngày thanh toán (null = hôm nay)
 * @param string $paymentMethod Phương thức thanh toán (cash, bank_transfer)
 * @param string|null $notes Ghi chú
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createDepositPayment($contractId, $studentId, $amount, $confirmedBy = null, $paymentDate = null, $paymentMethod = 'cash', $notes = null) {
    $conn = getDbConnection();
    
    // Validation
    if ($contractId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Contract ID không hợp lệ!'];
    }
    
    if ($studentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Student ID không hợp lệ!'];
    }
    
    if ($amount <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số tiền đặt cọc phải lớn hơn 0!'];
    }
    
    // Kiểm tra hợp đồng đã có payment đặt cọc chưa
    $sqlCheck = "SELECT id FROM payments WHERE contract_id = ? AND payment_type = 'deposit' LIMIT 1";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    $hasDeposit = false;
    
    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, "i", $contractId);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);
        $hasDeposit = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        mysqli_stmt_close($stmtCheck);
    }
    
    if ($hasDeposit) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hợp đồng này đã có payment đặt cọc!'];
    }
    
    // Kiểm tra xem database đã có cột contract_id và payment_type chưa
    $checkColumn = "SHOW COLUMNS FROM payments LIKE 'contract_id'";
    $resultCheck = mysqli_query($conn, $checkColumn);
    $hasContractId = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if (!$hasContractId) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Database chưa được migration! Vui lòng chạy file migration_add_deposit_support.sql trước.'];
    }
    
    // Tạo mã thanh toán
    $paymentCode = generatePaymentCode('deposit');
    
    // Ngày thanh toán
    if (!$paymentDate) {
        $paymentDate = date('Y-m-d');
    }
    
    // Status: nếu có confirmedBy thì confirmed, không thì pending
    $status = $confirmedBy ? 'confirmed' : 'pending';
    
    // confirmed_at: nếu đã confirmed thì là NOW(), không thì NULL
    $confirmedAt = ($status === 'confirmed' && $confirmedBy) ? date('Y-m-d H:i:s') : null;
    
    // Validate payment_method
    if (!in_array($paymentMethod, ['cash', 'bank_transfer'])) {
        $paymentMethod = 'cash'; // Default
    }
    
    // Escape các giá trị string
    $paymentCodeEscaped = mysqli_real_escape_string($conn, $paymentCode);
    $paymentDateEscaped = mysqli_real_escape_string($conn, $paymentDate);
    $paymentMethodEscaped = mysqli_real_escape_string($conn, $paymentMethod);
    $statusEscaped = mysqli_real_escape_string($conn, $status);
    $notesEscaped = mysqli_real_escape_string($conn, $notes ? $notes : '');
    
    // Xử lý confirmed_by và confirmed_at (có thể NULL)
    $confirmedBySql = $confirmedBy ? intval($confirmedBy) : 'NULL';
    $confirmedAtSql = $confirmedAt ? "'" . mysqli_real_escape_string($conn, $confirmedAt) . "'" : 'NULL';
    
    // Insert payment - sử dụng SQL trực tiếp để xử lý NULL dễ dàng hơn
    $sql = "INSERT INTO payments (
                invoice_id, contract_id, payment_type, student_id, payment_code, amount, payment_date, 
                payment_method, status, confirmed_by, notes, confirmed_at
            ) VALUES (
                NULL, 
                " . intval($contractId) . ", 
                'deposit', 
                " . intval($studentId) . ", 
                '" . $paymentCodeEscaped . "', 
                " . floatval($amount) . ", 
                '" . $paymentDateEscaped . "', 
                '" . $paymentMethodEscaped . "', 
                '" . $statusEscaped . "', 
                " . $confirmedBySql . ", 
                '" . $notesEscaped . "', 
                " . $confirmedAtSql . "
            )";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Lỗi tạo payment: ' . $error];
    }
    
    $paymentId = mysqli_insert_id($conn);
    mysqli_close($conn);
    return ['success' => true, 'message' => 'Tạo payment đặt cọc thành công!', 'id' => $paymentId];
}

/**
 * Lấy payment đặt cọc của hợp đồng
 * @param int $contractId
 * @return array|null
 */
function getDepositPaymentByContractId($contractId) {
    $conn = getDbConnection();
    $payment = null;
    
    $sql = "SELECT p.*, 
                   s.student_code, s.full_name as student_name,
                   c.contract_code,
                   u.full_name as confirmed_by_name
            FROM payments p
            INNER JOIN students s ON p.student_id = s.id
            LEFT JOIN contracts c ON p.contract_id = c.id
            LEFT JOIN users u ON p.confirmed_by = u.id
            WHERE p.contract_id = ? 
            AND p.payment_type = 'deposit'
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $contractId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $payment = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $payment;
}

/**
 * Lấy danh sách payments của sinh viên
 * @param int $studentId
 * @param string|null $paymentType Lọc theo loại (invoice_payment, deposit, refund)
 * @return array
 */
function getPaymentsByStudentId($studentId, $paymentType = null) {
    $conn = getDbConnection();
    $payments = [];
    
    $sql = "SELECT p.*, 
                   i.invoice_code, i.invoice_month,
                   c.contract_code,
                   u.full_name as confirmed_by_name
            FROM payments p
            LEFT JOIN invoices i ON p.invoice_id = i.id
            LEFT JOIN contracts c ON p.contract_id = c.id
            LEFT JOIN users u ON p.confirmed_by = u.id
            WHERE p.student_id = ?";
    
    if ($paymentType) {
        $sql .= " AND p.payment_type = ?";
    }
    
    $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if ($paymentType) {
            mysqli_stmt_bind_param($stmt, "is", $studentId, $paymentType);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $studentId);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $payments[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $payments;
}

/**
 * Lấy các loại payment
 * @return array
 */
function getPaymentTypes() {
    return [
        'invoice_payment' => 'Thanh toán hóa đơn',
        'deposit' => 'Tiền đặt cọc',
        'refund' => 'Hoàn tiền'
    ];
}

?>
