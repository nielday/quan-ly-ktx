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
 * Lấy tất cả payments
 * @param string|null $status Lọc theo status (pending, confirmed)
 * @param string|null $paymentType Lọc theo loại (invoice_payment, deposit, refund)
 * @param int|null $studentId Lọc theo sinh viên
 * @return array
 */
function getAllPayments($status = null, $paymentType = null, $studentId = null) {
    $conn = getDbConnection();
    $payments = [];
    
    $sql = "SELECT p.*, 
                   s.student_code, s.full_name as student_name,
                   i.invoice_code, i.invoice_month, i.total_amount as invoice_total,
                   c.contract_code,
                   u.full_name as confirmed_by_name
            FROM payments p
            INNER JOIN students s ON p.student_id = s.id
            LEFT JOIN invoices i ON p.invoice_id = i.id
            LEFT JOIN contracts c ON p.contract_id = c.id
            LEFT JOIN users u ON p.confirmed_by = u.id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($status) {
        $conditions[] = "p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($paymentType) {
        $conditions[] = "p.payment_type = ?";
        $params[] = $paymentType;
        $types .= "s";
    }
    
    if ($studentId) {
        $conditions[] = "p.student_id = ?";
        $params[] = $studentId;
        $types .= "i";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $payments[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $payments[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $payments;
}

/**
 * Lấy payment theo ID
 * @param int $id
 * @return array|null
 */
function getPaymentById($id) {
    $conn = getDbConnection();
    $payment = null;
    
    $sql = "SELECT p.*, 
                   s.student_code, s.full_name as student_name, s.phone, s.email,
                   i.invoice_code, i.invoice_month, i.total_amount as invoice_total,
                   c.contract_code,
                   u.full_name as confirmed_by_name
            FROM payments p
            INNER JOIN students s ON p.student_id = s.id
            LEFT JOIN invoices i ON p.invoice_id = i.id
            LEFT JOIN contracts c ON p.contract_id = c.id
            LEFT JOIN users u ON p.confirmed_by = u.id
            WHERE p.id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
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
 * Tạo payment cho hóa đơn (sinh viên nộp tiền)
 * @param int $invoiceId ID hóa đơn
 * @param int $studentId ID sinh viên
 * @param float $amount Số tiền nộp
 * @param string $paymentDate Ngày thanh toán
 * @param string $paymentMethod Phương thức thanh toán (cash, bank_transfer)
 * @param string|null $transactionCode Mã giao dịch (tùy chọn)
 * @param string|null $notes Ghi chú
 * @param bool $autoConfirm Tự động xác nhận (true = tự động, false = chờ manager xác nhận)
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createInvoicePayment($invoiceId, $studentId, $amount, $paymentDate, $paymentMethod = 'cash', $transactionCode = null, $notes = null, $autoConfirm = true) {
    $conn = getDbConnection();
    
    // Validation
    if ($invoiceId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Invoice ID không hợp lệ!'];
    }
    
    if ($studentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Student ID không hợp lệ!'];
    }
    
    if ($amount <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Số tiền phải lớn hơn 0!'];
    }
    
    // Kiểm tra hóa đơn có tồn tại không
    require_once __DIR__ . '/invoices.php';
    $invoice = getInvoiceById($invoiceId);
    if (!$invoice) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hóa đơn không tồn tại!'];
    }
    
    // Kiểm tra hóa đơn có thuộc về sinh viên này không
    if ($invoice['student_id'] != $studentId) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hóa đơn không thuộc về sinh viên này!'];
    }
    
    // Kiểm tra hóa đơn đã được thanh toán chưa
    if ($invoice['status'] === 'paid') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Hóa đơn này đã được thanh toán rồi!'];
    }
    
    // Validate payment_method
    if (!in_array($paymentMethod, ['cash', 'bank_transfer'])) {
        $paymentMethod = 'cash';
    }
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Tạo mã thanh toán
        $paymentCode = generatePaymentCode('invoice_payment');
        
        // Escape các giá trị
        $paymentCodeEscaped = mysqli_real_escape_string($conn, $paymentCode);
        $paymentDateEscaped = mysqli_real_escape_string($conn, $paymentDate);
        $paymentMethodEscaped = mysqli_real_escape_string($conn, $paymentMethod);
        $transactionCodeEscaped = $transactionCode ? mysqli_real_escape_string($conn, $transactionCode) : 'NULL';
        $notesEscaped = mysqli_real_escape_string($conn, $notes ? $notes : '');
        
        // Nếu autoConfirm = true, tự động xác nhận (status = 'confirmed', confirmed_at = NOW())
        // Nếu autoConfirm = false, chờ manager xác nhận (status = 'pending')
        $status = $autoConfirm ? 'confirmed' : 'pending';
        $confirmedAt = $autoConfirm ? date('Y-m-d H:i:s') : null;
        $confirmedAtSql = $confirmedAt ? "'" . mysqli_real_escape_string($conn, $confirmedAt) . "'" : 'NULL';
        
        // Insert payment
        $sql = "INSERT INTO payments (
                    invoice_id, contract_id, payment_type, student_id, payment_code, amount, payment_date, 
                    payment_method, transaction_code, status, confirmed_by, notes, confirmed_at
                ) VALUES (
                    " . intval($invoiceId) . ", 
                    NULL, 
                    'invoice_payment', 
                    " . intval($studentId) . ", 
                    '" . $paymentCodeEscaped . "', 
                    " . floatval($amount) . ", 
                    '" . $paymentDateEscaped . "', 
                    '" . $paymentMethodEscaped . "', 
                    " . ($transactionCodeEscaped !== 'NULL' ? "'" . $transactionCodeEscaped . "'" : 'NULL') . ",
                    '" . $status . "', 
                    NULL, 
                    '" . $notesEscaped . "', 
                    " . $confirmedAtSql . "
                )";
        
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            throw new Exception('Lỗi tạo payment: ' . mysqli_error($conn));
        }
        
        $paymentId = mysqli_insert_id($conn);
        
        // Nếu autoConfirm = true, tự động cập nhật trạng thái hóa đơn
        if ($autoConfirm) {
            // Tính tổng số tiền đã thanh toán (bao gồm payment vừa tạo)
            $sqlCheckTotal = "SELECT SUM(amount) as total_paid 
                             FROM payments 
                             WHERE invoice_id = ? 
                             AND status = 'confirmed' 
                             AND payment_type = 'invoice_payment'";
            $stmtCheckTotal = mysqli_prepare($conn, $sqlCheckTotal);
            
            if ($stmtCheckTotal) {
                mysqli_stmt_bind_param($stmtCheckTotal, "i", $invoiceId);
                mysqli_stmt_execute($stmtCheckTotal);
                $resultCheckTotal = mysqli_stmt_get_result($stmtCheckTotal);
                
                $totalPaid = 0;
                if ($resultCheckTotal && mysqli_num_rows($resultCheckTotal) > 0) {
                    $rowTotal = mysqli_fetch_assoc($resultCheckTotal);
                    $totalPaid = floatval($rowTotal['total_paid'] ?? 0);
                }
                
                mysqli_stmt_close($stmtCheckTotal);
                
                $invoiceTotal = floatval($invoice['total_amount']);
                
                // Nếu đã thanh toán đủ hoặc vượt quá, cập nhật status hóa đơn thành 'paid'
                if ($totalPaid >= $invoiceTotal) {
                    $sqlUpdateInvoice = "UPDATE invoices 
                                        SET status = 'paid', 
                                            paid_at = NOW() 
                                        WHERE id = ?";
                    $stmtUpdateInvoice = mysqli_prepare($conn, $sqlUpdateInvoice);
                    
                    if ($stmtUpdateInvoice) {
                        mysqli_stmt_bind_param($stmtUpdateInvoice, "i", $invoiceId);
                        if (!mysqli_stmt_execute($stmtUpdateInvoice)) {
                            throw new Exception('Lỗi cập nhật hóa đơn: ' . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmtUpdateInvoice);
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        
        if ($autoConfirm) {
            return ['success' => true, 'message' => 'Thanh toán thành công! Hóa đơn đã được cập nhật.', 'id' => $paymentId];
        } else {
            return ['success' => true, 'message' => 'Tạo payment thành công! Vui lòng chờ Manager xác nhận.', 'id' => $paymentId];
        }
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Xác nhận thanh toán
 * @param int $paymentId ID payment
 * @param int $confirmedBy ID Manager xác nhận
 * @return array ['success' => bool, 'message' => string]
 */
function confirmPayment($paymentId, $confirmedBy) {
    $conn = getDbConnection();
    
    // Validation
    if ($paymentId <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Payment ID không hợp lệ!'];
    }
    
    if ($confirmedBy <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Confirmed By ID không hợp lệ!'];
    }
    
    // Lấy thông tin payment
    $payment = getPaymentById($paymentId);
    if (!$payment) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Payment không tồn tại!'];
    }
    
    // Kiểm tra payment đã được xác nhận chưa
    if ($payment['status'] === 'confirmed') {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Payment này đã được xác nhận rồi!'];
    }
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Cập nhật status payment thành 'confirmed'
        $sqlUpdatePayment = "UPDATE payments 
                            SET status = 'confirmed', 
                                confirmed_by = ?, 
                                confirmed_at = NOW() 
                            WHERE id = ?";
        $stmtUpdatePayment = mysqli_prepare($conn, $sqlUpdatePayment);
        
        if (!$stmtUpdatePayment) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmtUpdatePayment, "ii", $confirmedBy, $paymentId);
        
        if (!mysqli_stmt_execute($stmtUpdatePayment)) {
            throw new Exception('Lỗi cập nhật payment: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmtUpdatePayment);
        
        // 2. Nếu là payment cho hóa đơn, cập nhật status hóa đơn thành 'paid'
        if ($payment['invoice_id'] && $payment['payment_type'] === 'invoice_payment') {
            // Kiểm tra tổng số tiền đã thanh toán cho hóa đơn này
            $sqlCheckTotal = "SELECT SUM(amount) as total_paid 
                             FROM payments 
                             WHERE invoice_id = ? 
                             AND status = 'confirmed' 
                             AND payment_type = 'invoice_payment'";
            $stmtCheckTotal = mysqli_prepare($conn, $sqlCheckTotal);
            
            if ($stmtCheckTotal) {
                mysqli_stmt_bind_param($stmtCheckTotal, "i", $payment['invoice_id']);
                mysqli_stmt_execute($stmtCheckTotal);
                $resultCheckTotal = mysqli_stmt_get_result($stmtCheckTotal);
                
                $totalPaid = 0;
                if ($resultCheckTotal && mysqli_num_rows($resultCheckTotal) > 0) {
                    $rowTotal = mysqli_fetch_assoc($resultCheckTotal);
                    $totalPaid = floatval($rowTotal['total_paid'] ?? 0);
                }
                
                mysqli_stmt_close($stmtCheckTotal);
                
                // Lấy tổng tiền hóa đơn
                require_once __DIR__ . '/invoices.php';
                $invoice = getInvoiceById($payment['invoice_id']);
                
                if ($invoice) {
                    $invoiceTotal = floatval($invoice['total_amount']);
                    
                    // Nếu đã thanh toán đủ hoặc vượt quá, cập nhật status hóa đơn thành 'paid'
                    if ($totalPaid >= $invoiceTotal) {
                        $sqlUpdateInvoice = "UPDATE invoices 
                                            SET status = 'paid', 
                                                paid_at = NOW() 
                                            WHERE id = ?";
                        $stmtUpdateInvoice = mysqli_prepare($conn, $sqlUpdateInvoice);
                        
                        if ($stmtUpdateInvoice) {
                            mysqli_stmt_bind_param($stmtUpdateInvoice, "i", $payment['invoice_id']);
                            if (!mysqli_stmt_execute($stmtUpdateInvoice)) {
                                throw new Exception('Lỗi cập nhật hóa đơn: ' . mysqli_error($conn));
                            }
                            mysqli_stmt_close($stmtUpdateInvoice);
                        }
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_close($conn);
        return ['success' => true, 'message' => 'Xác nhận thanh toán thành công!'];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy công nợ của sinh viên
 * @param int $studentId
 * @return array ['total_debt' => float, 'invoices' => array]
 */
function getDebtByStudentId($studentId) {
    $conn = getDbConnection();
    
    // Lấy tất cả hóa đơn chưa thanh toán (pending, overdue) của sinh viên
    $sql = "SELECT i.*, 
                   s.student_code, s.full_name as student_name,
                   r.room_code, r.room_number,
                   b.building_code, b.building_name
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.id
            LEFT JOIN rooms r ON i.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE i.student_id = ? 
            AND i.status IN ('pending', 'overdue')
            ORDER BY i.invoice_month DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $invoices = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $invoices[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Tính tổng công nợ
    $totalDebt = 0;
    foreach ($invoices as &$invoice) {
        // Tính số tiền đã thanh toán
        $sqlPaid = "SELECT COALESCE(SUM(amount), 0) as total_paid 
                   FROM payments 
                   WHERE invoice_id = ? 
                   AND status = 'confirmed' 
                   AND payment_type = 'invoice_payment'";
        $stmtPaid = mysqli_prepare($conn, $sqlPaid);
        $totalPaid = 0;
        
        if ($stmtPaid) {
            mysqli_stmt_bind_param($stmtPaid, "i", $invoice['id']);
            mysqli_stmt_execute($stmtPaid);
            $resultPaid = mysqli_stmt_get_result($stmtPaid);
            
            if ($resultPaid && mysqli_num_rows($resultPaid) > 0) {
                $rowPaid = mysqli_fetch_assoc($resultPaid);
                $totalPaid = floatval($rowPaid['total_paid'] ?? 0);
            }
            
            mysqli_stmt_close($stmtPaid);
        }
        
        $invoiceTotal = floatval($invoice['total_amount']);
        $remaining = $invoiceTotal - $totalPaid;
        
        if ($remaining > 0) {
            $totalDebt += $remaining;
            $invoice['total_paid'] = $totalPaid;
            $invoice['remaining'] = $remaining;
        } else {
            // Nếu đã thanh toán đủ, loại bỏ khỏi danh sách
            $invoice = null;
        }
    }
    
    // Loại bỏ các invoice null
    $invoices = array_filter($invoices, function($inv) {
        return $inv !== null;
    });
    
    mysqli_close($conn);
    return [
        'total_debt' => $totalDebt,
        'invoices' => array_values($invoices)
    ];
}

/**
 * Lấy tất cả công nợ (cho Manager)
 * @return array ['students' => array, 'total_debt' => float]
 */
function getAllDebts() {
    require_once __DIR__ . '/students.php';
    require_once __DIR__ . '/invoices.php';
    
    $allStudents = getAllStudents('active');
    $debts = [];
    $totalDebt = 0;
    
    foreach ($allStudents as $student) {
        $debtInfo = getDebtByStudentId($student['id']);
        
        if ($debtInfo['total_debt'] > 0) {
            $debts[] = [
                'student' => $student,
                'total_debt' => $debtInfo['total_debt'],
                'invoices' => $debtInfo['invoices']
            ];
            $totalDebt += $debtInfo['total_debt'];
        }
    }
    
    // Sắp xếp theo công nợ giảm dần
    usort($debts, function($a, $b) {
        return $b['total_debt'] <=> $a['total_debt'];
    });
    
    return [
        'students' => $debts,
        'total_debt' => $totalDebt
    ];
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

/**
 * Lấy các status payment
 * @return array
 */
function getPaymentStatuses() {
    return [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận'
    ];
}

?>
