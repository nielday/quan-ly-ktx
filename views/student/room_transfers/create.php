<?php
/**
 * Tạo yêu cầu chuyển phòng - Student
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/student_room.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/room_transfers.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../dashboard.php');
    exit;
}

// Lấy thông tin phòng hiện tại
$roomInfo = getStudentRoomInfo($student['id']);

if (!$roomInfo) {
    setErrorMessage("Bạn chưa được phân phòng!");
    redirect('../room.php');
    exit;
}

$currentRoom = $roomInfo['room'];

// Lấy danh sách phòng trống (available)
$availableRooms = getAllRooms(null, 'available');

// Lọc bỏ phòng hiện tại VÀ chỉ lấy phòng còn chỗ trống (current_occupancy < capacity)
$availableRooms = array_filter($availableRooms, function($room) use ($currentRoom) {
    // Bỏ phòng hiện tại
    if ($room['id'] == $currentRoom['id']) {
        return false;
    }
    // Chỉ lấy phòng còn chỗ trống
    return intval($room['current_occupancy']) < intval($room['capacity']);
});
$availableRooms = array_values($availableRooms);

$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo yêu cầu chuyển phòng - Sinh viên</title>
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
                <a class="nav-link active" href="../room_transfers.php">Chuyển phòng</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-left-right me-2"></i>Tạo yêu cầu chuyển phòng
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($errorMsg): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo escapeHtml($errorMsg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Thông tin phòng hiện tại -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Phòng hiện tại của bạn</h6>
                            <p class="mb-0">
                                <strong>Phòng:</strong> <?php echo escapeHtml($currentRoom['building_code'] . '-' . $currentRoom['room_code']); ?><br>
                                <strong>Tòa:</strong> <?php echo escapeHtml($currentRoom['building_name']); ?><br>
                                <strong>Sức chứa:</strong> <?php echo $currentRoom['capacity']; ?> người<br>
                                <strong>Đang ở:</strong> <?php echo $currentRoom['current_occupancy']; ?> người
                            </p>
                        </div>

                        <form method="POST" action="../../../handle/room_transfers_process.php">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="current_room_id" value="<?php echo $currentRoom['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="requested_room_id" class="form-label">
                                    Phòng muốn chuyển đến <span class="text-muted">(Tùy chọn)</span>
                                </label>
                                <select class="form-select" id="requested_room_id" name="requested_room_id">
                                    <option value="">-- Chỉ muốn chuyển đi, không chọn phòng cụ thể --</option>
                                    <?php foreach ($availableRooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>">
                                            <?php echo escapeHtml($room['building_code'] . '-' . $room['room_code']); ?> 
                                            (<?php echo escapeHtml($room['building_name']); ?>, 
                                            Tầng <?php echo $room['floor']; ?>, 
                                            <?php echo $room['capacity']; ?> người, 
                                            Đang ở: <?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Nếu bạn không chọn phòng cụ thể, quản lý sẽ phân phòng phù hợp cho bạn.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">
                                    Lý do chuyển phòng <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" 
                                          id="reason" 
                                          name="reason" 
                                          rows="5" 
                                          required
                                          placeholder="Vui lòng mô tả lý do bạn muốn chuyển phòng..."><?php echo escapeHtml($_POST['reason'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">
                                    Vui lòng mô tả chi tiết lý do bạn muốn chuyển phòng để quản lý có thể xem xét.
                                </small>
                            </div>

                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Lưu ý:</strong> Bạn chỉ có thể có 1 yêu cầu chuyển phòng đang chờ xử lý. 
                                Vui lòng chờ quản lý xử lý yêu cầu hiện tại trước khi tạo yêu cầu mới.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../room_transfers.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Gửi yêu cầu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

