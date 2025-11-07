<?php
/**
 * Danh sách vi phạm - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/violations.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy filters
$statusFilter = $_GET['status'] ?? null;
$violationTypeFilter = $_GET['violation_type'] ?? null;
$monthFilter = $_GET['month'] ?? null;

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($violationTypeFilter) $filters['violation_type'] = $violationTypeFilter;
if ($monthFilter) $filters['month'] = $monthFilter;

$violations = getAllViolations($filters);
$violationTypes = getViolationTypes();
$violationStatuses = getViolationStatuses();
$penaltyTypes = getPenaltyTypes();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Vi phạm - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge-fine { background-color: #dc3545; }
        .badge-warning-type { background-color: #ffc107; color: #000; }
        .badge-suspension { background-color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="violations.php">Vi phạm</a>
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Quản lý Vi phạm</h2>
            <a href="violations/create.php" class="btn btn-danger">
                <i class="bi bi-plus-circle me-2"></i>Ghi nhận vi phạm
            </a>
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
                <form method="GET" action="violations.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($violationStatuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($statusFilter == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="violation_type" class="form-label">Loại vi phạm</label>
                        <select class="form-select" id="violation_type" name="violation_type">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($violationTypes as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($violationTypeFilter == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="month" class="form-label">Tháng vi phạm</label>
                        <input type="month" class="form-control" id="month" name="month" 
                               value="<?php echo escapeHtml($monthFilter ?? ''); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="violations.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Thống kê nhanh -->
        <?php
        $stats = getViolationStatistics();
        ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Tổng vi phạm</h5>
                        <h2 class="text-primary"><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Chưa xử lý</h5>
                        <h2 class="text-warning"><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Đã xử lý</h5>
                        <h2 class="text-success"><?php echo $stats['resolved']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Tổng tiền phạt</h5>
                        <h2 class="text-danger"><?php echo number_format($stats['total_fine_amount'], 0, ',', '.'); ?> VNĐ</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách vi phạm -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách Vi phạm</h5>
            </div>
            <div class="card-body">
                <?php if (empty($violations)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Không có vi phạm nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày vi phạm</th>
                                    <th>Sinh viên</th>
                                    <th>Phòng</th>
                                    <th>Loại vi phạm</th>
                                    <th>Hình phạt</th>
                                    <th class="text-end">Tiền phạt</th>
                                    <th>Trạng thái</th>
                                    <th>Người báo cáo</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($violations as $violation): ?>
                                    <tr>
                                        <td><?php echo formatDate($violation['violation_date']); ?></td>
                                        <td>
                                            <strong><?php echo escapeHtml($violation['student_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo escapeHtml($violation['student_code']); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($violation['building_code']) {
                                                echo escapeHtml($violation['building_code'] . ' - ');
                                            }
                                            echo escapeHtml($violation['room_code']); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo escapeHtml($violationTypes[$violation['violation_type']] ?? $violation['violation_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $penaltyBadge = [
                                                'warning' => 'badge-warning-type',
                                                'fine' => 'badge-fine',
                                                'suspension' => 'badge-suspension'
                                            ];
                                            $badgeClass = $penaltyBadge[$violation['penalty_type']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo escapeHtml($penaltyTypes[$violation['penalty_type']] ?? $violation['penalty_type']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($violation['penalty_amount'] > 0): ?>
                                                <strong class="text-danger">
                                                    <?php echo number_format($violation['penalty_amount'], 0, ',', '.'); ?> VNĐ
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = [
                                                'pending' => 'warning',
                                                'resolved' => 'success'
                                            ];
                                            $badge = $statusBadge[$violation['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo escapeHtml($violationStatuses[$violation['status']] ?? $violation['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escapeHtml($violation['reported_by_name'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <a href="violations/view.php?id=<?php echo $violation['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($violation['status'] == 'pending'): ?>
                                                <a href="violations/edit.php?id=<?php echo $violation['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Sửa/Xử lý">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../../handle/violations_process.php?action=delete&id=<?php echo $violation['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa vi phạm này?')"
                                                   title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
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

