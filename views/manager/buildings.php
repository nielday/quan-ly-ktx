<?php
/**
 * Danh sách tòa nhà - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/buildings.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$buildings = getAllBuildings();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tòa nhà - Quản lý KTX</title>
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
                <a class="nav-link active" href="buildings.php">Tòa nhà</a>
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
            <h2><i class="bi bi-building me-2"></i>Quản lý Tòa nhà</h2>
            <a href="buildings/create_building.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Thêm tòa nhà mới
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

        <!-- Bảng danh sách tòa nhà -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách tòa nhà</h5>
            </div>
            <div class="card-body">
                <?php if (empty($buildings)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có tòa nhà nào. Hãy thêm tòa nhà mới!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã tòa</th>
                                    <th>Tên tòa</th>
                                    <th>Địa chỉ</th>
                                    <th>Số tầng</th>
                                    <th>Số phòng</th>
                                    <th>Mô tả</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buildings as $building): 
                                    $roomCount = countRoomsInBuilding($building['id']);
                                ?>
                                    <tr>
                                        <td><strong><?php echo escapeHtml($building['building_code']); ?></strong></td>
                                        <td><?php echo escapeHtml($building['building_name']); ?></td>
                                        <td><?php echo escapeHtml($building['address'] ?? 'N/A'); ?></td>
                                        <td><?php echo escapeHtml($building['floors']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $roomCount; ?> phòng</span>
                                        </td>
                                        <td>
                                            <?php 
                                            $description = $building['description'] ?? '';
                                            echo escapeHtml(mb_substr($description, 0, 50));
                                            if (mb_strlen($description) > 50) echo '...';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="buildings/edit_building.php?id=<?php echo $building['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $building['id']; ?>, '<?php echo escapeHtml($building['building_name']); ?>')"
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
    <form id="deleteForm" method="POST" action="../../handle/buildings_process.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa tòa nhà "' + name + '" không?\n\nLưu ý: Chỉ có thể xóa khi tòa nhà không có phòng nào.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

