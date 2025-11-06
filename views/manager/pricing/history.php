<?php
/**
 * Lịch sử đơn giá - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/pricing.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$priceType = $_GET['type'] ?? '';
$priceTypes = getPriceTypes();

if (empty($priceType) || !isset($priceTypes[$priceType])) {
    setErrorMessage('Loại đơn giá không hợp lệ!');
    redirect('../pricing.php');
}

$history = getPricingHistory($priceType);
$typeName = $priceTypes[$priceType];
$currentPrice = getCurrentPricing($priceType);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Đơn giá - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link active" href="../pricing.php">Đơn giá</a>
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
            <h2>
                <i class="bi bi-clock-history me-2"></i>Lịch sử: <?php echo escapeHtml($typeName); ?>
            </h2>
            <a href="../pricing.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        <?php if ($currentPrice): ?>
            <div class="alert alert-success">
                <strong>Đơn giá hiện tại:</strong> 
                <?php echo formatCurrency($currentPrice['price_value']); ?>/<?php echo escapeHtml($currentPrice['unit']); ?>
                (Có hiệu lực từ <?php echo formatDate($currentPrice['effective_from']); ?>)
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử thay đổi đơn giá</h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có lịch sử đơn giá.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Giá trị</th>
                                    <th class="text-nowrap">Đơn vị</th>
                                    <th class="text-nowrap">Có hiệu lực từ</th>
                                    <th class="text-nowrap">Có hiệu lực đến</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap">Người tạo</th>
                                    <th class="text-nowrap">Ngày tạo</th>
                                    <th class="text-nowrap" style="min-width: 150px;">Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $item): ?>
                                    <tr class="<?php echo ($item['id'] == ($currentPrice['id'] ?? 0)) ? 'table-success' : ''; ?>">
                                        <td class="text-nowrap align-middle"><strong><?php echo formatCurrency($item['price_value']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($item['unit']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($item['effective_from']); ?></td>
                                        <td class="text-nowrap align-middle">
                                            <?php if ($item['effective_to']): ?>
                                                <?php echo formatDate($item['effective_to']); ?>
                                            <?php else: ?>
                                                <span class="text-success">Đang áp dụng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($item['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($item['created_by_name'] ?? 'N/A'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($item['created_at'], 'd/m/Y H:i'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($item['description'] ?? ''); ?></td>
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

