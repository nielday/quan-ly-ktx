<?php
/**
 * Báo cáo tổng hợp - Admin
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/users.php';
require_once __DIR__ . '/../../functions/reports.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

$currentUser = getCurrentUser();

// Lấy tham số filter
$filterMonth = $_GET['month'] ?? null;

// Lấy thống kê
$userStats = getUserStatistics();
$roomStats = getRoomStatistics();
$studentStats = getStudentStatistics();
$financialStats = getFinancialStatistics($filterMonth);
$revenueByMonth = getRevenueByMonth(6);
$buildingStats = getBuildingStatistics();

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo tổng hợp - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="users.php">Tài khoản</a>
                <a class="nav-link active" href="reports.php">Báo cáo</a>
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
            <h2><i class="bi bi-file-bar-graph me-2"></i>Báo cáo Tổng hợp Hệ thống</h2>
            <form method="GET" class="d-flex gap-2">
                <input type="month" name="month" class="form-control" value="<?php echo $filterMonth ?? date('Y-m'); ?>" style="width: 200px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </a>
            </form>
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

        <!-- Thống kê Tài khoản -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Thống kê Tài khoản</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <h4 class="text-primary"><?php echo $userStats['total']; ?></h4>
                        <small class="text-muted">Tổng số</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <h4 class="text-danger"><?php echo $userStats['admin']; ?></h4>
                        <small class="text-muted">Admin</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <h4 class="text-success"><?php echo $userStats['manager']; ?></h4>
                        <small class="text-muted">Manager</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <h4 class="text-info"><?php echo $userStats['student']; ?></h4>
                        <small class="text-muted">Student</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <h4 class="text-success"><?php echo $userStats['active']; ?></h4>
                        <small class="text-muted">Hoạt động</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <h4 class="text-secondary"><?php echo $userStats['inactive']; ?></h4>
                        <small class="text-muted">Không hoạt động</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê Phòng -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-door-open me-2"></i>Thống kê Phòng</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h4 class="text-primary"><?php echo $roomStats['total_rooms']; ?></h4>
                        <small class="text-muted">Tổng số phòng</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-success"><?php echo $roomStats['available_rooms']; ?></h4>
                        <small class="text-muted">Phòng trống</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-warning"><?php echo $roomStats['occupied_rooms']; ?></h4>
                        <small class="text-muted">Phòng đã ở</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-danger"><?php echo $roomStats['maintenance_rooms']; ?></h4>
                        <small class="text-muted">Phòng sửa chữa</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 text-center">
                        <h5 class="text-muted">Tỷ lệ sử dụng</h5>
                        <h2 class="text-primary"><?php echo number_format($roomStats['occupancy_rate'], 1); ?>%</h2>
                        <small class="text-muted">
                            <?php echo $roomStats['total_occupancy']; ?> / <?php echo $roomStats['total_capacity']; ?> người
                        </small>
                    </div>
                    <div class="col-md-6">
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar" 
                                 role="progressbar" 
                                 style="width: <?php echo $roomStats['occupancy_rate']; ?>%"
                                 aria-valuenow="<?php echo $roomStats['occupancy_rate']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo number_format($roomStats['occupancy_rate'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê Sinh viên -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Thống kê Sinh viên</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h4 class="text-primary"><?php echo $studentStats['total_students']; ?></h4>
                        <small class="text-muted">Tổng số</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-success"><?php echo $studentStats['active_students']; ?></h4>
                        <small class="text-muted">Hoạt động</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-warning"><?php echo $studentStats['inactive_students']; ?></h4>
                        <small class="text-muted">Không hoạt động</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-secondary"><?php echo $studentStats['graduated_students']; ?></h4>
                        <small class="text-muted">Đã tốt nghiệp</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê Tài chính -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Thống kê Tài chính</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h4 class="text-primary"><?php echo $financialStats['invoices']['total_invoices'] ?? 0; ?></h4>
                        <small class="text-muted">Tổng hóa đơn</small>
                        <div class="mt-1">
                            <small class="text-muted">
                                <?php echo number_format($financialStats['invoices']['total_amount'] ?? 0, 0, ',', '.'); ?>₫
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-success"><?php echo $financialStats['invoices']['paid_invoices'] ?? 0; ?></h4>
                        <small class="text-muted">Đã thanh toán</small>
                        <div class="mt-1">
                            <small class="text-success">
                                <?php echo number_format($financialStats['invoices']['paid_amount'] ?? 0, 0, ',', '.'); ?>₫
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-warning"><?php echo $financialStats['invoices']['pending_invoices'] ?? 0; ?></h4>
                        <small class="text-muted">Chờ thanh toán</small>
                        <div class="mt-1">
                            <small class="text-warning">
                                <?php echo number_format($financialStats['invoices']['unpaid_amount'] ?? 0, 0, ',', '.'); ?>₫
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 class="text-danger"><?php echo $financialStats['invoices']['overdue_invoices'] ?? 0; ?></h4>
                        <small class="text-muted">Quá hạn</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h5 class="text-muted">Doanh thu tổng</h5>
                        <h2 class="text-success">
                            <?php echo number_format($financialStats['revenue'] ?? 0, 0, ',', '.'); ?>₫
                        </h2>
                        <small class="text-muted">
                            <?php echo $filterMonth ? 'Tháng ' . $filterMonth : 'Tổng doanh thu'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doanh thu theo tháng -->
        <?php if (!empty($revenueByMonth)): ?>
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Doanh thu 6 tháng gần đây</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Số giao dịch</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueByMonth as $month): ?>
                            <tr>
                                <td><strong><?php echo date('m/Y', strtotime($month['month'] . '-01')); ?></strong></td>
                                <td><?php echo $month['payment_count']; ?></td>
                                <td><strong class="text-success"><?php echo number_format($month['revenue'], 0, ',', '.'); ?>₫</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Thống kê theo tòa nhà -->
        <?php if (!empty($buildingStats)): ?>
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Thống kê theo Tòa nhà</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tòa nhà</th>
                                <th>Tổng phòng</th>
                                <th>Phòng trống</th>
                                <th>Phòng đã ở</th>
                                <th>Sức chứa</th>
                                <th>Số người đang ở</th>
                                <th>Tỷ lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buildingStats as $building): ?>
                            <tr>
                                <td>
                                    <strong><?php echo escapeHtml($building['building_code']); ?></strong><br>
                                    <small class="text-muted"><?php echo escapeHtml($building['building_name']); ?></small>
                                </td>
                                <td><?php echo $building['total_rooms']; ?></td>
                                <td><span class="badge bg-success"><?php echo $building['available_rooms']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $building['occupied_rooms']; ?></span></td>
                                <td><?php echo $building['total_capacity']; ?></td>
                                <td><?php echo $building['total_occupancy']; ?></td>
                                <td>
                                    <?php
                                    $rate = $building['occupancy_rate'];
                                    $badgeClass = 'bg-secondary';
                                    if ($rate >= 90) {
                                        $badgeClass = 'bg-danger';
                                    } elseif ($rate >= 70) {
                                        $badgeClass = 'bg-warning';
                                    } elseif ($rate >= 50) {
                                        $badgeClass = 'bg-info';
                                    } elseif ($rate > 0) {
                                        $badgeClass = 'bg-success';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo number_format($rate, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

