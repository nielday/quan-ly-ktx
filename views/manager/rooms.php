<?php
/**
 * Danh sách phòng - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/rooms.php';
require_once __DIR__ . '/../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$buildings = getAllBuildings();
$roomStatuses = getRoomStatuses();

// Lọc
$filterBuilding = isset($_GET['building_id']) ? intval($_GET['building_id']) : null;
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;

$rooms = getAllRooms($filterBuilding, $filterStatus);
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phòng - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="rooms.php">Phòng</a>
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
            <h2><i class="bi bi-door-open me-2"></i>Quản lý Phòng</h2>
            <a href="rooms/create_room.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Thêm phòng mới
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

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="rooms.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="building_id" class="form-label">Lọc theo tòa nhà</label>
                        <select class="form-select" id="building_id" name="building_id">
                            <option value="">Tất cả tòa nhà</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?php echo $building['id']; ?>" 
                                        <?php echo ($filterBuilding == $building['id']) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($building['building_code'] . ' - ' . $building['building_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Lọc theo trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($roomStatuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo ($filterStatus == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="rooms.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách phòng -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách phòng (<?php echo count($rooms); ?> phòng)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($rooms)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có phòng nào. Hãy thêm phòng mới!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Mã phòng</th>
                                    <th class="text-nowrap">Tòa nhà</th>
                                    <th class="text-nowrap">Số phòng</th>
                                    <th class="text-nowrap">Tầng</th>
                                    <th class="text-nowrap">Loại phòng</th>
                                    <th class="text-nowrap text-center">Sức chứa</th>
                                    <th class="text-nowrap text-center">Đang ở</th>
                                    <th class="text-nowrap">Giá/tháng</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap">Tiện ích</th>
                                    <th class="text-nowrap text-center" style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): 
                                    $occupancyPercent = $room['capacity'] > 0 ? ($room['current_occupancy'] / $room['capacity']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($room['room_code']); ?></strong></td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo escapeHtml($room['building_code'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($room['room_number']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($room['floor']); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($room['room_type']); ?></td>
                                        <td class="text-center align-middle"><?php echo escapeHtml($room['capacity']); ?> người</td>
                                        <td class="text-center align-middle">
                                            <span class="badge <?php echo $room['current_occupancy'] > 0 ? 'bg-info' : 'bg-secondary'; ?>">
                                                <?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo formatCurrency($room['price_per_month'] ?? 0); ?></td>
                                        <td class="text-center align-middle">
                                            <?php 
                                            $statusClass = [
                                                'available' => 'bg-success',
                                                'occupied' => 'bg-primary',
                                                'maintenance' => 'bg-warning'
                                            ];
                                            $statusLabel = $roomStatuses[$room['status']] ?? $room['status'];
                                            $class = $statusClass[$room['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                                        </td>
                                        <td class="align-middle">
                                            <?php 
                                            $amenities = $room['amenities'] ?? '';
                                            echo escapeHtml(mb_substr($amenities, 0, 30));
                                            if (mb_strlen($amenities) > 30) echo '...';
                                            ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="rooms/edit_room.php?id=<?php echo $room['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $room['id']; ?>, '<?php echo escapeHtml($room['room_code']); ?>')"
                                                    title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form xóa ẩn -->
    <form id="deleteForm" method="POST" action="../../handle/rooms_process.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, code) {
            if (confirm('Bạn có chắc chắn muốn xóa phòng "' + code + '" không?\n\nLưu ý: Chỉ có thể xóa khi phòng không có người ở.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

