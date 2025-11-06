<?php
/**
 * Xác nhận thanh toán - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/payments.php';
require_once __DIR__ . '/../../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$paymentId = intval($_GET['id'] ?? 0);

if ($paymentId <= 0) {
    setErrorMessage('Payment ID không hợp lệ!');
    header('Location: ../payments.php');
    exit;
}

$payment = getPaymentById($paymentId);

if (!$payment) {
    setErrorMessage('Không tìm thấy payment!');
    header('Location: ../payments.php');
    exit;
}

$paymentTypes = getPaymentTypes();
$paymentStatuses = getPaymentStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lấy thông tin hóa đơn nếu có
$invoice = null;
if ($payment['invoice_id']) {
    $invoice = getInvoiceById($payment['invoice_id']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Thanh toán - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../payments.php">Thanh toán</a>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escapeHtml($currentUser['full_name'] ?? $currentUser['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../../../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-coin me-2"></i>Chi tiết Thanh toán</h2>
            <a href="../payments.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo escapeHtml($successMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo escapeHtml($errorMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Thông tin thanh toán -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin Thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="200">Mã thanh toán:</th>
                                <td><strong><?php echo escapeHtml($payment['payment_code']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Loại thanh toán:</th>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo escapeHtml($paymentTypes[$payment['payment_type']] ?? $payment['payment_type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Sinh viên:</th>
                                <td>
                                    <strong><?php echo escapeHtml($payment['student_name']); ?></strong><br>
                                    <small class="text-muted">Mã SV: <?php echo escapeHtml($payment['student_code']); ?></small>
                                </td>
                            </tr>
                            <?php if ($payment['invoice_code']): ?>
                                <tr>
                                    <th>Hóa đơn:</th>
                                    <td>
                                        <a href="../invoices/view_invoice.php?id=<?php echo $payment['invoice_id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo escapeHtml($payment['invoice_code']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">Tháng: <?php echo escapeHtml($payment['invoice_month']); ?></small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($payment['contract_code']): ?>
                                <tr>
                                    <th>Hợp đồng:</th>
                                    <td>
                                        <a href="../contracts/view_contract.php?id=<?php echo $payment['contract_id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo escapeHtml($payment['contract_code']); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Số tiền:</th>
                                <td>
                                    <h4 class="text-success mb-0">
                                        <?php echo number_format($payment['amount'], 0, ',', '.'); ?> VNĐ
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <th>Ngày thanh toán:</th>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Phương thức:</th>
                                <td>
                                    <?php
                                    $methodLabels = [
                                        'cash' => 'Tiền mặt',
                                        'bank_transfer' => 'Chuyển khoản'
                                    ];
                                    $methodLabel = $methodLabels[$payment['payment_method']] ?? $payment['payment_method'];
                                    ?>
                                    <?php echo escapeHtml($methodLabel); ?>
                                </td>
                            </tr>
                            <?php if ($payment['transaction_code']): ?>
                                <tr>
                                    <th>Mã giao dịch:</th>
                                    <td><code><?php echo escapeHtml($payment['transaction_code']); ?></code></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'confirmed' => 'success'
                                    ];
                                    $statusLabel = $paymentStatuses[$payment['status']] ?? $payment['status'];
                                    $class = $statusClass[$payment['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?> fs-6">
                                        <?php echo escapeHtml($statusLabel); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if ($payment['confirmed_by_name']): ?>
                                <tr>
                                    <th>Người xác nhận:</th>
                                    <td>
                                        <?php echo escapeHtml($payment['confirmed_by_name']); ?><br>
                                        <small class="text-muted">
                                            Ngày xác nhận: <?php echo formatDateTime($payment['confirmed_at']); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($payment['notes']): ?>
                                <tr>
                                    <th>Ghi chú:</th>
                                    <td><?php echo nl2br(escapeHtml($payment['notes'])); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Thông tin hóa đơn (nếu có) -->
                <?php if ($invoice): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Thông tin Hóa đơn</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Mã hóa đơn:</th>
                                    <td>
                                        <a href="../invoices/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo escapeHtml($invoice['invoice_code']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tháng:</th>
                                    <td><?php echo escapeHtml($invoice['invoice_month']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tổng tiền hóa đơn:</th>
                                    <td>
                                        <strong><?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Đã thanh toán:</th>
                                    <td>
                                        <?php
                                        // Tính tổng đã thanh toán
                                        require_once __DIR__ . '/../../../functions/db_connection.php';
                                        $conn = getDbConnection();
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
                                        
                                        mysqli_close($conn);
                                        $remaining = floatval($invoice['total_amount']) - $totalPaid;
                                        ?>
                                        <strong class="text-success">
                                            <?php echo number_format($totalPaid, 0, ',', '.'); ?> VNĐ
                                        </strong>
                                        <?php if ($remaining > 0): ?>
                                            <br>
                                            <small class="text-danger">
                                                Còn lại: <?php echo number_format($remaining, 0, ',', '.'); ?> VNĐ
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Xác nhận thanh toán -->
                <?php if ($payment['status'] === 'pending'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận Thanh toán</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Thanh toán này đang chờ xác nhận. Sau khi xác nhận, hệ thống sẽ tự động cập nhật trạng thái hóa đơn (nếu có).
                            </p>
                            <form method="POST" action="../../../../handle/payments_process.php">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                <button type="submit" class="btn btn-success w-100" 
                                        onclick="return confirm('Bạn có chắc chắn muốn xác nhận thanh toán này?')">
                                    <i class="bi bi-check-circle me-2"></i>Xác nhận Thanh toán
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Đã Xác nhận</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Thanh toán này đã được xác nhận bởi <strong><?php echo escapeHtml($payment['confirmed_by_name']); ?></strong>
                                vào ngày <?php echo formatDateTime($payment['confirmed_at']); ?>.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

