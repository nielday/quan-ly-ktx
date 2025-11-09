<?php
/**
 * Báo cáo - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/reports.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy tham số filter
$filterMonth = $_GET['month'] ?? null;
$reportType = $_GET['type'] ?? 'all'; // all, rooms, finance, students

// Lấy thống kê
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
    <title>Báo cáo - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card.primary { border-left-color: #0d6efd; }
        .stat-card.success { border-left-color: #198754; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #0dcaf0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
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
            <h2><i class="bi bi-file-bar-graph me-2"></i>Báo cáo tổng hợp</h2>
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

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $reportType === 'all' || $reportType === 'rooms' ? 'active' : ''; ?>" 
                   data-bs-toggle="tab" href="#rooms" role="tab">
                    <i class="bi bi-door-open me-1"></i>Báo cáo Phòng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $reportType === 'finance' ? 'active' : ''; ?>" 
                   data-bs-toggle="tab" href="#finance" role="tab">
                    <i class="bi bi-cash-coin me-1"></i>Báo cáo Tài chính
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $reportType === 'students' ? 'active' : ''; ?>" 
                   data-bs-toggle="tab" href="#students" role="tab">
                    <i class="bi bi-people me-1"></i>Báo cáo Sinh viên
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Báo cáo Phòng -->
            <div class="tab-pane fade <?php echo $reportType === 'all' || $reportType === 'rooms' ? 'show active' : ''; ?>" id="rooms" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Tổng số phòng</h6>
                                <h3 class="mb-0 text-primary"><?php echo $roomStats['total_rooms']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Số phòng đang trống</h6>
                                <h3 class="mb-0 text-success"><?php echo $roomStats['available_rooms']; ?></h3>
                                <small class="text-muted">Chưa có người ở</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Phòng đã có người ở</h6>
                                <h3 class="mb-0 text-warning"><?php echo $roomStats['occupied_rooms']; ?></h3>
                                <small class="text-muted">Có ít nhất 1 người</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Phòng đang sửa</h6>
                                <h3 class="mb-0 text-danger"><?php echo $roomStats['maintenance_rooms']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Tỷ lệ sử dụng phòng</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <?php
                                    $rate = $roomStats['occupancy_rate'];
                                    $rateClass = 'text-secondary';
                                    $rateIcon = 'bi-circle';
                                    $progressClass = 'bg-secondary';
                                    if ($rate >= 90) {
                                        $rateClass = 'text-danger';
                                        $rateIcon = 'bi-exclamation-triangle-fill';
                                        $progressClass = 'bg-danger';
                                    } elseif ($rate >= 70) {
                                        $rateClass = 'text-warning';
                                        $rateIcon = 'bi-exclamation-circle-fill';
                                        $progressClass = 'bg-warning';
                                    } elseif ($rate >= 50) {
                                        $rateClass = 'text-info';
                                        $rateIcon = 'bi-info-circle-fill';
                                        $progressClass = 'bg-info';
                                    } elseif ($rate > 0) {
                                        $rateClass = 'text-success';
                                        $rateIcon = 'bi-check-circle-fill';
                                        $progressClass = 'bg-success';
                                    }
                                    ?>
                                    <div class="<?php echo $rateClass; ?>" style="font-size: 4rem; font-weight: bold;">
                                        <i class="bi <?php echo $rateIcon; ?>"></i>
                                    </div>
                                    <h1 class="<?php echo $rateClass; ?> mb-2" style="font-size: 3.5rem; font-weight: 700;">
                                        <?php echo number_format($rate, 1); ?>%
                                    </h1>
                                    <p class="text-muted mb-0">Tỷ lệ sử dụng phòng</p>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Tổng sức chứa:</span>
                                    <strong><?php echo $roomStats['total_capacity']; ?> người</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Số người đang ở:</span>
                                    <strong><?php echo $roomStats['total_occupancy']; ?> người</strong>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="progress" style="height: 25px; border-radius: 15px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="progress-bar <?php echo $progressClass; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $rate; ?>%"
                                             aria-valuenow="<?php echo $rate; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê theo tòa nhà -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Thống kê theo tòa nhà</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tòa nhà</th>
                                        <th>Tổng phòng</th>
                                        <th>Số phòng đang trống</th>
                                        <th>Phòng đã có người ở</th>
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
                                            <span class="badge <?php echo $badgeClass; ?> fs-6 px-3 py-2">
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
            </div>

            <!-- Báo cáo Tài chính -->
            <div class="tab-pane fade <?php echo $reportType === 'finance' ? 'show active' : ''; ?>" id="finance" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Tổng hóa đơn</h6>
                                <h3 class="mb-0 text-primary"><?php echo $financialStats['invoices']['total_invoices']; ?></h3>
                                <small class="text-muted">Tổng giá trị: <?php echo number_format($financialStats['invoices']['total_amount'], 0, ',', '.'); ?>₫</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Đã thanh toán</h6>
                                <h3 class="mb-0 text-success"><?php echo $financialStats['invoices']['paid_invoices']; ?></h3>
                                <small class="text-muted">Tổng: <?php echo number_format($financialStats['invoices']['paid_amount'], 0, ',', '.'); ?>₫</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Chờ thanh toán</h6>
                                <h3 class="mb-0 text-warning"><?php echo $financialStats['invoices']['pending_invoices']; ?></h3>
                                <small class="text-muted">Tổng: <?php echo number_format($financialStats['invoices']['unpaid_amount'], 0, ',', '.'); ?>₫</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Quá hạn</h6>
                                <h3 class="mb-0 text-danger"><?php echo $financialStats['invoices']['overdue_invoices']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Doanh thu</h5>
                            </div>
                            <div class="card-body">
                                <h2 class="text-success mb-0">
                                    <?php echo number_format($financialStats['revenue'], 0, ',', '.'); ?>₫
                                </h2>
                                <small class="text-muted">
                                    <?php echo $filterMonth ? 'Tháng ' . $filterMonth : 'Tổng doanh thu'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Thanh toán</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?php echo $financialStats['payments']['total_payments']; ?></h4>
                                        <small class="text-muted">Tổng giao dịch</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo $financialStats['payments']['confirmed_payments']; ?></h4>
                                        <small class="text-muted">Đã xác nhận</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doanh thu theo tháng -->
                <?php if (!empty($revenueByMonth)): ?>
                <div class="card">
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
            </div>

            <!-- Báo cáo Sinh viên -->
            <div class="tab-pane fade <?php echo $reportType === 'students' ? 'show active' : ''; ?>" id="students" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Tổng sinh viên</h6>
                                <h3 class="mb-0 text-primary"><?php echo $studentStats['total_students']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Đang hoạt động</h6>
                                <h3 class="mb-0 text-success"><?php echo $studentStats['active_students']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Không hoạt động</h6>
                                <h3 class="mb-0 text-warning"><?php echo $studentStats['inactive_students']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card info">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Mới trong tháng</h6>
                                <h3 class="mb-0 text-info"><?php echo $studentStats['new_students_this_month']; ?></h3>
                                <small class="text-muted">Tháng <?php echo date('m/Y'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Phân bố sinh viên</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="p-3">
                                    <h4 class="text-success"><?php echo $studentStats['active_students']; ?></h4>
                                    <p class="text-muted mb-0">Đang hoạt động</p>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $studentStats['total_students'] > 0 ? ($studentStats['active_students'] / $studentStats['total_students'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3">
                                    <h4 class="text-warning"><?php echo $studentStats['inactive_students']; ?></h4>
                                    <p class="text-muted mb-0">Không hoạt động</p>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $studentStats['total_students'] > 0 ? ($studentStats['inactive_students'] / $studentStats['total_students'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3">
                                    <h4 class="text-secondary"><?php echo $studentStats['graduated_students']; ?></h4>
                                    <p class="text-muted mb-0">Đã tốt nghiệp</p>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-secondary" role="progressbar" 
                                             style="width: <?php echo $studentStats['total_students'] > 0 ? ($studentStats['graduated_students'] / $studentStats['total_students'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

