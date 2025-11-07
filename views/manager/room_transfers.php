<?php
/**
 * Danh sách yêu cầu chuyển phòng - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/room_transfers.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy filters
$statusFilter = $_GET['status'] ?? null;

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;

$requests = getAllRoomTransferRequests($filters);
$statuses = getRoomTransferStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu Chuyển phòng - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="room_transfers.php">Chuyển phòng</a>
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
            <h2><i class="bi bi-arrow-left-right me-2"></i>Quản lý Yêu cầu Chuyển phòng</h2>
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

        <!-- Bộ lọc -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="room_transfers.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($statusFilter == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="room_transfers.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Thống kê nhanh -->
        <?php
        $stats = getRoomTransferStatistics();
        ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Tổng yêu cầu</h5>
                        <h2 class="text-primary"><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Chờ duyệt</h5>
                        <h2 class="text-warning"><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Đã duyệt</h5>
                        <h2 class="text-success"><?php echo $stats['approved']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Đã từ chối</h5>
                        <h2 class="text-danger"><?php echo $stats['rejected']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách yêu cầu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách Yêu cầu Chuyển phòng</h5>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Không có yêu cầu chuyển phòng nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày tạo</th>
                                    <th>Sinh viên</th>
                                    <th>Phòng hiện tại</th>
                                    <th>Phòng muốn chuyển</th>
                                    <th>Trạng thái</th>
                                    <th>Người duyệt</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($request['created_at']); ?></td>
                                        <td>
                                            <strong><?php echo escapeHtml($request['student_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo escapeHtml($request['student_code']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php 
                                                if ($request['current_building_code']) {
                                                    echo escapeHtml($request['current_building_code'] . ' - ');
                                                }
                                                echo escapeHtml($request['current_room_code']); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['requested_room_id']): ?>
                                                <span class="badge bg-info">
                                                    <?php 
                                                    if ($request['requested_building_code']) {
                                                        echo escapeHtml($request['requested_building_code'] . ' - ');
                                                    }
                                                    echo escapeHtml($request['requested_room_code']); 
                                                    ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa chọn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger'
                                            ];
                                            $badge = $statusBadge[$request['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo escapeHtml($statuses[$request['status']] ?? $request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escapeHtml($request['reviewed_by_name'] ?? '-'); ?></td>
                                        <td class="text-center">
                                            <a href="room_transfers/view.php?id=<?php echo $request['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i> Xem
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

        <?php if ($stats['pending'] > 0): ?>
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-circle me-2"></i>
            Có <strong><?php echo $stats['pending']; ?></strong> yêu cầu chờ duyệt. 
            <a href="room_transfers.php?status=pending" class="alert-link">Xem ngay</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

