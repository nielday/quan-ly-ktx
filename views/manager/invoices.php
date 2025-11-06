<?php
/**
 * Danh sách hóa đơn - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$statusFilter = $_GET['status'] ?? null;
$monthFilter = $_GET['month'] ?? null;
$invoices = getAllInvoices($statusFilter, $monthFilter);
$statuses = getInvoiceStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hóa đơn - Quản lý KTX</title>
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
            <h2><i class="bi bi-receipt me-2"></i>Quản lý Hóa đơn</h2>
            <a href="invoices/create_invoice.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tạo hóa đơn mới
            </a>
        </div>
        
        <?php if ($successMsg): ?>
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

        <!-- Bộ lọc -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="invoices.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Lọc theo trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($statusFilter == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="month" class="form-label">Lọc theo tháng</label>
                        <input type="month" class="form-control" id="month" name="month" 
                               value="<?php echo escapeHtml($monthFilter ?? ''); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="invoices.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách hóa đơn -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Danh sách hóa đơn</h5>
            </div>
            <div class="card-body">
                <?php if (empty($invoices)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có hóa đơn nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Tháng</th>
                                    <th>Sinh viên</th>
                                    <th>Phòng</th>
                                    <th>Tổng tiền</th>
                                    <th>Hạn thanh toán</th>
                                    <th>Trạng thái</th>
                                    <th>Người tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escapeHtml($invoice['invoice_code']); ?></strong>
                                        </td>
                                        <td><?php echo escapeHtml($invoice['invoice_month']); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo escapeHtml($invoice['student_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo escapeHtml($invoice['student_code']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($invoice['building_code']): ?>
                                                <?php echo escapeHtml($invoice['building_code']); ?> - 
                                            <?php endif; ?>
                                            <?php echo escapeHtml($invoice['room_code']); ?>
                                        </td>
                                        <td>
                                            <strong class="text-danger">
                                                <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                                            </strong>
                                        </td>
                                        <td><?php echo formatDate($invoice['due_date']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                                'overdue' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $statusLabel = $statuses[$invoice['status']] ?? $invoice['status'];
                                            $class = $statusClass[$invoice['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo escapeHtml($statusLabel); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escapeHtml($invoice['created_by_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="invoices/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                                   class="btn btn-info" title="Xem chi tiết">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($invoice['status'] !== 'paid'): ?>
                                                    <button type="button" class="btn btn-warning" 
                                                            onclick="updateStatus(<?php echo $invoice['id']; ?>, 'paid')" 
                                                            title="Đánh dấu đã thanh toán">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($invoice['status'] !== 'paid'): ?>
                                                    <a href="../../handle/invoices_process.php?action=delete&id=<?php echo $invoice['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')" 
                                                       title="Xóa">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
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

    <!-- Form ẩn để cập nhật status -->
    <form id="updateStatusForm" method="POST" action="../../handle/invoices_process.php" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="invoice_id" id="updateInvoiceId">
        <input type="hidden" name="status" id="updateStatus">
        <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(invoiceId, status) {
            if (confirm('Bạn có chắc chắn muốn cập nhật trạng thái hóa đơn này?')) {
                document.getElementById('updateInvoiceId').value = invoiceId;
                document.getElementById('updateStatus').value = status;
                document.getElementById('updateStatusForm').submit();
            }
        }
    </script>
</body>
</html>

