<?php
/**
 * Danh sách đơn giá - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/pricing.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$pricingList = getAllPricing(false); // Chỉ lấy active (để hiển thị trong danh sách chính)
$priceTypes = getPriceTypes();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Nhóm theo price_type - chỉ hiển thị đơn giá active (current)
$groupedPricing = [];
foreach ($priceTypes as $priceType => $typeName) {
    $currentPrice = getCurrentPricing($priceType);
    if ($currentPrice) {
        if (!isset($groupedPricing[$priceType])) {
            $groupedPricing[$priceType] = [];
        }
        $groupedPricing[$priceType][] = $currentPrice;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn giá - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="pricing.php">Đơn giá</a>
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
            <h2><i class="bi bi-currency-dollar me-2"></i>Quản lý Đơn giá</h2>
            <a href="pricing/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Thêm đơn giá mới
            </a>
        </div>
        
        <!-- Thông báo -->
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

        <!-- Danh sách đơn giá theo từng loại -->
        <?php if (empty($groupedPricing)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Chưa có đơn giá nào. Hãy thêm đơn giá mới!
            </div>
        <?php else: ?>
            <?php foreach ($groupedPricing as $priceType => $items): 
                $currentPrice = getCurrentPricing($priceType);
                $typeName = $priceTypes[$priceType] ?? $priceType;
            ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-tag me-2"></i><?php echo escapeHtml($typeName); ?>
                            <?php if ($currentPrice): ?>
                                <span class="badge bg-light text-dark ms-2">
                                    Hiện tại: <?php echo formatCurrency($currentPrice['price_value']); ?>/<?php echo escapeHtml($currentPrice['unit']); ?>
                                </span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>Chưa có đơn giá cho loại này.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">Giá trị</th>
                                            <th class="text-nowrap">Đơn vị</th>
                                            <th class="text-nowrap">Có hiệu lực từ</th>
                                            <th class="text-nowrap">Có hiệu lực đến</th>
                                            <th class="text-nowrap">Người tạo</th>
                                            <th class="text-nowrap" style="min-width: 150px;">Mô tả</th>
                                            <th class="text-nowrap text-center" style="width: 120px;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr class="table-success">
                                                <td class="text-nowrap align-middle"><strong><?php echo formatCurrency($item['price_value']); ?></strong></td>
                                                <td class="text-nowrap align-middle"><?php echo escapeHtml($item['unit']); ?></td>
                                                <td class="text-nowrap align-middle"><?php echo formatDate($item['effective_from']); ?></td>
                                                <td class="text-nowrap align-middle"><?php echo $item['effective_to'] ? formatDate($item['effective_to']) : '<span class="text-success">Đang áp dụng</span>'; ?></td>
                                                <td class="text-nowrap align-middle"><?php echo escapeHtml($item['created_by_name'] ?? 'N/A'); ?></td>
                                                <td class="text-nowrap align-middle">
                                                    <?php 
                                                    $desc = $item['description'] ?? '';
                                                    echo escapeHtml(mb_substr($desc, 0, 30));
                                                    if (mb_strlen($desc) > 30) echo '...';
                                                    ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <a href="pricing/edit.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-warning me-1" title="Sửa">
                                                        <i class="bi bi-pencil me-1"></i>Sửa
                                                    </a>
                                                    <a href="pricing/history.php?type=<?php echo urlencode($priceType); ?>" 
                                                       class="btn btn-sm btn-info" title="Xem lịch sử">
                                                        <i class="bi bi-clock-history me-1"></i>Lịch sử
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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

