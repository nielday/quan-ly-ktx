<?php
/**
 * Xem chi tiết sinh viên - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID từ GET
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setErrorMessage('ID không hợp lệ!');
    redirect('../students.php');
}

// Lấy thông tin sinh viên
$student = getStudentById($id);

if (!$student) {
    setErrorMessage('Sinh viên không tồn tại!');
    redirect('../students.php');
}

$currentRoom = getStudentCurrentRoom($id);
$statuses = getStudentStatuses();
$genders = getGenders();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Sinh viên - Quản lý KTX</title>
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
                <a class="nav-link active" href="../students.php">Sinh viên</a>
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
            <h2><i class="bi bi-person me-2"></i>Chi tiết Sinh viên</h2>
            <a href="../students.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        <div class="row">
            <!-- Thông tin cá nhân -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin cá nhân</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Mã sinh viên:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['student_code']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Họ tên:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['full_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Ngày sinh:</strong><br>
                                <span class="text-muted"><?php echo $student['date_of_birth'] ? formatDate($student['date_of_birth']) : '-'; ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Giới tính:</strong><br>
                                <span class="text-muted"><?php echo $student['gender'] ? escapeHtml($genders[$student['gender']] ?? $student['gender']) : '-'; ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Số điện thoại:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['phone'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['email'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <strong>Địa chỉ:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['address'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>CCCD/CMND:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['id_card'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong><br>
                                <?php
                                $statusClass = [
                                    'active' => 'bg-success',
                                    'inactive' => 'bg-secondary',
                                    'graduated' => 'bg-info'
                                ];
                                $statusLabel = $statuses[$student['status']] ?? $student['status'];
                                $class = $statusClass[$student['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin học tập -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin học tập</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Trường đại học:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['university'] ?: '-'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Ngành học:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['major'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Khóa học:</strong><br>
                                <span class="text-muted"><?php echo escapeHtml($student['year'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin phòng -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Phòng đang ở</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($currentRoom): ?>
                            <p>
                                <strong><?php echo escapeHtml($currentRoom['building_code'] . ' - ' . $currentRoom['room_code']); ?></strong><br>
                                <small class="text-muted">
                                    Loại: <?php echo escapeHtml($currentRoom['room_type']); ?><br>
                                    Ngày vào: <?php echo formatDate($currentRoom['assigned_date']); ?>
                                </small>
                            </p>
                            <a href="../rooms.php?room_id=<?php echo $currentRoom['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-door-open me-1"></i>Xem phòng
                            </a>
                        <?php else: ?>
                            <p class="text-muted">Chưa có phòng</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

