<?php
/**
 * Tạo đơn đăng ký - Student
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/applications.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/registration_periods.php';
require_once __DIR__ . '/../../../functions/rooms.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage('Không tìm thấy thông tin sinh viên!');
    redirect('../dashboard.php');
}

// Lấy đợt đăng ký đang mở
$openPeriod = getOpenRegistrationPeriod();
$roomTypes = getRoomTypes();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký ở KTX - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link active" href="view.php">Đơn đăng ký</a>
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
                            <i class="bi bi-file-earmark-plus me-2"></i>Đăng ký ở KTX
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$openPeriod): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>Hiện tại không có đợt đăng ký nào đang mở. Vui lòng chờ đợt đăng ký tiếp theo.
                            </div>
                            <a href="view.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Quay lại
                            </a>
                        <?php else: ?>
                            <?php if ($errorMsg): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo escapeHtml($errorMsg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-info">
                                <strong>Đợt đăng ký:</strong> <?php echo escapeHtml($openPeriod['period_name']); ?><br>
                                <small>Thời gian: <?php echo formatDate($openPeriod['start_date']); ?> - <?php echo formatDate($openPeriod['end_date']); ?></small>
                            </div>
                            
                            <form method="POST" action="../../../../handle/applications_process.php">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="registration_period_id" value="<?php echo $openPeriod['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Thông tin sinh viên</label>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <p class="mb-1"><strong>Mã SV:</strong> <?php echo escapeHtml($student['student_code']); ?></p>
                                            <p class="mb-1"><strong>Họ tên:</strong> <?php echo escapeHtml($student['full_name']); ?></p>
                                            <p class="mb-0"><strong>Trường:</strong> <?php echo escapeHtml($student['university'] ?: '-'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="application_date" class="form-label">
                                        Ngày đăng ký <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="application_date" 
                                           name="application_date" 
                                           value="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="semester" class="form-label">Học kỳ</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="semester" 
                                               name="semester" 
                                               value="<?php echo escapeHtml($openPeriod['semester'] ?? ''); ?>"
                                               placeholder="Ví dụ: Học kỳ 1"
                                               maxlength="20">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="academic_year" class="form-label">Năm học</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="academic_year" 
                                               name="academic_year" 
                                               value="<?php echo escapeHtml($openPeriod['academic_year'] ?? ''); ?>"
                                               placeholder="Ví dụ: 2024-2025"
                                               maxlength="20">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="preferred_room_type" class="form-label">Loại phòng mong muốn</label>
                                    <select class="form-select" id="preferred_room_type" name="preferred_room_type">
                                        <option value="">-- Chọn loại phòng --</option>
                                        <?php foreach ($roomTypes as $key => $label): ?>
                                            <option value="<?php echo $key; ?>">
                                                <?php echo escapeHtml($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Chọn loại phòng bạn muốn ở (tùy chọn)</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="view.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Quay lại
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Gửi đơn đăng ký
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

