<?php
/**
 * Invoices Process - Xử lý các action liên quan đến hóa đơn
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/invoices.php';
require_once __DIR__ . '/../functions/helpers.php';

// Kiểm tra đăng nhập và quyền Manager
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = getCurrentUser();
if ($user['role'] !== 'manager') {
    $_SESSION['error'] = 'Bạn không có quyền truy cập!';
    header('Location: ../views/manager/dashboard.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreateInvoice();
        break;
    
    case 'update_status':
        handleUpdateInvoiceStatus();
        break;
    
    case 'delete':
        handleDeleteInvoice();
        break;
    
    default:
        $_SESSION['error'] = 'Action không hợp lệ!';
        header('Location: ../views/manager/invoices.php');
        exit;
}

/**
 * Xử lý tạo hóa đơn
 */
function handleCreateInvoice() {
    $roomId = intval($_POST['room_id'] ?? 0);
    $invoiceMonth = trim($_POST['invoice_month'] ?? '');
    $electricityAmount = floatval($_POST['electricity_amount'] ?? 0);
    $waterAmount = floatval($_POST['water_amount'] ?? 0);
    $dueDate = trim($_POST['due_date'] ?? '');
    
    // Validation
    if ($roomId <= 0) {
        $_SESSION['error'] = 'Vui lòng chọn phòng!';
        header('Location: ../views/manager/invoices/create_invoice.php');
        exit;
    }
    
    if (empty($invoiceMonth) || !preg_match('/^\d{4}-\d{2}$/', $invoiceMonth)) {
        $_SESSION['error'] = 'Tháng hóa đơn không đúng định dạng (YYYY-MM)!';
        header('Location: ../views/manager/invoices/create_invoice.php');
        exit;
    }
    
    if ($electricityAmount < 0) {
        $_SESSION['error'] = 'Số kWh điện không hợp lệ!';
        header('Location: ../views/manager/invoices/create_invoice.php');
        exit;
    }
    
    if ($waterAmount < 0) {
        $_SESSION['error'] = 'Số m³ nước không hợp lệ!';
        header('Location: ../views/manager/invoices/create_invoice.php');
        exit;
    }
    
    // Nếu không có due_date, để null để tự động tính
    // Input type="date" trả về format YYYY-MM-DD hoặc chuỗi rỗng
    if (empty($dueDate) || trim($dueDate) === '') {
        $dueDate = null;
    } else {
        // Validate format Y-m-d
        $dueDate = trim($dueDate);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $_SESSION['error'] = 'Ngày hạn thanh toán không đúng định dạng (YYYY-MM-DD)!';
            header('Location: ../views/manager/invoices/create_invoice.php');
            exit;
        }
        // Validate date hợp lệ
        $dateParts = explode('-', $dueDate);
        if (count($dateParts) !== 3 || !checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
            $_SESSION['error'] = 'Ngày hạn thanh toán không hợp lệ!';
            header('Location: ../views/manager/invoices/create_invoice.php');
            exit;
        }
    }
    
    $userId = $_SESSION['user_id'];
    
    // Tạo hóa đơn
    $result = createInvoicesForRoom($roomId, $invoiceMonth, $electricityAmount, $waterAmount, $userId, $dueDate);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: ../views/manager/invoices.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ../views/manager/invoices/create_invoice.php');
    }
    exit;
}

/**
 * Xử lý cập nhật status hóa đơn
 */
function handleUpdateInvoiceStatus() {
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    
    if ($invoiceId <= 0) {
        $_SESSION['error'] = 'ID hóa đơn không hợp lệ!';
        header('Location: ../views/manager/invoices.php');
        exit;
    }
    
    $validStatuses = ['pending', 'paid', 'overdue', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        $_SESSION['error'] = 'Status không hợp lệ!';
        header('Location: ../views/manager/invoices.php');
        exit;
    }
    
    require_once __DIR__ . '/../functions/db_connection.php';
    $conn = getDbConnection();
    
    // Cập nhật status
    $sql = "UPDATE invoices SET status = ?";
    $params = [$status];
    $types = "s";
    
    // Nếu status là 'paid', cập nhật paid_at
    if ($status === 'paid') {
        $sql .= ", paid_at = NOW()";
    } else {
        $sql .= ", paid_at = NULL";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $invoiceId;
    $types .= "i";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Cập nhật trạng thái hóa đơn thành công!';
        } else {
            $_SESSION['error'] = 'Lỗi cập nhật trạng thái: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Lỗi chuẩn bị câu lệnh SQL!';
    }
    
    mysqli_close($conn);
    
    $redirectUrl = $_POST['redirect_url'] ?? '../views/manager/invoices.php';
    header('Location: ' . $redirectUrl);
    exit;
}

/**
 * Xử lý xóa hóa đơn
 */
function handleDeleteInvoice() {
    $invoiceId = intval($_GET['id'] ?? 0);
    
    if ($invoiceId <= 0) {
        $_SESSION['error'] = 'ID hóa đơn không hợp lệ!';
        header('Location: ../views/manager/invoices.php');
        exit;
    }
    
    // Kiểm tra hóa đơn có tồn tại không
    $invoice = getInvoiceById($invoiceId);
    if (!$invoice) {
        $_SESSION['error'] = 'Hóa đơn không tồn tại!';
        header('Location: ../views/manager/invoices.php');
        exit;
    }
    
    // Chỉ cho phép xóa hóa đơn chưa thanh toán
    if ($invoice['status'] === 'paid') {
        $_SESSION['error'] = 'Không thể xóa hóa đơn đã thanh toán!';
        header('Location: ../views/manager/invoices.php');
        exit;
    }
    
    require_once __DIR__ . '/../functions/db_connection.php';
    $conn = getDbConnection();
    
    $sql = "DELETE FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Xóa hóa đơn thành công!';
        } else {
            $_SESSION['error'] = 'Lỗi xóa hóa đơn: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Lỗi chuẩn bị câu lệnh SQL!';
    }
    
    mysqli_close($conn);
    
    header('Location: ../views/manager/invoices.php');
    exit;
}

?>

