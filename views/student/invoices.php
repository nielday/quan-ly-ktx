<?php
/**
 * Danh sách hóa đơn - Student
 * Xem các hóa đơn đã được Manager tạo
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('dashboard.php');
    exit;
}

// Lấy filters
$statusFilter = $_GET['status'] ?? null;
$monthFilter = $_GET['month'] ?? null;

// Lấy danh sách hóa đơn
$invoices = getInvoicesByStudentId($student['id']);

// Filter theo status
if ($statusFilter) {
    $invoices = array_filter($invoices, function($inv) use ($statusFilter) {
        return $inv['status'] === $statusFilter;
    });
    $invoices = array_values($invoices);
}

// Filter theo tháng
if ($monthFilter) {
    $invoices = array_filter($invoices, function($inv) use ($monthFilter) {
        return $inv['invoice_month'] === $monthFilter;
    });
    $invoices = array_values($invoices);
}

// Tính tổng công nợ (chưa thanh toán)
$totalDebt = 0;
foreach ($invoices as $invoice) {
    if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue') {
        $totalDebt += floatval($invoice['total_amount']);
    }
}

// Lấy danh sách tháng có hóa đơn (để filter)
$months = [];
foreach ($invoices as $invoice) {
    $month = $invoice['invoice_month'];
    if (!in_array($month, $months)) {
        $months[] = $month;
    }
}
rsort($months); // Sắp xếp mới nhất trước
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn của tôi - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="invoices.php">Hóa đơn</a>
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
            <h2><i class="bi bi-receipt me-2"></i>Hóa đơn của tôi</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
        </div>

        <?php
        $successMsg = getSuccessMessage();
        $errorMsg = getErrorMessage();
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

        <!-- Thống kê công nợ -->
        <?php if ($totalDebt > 0): ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Tổng công nợ:</strong> 
                    <span class="fs-4 text-danger"><?php echo number_format($totalDebt, 0, ',', '.'); ?> VNĐ</span>
                </div>
                <a href="payments/debt.php" class="btn btn-warning">
                    <i class="bi bi-cash-coin me-1"></i>Xem chi tiết công nợ
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="pending" <?php echo ($statusFilter === 'pending') ? 'selected' : ''; ?>>Chưa thanh toán</option>
                            <option value="paid" <?php echo ($statusFilter === 'paid') ? 'selected' : ''; ?>>Đã thanh toán</option>
                            <option value="overdue" <?php echo ($statusFilter === 'overdue') ? 'selected' : ''; ?>>Quá hạn</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tháng</label>
                        <select name="month" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($months as $month): ?>
                                <option value="<?php echo $month; ?>" <?php echo ($monthFilter === $month) ? 'selected' : ''; ?>>
                                    <?php 
                                    $date = DateTime::createFromFormat('Y-m', $month);
                                    echo $date ? $date->format('m/Y') : $month;
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="invoices.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách hóa đơn -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Danh sách hóa đơn 
                    <span class="badge bg-secondary"><?php echo count($invoices); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($invoices)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>Bạn chưa có hóa đơn nào.
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
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
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
                                            <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                                'overdue' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Chưa thanh toán',
                                                'paid' => 'Đã thanh toán',
                                                'overdue' => 'Quá hạn',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            $color = $statusColors[$invoice['status']] ?? 'secondary';
                                            $label = $statusLabels[$invoice['status']] ?? $invoice['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                        </td>
                                        <td>
                                            <a href="invoices/view.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
                                                <a href="payments/create.php?invoice_id=<?php echo $invoice['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Nộp tiền">
                                                    <i class="bi bi-cash-coin"></i>
                                                </a>
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

