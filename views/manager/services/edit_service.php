<?php
/**
 * Sửa dịch vụ - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/services.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID từ GET
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setErrorMessage('ID không hợp lệ!');
    redirect('../services.php');
}

// Lấy thông tin dịch vụ
$service = getServiceById($id);

if (!$service) {
    setErrorMessage('Dịch vụ không tồn tại!');
    redirect('../services.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Dịch vụ - Quản lý KTX</title>
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
                <a class="nav-link active" href="../services.php">Dịch vụ</a>
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
                            <i class="bi bi-pencil me-2"></i>Sửa Dịch vụ
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../../handle/services_process.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="service_code" class="form-label">
                                    Mã dịch vụ <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="service_code" 
                                       name="service_code" 
                                       value="<?php echo escapeHtml($service['service_code']); ?>"
                                       required
                                       maxlength="20">
                                <div class="form-text">Mã dịch vụ phải là duy nhất</div>
                            </div>

                            <div class="mb-3">
                                <label for="service_name" class="form-label">
                                    Tên dịch vụ <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="service_name" 
                                       name="service_name" 
                                       value="<?php echo escapeHtml($service['service_name']); ?>"
                                       required
                                       maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">
                                    Giá/phòng (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       value="<?php echo escapeHtml($service['price']); ?>"
                                       step="0.01"
                                       min="0.01"
                                       required>
                                <div class="form-text">Giá này tính theo phòng, sẽ chia đều cho số người trong phòng khi tạo hóa đơn</div>
                            </div>

                            <div class="mb-3">
                                <label for="unit" class="form-label">
                                    Đơn vị tính <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="unit" 
                                       name="unit" 
                                       value="<?php echo escapeHtml($service['unit']); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"><?php echo escapeHtml($service['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($service['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($service['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../services.php" class="btn btn-secondary">
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

