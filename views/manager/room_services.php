<?php
/**
 * Quản lý dịch vụ của phòng - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/room_services.php';
require_once __DIR__ . '/../../functions/rooms.php';
require_once __DIR__ . '/../../functions/services.php';
require_once __DIR__ . '/../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$rooms = getAllRoomsWithServices();
$allServices = getAllServices(false); // Chỉ lấy active
$buildings = getAllBuildings();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lọc theo tòa nhà
$filterBuilding = isset($_GET['building_id']) ? intval($_GET['building_id']) : null;
if ($filterBuilding) {
    $rooms = array_filter($rooms, function($room) use ($filterBuilding) {
        return $room['building_id'] == $filterBuilding;
    });
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gán Dịch vụ cho Phòng - Quản lý KTX</title>
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
                <a class="nav-link active" href="room_services.php">Dịch vụ phòng</a>
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
            <h2><i class="bi bi-link-45deg me-2"></i>Gán Dịch vụ cho Phòng</h2>
            <a href="services.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Quản lý Dịch vụ
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
                <form method="GET" action="room_services.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="building_id" class="form-label">Lọc theo tòa nhà</label>
                        <select class="form-select" id="building_id" name="building_id" onchange="this.form.submit()">
                            <option value="">Tất cả tòa nhà</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?php echo $building['id']; ?>" 
                                        <?php echo ($filterBuilding == $building['id']) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($building['building_code'] . ' - ' . $building['building_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="room_services.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách phòng và dịch vụ -->
        <div class="row">
            <?php foreach ($rooms as $room): 
                $roomServices = getRoomServices($room['id']);
                $totalServicePrice = getTotalRoomServicePrice($room['id']);
            ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-door-open me-2"></i>
                                <?php echo escapeHtml($room['building_code'] . ' - ' . $room['room_code']); ?>
                                <span class="badge bg-light text-dark ms-2">
                                    <?php echo $room['service_count'] ?? 0; ?> dịch vụ
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Loại phòng:</strong> <?php echo escapeHtml($room['room_type']); ?><br>
                                <strong>Sức chứa:</strong> <?php echo $room['capacity']; ?> người<br>
                                <strong>Đang ở:</strong> <?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?> người
                            </p>
                            
                            <hr>
                            
                            <!-- Tóm tắt dịch vụ -->
                            <div class="mb-3">
                                <?php if (empty($roomServices)): ?>
                                    <div class="alert alert-warning mb-2 py-2">
                                        <small><i class="bi bi-info-circle me-1"></i>Chưa có dịch vụ nào</small>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo count($roomServices); ?> dịch vụ</strong> | 
                                            <span class="text-success"><strong><?php echo formatCurrency($totalServicePrice); ?>/tháng</strong></span>
                                        </div>
                                        <a href="room_services/view.php?room_id=<?php echo $room['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Xem chi tiết
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Form gán dịch vụ mới -->
                            <form method="POST" action="../../handle/room_services_process.php" class="mt-3">
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                
                                <label class="form-label"><small>Chọn dịch vụ để gán (có thể chọn nhiều):</small></label>
                                <?php 
                                // Lọc các dịch vụ chưa được gán
                                $assignedServiceIds = array_column($roomServices, 'service_id');
                                $availableServices = array_filter($allServices, function($service) use ($assignedServiceIds) {
                                    return !in_array($service['id'], $assignedServiceIds);
                                });
                                
                                if (empty($availableServices)): ?>
                                    <div class="alert alert-info mb-0 py-2">
                                        <small><i class="bi bi-check-circle me-1"></i>Đã gán tất cả dịch vụ</small>
                                    </div>
                                <?php else: ?>
                                    <div class="dropdown mb-2">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100" 
                                                type="button" 
                                                id="dropdownMenuButton_<?php echo $room['id']; ?>" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <span id="selectedText_<?php echo $room['id']; ?>">-- Chọn dịch vụ --</span>
                                        </button>
                                        <ul class="dropdown-menu w-100" aria-labelledby="dropdownMenuButton_<?php echo $room['id']; ?>" style="max-height: 250px; overflow-y: auto;">
                                            <?php foreach ($availableServices as $service): ?>
                                                <li>
                                                    <div class="dropdown-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input service-checkbox" 
                                                                   type="checkbox" 
                                                                   name="service_ids[]" 
                                                                   value="<?php echo $service['id']; ?>" 
                                                                   id="service_<?php echo $room['id']; ?>_<?php echo $service['id']; ?>"
                                                                   onchange="updateSelectedText(<?php echo $room['id']; ?>)">
                                                            <label class="form-check-label w-100" for="service_<?php echo $room['id']; ?>_<?php echo $service['id']; ?>">
                                                                <?php echo escapeHtml($service['service_name']); ?> 
                                                                <span class="text-muted">(<?php echo formatCurrency($service['price']); ?>)</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-plus-circle me-1"></i>Thêm dịch vụ đã chọn
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Form xóa ẩn -->
    <form id="removeForm" method="POST" action="../../handle/room_services_process.php" style="display: none;">
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
        
        function updateSelectedText(roomId) {
            const dropdown = document.getElementById('dropdownMenuButton_' + roomId);
            const menu = dropdown.nextElementSibling;
            const checkboxes = menu.querySelectorAll('input[type="checkbox"]');
            const selectedText = document.getElementById('selectedText_' + roomId);
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            
            if (selected.length === 0) {
                selectedText.textContent = '-- Chọn dịch vụ --';
            } else if (selected.length === 1) {
                const label = selected[0].closest('.dropdown-item').querySelector('label').textContent.trim();
                selectedText.textContent = label;
            } else {
                selectedText.textContent = 'Đã chọn ' + selected.length + ' dịch vụ';
            }
        }
        
        // Ngăn dropdown đóng khi click vào checkbox
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.dropdown-menu').forEach(function(dropdown) {
                dropdown.addEventListener('click', function(e) {
                    if (e.target.type === 'checkbox' || e.target.closest('.form-check')) {
                        e.stopPropagation();
                    }
                });
            });
        });
    </script>
</body>
</html>

