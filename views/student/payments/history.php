<?php
/**
 * Lịch sử thanh toán - Student
 * Xem các giao dịch thanh toán đã thực hiện
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/payments.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../dashboard.php');
    exit;
}

// Lấy danh sách thanh toán
$payments = getPaymentsByStudentId($student['id']);

// Tính tổng
$totalPaid = 0;
$totalConfirmed = 0;
$totalPending = 0;

foreach ($payments as $payment) {
    $totalPaid += floatval($payment['amount']);
    if ($payment['status'] === 'confirmed') {
        $totalConfirmed += floatval($payment['amount']);
    } else {
        $totalPending += floatval($payment['amount']);
    }
}

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử thanh toán - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../invoices.php">Hóa đơn</a>
                <a class="nav-link active" href="history.php">Thanh toán</a>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escapeHtml($currentUser['full_name'] ?? $currentUser['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-coin me-2"></i>Lịch sử thanh toán</h2>
            <div>
                <a href="debt.php" class="btn btn-warning me-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>Xem công nợ
                </a>
                <a href="../dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>

        <?php
        if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo escapeHtml($successMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo escapeHtml($errorMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <h4 class="text-primary mb-0"><?php echo number_format($totalPaid, 0, ',', '.'); ?> VNĐ</h4>
                        <small class="text-muted">Tổng đã nộp</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="text-success mb-0"><?php echo number_format($totalConfirmed, 0, ',', '.'); ?> VNĐ</h4>
                        <small class="text-muted">Đã xác nhận</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h4 class="text-warning mb-0"><?php echo number_format($totalPending, 0, ',', '.'); ?> VNĐ</h4>
                        <small class="text-muted">Chờ xác nhận</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách thanh toán -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Danh sách giao dịch 
                    <span class="badge bg-secondary"><?php echo count($payments); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>Bạn chưa có giao dịch thanh toán nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã GD</th>
                                    <th>Hóa đơn</th>
                                    <th>Số tiền</th>
                                    <th>Ngày nộp</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày xác nhận</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><strong><?php echo escapeHtml($payment['payment_code']); ?></strong></td>
                                        <td>
                                            <?php if ($payment['invoice_code']): ?>
                                                <a href="../invoices/view.php?id=<?php echo $payment['invoice_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo escapeHtml($payment['invoice_code']); ?>
                                                </a>
                                                <br>
                                                <small class="text-muted">
                                                    <?php 
                                                    $date = DateTime::createFromFormat('Y-m', $payment['invoice_month']);
                                                    echo $date ? $date->format('m/Y') : $payment['invoice_month'];
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-primary">
                                                <?php echo number_format($payment['amount'], 0, ',', '.'); ?> VNĐ
                                            </strong>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <?php
                                            $methods = [
                                                'cash' => 'Tiền mặt',
                                                'bank_transfer' => 'Chuyển khoản'
                                            ];
                                            echo $methods[$payment['payment_method']] ?? $payment['payment_method'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'success'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Chờ xác nhận',
                                                'confirmed' => 'Đã xác nhận'
                                            ];
                                            $color = $statusColors[$payment['status']] ?? 'secondary';
                                            $label = $statusLabels[$payment['status']] ?? $payment['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($payment['confirmed_at']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($payment['confirmed_at'])); ?>
                                                <?php if ($payment['confirmed_by_name']): ?>
                                                    <br><small class="text-muted">bởi <?php echo escapeHtml($payment['confirmed_by_name']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

