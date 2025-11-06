<?php
/**
 * Payments Process Handler - Xử lý các request liên quan đến thanh toán
 */

require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/payments.php';

// Kiểm tra đăng nhập
checkLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'confirm':
        handleConfirmPayment();
        break;
    
    case 'create':
        handleCreatePayment();
        break;
    
    default:
        setErrorMessage('Action không hợp lệ!');
        header('Location: ../views/manager/payments.php');
        exit;
}

/**
 * Xử lý xác nhận thanh toán
 */
function handleConfirmPayment() {
    // Chỉ Manager mới được xác nhận
    checkRole('manager');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        setErrorMessage('Phương thức không hợp lệ!');
        header('Location: ../views/manager/payments.php');
        exit;
    }
    
    $paymentId = intval($_POST['payment_id'] ?? 0);
    $currentUser = getCurrentUser();
    
    if ($paymentId <= 0) {
        setErrorMessage('Payment ID không hợp lệ!');
        header('Location: ../views/manager/payments.php');
        exit;
    }
    
    $result = confirmPayment($paymentId, $currentUser['id']);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    // Redirect về trang trước đó
    $redirectUrl = $_POST['redirect_url'] ?? '../views/manager/payments.php';
    header('Location: ' . $redirectUrl);
    exit;
}

/**
 * Xử lý tạo payment (sinh viên nộp tiền)
 */
function handleCreatePayment() {
    // Sinh viên có thể tạo payment
    checkRole(['student', 'manager']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        setErrorMessage('Phương thức không hợp lệ!');
        if (getCurrentUser()['role'] === 'student') {
            header('Location: ../views/student/payments/create.php');
        } else {
            header('Location: ../views/manager/payments.php');
        }
        exit;
    }
    
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    $studentId = intval($_POST['student_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $transactionCode = $_POST['transaction_code'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    // Validation
    if ($invoiceId <= 0) {
        setErrorMessage('Vui lòng chọn hóa đơn!');
        if (getCurrentUser()['role'] === 'student') {
            header('Location: ../views/student/payments/create.php');
        } else {
            header('Location: ../views/manager/payments.php');
        }
        exit;
    }
    
    if ($amount <= 0) {
        setErrorMessage('Số tiền phải lớn hơn 0!');
        if (getCurrentUser()['role'] === 'student') {
            header('Location: ../views/student/payments/create.php?invoice_id=' . $invoiceId);
        } else {
            header('Location: ../views/manager/payments.php');
        }
        exit;
    }
    
    // Nếu là sinh viên, lấy student_id từ session
    $currentUser = getCurrentUser();
    if ($currentUser['role'] === 'student') {
        require_once __DIR__ . '/../functions/students.php';
        $student = getStudentByUserId($currentUser['id']);
        if (!$student) {
            setErrorMessage('Không tìm thấy thông tin sinh viên!');
            header('Location: ../views/student/dashboard.php');
            exit;
        }
        $studentId = $student['id'];
    }
    
    // Validate payment_date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
        setErrorMessage('Ngày thanh toán không hợp lệ!');
        if (getCurrentUser()['role'] === 'student') {
            header('Location: ../views/student/payments/create.php?invoice_id=' . $invoiceId);
        } else {
            header('Location: ../views/manager/payments.php');
        }
        exit;
    }
    
    // Validate payment_method
    if (!in_array($paymentMethod, ['cash', 'bank_transfer'])) {
        $paymentMethod = 'cash';
    }
    
    $result = createInvoicePayment($invoiceId, $studentId, $amount, $paymentDate, $paymentMethod, $transactionCode, $notes);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
        if ($currentUser['role'] === 'student') {
            header('Location: ../views/student/payments/history.php');
        } else {
            header('Location: ../views/manager/payments.php');
        }
    } else {
        setErrorMessage($result['message']);
        if ($currentUser['role'] === 'student') {
            header('Location: ../views/student/payments/create.php?invoice_id=' . $invoiceId);
        } else {
            header('Location: ../views/manager/payments.php');
        }
    }
    exit;
}

?>
