<?php
/**
 * Dashboard Manager
 */

require_once __DIR__ . '/../../functions/auth.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
require_once __DIR__ . '/../../functions/helpers.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
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
        <h2 class="mb-4">Dashboard Manager</h2>
        
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

        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-building me-2"></i>Quản lý Tòa nhà</h5>
                        <p class="card-text">Quản lý tòa nhà trong KTX</p>
                        <a href="buildings.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-door-open me-2"></i>Quản lý Phòng</h5>
                        <p class="card-text">Quản lý phòng ở trong KTX</p>
                        <a href="rooms.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-currency-dollar me-2"></i>Quản lý Đơn giá</h5>
                        <p class="card-text">Quản lý đơn giá điện, nước, phòng</p>
                        <a href="pricing.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-gear me-2"></i>Quản lý Dịch vụ</h5>
                        <p class="card-text">Quản lý dịch vụ và gán cho phòng</p>
                        <a href="services.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-event me-2"></i>Đợt Đăng ký</h5>
                        <p class="card-text">Quản lý đợt đăng ký ở KTX</p>
                        <a href="registration_periods.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people me-2"></i>Quản lý Sinh viên</h5>
                        <p class="card-text">Xem danh sách và thông tin sinh viên</p>
                        <a href="students.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-check me-2"></i>Duyệt Đơn Đăng ký</h5>
                        <p class="card-text">Xem và duyệt đơn đăng ký ở KTX</p>
                        <a href="applications.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-text me-2"></i>Quản lý Hợp đồng</h5>
                        <p class="card-text">Tạo và quản lý hợp đồng</p>
                        <a href="contracts.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-receipt me-2"></i>Quản lý Hóa đơn</h5>
                        <p class="card-text">Tạo và quản lý hóa đơn</p>
                        <a href="invoices.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-cash-coin me-2"></i>Thanh toán</h5>
                        <p class="card-text">Xác nhận thanh toán</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
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
                <p><strong>Role:</strong> <span class="badge bg-success"><?php echo escapeHtml($currentUser['role']); ?></span></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

