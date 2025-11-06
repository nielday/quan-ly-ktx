<?php
/**
 * Dashboard Admin
 */

require_once __DIR__ . '/../../functions/auth.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

$currentUser = getCurrentUser();
require_once __DIR__ . '/../../functions/helpers.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Admin
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
        <h2 class="mb-4">Dashboard Admin</h2>
        
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
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people me-2"></i>Quản lý tài khoản</h5>
                        <p class="card-text">Quản lý users, phân quyền</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-bar-chart me-2"></i>Báo cáo</h5>
                        <p class="card-text">Xem báo cáo tổng hợp</p>
                        <a href="#" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-gear me-2"></i>Cấu hình hệ thống</h5>
                        <p class="card-text">Cài đặt hệ thống</p>
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
                <p><strong>Role:</strong> <span class="badge bg-primary"><?php echo escapeHtml($currentUser['role']); ?></span></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

