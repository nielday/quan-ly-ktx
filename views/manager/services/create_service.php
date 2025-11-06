<?php
/**
 * Tạo dịch vụ mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Dịch vụ mới - Quản lý KTX</title>
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
                            <i class="bi bi-plus-circle me-2"></i>Thêm Dịch vụ mới
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../../handle/services_process.php">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="service_code" class="form-label">
                                    Mã dịch vụ <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="service_code" 
                                       name="service_code" 
                                       required
                                       placeholder="Ví dụ: WIFI, WASHING, FRIDGE..."
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
                                       required
                                       placeholder="Ví dụ: WiFi, Máy giặt, Tủ lạnh..."
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
                                       step="0.01"
                                       min="0.01"
                                       required
                                       placeholder="Nhập giá dịch vụ/phòng">
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
                                       value="tháng"
                                       required
                                       placeholder="Ví dụ: tháng, phòng...">
                                <div class="form-text">Ví dụ: tháng, phòng, lần...</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Mô tả về dịch vụ này..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../services.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Thêm dịch vụ
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

