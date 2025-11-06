<?php
/**
 * Xem công nợ - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/payments.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$debts = getAllDebts();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Công nợ - Quản lý KTX</title>
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
                <a class="nav-link active" href="debt.php">Công nợ</a>
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Công nợ Sinh viên</h2>
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

        <!-- Tổng công nợ -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-0">Tổng số sinh viên có công nợ</h5>
                        <h2 class="text-primary mb-0"><?php echo count($debts['students']); ?></h2>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted mb-0">Tổng công nợ</h5>
                        <h2 class="text-danger mb-0">
                            <?php echo number_format($debts['total_debt'], 0, ',', '.'); ?> VNĐ
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách công nợ -->
        <?php if (empty($debts['students'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>Không có sinh viên nào có công nợ!
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Danh sách Công nợ</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="debtAccordion">
                        <?php foreach ($debts['students'] as $index => $debtInfo): ?>
                            <?php $student = $debtInfo['student']; ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $index; ?>" 
                                            aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                            aria-controls="collapse<?php echo $index; ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div>
                                                <strong><?php echo escapeHtml($student['full_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Mã SV: <?php echo escapeHtml($student['student_code']); ?>
                                                    <?php if ($student['phone']): ?>
                                                        | ĐT: <?php echo escapeHtml($student['phone']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="text-danger mb-0">
                                                    <?php echo number_format($debtInfo['total_debt'], 0, ',', '.'); ?> VNĐ
                                                </h5>
                                                <small class="text-muted">
                                                    <?php echo count($debtInfo['invoices']); ?> hóa đơn
                                                </small>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $index; ?>" 
                                     class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $index; ?>" 
                                     data-bs-parent="#debtAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Mã HĐ</th>
                                                        <th>Tháng</th>
                                                        <th>Phòng</th>
                                                        <th>Tổng tiền</th>
                                                        <th>Đã thanh toán</th>
                                                        <th>Còn lại</th>
                                                        <th>Hạn thanh toán</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($debtInfo['invoices'] as $invoice): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="../invoices/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                                                   class="text-decoration-none">
                                                                    <?php echo escapeHtml($invoice['invoice_code']); ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo escapeHtml($invoice['invoice_month']); ?></td>
                                                            <td>
                                                                <?php if ($invoice['room_code']): ?>
                                                                    <?php echo escapeHtml($invoice['building_code'] ?? ''); ?>
                                                                    - <?php echo escapeHtml($invoice['room_code']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong>
                                                                    <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">
                                                                    <?php echo number_format($invoice['total_paid'] ?? 0, 0, ',', '.'); ?> VNĐ
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <strong class="text-danger">
                                                                    <?php echo number_format($invoice['remaining'] ?? $invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                <?php if ($invoice['due_date']): ?>
                                                                    <?php 
                                                                    $dueDate = strtotime($invoice['due_date']);
                                                                    $today = time();
                                                                    $isOverdue = $dueDate < $today;
                                                                    ?>
                                                                    <span class="<?php echo $isOverdue ? 'text-danger' : 'text-muted'; ?>">
                                                                        <?php echo formatDate($invoice['due_date']); ?>
                                                                    </span>
                                                                    <?php if ($isOverdue): ?>
                                                                        <br>
                                                                        <small class="badge bg-danger">Quá hạn</small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="../invoices/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                                                   class="btn btn-sm btn-info" title="Xem chi tiết">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

