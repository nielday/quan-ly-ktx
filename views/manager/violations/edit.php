<?php
/**
 * Sửa/Xử lý vi phạm - Manager
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
    <title>Sửa Vi phạm - Quản lý KTX</title>
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
            <h2><i class="bi bi-pencil me-2"></i>Sửa/Xử lý Vi phạm</h2>
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

        <!-- Thông tin sinh viên và phòng -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin Vi phạm</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Sinh viên:</strong> <?php echo escapeHtml($violation['student_name']); ?></p>
                        <p class="mb-2"><strong>Mã sinh viên:</strong> <?php echo escapeHtml($violation['student_code']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Phòng:</strong> 
                            <?php 
                            if ($violation['building_code']) {
                                echo escapeHtml($violation['building_code'] . ' - ');
                            }
                            echo escapeHtml($violation['room_code']); 
                            ?>
                        </p>
                        <p class="mb-2"><strong>Người báo cáo:</strong> <?php echo escapeHtml($violation['reported_by_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Chỉnh sửa Thông tin</h5>
            </div>
            <div class="card-body">
                <form action="../../../handle/violations_process.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="violation_id" value="<?php echo $violation['id']; ?>">
                    <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="violation_type" class="form-label">Loại vi phạm <span class="text-danger">*</span></label>
                            <select class="form-select" id="violation_type" name="violation_type" required>
                                <?php foreach ($violationTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($violation['violation_type'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo escapeHtml($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="violation_date" class="form-label">Ngày vi phạm <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="violation_date" name="violation_date" 
                                   value="<?php echo $violation['violation_date']; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả chi tiết</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo escapeHtml($violation['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="penalty_type" class="form-label">Hình phạt <span class="text-danger">*</span></label>
                            <select class="form-select" id="penalty_type" name="penalty_type" required onchange="togglePenaltyAmount()">
                                <?php foreach ($penaltyTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($violation['penalty_type'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo escapeHtml($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="penalty_amount_group">
                            <label for="penalty_amount" class="form-label">Số tiền phạt (VNĐ)</label>
                            <input type="number" class="form-control" id="penalty_amount" name="penalty_amount" 
                                   min="0" step="1000" value="<?php echo $violation['penalty_amount']; ?>">
                            <small class="text-muted">Chỉ áp dụng khi hình phạt là "Phạt tiền"</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="evidence" class="form-label">Chứng cứ / Ghi chú</label>
                        <textarea class="form-control" id="evidence" name="evidence" rows="3"><?php echo escapeHtml($violation['evidence'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach ($violationStatuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($violation['status'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Đánh dấu "Đã xử lý" khi đã xử lý xong vi phạm</small>
                    </div>

                    <?php if ($violation['status'] == 'resolved' && $violation['resolved_at']): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            Đã xử lý vào: <?php echo formatDateTime($violation['resolved_at']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Nếu thay đổi số tiền phạt hoặc hình phạt, các hóa đơn đã tạo sẽ KHÔNG tự động cập nhật.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Lưu thay đổi
                        </button>
                        <a href="../violations.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Hủy
                        </a>
                        <a href="view.php?id=<?php echo $violation['id']; ?>" class="btn btn-info">
                            <i class="bi bi-eye me-2"></i>Xem chi tiết
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePenaltyAmount() {
            const penaltyType = document.getElementById('penalty_type').value;
            const penaltyAmountInput = document.getElementById('penalty_amount');
            
            if (penaltyType === 'fine') {
                penaltyAmountInput.required = true;
            } else {
                penaltyAmountInput.required = false;
                penaltyAmountInput.value = '0';
            }
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            togglePenaltyAmount();
        });
    </script>
</body>
</html>

