<?php
/**
 * Tạo đợt đăng ký mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/registration_periods.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$statuses = getRegistrationPeriodStatuses();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Đợt Đăng ký mới - Quản lý KTX</title>
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
                <a class="nav-link active" href="../registration_periods.php">Đợt đăng ký</a>
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
                            <i class="bi bi-plus-circle me-2"></i>Tạo Đợt Đăng ký mới
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../../handle/registration_periods_process.php">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="period_name" class="form-label">
                                    Tên đợt đăng ký <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="period_name" 
                                       name="period_name" 
                                       required
                                       placeholder="Ví dụ: Đăng ký học kỳ 1 năm 2024-2025"
                                       maxlength="200">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">
                                        Ngày bắt đầu <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           required
                                           min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">
                                        Ngày kết thúc <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="end_date" 
                                           name="end_date" 
                                           required
                                           min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="semester" class="form-label">Học kỳ</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="semester" 
                                           name="semester" 
                                           placeholder="Ví dụ: Học kỳ 1, Học kỳ 2"
                                           maxlength="20">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="academic_year" class="form-label">Năm học</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="academic_year" 
                                           name="academic_year" 
                                           placeholder="Ví dụ: 2024-2025"
                                           maxlength="20">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="total_rooms_available" class="form-label">Tổng số phòng có sẵn</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="total_rooms_available" 
                                       name="total_rooms_available" 
                                       min="0"
                                       placeholder="Để trống nếu không giới hạn">
                                <div class="form-text">Số phòng có sẵn cho đợt đăng ký này (tùy chọn)</div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($key == 'upcoming') ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Trạng thái sẽ được tự động cập nhật dựa trên ngày bắt đầu và kết thúc</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../registration_periods.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Tạo đợt đăng ký
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation: end_date phải sau start_date
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
            endDate.min = this.value;
        });

        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date');
            if (this.value < startDate.value) {
                alert('Ngày kết thúc phải sau ngày bắt đầu!');
                this.value = startDate.value;
            }
        });
    </script>
</body>
</html>

