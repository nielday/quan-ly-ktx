<?php
/**
 * Tạo hóa đơn mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/invoices.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lấy danh sách phòng có người ở (để tạo hóa đơn)
// Lấy phòng có room_assignments active (có người đang ở)
require_once __DIR__ . '/../../../functions/room_assignments.php';
require_once __DIR__ . '/../../../functions/db_connection.php';

$conn = getDbConnection();
$sql = "SELECT DISTINCT r.*, b.building_name, b.building_code,
               COUNT(DISTINCT ra.id) as active_students
        FROM rooms r
        LEFT JOIN buildings b ON r.building_id = b.id
        INNER JOIN room_assignments ra ON r.id = ra.room_id
        WHERE ra.status = 'active' 
        AND ra.end_date IS NULL
        GROUP BY r.id
        HAVING active_students > 0
        ORDER BY b.building_code ASC, r.room_number ASC";

$result = mysqli_query($conn, $sql);
$occupiedRooms = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $occupiedRooms[] = $row;
    }
}

mysqli_close($conn);

// Tháng hiện tại mặc định
$defaultMonth = date('Y-m');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Hóa đơn - Quản lý KTX</title>
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
                <a class="nav-link" href="invoices.php">Hóa đơn</a>
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
            <h2><i class="bi bi-receipt me-2"></i>Tạo Hóa đơn Mới</h2>
            <a href="invoices.php" class="btn btn-secondary">
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Thông tin Hóa đơn</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/invoices_process.php" id="createInvoiceForm">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="room_id" class="form-label">
                                    <strong>Chọn phòng <span class="text-danger">*</span></strong>
                                </label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">-- Chọn phòng --</option>
                                    <?php foreach ($occupiedRooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>">
                                            <?php 
                                            if ($room['building_code']) {
                                                echo escapeHtml($room['building_code'] . ' - ');
                                            }
                                            echo escapeHtml($room['room_code'] . ' (' . $room['room_type'] . ')');
                                            $activeCount = $room['active_students'] ?? $room['current_occupancy'] ?? 0;
                                            echo ' - ' . $activeCount . '/' . $room['capacity'] . ' người';
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Chỉ hiển thị các phòng đang có người ở
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="invoice_month" class="form-label">
                                    <strong>Tháng hóa đơn <span class="text-danger">*</span></strong>
                                </label>
                                <input type="month" class="form-control" id="invoice_month" name="invoice_month" 
                                       value="<?php echo escapeHtml($defaultMonth); ?>" required>
                                <small class="form-text text-muted">
                                    Chọn tháng cần tạo hóa đơn (định dạng: YYYY-MM)
                                </small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="electricity_amount" class="form-label">
                                        <strong>Số kWh điện (cả phòng) <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="number" class="form-control" id="electricity_amount" 
                                           name="electricity_amount" step="0.01" min="0" value="0" required>
                                    <small class="form-text text-muted">
                                        Tổng số kWh điện của cả phòng trong tháng
                                    </small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="water_amount" class="form-label">
                                        <strong>Số m³ nước (cả phòng) <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="number" class="form-control" id="water_amount" 
                                           name="water_amount" step="0.01" min="0" value="0" required>
                                    <small class="form-text text-muted">
                                        Tổng số m³ nước của cả phòng trong tháng
                                    </small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="due_date" class="form-label">
                                    <strong>Hạn thanh toán</strong>
                                </label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                                <small class="form-text text-muted">
                                    Để trống sẽ tự động tính (cuối tháng sau)
                                </small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Hệ thống sẽ tự động tính toán và chia đều tiền phòng, điện, nước, dịch vụ cho từng sinh viên trong phòng</li>
                                    <li>Phí vi phạm (nếu có) sẽ được tính riêng cho từng sinh viên</li>
                                    <li>Hóa đơn sẽ được tạo riêng cho từng sinh viên trong phòng</li>
                                    <li>Tất cả đơn giá và số tiền sẽ được lưu cố định vào hóa đơn</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="invoices.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Tạo Hóa đơn
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Hướng dẫn</h5>
                    </div>
                    <div class="card-body">
                        <h6>Quy trình tạo hóa đơn:</h6>
                        <ol>
                            <li>Chọn phòng cần tạo hóa đơn</li>
                            <li>Chọn tháng hóa đơn</li>
                            <li>Nhập số kWh điện và m³ nước của cả phòng</li>
                            <li>Hệ thống tự động:
                                <ul>
                                    <li>Lấy đơn giá từ bảng Pricing</li>
                                    <li>Đếm số người trong phòng</li>
                                    <li>Tính tổng tiền phòng, điện, nước, dịch vụ</li>
                                    <li>Chia đều cho từng người</li>
                                    <li>Lấy phí vi phạm (nếu có)</li>
                                    <li>Tạo hóa đơn riêng cho từng sinh viên</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <div class="alert alert-warning mt-3">
                            <small>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Lưu ý:</strong> Mỗi phòng chỉ có thể tạo 1 lần hóa đơn cho mỗi tháng.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

