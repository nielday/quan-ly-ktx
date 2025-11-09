<?php
/**
 * Sửa đơn giá - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/pricing.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID đơn giá
$id = $_GET['id'] ?? null;
if (!$id) {
    setErrorMessage("Không tìm thấy đơn giá!");
    redirect('../pricing.php');
    exit;
}

// Lấy thông tin đơn giá
$pricing = getPricingById($id);
if (!$pricing) {
    setErrorMessage("Đơn giá không tồn tại!");
    redirect('../pricing.php');
    exit;
}

// Kiểm tra đơn giá đã có hiệu lực chưa
$today = date('Y-m-d');
$isEffective = strtotime($pricing['effective_from']) <= strtotime($today) && 
               (!$pricing['effective_to'] || strtotime($pricing['effective_to']) >= strtotime($today));

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Đơn giá - Quản lý KTX</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-pencil me-2"></i>Sửa Đơn giá
                        </h5>
                    </div>
                    <div class="card-body">
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

                        <?php if ($isEffective): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Lưu ý:</strong> Đơn giá này đang có hiệu lực. Bạn chỉ có thể sửa mô tả và trạng thái. 
                                Để thay đổi giá trị hoặc ngày hiệu lực, vui lòng tạo đơn giá mới.
                            </div>
                        <?php endif; ?>

                        <!-- Thông tin đơn giá (readonly) -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin đơn giá</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Loại đơn giá:</strong></p>
                                        <p><?php echo escapeHtml($pricing['price_type']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Giá trị:</strong></p>
                                        <p><?php echo formatCurrency($pricing['price_value']); ?> / <?php echo escapeHtml($pricing['unit']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Có hiệu lực từ:</strong></p>
                                        <p><?php echo formatDate($pricing['effective_from']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Có hiệu lực đến:</strong></p>
                                        <p><?php echo $pricing['effective_to'] ? formatDate($pricing['effective_to']) : '<span class="text-success">Đang áp dụng</span>'; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="../../../../handle/pricing_process.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $pricing['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Mô tả về đơn giá này..."><?php echo escapeHtml($pricing['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo $pricing['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $pricing['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="form-text">
                                    <?php if ($isEffective): ?>
                                        <span class="text-warning">Đơn giá đang có hiệu lực. Nếu inactive, đơn giá sẽ không hiển thị trong danh sách.</span>
                                    <?php else: ?>
                                        Đơn giá chưa có hiệu lực. Có thể inactive nếu không cần thiết.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Lưu ý:</strong> Để thay đổi giá trị, đơn vị, hoặc ngày hiệu lực, vui lòng tạo đơn giá mới. 
                                Khi tạo đơn giá mới, đơn giá cũ sẽ tự động kết thúc.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../pricing.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Cập nhật
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

