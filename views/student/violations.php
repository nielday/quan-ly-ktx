<?php
/**
 * Vi phạm - Student
 * Xem các vi phạm đã mắc phải
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/violations.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('dashboard.php');
    exit;
}

// Lấy filters
$filterStatus = $_GET['status'] ?? null;
$filterType = $_GET['type'] ?? null;
$filterMonth = $_GET['month'] ?? null;

// Lấy danh sách vi phạm của sinh viên
$filters = ['student_id' => $student['id']];
if ($filterStatus) {
    $filters['status'] = $filterStatus;
}
if ($filterType) {
    $filters['violation_type'] = $filterType;
}
if ($filterMonth) {
    $filters['month'] = $filterMonth;
}

$violations = getAllViolations($filters);

// Tính tổng tiền phạt
$totalFine = 0;
foreach ($violations as $violation) {
    $totalFine += floatval($violation['penalty_amount'] ?? 0);
}

// Lấy danh sách loại vi phạm và status
$violationTypes = getViolationTypes();
$violationStatuses = getViolationStatuses();

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vi phạm - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Vi phạm của tôi</h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
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

        <!-- Thống kê -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-left-danger">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Tổng số vi phạm</h6>
                        <h3 class="mb-0 text-danger"><?php echo count($violations); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Chưa xử lý</h6>
                        <h3 class="mb-0 text-warning">
                            <?php echo count(array_filter($violations, function($v) { return $v['status'] === 'pending'; })); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-danger">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Tổng tiền phạt</h6>
                        <h3 class="mb-0 text-danger"><?php echo number_format($totalFine, 0, ',', '.'); ?>₫</h3>
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
                            <?php foreach ($violationStatuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loại vi phạm</label>
                        <select name="type" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($violationTypes as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filterType === $key ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tháng</label>
                        <input type="month" name="month" class="form-control" value="<?php echo $filterMonth ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Lọc
                            </button>
                            <a href="violations.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách vi phạm -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list me-2"></i>Danh sách vi phạm</h5>
            </div>
            <div class="card-body">
                <?php if (empty($violations)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>Bạn chưa có vi phạm nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ngày vi phạm</th>
                                    <th>Loại vi phạm</th>
                                    <th>Phòng</th>
                                    <th>Mô tả</th>
                                    <th>Hình thức xử lý</th>
                                    <th>Tiền phạt</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($violations as $violation): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($violation['violation_date'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($violation['violation_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo escapeHtml($violationTypes[$violation['violation_type']] ?? $violation['violation_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo escapeHtml($violation['building_code'] . '-' . $violation['room_code']); ?>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <?php echo nl2br(escapeHtml($violation['description'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $penaltyTypes = [
                                            'warning' => 'Cảnh báo',
                                            'fine' => 'Phạt tiền',
                                            'suspension' => 'Đình chỉ'
                                        ];
                                        echo escapeHtml($penaltyTypes[$violation['penalty_type']] ?? $violation['penalty_type']);
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($violation['penalty_amount'] > 0): ?>
                                            <strong class="text-danger">
                                                <?php echo number_format($violation['penalty_amount'], 0, ',', '.'); ?>₫
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = $violation['status'] === 'resolved' ? 'success' : 'warning';
                                        $statusLabel = $violationStatuses[$violation['status']] ?? $violation['status'];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo escapeHtml($statusLabel); ?>
                                        </span>
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

