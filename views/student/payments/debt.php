<?php
/**
 * Công nợ - Student
 * Xem các hóa đơn chưa thanh toán
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../dashboard.php');
    exit;
}

// Lấy danh sách hóa đơn chưa thanh toán
$allInvoices = getInvoicesByStudentId($student['id']);
$unpaidInvoices = array_filter($allInvoices, function($inv) {
    return $inv['status'] === 'pending' || $inv['status'] === 'overdue';
});
$unpaidInvoices = array_values($unpaidInvoices);

// Tính tổng công nợ
$totalDebt = 0;
$overdueDebt = 0;
$pendingDebt = 0;

foreach ($unpaidInvoices as $invoice) {
    $amount = floatval($invoice['total_amount']);
    $totalDebt += $amount;
    
    if ($invoice['status'] === 'overdue') {
        $overdueDebt += $amount;
    } else {
        $pendingDebt += $amount;
    }
}

// Sắp xếp: overdue trước, sau đó pending
usort($unpaidInvoices, function($a, $b) {
    if ($a['status'] === 'overdue' && $b['status'] !== 'overdue') {
        return -1;
    }
    if ($a['status'] !== 'overdue' && $b['status'] === 'overdue') {
        return 1;
    }
    return strtotime($b['due_date']) - strtotime($a['due_date']);
});
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Công nợ - Sinh viên</title>
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Công nợ</h2>
            <a href="history.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
        </div>

        <!-- Tổng công nợ -->
        <div class="alert alert-warning mb-4">
            <div class="row text-center">
                <div class="col-md-4">
                    <h5 class="mb-0">Tổng công nợ</h5>
                    <h3 class="text-danger mb-0"><?php echo number_format($totalDebt, 0, ',', '.'); ?> VNĐ</h3>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-0">Quá hạn</h5>
                    <h4 class="text-danger mb-0"><?php echo number_format($overdueDebt, 0, ',', '.'); ?> VNĐ</h4>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-0">Chưa đến hạn</h5>
                    <h4 class="text-warning mb-0"><?php echo number_format($pendingDebt, 0, ',', '.'); ?> VNĐ</h4>
                </div>
            </div>
        </div>

        <!-- Danh sách hóa đơn chưa thanh toán -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Hóa đơn chưa thanh toán 
                    <span class="badge bg-secondary"><?php echo count($unpaidInvoices); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($unpaidInvoices)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Chúc mừng!</strong> Bạn không có công nợ nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Tháng</th>
                                    <th>Phòng</th>
                                    <th>Tổng tiền</th>
                                    <th>Hạn thanh toán</th>
                                    <th>Trạng thái</th>
                                    <th>Số ngày quá hạn</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unpaidInvoices as $invoice): ?>
                                    <?php
                                    $daysOverdue = 0;
                                    if ($invoice['status'] === 'overdue') {
                                        $dueDate = new DateTime($invoice['due_date']);
                                        $today = new DateTime();
                                        $daysOverdue = $today->diff($dueDate)->days;
                                    }
                                    ?>
                                    <tr class="<?php echo ($invoice['status'] === 'overdue') ? 'table-danger' : ''; ?>">
                                        <td><strong><?php echo escapeHtml($invoice['invoice_code']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $date = DateTime::createFromFormat('Y-m', $invoice['invoice_month']);
                                            echo $date ? $date->format('m/Y') : $invoice['invoice_month'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo escapeHtml($invoice['building_code']); ?> - 
                                            <?php echo escapeHtml($invoice['room_code']); ?>
                                        </td>
                                        <td>
                                            <strong class="text-primary">
                                                <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="<?php echo ($invoice['status'] === 'overdue') ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'overdue' => 'danger'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Chưa thanh toán',
                                                'overdue' => 'Quá hạn'
                                            ];
                                            $color = $statusColors[$invoice['status']] ?? 'secondary';
                                            $label = $statusLabels[$invoice['status']] ?? $invoice['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($daysOverdue > 0): ?>
                                                <span class="text-danger fw-bold"><?php echo $daysOverdue; ?> ngày</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../invoices/view.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="create.php?invoice_id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Nộp tiền">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
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

