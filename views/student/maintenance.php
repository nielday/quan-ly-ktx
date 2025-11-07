<?php
/**
 * Danh sách yêu cầu sửa chữa - Student
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/maintenance.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('dashboard.php');
    exit;
}

// Lấy filters
$statusFilter = $_GET['status'] ?? null;
$typeFilter = $_GET['request_type'] ?? null;

// Lấy danh sách yêu cầu của sinh viên này
$filters = ['student_id' => $student['id']];
if ($statusFilter) {
    $filters['status'] = $statusFilter;
}
if ($typeFilter) {
    $filters['request_type'] = $typeFilter;
}

$requests = getAllMaintenanceRequests($filters);
$statuses = getMaintenanceStatuses();
$requestTypes = getRequestTypes();
$priorities = getPriorityLevels();

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu sửa chữa - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="room.php">Phòng của tôi</a>
                <a class="nav-link active" href="maintenance.php">Sửa chữa</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-tools me-2"></i>Yêu cầu sửa chữa</h2>
            <div>
                <a href="maintenance/create.php" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-1"></i>Tạo yêu cầu mới
                </a>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>

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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Lọc theo trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($statusFilter === $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lọc theo loại</label>
                        <select name="request_type" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($requestTypes as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($typeFilter === $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="maintenance.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách yêu cầu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách yêu cầu (<?php echo count($requests); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Bạn chưa có yêu cầu sửa chữa nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã yêu cầu</th>
                                    <th>Phòng</th>
                                    <th>Loại</th>
                                    <th>Mô tả</th>
                                    <th>Mức độ</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><strong>#<?php echo $request['id']; ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo escapeHtml($request['building_code'] . '-' . $request['room_code']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo escapeHtml($requestTypes[$request['request_type']] ?? $request['request_type']); ?>
                                        </td>
                                        <td>
                                            <small><?php echo escapeHtml(substr($request['description'], 0, 50)); ?><?php echo strlen($request['description']) > 50 ? '...' : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $priorityClass = [
                                                'urgent' => 'danger',
                                                'high' => 'warning',
                                                'medium' => 'info',
                                                'low' => 'secondary'
                                            ];
                                            $priorityBadge = $priorityClass[$request['priority']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $priorityBadge; ?>">
                                                <?php echo escapeHtml($priorities[$request['priority']] ?? $request['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'secondary'
                                            ];
                                            $statusBadge = $statusClass[$request['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusBadge; ?>">
                                                <?php echo escapeHtml($statuses[$request['status']] ?? $request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($request['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="maintenance/view.php?id=<?php echo $request['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
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

