<?php
/**
 * Thông tin phòng - Student
 * Xem thông tin phòng đang ở và bạn cùng phòng
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';
require_once __DIR__ . '/../../functions/student_room.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('dashboard.php');
    exit;
}

// Lấy thông tin phòng
$roomInfo = getStudentRoomInfo($student['id']);

if (!$roomInfo) {
    // Sinh viên chưa có phòng
    $hasRoom = false;
} else {
    $hasRoom = true;
    $room = $roomInfo['room'];
    $roommates = $roomInfo['roommates'];
    $services = $roomInfo['services'];
    $totalOccupancy = $roomInfo['total_occupancy'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin phòng - Sinh viên</title>
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
                <a class="nav-link active" href="room.php">Phòng của tôi</a>
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
            <h2><i class="bi bi-door-open me-2"></i>Thông tin phòng</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại Dashboard
            </a>
        </div>

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

        <?php if (!$hasRoom): ?>
            <!-- Chưa có phòng -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-house-x text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Bạn chưa được phân phòng</h4>
                    <p class="text-muted">Vui lòng đăng ký ở KTX và chờ quản lý duyệt đơn.</p>
                    <a href="applications/create.php" class="btn btn-primary">
                        <i class="bi bi-file-earmark-plus me-1"></i>Đăng ký ở KTX
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Thông tin phòng -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-building me-2"></i>
                                Phòng <?php echo escapeHtml($room['building_code']); ?> - <?php echo escapeHtml($room['room_code']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Tòa nhà</label>
                                    <div><strong><?php echo escapeHtml($room['building_name']); ?></strong></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Mã phòng</label>
                                    <div><strong><?php echo escapeHtml($room['room_code']); ?></strong></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Tầng</label>
                                    <div><?php echo $room['floor']; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Loại phòng</label>
                                    <div>
                                        <?php
                                        $roomTypes = [
                                            'single' => 'Phòng đơn',
                                            'double' => 'Phòng đôi',
                                            '4people' => 'Phòng 4 người',
                                            '6people' => 'Phòng 6 người'
                                        ];
                                        echo $roomTypes[$room['room_type']] ?? $room['room_type'];
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Sức chứa</label>
                                    <div><?php echo $room['capacity']; ?> người</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Số người hiện tại</label>
                                    <div>
                                        <strong><?php echo $totalOccupancy; ?></strong> / <?php echo $room['capacity']; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Ngày vào ở</label>
                                    <div><?php echo date('d/m/Y', strtotime($roomInfo['room_assignment']['assigned_date'])); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Trạng thái</label>
                                    <div>
                                        <?php
                                        $statusColors = [
                                            'available' => 'success',
                                            'occupied' => 'info',
                                            'maintenance' => 'warning'
                                        ];
                                        $statusLabels = [
                                            'available' => 'Còn trống',
                                            'occupied' => 'Đã đầy',
                                            'maintenance' => 'Đang sửa chữa'
                                        ];
                                        $color = $statusColors[$room['status']] ?? 'secondary';
                                        $label = $statusLabels[$room['status']] ?? $room['status'];
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($room['amenities'])): ?>
                            <hr>
                            <div>
                                <label class="text-muted small">Tiện ích phòng</label>
                                <div class="mt-2">
                                    <?php
                                    $amenities = is_string($room['amenities']) ? json_decode($room['amenities'], true) : $room['amenities'];
                                    if (is_array($amenities)) {
                                        foreach ($amenities as $amenity) {
                                            echo '<span class="badge bg-secondary me-1 mb-1">' . escapeHtml($amenity) . '</span>';
                                        }
                                    } else {
                                        echo '<p class="text-muted mb-0">' . nl2br(escapeHtml($room['amenities'])) . '</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Dịch vụ phòng -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-gear me-2"></i>Dịch vụ phòng
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($services)): ?>
                                <p class="text-muted mb-0">Phòng chưa có dịch vụ nào.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($services as $service): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="border rounded p-3">
                                                <h6 class="mb-1">
                                                    <i class="bi bi-check-circle text-success me-2"></i>
                                                    <?php echo escapeHtml($service['service_name']); ?>
                                                </h6>
                                                <?php if (!empty($service['description'])): ?>
                                                    <p class="text-muted small mb-1"><?php echo escapeHtml($service['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="text-primary">
                                                    <strong><?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ</strong>
                                                    <small class="text-muted">/ <?php echo escapeHtml($service['unit']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bạn cùng phòng -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-people me-2"></i>Bạn cùng phòng
                                <span class="badge bg-light text-dark"><?php echo count($roommates); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roommates)): ?>
                                <p class="text-muted mb-0">Bạn đang ở một mình trong phòng.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($roommates as $mate): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo escapeHtml($mate['full_name']); ?></h6>
                                                    <small class="text-muted">
                                                        MSSV: <?php echo escapeHtml($mate['student_code']); ?>
                                                    </small>
                                                    <?php if (!empty($mate['phone'])): ?>
                                                        <br><small class="text-muted">
                                                            <i class="bi bi-telephone me-1"></i><?php echo escapeHtml($mate['phone']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Thao tác nhanh</h6>
                        </div>
                        <div class="card-body">
                            <a href="room_transfers/create.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-arrow-left-right me-1"></i>Yêu cầu chuyển phòng
                            </a>
                            <a href="maintenance/create.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-tools me-1"></i>Yêu cầu sửa chữa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

