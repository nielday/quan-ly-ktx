<?php
/**
 * Maintenance Request Detail View
 * Xem chi tiết và xử lý yêu cầu sửa chữa
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/maintenance.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID yêu cầu
$requestId = $_GET['id'] ?? null;
if (!$requestId) {
    setErrorMessage("Không tìm thấy yêu cầu");
    redirect('../maintenance.php');
    exit;
}

// Lấy thông tin yêu cầu
$request = getMaintenanceRequestById($requestId);
if (!$request) {
    setErrorMessage("Yêu cầu không tồn tại");
    redirect('../maintenance.php');
    exit;
}

// Lấy các option
$requestTypes = getRequestTypes();
$priorityLevels = getPriorityLevels();
$statuses = getMaintenanceStatuses();

// Lấy danh sách manager để phân công
$conn = getDbConnection();
$sql = "SELECT id, full_name, username FROM users WHERE role = 'manager' AND status = 'active' ORDER BY full_name ASC";
$result = mysqli_query($conn, $sql);
$managers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $managers[] = $row;
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết yêu cầu sửa chữa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link active" href="../maintenance.php">Yêu cầu sửa chữa</a>
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
            <h2><i class="bi bi-tools me-2"></i>Chi tiết yêu cầu sửa chữa #<?php echo $request['id']; ?></h2>
            <a href="../maintenance.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
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

        <div class="row">
            <!-- Thông tin yêu cầu -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin yêu cầu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Loại yêu cầu</label>
                                <div>
                                    <?php
                                    $typeIcons = [
                                        'electrical' => 'bi-lightning-charge text-warning',
                                        'plumbing' => 'bi-droplet text-primary',
                                        'furniture' => 'bi-box text-secondary',
                                        'other' => 'bi-three-dots text-muted'
                                    ];
                                    $icon = $typeIcons[$request['request_type']] ?? 'bi-tools';
                                    ?>
                                    <i class="bi <?php echo $icon; ?> me-2"></i>
                                    <strong><?php echo $requestTypes[$request['request_type']] ?? $request['request_type']; ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Mức độ ưu tiên</label>
                                <div>
                                    <?php
                                    $priorityColors = [
                                        'low' => 'secondary',
                                        'medium' => 'info',
                                        'high' => 'warning',
                                        'urgent' => 'danger'
                                    ];
                                    $color = $priorityColors[$request['priority']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?> fs-6">
                                        <?php echo $priorityLevels[$request['priority']] ?? $request['priority']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Trạng thái</label>
                                <div>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'secondary'
                                    ];
                                    $color = $statusColors[$request['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?> fs-6">
                                        <?php echo $statuses[$request['status']] ?? $request['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Phân công</label>
                                <div>
                                    <?php if ($request['assigned_to_name']): ?>
                                        <strong><?php echo escapeHtml($request['assigned_to_name']); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa phân công</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small">Mô tả chi tiết</label>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br(escapeHtml($request['description'])); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="text-muted small">Ngày tạo</label>
                                <div><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Cập nhật lần cuối</label>
                                <div><?php echo date('d/m/Y H:i', strtotime($request['updated_at'])); ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Hoàn thành</label>
                                <div>
                                    <?php if ($request['completed_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($request['completed_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa hoàn thành</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin sinh viên và phòng -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin sinh viên & Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Sinh viên</h6>
                                <div class="mb-2">
                                    <strong><?php echo escapeHtml($request['student_name']); ?></strong>
                                </div>
                                <div class="text-muted small">
                                    <div>MSSV: <?php echo escapeHtml($request['student_code']); ?></div>
                                    <div>SĐT: <?php echo escapeHtml($request['student_phone']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">Phòng</h6>
                                <div class="mb-2">
                                    <strong><?php echo escapeHtml($request['building_code']); ?> - <?php echo escapeHtml($request['room_code']); ?></strong>
                                </div>
                                <div class="text-muted small">
                                    <div><?php echo escapeHtml($request['building_name']); ?></div>
                                    <div>Tầng: <?php echo $request['floor']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="col-lg-4">
                <!-- Cập nhật trạng thái -->
                <?php if ($request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Cập nhật trạng thái</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/maintenance_process.php" onsubmit="return confirm('Xác nhận cập nhật trạng thái?');">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Chọn trạng thái mới</label>
                                <select name="status" class="form-select" required>
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <?php if ($value !== $request['status']): ?>
                                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i>Cập nhật
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Phân công -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Phân công & Ưu tiên</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/maintenance_process.php">
                            <input type="hidden" name="action" value="assign">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Phân công cho</label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">-- Chọn người --</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?php echo $manager['id']; ?>" 
                                            <?php echo ($request['assigned_to'] == $manager['id']) ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($manager['full_name'] ?? $manager['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mức độ ưu tiên</label>
                                <select name="priority" class="form-select">
                                    <?php foreach ($priorityLevels as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                            <?php echo ($request['priority'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-info w-100">
                                <i class="bi bi-person-check me-1"></i>Cập nhật
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hủy yêu cầu -->
                <?php if ($request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0">Hủy yêu cầu</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Hủy yêu cầu này nếu không còn cần thiết.</p>
                        <form method="POST" action="../../../handle/maintenance_process.php" 
                              onsubmit="return confirm('Xác nhận hủy yêu cầu sửa chữa này?');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-x-circle me-1"></i>Hủy yêu cầu
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

