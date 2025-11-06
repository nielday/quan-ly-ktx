<?php
/**
 * Xem chi tiết dịch vụ của phòng - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/room_services.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();

// Lấy ID phòng từ GET
$roomId = intval($_GET['room_id'] ?? 0);

if ($roomId <= 0) {
    setErrorMessage('ID phòng không hợp lệ!');
    redirect('../room_services.php');
}

// Lấy thông tin phòng
$room = getRoomById($roomId);

if (!$room) {
    setErrorMessage('Phòng không tồn tại!');
    redirect('../room_services.php');
}

// Lấy thông tin tòa nhà
$building = getBuildingById($room['building_id']);

// Lấy dịch vụ của phòng
$roomServices = getRoomServices($roomId);
$totalServicePrice = getTotalRoomServicePrice($roomId);

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Dịch vụ Phòng - Quản lý KTX</title>
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
                <a class="nav-link active" href="../room_services.php">Dịch vụ phòng</a>
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
            <div>
                <h2><i class="bi bi-door-open me-2"></i>Chi tiết Dịch vụ Phòng</h2>
                <p class="text-muted mb-0">
                    <strong><?php echo escapeHtml($building['building_code'] . ' - ' . $room['room_code']); ?></strong>
                    | <?php echo escapeHtml($room['room_type']); ?> | 
                    Sức chứa: <?php echo $room['capacity']; ?> người | 
                    Đang ở: <?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?>
                </p>
            </div>
            <a href="../room_services.php" class="btn btn-secondary">
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

        <!-- Tóm tắt -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Tổng số dịch vụ</h5>
                        <h2><?php echo count($roomServices); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Tổng tiền/tháng</h5>
                        <h2><?php echo formatCurrency($totalServicePrice); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Tiền/người/tháng</h5>
                        <h2><?php echo formatCurrency($totalServicePrice / max($room['capacity'], 1)); ?></h2>
                        <small>Chia đều cho <?php echo $room['capacity']; ?> người</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách dịch vụ -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách dịch vụ</h5>
            </div>
            <div class="card-body">
                <?php if (empty($roomServices)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>Phòng này chưa có dịch vụ nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Mã dịch vụ</th>
                                    <th class="text-nowrap">Tên dịch vụ</th>
                                    <th class="text-nowrap text-end">Giá/phòng</th>
                                    <th class="text-nowrap">Đơn vị</th>
                                    <th class="text-nowrap">Ngày bắt đầu</th>
                                    <th class="text-nowrap">Ngày kết thúc</th>
                                    <th class="text-nowrap text-center" style="width: 100px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roomServices as $rs): ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($rs['service_code']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($rs['service_name']); ?></td>
                                        <td class="text-nowrap text-end align-middle"><strong><?php echo formatCurrency($rs['price']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($rs['unit']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo formatDate($rs['start_date']); ?></td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo $rs['end_date'] ? formatDate($rs['end_date']) : '<span class="text-muted">Đang áp dụng</span>'; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmRemove(<?php echo $rs['id']; ?>, '<?php echo escapeHtml($rs['service_name']); ?>')"
                                                    title="Hủy dịch vụ">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="2" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($totalServicePrice); ?></strong></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form xóa ẩn -->
    <form id="removeForm" method="POST" action="../../../../handle/room_services_process.php" style="display: none;">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="id" id="removeId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmRemove(id, serviceName) {
            if (confirm('Bạn có chắc chắn muốn hủy dịch vụ "' + serviceName + '" khỏi phòng này không?')) {
                document.getElementById('removeId').value = id;
                document.getElementById('removeForm').submit();
            }
        }
    </script>
</body>
</html>

