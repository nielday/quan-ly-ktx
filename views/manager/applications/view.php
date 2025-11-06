<?php
/**
 * Xem chi tiết và duyệt/từ chối đơn đăng ký - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/applications.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/rooms.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID từ GET
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setErrorMessage('ID không hợp lệ!');
    redirect('../applications.php');
}

// Lấy thông tin đơn đăng ký
$application = getApplicationById($id);

if (!$application) {
    setErrorMessage('Đơn đăng ký không tồn tại!');
    redirect('../applications.php');
}

$statuses = getApplicationStatuses();
$roomTypes = getRoomTypes();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Kiểm tra có thể duyệt/từ chối không
$canApprove = ($application['status'] == 'pending' || $application['status'] == 'waiting_list');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn Đăng ký - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link active" href="../applications.php">Duyệt đơn</a>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo escapeHtml($currentUser['full_name'] ?? $currentUser['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../../../../handle/logout_process.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text me-2"></i>Chi tiết Đơn Đăng ký</h2>
            <a href="../applications.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
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

        <div class="row">
            <!-- Thông tin đơn đăng ký -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin đơn đăng ký</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Mã sinh viên:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['student_code']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Họ tên:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['student_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Đợt đăng ký:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['period_name'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Ngày đăng ký:</strong><br>
                                <span class="text-muted"><?php echo formatDate($application['application_date']); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Học kỳ:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['semester'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Năm học:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['academic_year'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Loại phòng mong muốn:</strong><br>
                                <span class="text-muted">
                                    <?php echo $application['preferred_room_type'] ? escapeHtml($roomTypes[$application['preferred_room_type']] ?? $application['preferred_room_type']) : '-'; ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong><br>
                                <?php
                                $statusClass = [
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'waiting_list' => 'bg-info'
                                ];
                                $statusLabel = $statuses[$application['status']] ?? $application['status'];
                                $class = $statusClass[$application['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($application['rejection_reason']): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Lý do từ chối:</strong><br>
                                    <span class="text-danger"><?php echo escapeHtml($application['rejection_reason']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($application['approved_by_name']): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Người duyệt:</strong><br>
                                    <span class="text-muted"><?php echo escapeHtml($application['approved_by_name']); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Ngày duyệt:</strong><br>
                                    <span class="text-muted"><?php echo $application['approved_at'] ? formatDate($application['approved_at']) : '-'; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thông tin sinh viên -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin sinh viên</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Số điện thoại:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['phone'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['email'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Trường:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['university'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Ngành:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['major'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Khóa:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($application['year'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thao tác -->
            <div class="col-md-4">
                <?php if ($canApprove): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Duyệt đơn</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../../../../handle/applications_process.php">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                                <p class="text-muted">Xác nhận duyệt đơn đăng ký này?</p>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle me-2"></i>Duyệt đơn
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Từ chối đơn</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../../../../handle/applications_process.php">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                                <div class="mb-3">
                                    <label for="rejection_reason" class="form-label">Lý do từ chối</label>
                                    <textarea class="form-control" 
                                              id="rejection_reason" 
                                              name="rejection_reason" 
                                              rows="3"
                                              placeholder="Nhập lý do từ chối..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Bạn có chắc chắn muốn từ chối đơn đăng ký này?');">
                                    <i class="bi bi-x-circle me-2"></i>Từ chối đơn
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted">Đơn đăng ký này đã được xử lý.</p>
                            <?php if ($application['status'] == 'approved'): ?>
                                <div class="alert alert-success mb-3">
                                    <i class="bi bi-check-circle me-2"></i>Đơn đã được duyệt
                                </div>
                                
                                <?php
                                // Kiểm tra xem đơn đăng ký này đã có hợp đồng chưa
                                require_once __DIR__ . '/../../../functions/contracts.php';
                                $existingContract = getContractByApplication(
                                    $application['id'], 
                                    $application['student_id'], 
                                    $application['approved_at']
                                );
                                ?>
                                
                                <?php if (!$existingContract): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Bước tiếp theo:</strong> Tạo hợp đồng và phân phòng cho sinh viên
                                    </div>
                                    <a href="../contracts/create_contract.php?application_id=<?php echo $application['id']; ?>&student_id=<?php echo $application['student_id']; ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="bi bi-file-earmark-plus me-2"></i>Tạo hợp đồng
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>Đơn đăng ký này đã có hợp đồng:</strong><br>
                                        <a href="../contracts/view_contract.php?id=<?php echo $existingContract['id']; ?>" 
                                           class="alert-link">
                                            <?php echo escapeHtml($existingContract['contract_code']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">Ngày tạo: <?php echo formatDate($existingContract['created_at']); ?></small>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($application['status'] == 'rejected'): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle me-2"></i>Đơn đã bị từ chối
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

