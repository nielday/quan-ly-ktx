<?php
/**
 * Chi tiết yêu cầu sửa chữa - Student
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/maintenance.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../dashboard.php');
    exit;
}

// Lấy ID yêu cầu
$requestId = $_GET['id'] ?? null;
if (!$requestId) {
    setErrorMessage("Không tìm thấy yêu cầu");
    redirect('../maintenance.php');
    exit;
}

// Lấy thông tin yêu cầu
$request = getMaintenanceRequestById($requestId);
if (!$request) {
    setErrorMessage("Yêu cầu không tồn tại");
    redirect('../maintenance.php');
    exit;
}

// Kiểm tra yêu cầu thuộc về sinh viên này
if ($request['student_id'] != $student['id']) {
    setErrorMessage("Bạn không có quyền xem yêu cầu này");
    redirect('../maintenance.php');
    exit;
}

// Lấy các option
$requestTypes = getRequestTypes();
$priorityLevels = getPriorityLevels();
$statuses = getMaintenanceStatuses();

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết yêu cầu sửa chữa - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../room.php">Phòng của tôi</a>
                <a class="nav-link active" href="../maintenance.php">Sửa chữa</a>
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
            <h2><i class="bi bi-tools me-2"></i>Chi tiết yêu cầu sửa chữa #<?php echo $request['id']; ?></h2>
            <a href="../maintenance.php" class="btn btn-secondary">
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

        <div class="row">
            <div class="col-md-8">
                <!-- Thông tin yêu cầu -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin yêu cầu</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="200">Mã yêu cầu:</th>
                                <td><strong>#<?php echo $request['id']; ?></strong></td>
                            </tr>
                            <tr>
                                <th>Phòng:</th>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo escapeHtml($request['building_code'] . '-' . $request['room_code']); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo escapeHtml($request['building_name']); ?>, Tầng <?php echo $request['floor']; ?>
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Loại sửa chữa:</th>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo escapeHtml($requestTypes[$request['request_type']] ?? $request['request_type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Mức độ ưu tiên:</th>
                                <td>
                                    <?php
                                    $priorityClass = [
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'secondary'
                                    ];
                                    $priorityBadge = $priorityClass[$request['priority']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $priorityBadge; ?>">
                                        <?php echo escapeHtml($priorityLevels[$request['priority']] ?? $request['priority']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'secondary'
                                    ];
                                    $statusBadge = $statusClass[$request['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusBadge; ?>">
                                        <?php echo escapeHtml($statuses[$request['status']] ?? $request['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Mô tả vấn đề:</th>
                                <td>
                                    <div class="alert alert-light">
                                        <?php echo nl2br(escapeHtml($request['description'])); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php if ($request['assigned_to_name']): ?>
                            <tr>
                                <th>Người được phân công:</th>
                                <td>
                                    <i class="bi bi-person-check me-1"></i>
                                    <?php echo escapeHtml($request['assigned_to_name']); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Ngày tạo:</th>
                                <td>
                                    <i class="bi bi-calendar me-1"></i>
                                    <?php echo formatDate($request['created_at']); ?>
                                </td>
                            </tr>
                            <?php if ($request['completed_at']): ?>
                            <tr>
                                <th>Ngày hoàn thành:</th>
                                <td>
                                    <i class="bi bi-check-circle me-1"></i>
                                    <?php echo formatDate($request['completed_at']); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Thông tin sinh viên -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-person me-2"></i>Thông tin sinh viên</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            <strong>Mã SV:</strong> <?php echo escapeHtml($request['student_code']); ?>
                        </p>
                        <p class="mb-1">
                            <strong>Họ tên:</strong> <?php echo escapeHtml($request['student_name']); ?>
                        </p>
                        <?php if ($request['student_phone']): ?>
                        <p class="mb-0">
                            <strong>SĐT:</strong> <?php echo escapeHtml($request['student_phone']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thông báo -->
                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Lưu ý</h6>
                    <p class="mb-0 small">
                        Yêu cầu của bạn đang được xử lý bởi quản lý. 
                        Vui lòng chờ quản lý phân công người sửa chữa và cập nhật trạng thái.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

