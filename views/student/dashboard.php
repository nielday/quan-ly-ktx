<?php
/**
 * Dashboard Student
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/applications.php';
require_once __DIR__ . '/../../functions/invoices.php';
require_once __DIR__ . '/../../functions/payments.php';
require_once __DIR__ . '/../../functions/room_transfers.php';
require_once __DIR__ . '/../../functions/maintenance.php';
require_once __DIR__ . '/../../functions/student_room.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../../handle/logout_process.php');
    exit;
}

// Lấy thông tin phòng hiện tại
$roomInfo = getStudentRoomInfo($student['id']);
$currentRoom = $roomInfo ? $roomInfo['room'] : null;

// Lấy thống kê hóa đơn
$allInvoices = getInvoicesByStudentId($student['id']);
$pendingInvoices = array_filter($allInvoices, function($inv) {
    return $inv['status'] === 'pending';
});
$overdueInvoices = array_filter($allInvoices, function($inv) {
    return $inv['status'] === 'overdue';
});
$totalDebt = 0;
foreach (array_merge($pendingInvoices, $overdueInvoices) as $inv) {
    $totalDebt += floatval($inv['total_amount']);
}

// Lấy thống kê thanh toán
$allPayments = getPaymentsByStudentId($student['id']);
$pendingPayments = array_filter($allPayments, function($p) {
    return $p['status'] === 'pending';
});

// Lấy thống kê yêu cầu chuyển phòng
$transferRequests = getAllRoomTransferRequests(['student_id' => $student['id']]);
$pendingTransfers = array_filter($transferRequests, function($t) {
    return $t['status'] === 'pending';
});

// Lấy thống kê yêu cầu sửa chữa
$allMaintenanceRequests = getAllMaintenanceRequests([]);
$maintenanceRequests = array_filter($allMaintenanceRequests, function($m) use ($student) {
    return $m['student_id'] == $student['id'];
});
$maintenanceRequests = array_values($maintenanceRequests);
$pendingMaintenance = array_filter($maintenanceRequests, function($m) {
    return $m['status'] === 'pending';
});

// Lấy số đơn đăng ký pending
$pendingApplications = getApplicationsByStudentId($student['id']);
$pendingAppCount = count(array_filter($pendingApplications, function($app) {
    return $app['status'] == 'pending';
}));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sinh viên - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }
        
        /* Sidebar */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            height: 100vh;
            max-height: 100vh;
            background: linear-gradient(180deg, #0dcaf0 0%, #0aa2c0 100%);
            color: white;
            transition: all 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        #sidebar.collapsed {
            margin-left: -260px;
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        
        #sidebar .sidebar-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        #sidebar .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 20px;
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        #sidebar .sidebar-menu::-webkit-scrollbar {
            width: 6px;
        }
        
        #sidebar .sidebar-menu::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }
        
        #sidebar .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        #sidebar .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        #sidebar .sidebar-menu > li {
            list-style: none;
        }
        
        #sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        #sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ffc107;
        }
        
        #sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #ffc107;
            font-weight: 600;
        }
        
        #sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        #sidebar .nav-link .badge {
            margin-left: auto;
            font-size: 0.7rem;
        }
        
        #sidebar .sidebar-heading {
            padding: 15px 20px 10px 20px;
            font-size: 0.7rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 700;
            letter-spacing: 1.2px;
            margin-top: 10px;
            margin-bottom: 5px;
            list-style: none;
        }
        
        #sidebar .sidebar-heading:first-child {
            margin-top: 0;
        }
        
        /* Content area */
        #content {
            width: 100%;
            padding: 0;
            min-height: 100vh;
            transition: all 0.3s;
            margin-left: 260px;
        }
        
        #content.expanded {
            margin-left: 0;
        }
        
        /* Topbar */
        .topbar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toggle-btn {
            background: #0dcaf0;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-btn:hover {
            background: #0aa2c0;
        }
        
        .main-content {
            padding: 25px;
        }
        
        /* Stats card */
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.primary {
            border-left-color: #0d6efd;
        }
        
        .stat-card.success {
            border-left-color: #198754;
        }
        
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        
        .stat-card.danger {
            border-left-color: #dc3545;
        }
        
        .stat-card.info {
            border-left-color: #0dcaf0;
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                margin-left: 0;
            }
            
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-building me-2"></i>Quản lý KTX</h4>
            <small>Student Panel</small>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-heading">THÔNG TIN</li>
            <li><a href="room.php" class="nav-link"><i class="bi bi-house-door"></i>Thông tin Phòng</a></li>
            <li><a href="profile.php" class="nav-link"><i class="bi bi-person-circle"></i>Thông tin Cá nhân</a></li>
            
            <li class="sidebar-heading">TÀI CHÍNH</li>
            <li>
                <a href="invoices.php" class="nav-link">
                    <i class="bi bi-receipt"></i>Hóa đơn
                    <?php if (count($pendingInvoices) > 0 || count($overdueInvoices) > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo count($pendingInvoices) + count($overdueInvoices); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="payments/history.php" class="nav-link">
                    <i class="bi bi-cash-coin"></i>Thanh toán
                    <?php if (count($pendingPayments) > 0): ?>
                        <span class="badge bg-info"><?php echo count($pendingPayments); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="payments/debt.php" class="nav-link"><i class="bi bi-exclamation-circle"></i>Công nợ</a></li>
            
            <li class="sidebar-heading">YÊU CẦU</li>
            <li>
                <a href="room_transfers.php" class="nav-link">
                    <i class="bi bi-arrow-left-right"></i>Chuyển phòng
                    <?php if (count($pendingTransfers) > 0): ?>
                        <span class="badge bg-info"><?php echo count($pendingTransfers); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="maintenance.php" class="nav-link">
                    <i class="bi bi-tools"></i>Sửa chữa
                    <?php if (count($pendingMaintenance) > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo count($pendingMaintenance); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="sidebar-heading">ĐĂNG KÝ</li>
            <li>
                <a href="applications/view.php" class="nav-link">
                    <i class="bi bi-file-earmark-check"></i>Đơn đăng ký
                    <?php if ($pendingAppCount > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo $pendingAppCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="sidebar-heading">KHÁC</li>
            <li><a href="violations.php" class="nav-link"><i class="bi bi-exclamation-triangle"></i>Vi phạm</a></li>
        </ul>
    </nav>
    
    <!-- Content -->
    <div id="content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="toggle-btn" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escapeHtml($currentUser['full_name'] ?? $currentUser['username']); ?>
                </span>
                <a class="btn btn-outline-danger btn-sm" href="../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="mb-4">Dashboard Overview</h2>
            
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

            <!-- Thông tin phòng đang ở -->
            <?php if ($currentRoom): ?>
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="bi bi-house-door me-2"></i>Phòng đang ở</h5>
                    <p class="mb-0">
                        <strong><?php echo escapeHtml($currentRoom['building_code'] . '-' . $currentRoom['room_code']); ?></strong> | 
                        Loại: <?php echo escapeHtml($currentRoom['room_type']); ?> | 
                        Sức chứa: <?php echo $currentRoom['capacity']; ?> người |
                        Đang ở: <?php echo $currentRoom['current_occupancy']; ?>/<?php echo $currentRoom['capacity']; ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Công nợ</h6>
                                    <h3 class="mb-0 text-danger"><?php echo number_format($totalDebt, 0, ',', '.'); ?>₫</h3>
                                    <small class="text-muted">
                                        <?php echo count($overdueInvoices); ?> quá hạn, 
                                        <?php echo count($pendingInvoices); ?> chờ thanh toán
                                    </small>
                                </div>
                                <div>
                                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Hóa đơn</h6>
                                    <h3 class="mb-0 text-warning"><?php echo count($allInvoices); ?></h3>
                                    <small class="text-muted">Tổng số hóa đơn</small>
                                </div>
                                <div>
                                    <i class="bi bi-receipt text-warning" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Chuyển phòng</h6>
                                    <h3 class="mb-0 text-info"><?php echo count($pendingTransfers); ?></h3>
                                    <small class="text-muted">Chờ duyệt</small>
                                </div>
                                <div>
                                    <i class="bi bi-arrow-left-right text-info" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Sửa chữa</h6>
                                    <h3 class="mb-0 text-warning"><?php echo count($pendingMaintenance); ?></h3>
                                    <small class="text-muted">Chờ xử lý</small>
                                </div>
                                <div>
                                    <i class="bi bi-tools text-warning" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div class="row g-4">
                <?php if (count($allInvoices) > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-receipt text-primary me-2"></i>Thống kê Hóa đơn</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo count($allInvoices); ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo count($pendingInvoices); ?></h4>
                                    <small class="text-muted">Chờ thanh toán</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-danger"><?php echo count($overdueInvoices); ?></h4>
                                    <small class="text-muted">Quá hạn</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo count($allInvoices) - count($pendingInvoices) - count($overdueInvoices); ?></h4>
                                    <small class="text-muted">Đã thanh toán</small>
                                </div>
                            </div>
                            <?php if (count($overdueInvoices) > 0 || count($pendingInvoices) > 0): ?>
                            <hr>
                            <div class="alert alert-<?php echo count($overdueInvoices) > 0 ? 'danger' : 'warning'; ?> py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                <?php if (count($overdueInvoices) > 0): ?>
                                    Có <strong><?php echo count($overdueInvoices); ?></strong> hóa đơn quá hạn. 
                                <?php else: ?>
                                    Có <strong><?php echo count($pendingInvoices); ?></strong> hóa đơn chờ thanh toán. 
                                <?php endif; ?>
                                <a href="invoices.php" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (count($transferRequests) > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-arrow-left-right text-info me-2"></i>Thống kê Chuyển phòng</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo count($transferRequests); ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo count($pendingTransfers); ?></h4>
                                    <small class="text-muted">Chờ duyệt</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo count(array_filter($transferRequests, function($t) { return $t['status'] === 'approved'; })); ?></h4>
                                    <small class="text-muted">Đã duyệt</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-danger"><?php echo count(array_filter($transferRequests, function($t) { return $t['status'] === 'rejected'; })); ?></h4>
                                    <small class="text-muted">Từ chối</small>
                                </div>
                            </div>
                            <?php if (count($pendingTransfers) > 0): ?>
                            <hr>
                            <div class="alert alert-info py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Có <strong><?php echo count($pendingTransfers); ?></strong> yêu cầu chờ duyệt. 
                                <a href="room_transfers.php?status=pending" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (count($maintenanceRequests) > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-tools text-warning me-2"></i>Thống kê Sửa chữa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo count($maintenanceRequests); ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo count($pendingMaintenance); ?></h4>
                                    <small class="text-muted">Chờ xử lý</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-info"><?php echo count(array_filter($maintenanceRequests, function($m) { return $m['status'] === 'in_progress'; })); ?></h4>
                                    <small class="text-muted">Đang sửa</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo count(array_filter($maintenanceRequests, function($m) { return $m['status'] === 'completed'; })); ?></h4>
                                    <small class="text-muted">Hoàn thành</small>
                                </div>
                            </div>
                            <?php if (count($pendingMaintenance) > 0): ?>
                            <hr>
                            <div class="alert alert-warning py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Có <strong><?php echo count($pendingMaintenance); ?></strong> yêu cầu chờ xử lý. 
                                <a href="maintenance.php?status=pending" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // Toggle sidebar
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                // Mobile: use overlay
                sidebar.classList.toggle('active');
                overlay.classList.toggle('show');
            } else {
                // Desktop: collapse sidebar
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });
        
        // Close sidebar when clicking overlay (mobile)
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('show');
        });
        
        // Close sidebar on window resize if changed to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('show');
            }
        });
        
        // Highlight active menu item
        const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
        document.querySelectorAll('#sidebar .nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage || link.getAttribute('href').includes(currentPage)) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>
