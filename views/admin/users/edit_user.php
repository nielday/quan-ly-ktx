<?php
/**
 * Sửa User - Admin
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/users.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

$currentUser = getCurrentUser();
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    setErrorMessage('ID user không hợp lệ!');
    redirect('../users.php');
    exit;
}

$user = getUserById($userId);

if (!$user) {
    setErrorMessage('User không tồn tại!');
    redirect('../users.php');
    exit;
}

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
    <title>Sửa Tài khoản - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../users.php">Tài khoản</a>
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
            <h2><i class="bi bi-pencil me-2"></i>Sửa Tài khoản: <?php echo escapeHtml($user['username']); ?></h2>
            <a href="../users.php" class="btn btn-secondary">
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
            <div class="col-md-8 mx-auto">
                <!-- Form sửa thông tin -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Thông tin Tài khoản</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/users_process.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo escapeHtml($user['username']); ?>" 
                                       disabled>
                                <small class="text-muted">Username không thể thay đổi</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo escapeHtml($user['full_name']); ?>" 
                                       required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo escapeHtml($user['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Điện thoại</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?php echo escapeHtml($user['phone'] ?? ''); ?>"
                                           pattern="[0-9]{10,11}" 
                                           title="Số điện thoại từ 10-11 chữ số">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <?php foreach ($roles as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" 
                                                    <?php echo ($user['role'] === $value) ? 'selected' : ''; ?>>
                                                <?php echo escapeHtml($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <input type="text" class="form-control" value="Hoạt động" disabled>
                                        <input type="hidden" name="status" value="active">
                                        <small class="text-muted">Tài khoản Admin luôn ở trạng thái hoạt động và không thể khóa</small>
                                    <?php else: ?>
                                        <select class="form-select" id="status" name="status" required>
                                            <?php foreach ($statuses as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" 
                                                        <?php echo ($user['status'] === $value) ? 'selected' : ''; ?>
                                                        <?php echo ($user['id'] == $currentUser['id'] && $value === 'inactive') ? 'disabled' : ''; ?>>
                                                    <?php echo escapeHtml($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($user['id'] == $currentUser['id']): ?>
                                            <small class="text-muted">Bạn không thể khóa tài khoản của chính mình</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="../users.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Cập nhật
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Form reset mật khẩu -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Reset Mật khẩu</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/users_process.php" onsubmit="return confirmResetPassword()">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       required 
                                       minlength="6">
                                <small class="text-muted">Tối thiểu 6 ký tự</small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Lưu ý:</strong> Mật khẩu mới sẽ thay thế mật khẩu cũ. User sẽ cần đăng nhập lại với mật khẩu mới.
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-2"></i>Reset Mật khẩu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmResetPassword() {
            return confirm('Bạn có chắc chắn muốn reset mật khẩu cho tài khoản này không?\n\nUser sẽ cần đăng nhập lại với mật khẩu mới.');
        }
    </script>
</body>
</html>

