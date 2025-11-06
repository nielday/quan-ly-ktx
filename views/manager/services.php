<?php
/**
 * Danh sách dịch vụ - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/services.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$services = getAllServices(true); // Lấy cả inactive
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Dịch vụ - Quản lý KTX</title>
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
                <a class="nav-link active" href="services.php">Dịch vụ</a>
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
            <h2><i class="bi bi-gear me-2"></i>Quản lý Dịch vụ</h2>
            <div>
                <a href="room_services.php" class="btn btn-info me-2">
                    <i class="bi bi-link-45deg me-2"></i>Gán dịch vụ cho phòng
                </a>
                <a href="services/create_service.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Thêm dịch vụ mới
                </a>
            </div>
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

        <!-- Bảng danh sách dịch vụ -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách dịch vụ</h5>
            </div>
            <div class="card-body">
                <?php if (empty($services)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có dịch vụ nào. Hãy thêm dịch vụ mới!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Mã dịch vụ</th>
                                    <th class="text-nowrap">Tên dịch vụ</th>
                                    <th class="text-nowrap">Giá/phòng</th>
                                    <th class="text-nowrap">Đơn vị</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th>Mô tả</th>
                                    <th class="text-nowrap text-center" style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($service['service_code']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($service['service_name']); ?></td>
                                        <td class="text-nowrap align-middle"><strong><?php echo formatCurrency($service['price']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($service['unit']); ?></td>
                                        <td class="text-center align-middle">
                                            <?php if ($service['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php 
                                            $desc = $service['description'] ?? '';
                                            echo escapeHtml(mb_substr($desc, 0, 50));
                                            if (mb_strlen($desc) > 50) echo '...';
                                            ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="services/edit_service.php?id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $service['id']; ?>, '<?php echo escapeHtml($service['service_name']); ?>')"
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
    <form id="deleteForm" method="POST" action="../../handle/services_process.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa dịch vụ "' + name + '" không?\n\nLưu ý: Chỉ có thể xóa khi dịch vụ không được sử dụng bởi phòng nào.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

