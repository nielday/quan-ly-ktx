<?php
/**
 * Xem chi tiết hợp đồng - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/contracts.php';
require_once __DIR__ . '/../../../functions/room_assignments.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setErrorMessage('ID không hợp lệ!');
    redirect('../contracts.php');
}

$contract = getContractById($id);
if (!$contract) {
    setErrorMessage('Hợp đồng không tồn tại!');
    redirect('../contracts.php');
}

$statuses = getContractStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lấy thông tin phân phòng
$assignment = getActiveRoomAssignmentByStudentId($contract['student_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Hợp đồng - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../contracts.php">Hợp đồng</a>
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
            <h2><i class="bi bi-file-earmark-text me-2"></i>Chi tiết Hợp đồng</h2>
            <div>
                <?php if ($contract['status'] != 'terminated'): ?>
                    <a href="edit_contract.php?id=<?php echo $contract['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Sửa
                    </a>
                <?php endif; ?>
                <a href="../contracts.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                </a>
            </div>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin Hợp đồng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Mã hợp đồng:</strong><br>
                                <span class="fs-5"><?php echo escapeHtml($contract['contract_code']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong><br>
                                <?php
                                $statusClass = [
                                    'active' => 'success',
                                    'expired' => 'warning',
                                    'terminated' => 'danger'
                                ];
                                $statusLabel = $statuses[$contract['status']] ?? $contract['status'];
                                $class = $statusClass[$contract['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $class; ?> fs-6"><?php echo escapeHtml($statusLabel); ?></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Ngày bắt đầu:</strong><br>
                                <?php echo formatDate($contract['start_date']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Ngày kết thúc:</strong><br>
                                <?php echo formatDate($contract['end_date']); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Phí hàng tháng:</strong><br>
                                <span class="text-primary fs-5"><?php echo formatCurrency($contract['monthly_fee']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Tiền đặt cọc:</strong><br>
                                <span class="text-info fs-5"><?php echo formatCurrency($contract['deposit']); ?></span>
                            </div>
                        </div>

                        <?php if ($contract['signed_at']): ?>
                            <div class="mb-3">
                                <strong>Ngày ký:</strong><br>
                                <?php echo formatDateTime($contract['signed_at']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($contract['terminated_at']): ?>
                            <div class="mb-3">
                                <strong>Ngày thanh lý:</strong><br>
                                <?php echo formatDateTime($contract['terminated_at']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin Sinh viên</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Mã sinh viên:</strong><br>
                                <?php echo escapeHtml($contract['student_code']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Họ tên:</strong><br>
                                <?php echo escapeHtml($contract['student_name']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Số điện thoại:</strong><br>
                                <?php echo escapeHtml($contract['phone'] ?? '-'); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Email:</strong><br>
                                <?php echo escapeHtml($contract['email'] ?? '-'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Tòa nhà:</strong><br>
                                <?php echo escapeHtml($contract['building_name'] . ' (' . $contract['building_code'] . ')'); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Phòng:</strong><br>
                                <?php echo escapeHtml($contract['room_code']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Loại phòng:</strong><br>
                                <?php echo escapeHtml($contract['room_type']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Sức chứa:</strong><br>
                                <?php echo escapeHtml($contract['capacity']); ?> người
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <?php if ($contract['status'] == 'active'): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Gia hạn hợp đồng</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../../../handle/contracts_process.php">
                                <input type="hidden" name="action" value="extend">
                                <input type="hidden" name="id" value="<?php echo $contract['id']; ?>">
                                <div class="mb-3">
                                    <label for="new_end_date" class="form-label">Ngày kết thúc mới</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="new_end_date" 
                                           name="new_end_date" 
                                           min="<?php echo date('Y-m-d', strtotime($contract['end_date'] . ' +1 day')); ?>"
                                           required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-calendar-plus me-2"></i>Gia hạn
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Thanh lý hợp đồng</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Chấm dứt hợp đồng này?</p>
                            <form method="POST" action="../../../handle/contracts_process.php" onsubmit="return confirm('Bạn có chắc chắn muốn thanh lý hợp đồng này? Hành động này sẽ cập nhật phân phòng và không thể hoàn tác!');">
                                <input type="hidden" name="action" value="terminate">
                                <input type="hidden" name="id" value="<?php echo $contract['id']; ?>">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-x-circle me-2"></i>Thanh lý
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

