<?php
/**
 * Danh sách Users - Admin
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/users.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

$currentUser = getCurrentUser();

// Lấy filters
$filters = [];
if (!empty($_GET['role'])) {
    $filters['role'] = $_GET['role'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Lấy danh sách users
$users = getAllUsers($filters);
$stats = getUserStatistics();
$roles = getUserRoles();
$statuses = getUserStatuses();

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài khoản - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="users.php">Tài khoản</a>
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
            <h2><i class="bi bi-people me-2"></i>Quản lý Tài khoản</h2>
            <a href="users/create_user.php" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Tạo tài khoản mới
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

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <h4 class="mb-0 text-primary"><?php echo $stats['total']; ?></h4>
                        <small class="text-muted">Tổng số</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h4 class="mb-0 text-danger"><?php echo $stats['admin']; ?></h4>
                        <small class="text-muted">Admin</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="mb-0 text-success"><?php echo $stats['manager']; ?></h4>
                        <small class="text-muted">Manager</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h4 class="mb-0 text-info"><?php echo $stats['student']; ?></h4>
                        <small class="text-muted">Student</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="mb-0 text-success"><?php echo $stats['active']; ?></h4>
                        <small class="text-muted">Hoạt động</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-secondary">
                    <div class="card-body">
                        <h4 class="mb-0 text-secondary"><?php echo $stats['inactive']; ?></h4>
                        <small class="text-muted">Không hoạt động</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="users.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (isset($filters['role']) && $filters['role'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (isset($filters['status']) && $filters['status'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Username, tên, email..." 
                               value="<?php echo escapeHtml($filters['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách users -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách Tài khoản (<?php echo count($users); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Không tìm thấy tài khoản nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Điện thoại</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><strong><?php echo escapeHtml($user['username']); ?></strong></td>
                                        <td><?php echo escapeHtml($user['full_name']); ?></td>
                                        <td><?php echo escapeHtml($user['email'] ?? '-'); ?></td>
                                        <td><?php echo escapeHtml($user['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $roleBadges = [
                                                'admin' => 'danger',
                                                'manager' => 'success',
                                                'student' => 'info'
                                            ];
                                            $badge = $roleBadges[$user['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo escapeHtml($roles[$user['role']] ?? $user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = ($user['status'] === 'active') ? 'success' : 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusBadge; ?>">
                                                <?php echo escapeHtml($statuses[$user['status']] ?? $user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="users/edit_user.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning" 
                                                            onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')"
                                                            title="Khóa/Mở khóa">
                                                        <i class="bi bi-<?php echo $user['status'] === 'active' ? 'lock' : 'unlock'; ?>"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-secondary" 
                                                            disabled
                                                            title="Không thể khóa tài khoản Admin">
                                                        <i class="bi bi-shield-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($user['id'] != $currentUser['id'] && $user['role'] !== 'admin'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo escapeHtml($user['username']); ?>')"
                                                            title="Xóa">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
    <form id="deleteForm" method="POST" action="../../handle/users_process.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <!-- Form toggle status ẩn -->
    <form id="toggleStatusForm" method="POST" action="../../handle/users_process.php" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="user_id" id="toggleStatusUserId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(userId, username) {
            if (confirm('Bạn có chắc chắn muốn xóa tài khoản "' + username + '" không?\n\nLưu ý: Hành động này không thể hoàn tác!')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        function toggleStatus(userId, currentStatus) {
            const action = currentStatus === 'active' ? 'khóa' : 'mở khóa';
            if (confirm('Bạn có chắc chắn muốn ' + action + ' tài khoản này không?')) {
                document.getElementById('toggleStatusUserId').value = userId;
                document.getElementById('toggleStatusForm').submit();
            }
        }
    </script>
</body>
</html>

