<?php
/**
 * Tạo yêu cầu sửa chữa - Student
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/student_room.php';
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

// Lấy thông tin phòng hiện tại
$roomInfo = getStudentRoomInfo($student['id']);

if (!$roomInfo) {
    setErrorMessage("Bạn chưa được phân phòng!");
    redirect('../room.php');
    exit;
}

$currentRoom = $roomInfo['room'];
$requestTypes = getRequestTypes();
$priorities = getPriorityLevels();

$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo yêu cầu sửa chữa - Sinh viên</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-tools me-2"></i>Tạo yêu cầu sửa chữa
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
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Phòng của bạn</h6>
                            <p class="mb-0">
                                <strong>Phòng:</strong> <?php echo escapeHtml($currentRoom['building_code'] . '-' . $currentRoom['room_code']); ?><br>
                                <strong>Tòa:</strong> <?php echo escapeHtml($currentRoom['building_name']); ?>
                            </p>
                        </div>

                        <form method="POST" action="../../../handle/maintenance_process.php">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="room_id" value="<?php echo $currentRoom['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="request_type" class="form-label">
                                    Loại sửa chữa <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="request_type" name="request_type" required>
                                    <option value="">-- Chọn loại sửa chữa --</option>
                                    <?php foreach ($requestTypes as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    Mức độ ưu tiên
                                </label>
                                <select class="form-select" id="priority" name="priority">
                                    <?php foreach ($priorities as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($key === 'medium' || (isset($_POST['priority']) && $_POST['priority'] === $key)) ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Chọn "Khẩn cấp" nếu vấn đề ảnh hưởng nghiêm trọng đến sinh hoạt (mất điện, rò rỉ nước lớn...).
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    Mô tả vấn đề <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="6" 
                                          required
                                          placeholder="Vui lòng mô tả chi tiết vấn đề cần sửa chữa..."><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">
                                    Ví dụ: "Bóng đèn phòng tắm bị cháy", "Vòi nước rửa tay bị rò rỉ", "Quạt trần không quay"...
                                </small>
                            </div>

                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Lưu ý:</strong> Sau khi gửi yêu cầu, quản lý sẽ xem xét và phân công người sửa chữa. 
                                Vui lòng chờ quản lý xử lý.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../maintenance.php" class="btn btn-secondary">
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

