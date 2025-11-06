<?php
/**
 * Tạo phòng mới - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/rooms.php';
require_once __DIR__ . '/../../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$buildings = getAllBuildings();
$roomTypes = getRoomTypes();
$roomStatuses = getRoomStatuses();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Phòng mới - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        function updateCapacity() {
            const roomType = document.getElementById('room_type').value;
            const capacityField = document.getElementById('capacity');
            
            const capacities = {
                'đơn': 1,
                'đôi': 2,
                '4 người': 4,
                '6 người': 6
            };
            
            if (capacities[roomType]) {
                capacityField.value = capacities[roomType];
            }
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link active" href="../rooms.php">Phòng</a>
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
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-plus-circle me-2"></i>Thêm Phòng mới
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../../handle/rooms_process.php">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="building_id" class="form-label">
                                        Tòa nhà <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="building_id" name="building_id" required>
                                        <option value="">-- Chọn tòa nhà --</option>
                                        <?php foreach ($buildings as $building): ?>
                                            <option value="<?php echo $building['id']; ?>">
                                                <?php echo escapeHtml($building['building_code'] . ' - ' . $building['building_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="room_code" class="form-label">
                                        Mã phòng <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="room_code" 
                                           name="room_code" 
                                           required
                                           placeholder="Ví dụ: A101, B201..."
                                           maxlength="20">
                                    <div class="form-text">Mã phòng phải là duy nhất</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="room_number" class="form-label">
                                        Số phòng <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="room_number" 
                                           name="room_number" 
                                           required
                                           placeholder="Ví dụ: 101, 201..."
                                           maxlength="20">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="floor" class="form-label">Tầng</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="floor" 
                                           name="floor" 
                                           value="1"
                                           min="1"
                                           max="50">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="room_type" class="form-label">
                                        Loại phòng <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="room_type" name="room_type" required onchange="updateCapacity()">
                                        <option value="">-- Chọn loại phòng --</option>
                                        <?php foreach ($roomTypes as $key => $label): ?>
                                            <option value="<?php echo escapeHtml($key); ?>">
                                                <?php echo escapeHtml($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Giá phòng sẽ tự động lấy từ đơn giá</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="capacity" class="form-label">
                                        Sức chứa (số người) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="capacity" 
                                           name="capacity" 
                                           value="4"
                                           min="1"
                                           max="10"
                                           required>
                                    <div class="form-text">Sức chứa sẽ tự động điền khi chọn loại phòng</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach ($roomStatuses as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" 
                                                    <?php echo ($key == 'available') ? 'selected' : ''; ?>>
                                                <?php echo escapeHtml($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="amenities" class="form-label">Tiện ích</label>
                                <textarea class="form-control" 
                                          id="amenities" 
                                          name="amenities" 
                                          rows="3"
                                          placeholder="Ví dụ: Điều hòa, Tủ lạnh, WiFi, Máy nước nóng..."></textarea>
                                <div class="form-text">Liệt kê các tiện ích của phòng, cách nhau bởi dấu phẩy</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../rooms.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Thêm phòng
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

