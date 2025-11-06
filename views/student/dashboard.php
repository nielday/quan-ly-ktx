<?php
/**
 * Dashboard Student
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/applications.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);
$currentRoom = $student ? getStudentCurrentRoom($student['id']) : null;

// Lấy số đơn đăng ký pending
$pendingApplications = $student ? getApplicationsByStudentId($student['id']) : [];
$pendingCount = count(array_filter($pendingApplications, function($app) {
    return $app['status'] == 'pending';
}));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sinh viên - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
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
        <h2 class="mb-4">Dashboard Sinh viên</h2>
        
        <?php
        $successMsg = getSuccessMessage();
        $errorMsg = getErrorMessage();
        if ($successMsg): ?>
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

        <!-- Thông tin phòng đang ở -->
        <?php if ($currentRoom): ?>
            <div class="alert alert-info">
                <h5><i class="bi bi-house-door me-2"></i>Phòng đang ở</h5>
                <p class="mb-0">
                    <strong><?php echo escapeHtml($currentRoom['building_code'] . ' - ' . $currentRoom['room_code']); ?></strong> | 
                    Loại: <?php echo escapeHtml($currentRoom['room_type']); ?> | 
                    Sức chứa: <?php echo $currentRoom['capacity']; ?> người
                </p>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-text me-2"></i>Đơn Đăng ký</h5>
                        <p class="card-text">Đăng ký ở KTX</p>
                        <?php if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark mb-2"><?php echo $pendingCount; ?> đơn chờ duyệt</span><br>
                        <?php endif; ?>
                        <a href="applications/view.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-house-door me-2"></i>Thông tin Phòng</h5>
                        <p class="card-text">Xem thông tin phòng đang ở</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-receipt me-2"></i>Hóa đơn</h5>
                        <p class="card-text">Xem và thanh toán hóa đơn</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-gear me-2"></i>Dịch vụ Phòng</h5>
                        <p class="card-text">Xem dịch vụ của phòng</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-arrow-left-right me-2"></i>Yêu cầu Chuyển phòng</h5>
                        <p class="card-text">Gửi yêu cầu chuyển phòng</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-tools me-2"></i>Yêu cầu Sửa chữa</h5>
                        <p class="card-text">Báo hỏng hóc, yêu cầu sửa</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Vi phạm</h5>
                        <p class="card-text">Xem vi phạm đã mắc phải</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person-circle me-2"></i>Thông tin Cá nhân</h5>
                        <p class="card-text">Xem và cập nhật thông tin</p>
                        <a href="profile.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Thông tin tài khoản</h5>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo escapeHtml($currentUser['username']); ?></p>
                <p><strong>Họ tên:</strong> <?php echo escapeHtml($currentUser['full_name'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?php echo escapeHtml($currentUser['email'] ?? 'N/A'); ?></p>
                <p><strong>Role:</strong> <span class="badge bg-info"><?php echo escapeHtml($currentUser['role']); ?></span></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

