<?php
/**
 * Tạo đơn giá mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/pricing.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$priceTypes = getPriceTypes();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Đơn giá mới - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        function updateUnit() {
            const priceType = document.getElementById('price_type').value;
            const unitField = document.getElementById('unit');
            
            const units = {
                'electricity': 'kWh',
                'water': 'm³',
                'room_single': 'tháng',
                'room_double': 'tháng',
                'room_4people': 'tháng',
                'room_6people': 'tháng'
            };
            
            if (units[priceType]) {
                unitField.value = units[priceType];
            }
        }
    </script>
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
                            <i class="bi bi-plus-circle me-2"></i>Thêm Đơn giá mới
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../../handle/pricing_process.php">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="price_type" class="form-label">
                                    Loại đơn giá <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="price_type" name="price_type" required onchange="updateUnit()">
                                    <option value="">-- Chọn loại đơn giá --</option>
                                    <?php foreach ($priceTypes as $key => $label): ?>
                                        <option value="<?php echo escapeHtml($key); ?>">
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Khi tạo đơn giá mới, đơn giá cũ sẽ tự động kết thúc</div>
                            </div>

                            <div class="mb-3">
                                <label for="price_value" class="form-label">
                                    Giá trị (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="price_value" 
                                       name="price_value" 
                                       step="0.01"
                                       min="0.01"
                                       required
                                       placeholder="Nhập giá trị">
                            </div>

                            <div class="mb-3">
                                <label for="unit" class="form-label">
                                    Đơn vị tính <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="unit" 
                                       name="unit" 
                                       required
                                       placeholder="Ví dụ: kWh, m³, tháng">
                                <div class="form-text">Đơn vị sẽ tự động điền khi chọn loại đơn giá</div>
                            </div>

                            <div class="mb-3">
                                <label for="effective_from" class="form-label">
                                    Có hiệu lực từ ngày <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="effective_from" 
                                       name="effective_from" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="effective_to" class="form-label">
                                    Có hiệu lực đến ngày (để trống = đang áp dụng)
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="effective_to" 
                                       name="effective_to">
                                <div class="form-text">Để trống nếu đơn giá đang áp dụng</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Mô tả về đơn giá này..."></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../pricing.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Thêm đơn giá
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

