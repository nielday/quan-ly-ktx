<?php
/**
 * Dashboard Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/violations.php';
require_once __DIR__ . '/../../functions/room_transfers.php';
require_once __DIR__ . '/../../functions/maintenance.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy thống kê vi phạm
$violationStats = getViolationStatistics();

// Lấy thống kê chuyển phòng
$transferStats = getRoomTransferStatistics();

// Lấy thống kê yêu cầu sửa chữa
$maintenanceStats = getMaintenanceStatistics();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - Quản lý KTX</title>
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
            background: linear-gradient(180deg, #198754 0%, #157347 100%);
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
            background: #198754;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-btn:hover {
            background: #157347;
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
            <small>Manager Panel</small>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-heading">CƠ SỞ VẬT CHẤT</li>
            <li><a href="buildings.php" class="nav-link"><i class="bi bi-building"></i>Tòa nhà</a></li>
            <li><a href="rooms.php" class="nav-link"><i class="bi bi-door-open"></i>Phòng ở</a></li>
            <li><a href="pricing.php" class="nav-link"><i class="bi bi-currency-dollar"></i>Đơn giá</a></li>
            <li><a href="services.php" class="nav-link"><i class="bi bi-gear"></i>Dịch vụ</a></li>
            
            <li class="sidebar-heading">SINH VIÊN</li>
            <li><a href="registration_periods.php" class="nav-link"><i class="bi bi-calendar-event"></i>Đợt đăng ký</a></li>
            <li><a href="students.php" class="nav-link"><i class="bi bi-people"></i>Sinh viên</a></li>
            <li><a href="applications.php" class="nav-link"><i class="bi bi-file-earmark-check"></i>Duyệt đơn</a></li>
            <li><a href="contracts.php" class="nav-link"><i class="bi bi-file-earmark-text"></i>Hợp đồng</a></li>
            
            <li class="sidebar-heading">TÀI CHÍNH</li>
            <li><a href="invoices.php" class="nav-link"><i class="bi bi-receipt"></i>Hóa đơn</a></li>
            <li><a href="payments.php" class="nav-link"><i class="bi bi-cash-coin"></i>Thanh toán</a></li>
            
            <li class="sidebar-heading">QUẢN LÝ</li>
            <li>
                <a href="violations.php" class="nav-link">
                    <i class="bi bi-exclamation-triangle"></i>Vi phạm
                    <?php if ($violationStats['pending'] > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo $violationStats['pending']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="room_transfers.php" class="nav-link">
                    <i class="bi bi-arrow-left-right"></i>Chuyển phòng
                    <?php if ($transferStats['pending'] > 0): ?>
                        <span class="badge bg-info"><?php echo $transferStats['pending']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="maintenance.php" class="nav-link">
                    <i class="bi bi-tools"></i>Yêu cầu sửa chữa
                    <?php if ($maintenanceStats['pending'] > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo $maintenanceStats['pending']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="reports.php" class="nav-link">
                    <i class="bi bi-file-bar-graph"></i>Báo cáo
                </a>
            </li>
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

            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Sinh viên</h6>
                                    <h3 class="mb-0 text-primary">--</h3>
                                    <small class="text-muted">Đang ở KTX</small>
                                </div>
                                <div>
                                    <i class="bi bi-people text-primary" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Phòng trống</h6>
                                    <h3 class="mb-0 text-success">--</h3>
                                    <small class="text-muted">Có thể sử dụng</small>
                                </div>
                                <div>
                                    <i class="bi bi-door-open text-success" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Vi phạm</h6>
                                    <h3 class="mb-0 text-warning"><?php echo $violationStats['pending']; ?></h3>
                                    <small class="text-muted">Chưa xử lý</small>
                                </div>
                                <div>
                                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Chuyển phòng</h6>
                                    <h3 class="mb-0 text-info"><?php echo $transferStats['pending']; ?></h3>
                                    <small class="text-muted">Chờ duyệt</small>
                                </div>
                                <div>
                                    <i class="bi bi-arrow-left-right text-info" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Sửa chữa</h6>
                                    <h3 class="mb-0 text-warning"><?php echo $maintenanceStats['pending']; ?></h3>
                                    <small class="text-muted">Chờ xử lý</small>
                                </div>
                                <div>
                                    <i class="bi bi-tools text-warning" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($maintenanceStats['urgent'] > 0): ?>
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Khẩn cấp</h6>
                                    <h3 class="mb-0 text-danger"><?php echo $maintenanceStats['urgent']; ?></h3>
                                    <small class="text-muted">Cần xử lý gấp</small>
                                </div>
                                <div>
                                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="row g-4">
                <?php if ($violationStats['total'] > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Thống kê Vi phạm</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo $violationStats['total']; ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo $violationStats['pending']; ?></h4>
                                    <small class="text-muted">Chưa xử lý</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo $violationStats['resolved']; ?></h4>
                                    <small class="text-muted">Đã xử lý</small>
                                </div>
                                <div class="col-3">
                                    <h6 class="text-danger"><?php echo number_format($violationStats['total_fine_amount']/1000, 0); ?>K</h6>
                                    <small class="text-muted">Tiền phạt</small>
                                </div>
                            </div>
                            <?php if ($violationStats['pending'] > 0): ?>
                            <hr>
                            <div class="alert alert-warning py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Có <strong><?php echo $violationStats['pending']; ?></strong> vi phạm cần xử lý. 
                                <a href="violations.php?status=pending" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($transferStats['total'] > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-arrow-left-right text-info me-2"></i>Thống kê Chuyển phòng</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo $transferStats['total']; ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo $transferStats['pending']; ?></h4>
                                    <small class="text-muted">Chờ duyệt</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo $transferStats['approved']; ?></h4>
                                    <small class="text-muted">Đã duyệt</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-danger"><?php echo $transferStats['rejected']; ?></h4>
                                    <small class="text-muted">Từ chối</small>
                                </div>
                            </div>
                            <?php if ($transferStats['pending'] > 0): ?>
                            <hr>
                            <div class="alert alert-info py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Có <strong><?php echo $transferStats['pending']; ?></strong> yêu cầu chờ duyệt. 
                                <a href="room_transfers.php?status=pending" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($maintenanceStats['total'] > 0): ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-tools text-warning me-2"></i>Thống kê Sửa chữa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?php echo $maintenanceStats['total']; ?></h4>
                                    <small class="text-muted">Tổng</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning"><?php echo $maintenanceStats['pending']; ?></h4>
                                    <small class="text-muted">Chờ xử lý</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-info"><?php echo $maintenanceStats['in_progress']; ?></h4>
                                    <small class="text-muted">Đang sửa</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success"><?php echo $maintenanceStats['completed']; ?></h4>
                                    <small class="text-muted">Hoàn thành</small>
                                </div>
                            </div>
                            <?php if ($maintenanceStats['urgent'] > 0): ?>
                            <hr>
                            <div class="alert alert-danger py-2 px-3 mb-0">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                Có <strong><?php echo $maintenanceStats['urgent']; ?></strong> yêu cầu khẩn cấp cần xử lý ngay! 
                                <a href="maintenance.php?priority=urgent" class="alert-link">Xem ngay →</a>
                            </div>
                            <?php elseif ($maintenanceStats['pending'] > 0): ?>
                            <hr>
                            <div class="alert alert-warning py-2 px-3 mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Có <strong><?php echo $maintenanceStats['pending']; ?></strong> yêu cầu chờ xử lý. 
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
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>

