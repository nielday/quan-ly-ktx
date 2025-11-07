<?php
/**
 * Tạo vi phạm mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/violations.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/db_connection.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$violationTypes = getViolationTypes();
$penaltyTypes = getPenaltyTypes();
$rooms = getAllRooms(); // Lấy danh sách phòng
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lấy danh sách sinh viên với thông tin phòng hiện tại
$conn = getDbConnection();
$sql = "SELECT s.id, s.student_code, s.full_name, 
               ra.room_id, r.room_code, b.building_code
        FROM students s
        LEFT JOIN room_assignments ra ON s.id = ra.student_id AND ra.status = 'active'
        LEFT JOIN rooms r ON ra.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        WHERE s.status = 'active'
        ORDER BY s.student_code ASC";
$result = mysqli_query($conn, $sql);
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghi nhận Vi phạm - Quản lý KTX</title>
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
            <h2><i class="bi bi-exclamation-triangle me-2"></i>Ghi nhận Vi phạm</h2>
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

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin Vi phạm</h5>
            </div>
            <div class="card-body">
                <form action="../../../handle/violations_process.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Sinh viên vi phạm <span class="text-danger">*</span></label>
                            <select class="form-select" id="student_id" name="student_id" required onchange="autoFillRoom()">
                                <option value="">-- Chọn sinh viên --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                            data-room-id="<?php echo $student['room_id'] ?? ''; ?>"
                                            data-room-name="<?php echo $student['building_code'] ? escapeHtml($student['building_code'] . ' - ' . $student['room_code']) : ''; ?>">
                                        <?php 
                                        $displayText = escapeHtml($student['student_code'] . ' - ' . $student['full_name']);
                                        if ($student['room_code']) {
                                            $displayText .= ' (' . escapeHtml($student['building_code'] ? $student['building_code'] . '-' : '') . escapeHtml($student['room_code']) . ')';
                                        }
                                        echo $displayText;
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Chọn sinh viên đã vi phạm nội quy</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="room_id" class="form-label">Phòng <span class="text-danger">*</span></label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">-- Chọn sinh viên trước --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php 
                                        $roomDisplay = $room['building_code'] ? $room['building_code'] . ' - ' : '';
                                        $roomDisplay .= $room['room_code'];
                                        echo escapeHtml($roomDisplay); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="room-helper">Phòng sẽ tự động chọn theo sinh viên</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="violation_type" class="form-label">Loại vi phạm <span class="text-danger">*</span></label>
                            <select class="form-select" id="violation_type" name="violation_type" required>
                                <option value="">-- Chọn loại vi phạm --</option>
                                <?php foreach ($violationTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>">
                                        <?php echo escapeHtml($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="violation_date" class="form-label">Ngày vi phạm <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="violation_date" name="violation_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả chi tiết</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Mô tả chi tiết về vi phạm..."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="penalty_type" class="form-label">Hình phạt <span class="text-danger">*</span></label>
                            <select class="form-select" id="penalty_type" name="penalty_type" required onchange="togglePenaltyAmount()">
                                <?php foreach ($penaltyTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($key == 'warning') ? 'selected' : ''; ?>>
                                        <?php echo escapeHtml($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="penalty_amount_group">
                            <label for="penalty_amount" class="form-label">Số tiền phạt (VNĐ)</label>
                            <input type="number" class="form-control" id="penalty_amount" name="penalty_amount" 
                                   min="0" step="1000" value="0">
                            <small class="text-muted">Chỉ áp dụng khi hình phạt là "Phạt tiền"</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="evidence" class="form-label">Chứng cứ / Ghi chú</label>
                        <textarea class="form-control" id="evidence" name="evidence" rows="3" 
                                  placeholder="Ghi chú về chứng cứ, tình tiết..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Nếu hình phạt là "Phạt tiền", số tiền phạt sẽ được cộng vào hóa đơn của sinh viên trong tháng vi phạm.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-save me-2"></i>Ghi nhận vi phạm
                        </button>
                        <a href="../violations.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Hủy
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
            const penaltyAmountGroup = document.getElementById('penalty_amount_group');
            const penaltyAmountInput = document.getElementById('penalty_amount');
            
            if (penaltyType === 'fine') {
                penaltyAmountInput.required = true;
                penaltyAmountGroup.style.display = 'block';
            } else {
                penaltyAmountInput.required = false;
                penaltyAmountInput.value = '0';
            }
        }
        
        // Tự động chọn phòng khi chọn sinh viên
        function autoFillRoom() {
            const studentSelect = document.getElementById('student_id');
            const roomSelect = document.getElementById('room_id');
            const roomHelper = document.getElementById('room-helper');
            
            const selectedOption = studentSelect.options[studentSelect.selectedIndex];
            const roomId = selectedOption.getAttribute('data-room-id');
            const roomName = selectedOption.getAttribute('data-room-name');
            
            if (roomId) {
                // Tự động chọn phòng
                roomSelect.value = roomId;
                roomHelper.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Đã tự động chọn phòng: <strong>' + roomName + '</strong>';
                roomHelper.className = 'text-success';
            } else {
                // Sinh viên chưa có phòng, cho phép chọn thủ công
                roomSelect.value = '';
                roomHelper.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Sinh viên chưa được phân phòng. Vui lòng chọn phòng thủ công.';
                roomHelper.className = 'text-warning';
            }
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            togglePenaltyAmount();
        });
    </script>
</body>
</html>

