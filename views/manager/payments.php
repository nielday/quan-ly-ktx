<?php
/**
 * Danh sách thanh toán - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/payments.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$statusFilter = $_GET['status'] ?? null;
$paymentTypeFilter = $_GET['payment_type'] ?? null;
$payments = getAllPayments($statusFilter, $paymentTypeFilter);
$paymentTypes = getPaymentTypes();
$paymentStatuses = getPaymentStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thanh toán - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="invoices.php">Hóa đơn</a>
                <a class="nav-link active" href="payments.php">Thanh toán</a>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escapeHtml($currentUser['full_name'] ?? $currentUser['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-coin me-2"></i>Quản lý Thanh toán</h2>
            <div>
                <a href="payments/debt.php" class="btn btn-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>Xem Công nợ
                </a>
            </div>
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

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Bộ lọc</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="payments.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($paymentStatuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $statusFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="payment_type" class="form-label">Loại thanh toán</label>
                        <select name="payment_type" id="payment_type" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($paymentTypes as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $paymentTypeFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-2"></i>Lọc
                        </button>
                        <a href="payments.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách thanh toán -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Danh sách thanh toán</h5>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có thanh toán nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã TT</th>
                                    <th>Loại</th>
                                    <th>Sinh viên</th>
                                    <th>Hóa đơn/Hợp đồng</th>
                                    <th>Số tiền</th>
                                    <th>Ngày thanh toán</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Người xác nhận</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escapeHtml($payment['payment_code']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $typeLabels = [
                                                'invoice_payment' => 'Thanh toán HĐ',
                                                'deposit' => 'Đặt cọc',
                                                'refund' => 'Hoàn tiền'
                                            ];
                                            $typeLabel = $typeLabels[$payment['payment_type']] ?? $payment['payment_type'];
                                            ?>
                                            <span class="badge bg-info"><?php echo escapeHtml($typeLabel); ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo escapeHtml($payment['student_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo escapeHtml($payment['student_code']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($payment['invoice_code']): ?>
                                                <a href="invoices/view_invoice.php?id=<?php echo $payment['invoice_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo escapeHtml($payment['invoice_code']); ?>
                                                </a>
                                                <br>
                                                <small class="text-muted"><?php echo escapeHtml($payment['invoice_month']); ?></small>
                                            <?php elseif ($payment['contract_code']): ?>
                                                <a href="contracts/view_contract.php?id=<?php echo $payment['contract_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo escapeHtml($payment['contract_code']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                <?php echo number_format($payment['amount'], 0, ',', '.'); ?> VNĐ
                                            </strong>
                                        </td>
                                        <td><?php echo formatDate($payment['payment_date']); ?></td>
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
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'confirmed' => 'success'
                                            ];
                                            $statusLabel = $paymentStatuses[$payment['status']] ?? $payment['status'];
                                            $class = $statusClass[$payment['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo escapeHtml($statusLabel); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['confirmed_by_name']): ?>
                                                <?php echo escapeHtml($payment['confirmed_by_name']); ?><br>
                                                <small class="text-muted">
                                                    <?php echo formatDateTime($payment['confirmed_at']); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="payments/confirm.php?id=<?php echo $payment['id']; ?>" 
                                                   class="btn btn-info" title="Xem chi tiết">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success" 
                                                            onclick="confirmPayment(<?php echo $payment['id']; ?>)" 
                                                            title="Xác nhận thanh toán">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form ẩn để xác nhận payment -->
    <form id="confirmPaymentForm" method="POST" action="../../handle/payments_process.php" style="display: none;">
        <input type="hidden" name="action" value="confirm">
        <input type="hidden" name="payment_id" id="confirmPaymentId">
        <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmPayment(paymentId) {
            if (confirm('Bạn có chắc chắn muốn xác nhận thanh toán này?')) {
                document.getElementById('confirmPaymentId').value = paymentId;
                document.getElementById('confirmPaymentForm').submit();
            }
        }
    </script>
</body>
</html>

