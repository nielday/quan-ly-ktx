<?php
/**
 * Tạo hợp đồng mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/contracts.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/applications.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$statuses = getContractStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lấy student_id từ application nếu có
$applicationId = $_GET['application_id'] ?? null;
$studentId = $_GET['student_id'] ?? null;
$application = null;
$student = null;

if ($applicationId) {
    $application = getApplicationById($applicationId);
    if ($application && $application['status'] == 'approved') {
        $studentId = $application['student_id'];
        
        // Kiểm tra xem application đã có hợp đồng chưa
        require_once __DIR__ . '/../../../functions/contracts.php';
        $existingContract = getContractByApplication(
            $applicationId, 
            $application['student_id'], 
            $application['approved_at']
        );
        
        if ($existingContract) {
            setErrorMessage('Đơn đăng ký này đã có hợp đồng rồi! Mỗi đơn đăng ký chỉ được tạo 1 hợp đồng.');
            redirect('../applications/view.php?id=' . $applicationId);
        }
    }
}

if ($studentId) {
    $student = getStudentById($studentId);
}

// Lấy danh sách phòng còn chỗ
$allRooms = getAllRooms(null, 'available');
$availableRooms = array_filter($allRooms, function($room) {
    return $room['current_occupancy'] < $room['capacity'];
});

// Lấy danh sách sinh viên chưa có hợp đồng active
$allStudents = getAllStudents('active');
$studentsWithoutContract = [];
foreach ($allStudents as $s) {
    $existingContract = getContractByStudentId($s['id']);
    if (!$existingContract) {
        $studentsWithoutContract[] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Hợp đồng - Quản lý KTX</title>
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
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-plus me-2"></i>Tạo Hợp đồng mới
                        </h5>
                    </div>
                    <div class="card-body">
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
                        
                        <?php if ($application): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Đơn đăng ký:</strong> <?php echo escapeHtml($application['student_name']); ?> 
                                (<?php echo escapeHtml($application['student_code']); ?>)
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="../../../handle/contracts_process.php" id="contractForm">
                            <input type="hidden" name="action" value="create">
                            <?php if ($applicationId): ?>
                                <input type="hidden" name="application_id" value="<?php echo $applicationId; ?>">
                            <?php endif; ?>
                            <input type="hidden" name="student_id" value="<?php echo $student ? $student['id'] : ''; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">
                                        Sinh viên <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="student_id" name="student_id" required <?php echo $student ? 'readonly' : ''; ?>>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach ($studentsWithoutContract as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" 
                                                    <?php echo ($student && $student['id'] == $s['id']) ? 'selected' : ''; ?>>
                                                <?php echo escapeHtml($s['student_code'] . ' - ' . $s['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($student): ?>
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="room_id" class="form-label">
                                        Phòng <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">-- Chọn phòng --</option>
                                        <?php foreach ($availableRooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>" 
                                                    data-room-type="<?php echo escapeHtml($room['room_type']); ?>"
                                                    data-price="<?php echo getRoomPriceFromPricing($room['room_type']) ?? 0; ?>">
                                                <?php echo escapeHtml($room['building_code'] . ' - ' . $room['room_code']); ?> 
                                                (<?php echo escapeHtml($room['room_type']); ?>, 
                                                Còn: <?php echo ($room['capacity'] - $room['current_occupancy']); ?>/<?php echo $room['capacity']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contract_code" class="form-label">
                                        Mã hợp đồng <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="contract_code" 
                                           name="contract_code" 
                                           value="<?php echo generateContractCode(); ?>"
                                           required
                                           maxlength="50">
                                    <small class="text-muted">Mã hợp đồng sẽ được tạo tự động nếu để trống</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="monthly_fee" class="form-label">
                                        Phí hàng tháng (VNĐ)
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="monthly_fee" 
                                           name="monthly_fee" 
                                           step="1000"
                                           min="0"
                                           placeholder="Tự động lấy từ giá phòng">
                                    <small class="text-muted">Để trống để tự động lấy từ giá phòng</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">
                                        Ngày bắt đầu <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">
                                        Ngày kết thúc <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="end_date" 
                                           name="end_date" 
                                           value=""
                                           required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="deposit" class="form-label">
                                        Tiền đặt cọc (VNĐ)
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="deposit" 
                                           name="deposit" 
                                           value="0"
                                           step="1000"
                                           min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($key == 'active') ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../contracts.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Tạo hợp đồng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự động cập nhật monthly_fee khi chọn phòng
        document.getElementById('room_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const monthlyFeeInput = document.getElementById('monthly_fee');
            
            if (price && price > 0) {
                monthlyFeeInput.value = price;
            }
        });
    </script>
</body>
</html>

