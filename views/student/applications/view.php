<?php
/**
 * Xem trạng thái đơn đăng ký - Student
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/applications.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/rooms.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage('Không tìm thấy thông tin sinh viên!');
    redirect('../dashboard.php');
}

$applications = getApplicationsByStudentId($student['id']);
$statuses = getApplicationStatuses();
$roomTypes = getRoomTypes();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đăng ký của tôi - Quản lý KTX</title>
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
                <a class="btn btn-outline-light btn-sm" href="../../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text me-2"></i>Đơn đăng ký của tôi</h2>
            <a href="create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tạo đơn đăng ký mới
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

        <!-- Danh sách đơn đăng ký -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách đơn đăng ký</h5>
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Bạn chưa có đơn đăng ký nào. 
                        <a href="create.php" class="alert-link">Tạo đơn đăng ký mới</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Đợt đăng ký</th>
                                    <th class="text-nowrap">Ngày đăng ký</th>
                                    <th class="text-nowrap">Học kỳ</th>
                                    <th class="text-nowrap">Năm học</th>
                                    <th class="text-nowrap">Loại phòng</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap">Ngày duyệt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="text-nowrap align-middle">
                                            <?php echo escapeHtml($app['period_name'] ?: '-'); ?>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($app['application_date']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($app['semester'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($app['academic_year'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo $app['preferred_room_type'] ? escapeHtml($roomTypes[$app['preferred_room_type']] ?? $app['preferred_room_type']) : '-'; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $statusClass = [
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'waiting_list' => 'bg-info'
                                            ];
                                            $statusLabel = $statuses[$app['status']] ?? $app['status'];
                                            $class = $statusClass[$app['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                                        </td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo $app['approved_at'] ? formatDate($app['approved_at']) : '-'; ?>
                                        </td>
                                    </tr>
                                    <?php if ($app['rejection_reason']): ?>
                                        <tr>
                                            <td colspan="7" class="text-muted">
                                                <small><strong>Lý do từ chối:</strong> <?php echo escapeHtml($app['rejection_reason']); ?></small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

