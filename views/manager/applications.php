<?php
/**
 * Danh sách đơn đăng ký - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/applications.php';
require_once __DIR__ . '/../../functions/registration_periods.php';
require_once __DIR__ . '/../../functions/rooms.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lọc theo status và đợt đăng ký
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;
$filterPeriod = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;

$applications = getAllApplications($filterStatus, $filterPeriod);
$statuses = getApplicationStatuses();
$roomTypes = getRoomTypes();
$periods = getAllRegistrationPeriods();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt Đơn Đăng ký - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="applications.php">Duyệt đơn</a>
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
            <h2><i class="bi bi-file-earmark-check me-2"></i>Duyệt Đơn Đăng ký</h2>
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

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="applications.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Lọc theo trạng thái</label>
                        <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($filterStatus == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="period_id" class="form-label">Lọc theo đợt đăng ký</label>
                        <select class="form-select" id="period_id" name="period_id" onchange="this.form.submit()">
                            <option value="">Tất cả đợt</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['id']; ?>" <?php echo ($filterPeriod == $period['id']) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($period['period_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="applications.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách đơn đăng ký -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách đơn đăng ký</h5>
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có đơn đăng ký nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Mã SV</th>
                                    <th class="text-nowrap">Họ tên</th>
                                    <th class="text-nowrap">Đợt đăng ký</th>
                                    <th class="text-nowrap">Ngày đăng ký</th>
                                    <th class="text-nowrap">Loại phòng</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap">Người duyệt</th>
                                    <th class="text-nowrap text-center" style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($app['student_code']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($app['student_name']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($app['period_name'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($app['application_date']); ?></td>
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
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($app['approved_by_name'] ?: '-'); ?></td>
                                        <td class="text-center align-middle">
                                            <a href="applications/view.php?id=<?php echo $app['id']; ?>" 
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

