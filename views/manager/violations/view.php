<?php
/**
 * Chi tiết vi phạm - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/violations.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$violationId = intval($_GET['id'] ?? 0);

if ($violationId <= 0) {
    setErrorMessage('ID vi phạm không hợp lệ!');
    redirect('../violations.php');
}

$violation = getViolationById($violationId);

if (!$violation) {
    setErrorMessage('Vi phạm không tồn tại!');
    redirect('../violations.php');
}

$violationTypes = getViolationTypes();
$penaltyTypes = getPenaltyTypes();
$violationStatuses = getViolationStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Vi phạm - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .violation-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .detail-card {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../violations.php">Vi phạm</a>
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Chi tiết Vi phạm</h2>
            <a href="../violations.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
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

        <!-- Header -->
        <div class="violation-header">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">THÔNG BÁO VI PHẠM</h3>
                    <p class="mb-1"><strong>Loại vi phạm:</strong> <?php echo escapeHtml($violationTypes[$violation['violation_type']] ?? $violation['violation_type']); ?></p>
                    <p class="mb-1"><strong>Ngày vi phạm:</strong> <?php echo formatDate($violation['violation_date']); ?></p>
                    <p class="mb-0"><strong>Ngày ghi nhận:</strong> <?php echo formatDateTime($violation['created_at']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php
                    $statusBadge = [
                        'pending' => 'warning',
                        'resolved' => 'success'
                    ];
                    $badge = $statusBadge[$violation['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $badge; ?> fs-6 px-3 py-2">
                        <?php echo escapeHtml($violationStatuses[$violation['status']] ?? $violation['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin sinh viên -->
            <div class="col-md-6 mb-4">
                <div class="card detail-card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Thông tin Sinh viên</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Họ tên:</strong> <?php echo escapeHtml($violation['student_name']); ?></p>
                        <p class="mb-2"><strong>Mã sinh viên:</strong> <?php echo escapeHtml($violation['student_code']); ?></p>
                        <?php if ($violation['student_phone']): ?>
                            <p class="mb-2"><strong>Điện thoại:</strong> <?php echo escapeHtml($violation['student_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($violation['student_email']): ?>
                            <p class="mb-0"><strong>Email:</strong> <?php echo escapeHtml($violation['student_email']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin phòng -->
            <div class="col-md-6 mb-4">
                <div class="card detail-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-door-open me-2"></i>Thông tin Phòng</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Phòng:</strong> 
                            <?php 
                            if ($violation['building_code']) {
                                echo escapeHtml($violation['building_code'] . ' - ');
                            }
                            echo escapeHtml($violation['room_code']); 
                            ?>
                        </p>
                        <?php if ($violation['building_name']): ?>
                            <p class="mb-2"><strong>Tòa nhà:</strong> <?php echo escapeHtml($violation['building_name']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Tầng:</strong> Tầng <?php echo $violation['floor']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chi tiết vi phạm -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Chi tiết Vi phạm</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Loại vi phạm:</strong>
                        <p><?php echo escapeHtml($violationTypes[$violation['violation_type']] ?? $violation['violation_type']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Ngày vi phạm:</strong>
                        <p><?php echo formatDate($violation['violation_date']); ?></p>
                    </div>
                </div>

                <?php if ($violation['description']): ?>
                    <div class="mb-3">
                        <strong>Mô tả chi tiết:</strong>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(escapeHtml($violation['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($violation['evidence']): ?>
                    <div class="mb-3">
                        <strong>Chứng cứ / Ghi chú:</strong>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(escapeHtml($violation['evidence'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <strong>Hình phạt:</strong>
                        <p>
                            <?php
                            $penaltyBadge = [
                                'warning' => 'warning',
                                'fine' => 'danger',
                                'suspension' => 'secondary'
                            ];
                            $badge = $penaltyBadge[$violation['penalty_type']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>">
                                <?php echo escapeHtml($penaltyTypes[$violation['penalty_type']] ?? $violation['penalty_type']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Số tiền phạt:</strong>
                        <p class="text-danger fs-5">
                            <?php if ($violation['penalty_amount'] > 0): ?>
                                <strong><?php echo number_format($violation['penalty_amount'], 0, ',', '.'); ?> VNĐ</strong>
                            <?php else: ?>
                                <span class="text-muted">Không có</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin xử lý -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Thông tin Xử lý</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Người báo cáo:</strong> <?php echo escapeHtml($violation['reported_by_name']); ?></p>
                        <p class="mb-2"><strong>Ngày ghi nhận:</strong> <?php echo formatDateTime($violation['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Trạng thái:</strong> 
                            <span class="badge bg-<?php echo $statusBadge[$violation['status']] ?? 'secondary'; ?>">
                                <?php echo escapeHtml($violationStatuses[$violation['status']] ?? $violation['status']); ?>
                            </span>
                        </p>
                        <?php if ($violation['status'] == 'resolved' && $violation['resolved_at']): ?>
                            <p class="mb-2"><strong>Đã xử lý vào:</strong> <?php echo formatDateTime($violation['resolved_at']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($violation['penalty_type'] == 'fine' && $violation['penalty_amount'] > 0): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Số tiền phạt này sẽ được cộng vào hóa đơn của sinh viên trong tháng <?php echo date('m/Y', strtotime($violation['violation_date'])); ?>.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Thao tác -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="../violations.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Quay lại
                    </a>
                    <?php if ($violation['status'] == 'pending'): ?>
                        <a href="edit.php?id=<?php echo $violation['id']; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>Chỉnh sửa
                        </a>
                        <a href="../../../handle/violations_process.php?action=delete&id=<?php echo $violation['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Bạn có chắc chắn muốn xóa vi phạm này?')">
                            <i class="bi bi-trash me-2"></i>Xóa vi phạm
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

