<?php
/**
 * Maintenance Requests List
 * Danh sách yêu cầu sửa chữa
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/maintenance.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy filters
$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['request_type'])) {
    $filters['request_type'] = $_GET['request_type'];
}
if (!empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

// Lấy danh sách yêu cầu
$requests = getAllMaintenanceRequests($filters);

// Lấy thống kê
$stats = getMaintenanceStatistics();

// Lấy các option cho filters
$requestTypes = getRequestTypes();
$priorityLevels = getPriorityLevels();
$statuses = getMaintenanceStatuses();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu sửa chữa - Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="maintenance.php">Yêu cầu sửa chữa</a>
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
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại Dashboard
            </a>
        </div>

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

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                        <small class="text-muted">Tổng</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h4 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h4>
                        <small class="text-muted">Chờ xử lý</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h4 class="mb-0 text-info"><?php echo $stats['in_progress']; ?></h4>
                        <small class="text-muted">Đang sửa</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="mb-0 text-success"><?php echo $stats['completed']; ?></h4>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-secondary">
                    <div class="card-body">
                        <h4 class="mb-0 text-secondary"><?php echo $stats['cancelled']; ?></h4>
                        <small class="text-muted">Đã hủy</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h4 class="mb-0 text-danger"><?php echo $stats['urgent']; ?></h4>
                        <small class="text-muted">Khẩn cấp</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($filters['status']) && $filters['status'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loại yêu cầu</label>
                        <select name="request_type" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($requestTypes as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($filters['request_type']) && $filters['request_type'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mức độ ưu tiên</label>
                        <select name="priority" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($priorityLevels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($filters['priority']) && $filters['priority'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="maintenance.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách yêu cầu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Danh sách yêu cầu 
                    <span class="badge bg-secondary"><?php echo count($requests); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>Không có yêu cầu sửa chữa nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Sinh viên</th>
                                    <th>Phòng</th>
                                    <th>Loại</th>
                                    <th>Mô tả</th>
                                    <th>Ưu tiên</th>
                                    <th>Trạng thái</th>
                                    <th>Phân công</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['id']; ?></td>
                                        <td>
                                            <div><?php echo escapeHtml($request['student_name']); ?></div>
                                            <small class="text-muted"><?php echo escapeHtml($request['student_code']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo escapeHtml($request['building_code']); ?>-<?php echo escapeHtml($request['room_code']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $typeIcons = [
                                                'electrical' => 'bi-lightning-charge text-warning',
                                                'plumbing' => 'bi-droplet text-primary',
                                                'furniture' => 'bi-box text-secondary',
                                                'other' => 'bi-three-dots text-muted'
                                            ];
                                            $icon = $typeIcons[$request['request_type']] ?? 'bi-tools';
                                            ?>
                                            <i class="bi <?php echo $icon; ?> me-1"></i>
                                            <?php echo $requestTypes[$request['request_type']] ?? $request['request_type']; ?>
                                        </td>
                                        <td>
                                            <small><?php echo escapeHtml(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $priorityColors = [
                                                'low' => 'secondary',
                                                'medium' => 'info',
                                                'high' => 'warning',
                                                'urgent' => 'danger'
                                            ];
                                            $color = $priorityColors[$request['priority']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo $priorityLevels[$request['priority']] ?? $request['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'secondary'
                                            ];
                                            $color = $statusColors[$request['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo $statuses[$request['status']] ?? $request['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['assigned_to_name']): ?>
                                                <small><?php echo escapeHtml($request['assigned_to_name']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Chưa phân công</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="maintenance/view.php?id=<?php echo $request['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Xem chi tiết">
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

