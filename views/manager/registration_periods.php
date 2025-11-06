<?php
/**
 * Danh sách đợt đăng ký - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/registration_periods.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lọc theo status
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;
$periods = getAllRegistrationPeriods($filterStatus);
$statuses = getRegistrationPeriodStatuses();

// Tự động cập nhật status cho tất cả đợt đăng ký (chỉ khi đã qua ngày kết thúc)
// Không tự động cập nhật nếu Manager đã set thủ công
$today = date('Y-m-d');
foreach ($periods as $period) {
    // Chỉ tự động cập nhật nếu đã qua ngày kết thúc và status chưa phải closed
    if ($today > $period['end_date'] && $period['status'] != 'closed') {
        updateRegistrationPeriodStatus($period['id']);
    }
}

// Lấy lại danh sách sau khi cập nhật
$periods = getAllRegistrationPeriods($filterStatus);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đợt Đăng ký - Quản lý KTX</title>
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
                <a class="nav-link active" href="registration_periods.php">Đợt đăng ký</a>
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
            <h2><i class="bi bi-calendar-event me-2"></i>Quản lý Đợt Đăng ký</h2>
            <a href="registration_periods/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tạo đợt đăng ký mới
            </a>
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
                <form method="GET" action="registration_periods.php" class="row g-3">
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
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="registration_periods.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách đợt đăng ký -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách đợt đăng ký</h5>
            </div>
            <div class="card-body">
                <?php if (empty($periods)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có đợt đăng ký nào. Hãy tạo đợt đăng ký mới!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Tên đợt</th>
                                    <th class="text-nowrap">Học kỳ</th>
                                    <th class="text-nowrap">Năm học</th>
                                    <th class="text-nowrap">Ngày bắt đầu</th>
                                    <th class="text-nowrap">Ngày kết thúc</th>
                                    <th class="text-nowrap">Số phòng</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap">Người tạo</th>
                                    <th class="text-nowrap text-center" style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($period['period_name']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($period['semester'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($period['academic_year'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($period['start_date']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($period['end_date']); ?></td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo $period['total_rooms_available'] ? $period['total_rooms_available'] : '-'; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $statusClass = [
                                                'upcoming' => 'bg-secondary',
                                                'open' => 'bg-success',
                                                'closed' => 'bg-dark'
                                            ];
                                            $statusLabel = $statuses[$period['status']] ?? $period['status'];
                                            $class = $statusClass[$period['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($period['created_by_name'] ?: 'N/A'); ?></td>
                                        <td class="text-center align-middle">
                                            <a href="registration_periods/edit.php?id=<?php echo $period['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $period['id']; ?>, '<?php echo escapeHtml($period['period_name']); ?>')"
                                                    title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Form xóa ẩn -->
    <form id="deleteForm" method="POST" action="../../handle/registration_periods_process.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa đợt đăng ký "' + name + '" không?\n\nLưu ý: Chỉ có thể xóa khi đợt đăng ký chưa có đơn đăng ký nào.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

