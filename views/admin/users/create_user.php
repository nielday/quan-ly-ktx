<?php
/**
 * Tạo User mới - Admin
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/users.php';

// Kiểm tra đăng nhập và quyền admin
checkRole('admin');

$currentUser = getCurrentUser();
$roles = getUserRoles();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Tài khoản mới - Admin</title>
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
            <h2><i class="bi bi-person-plus me-2"></i>Tạo Tài khoản mới</h2>
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
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Thông tin Tài khoản</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/users_process.php" onsubmit="return validateForm()">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo escapeHtml($_POST['username'] ?? ''); ?>" 
                                           required 
                                           pattern="[a-zA-Z0-9_]+" 
                                           title="Chỉ chấp nhận chữ cái, số và dấu gạch dưới">
                                    <small class="text-muted">Chỉ chấp nhận chữ cái, số và dấu gạch dưới</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required 
                                           minlength="6">
                                    <small class="text-muted">Tối thiểu 6 ký tự</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo escapeHtml($_POST['full_name'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo escapeHtml($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Điện thoại</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?php echo escapeHtml($_POST['phone'] ?? ''); ?>"
                                           pattern="[0-9]{10,11}" 
                                           title="Số điện thoại từ 10-11 chữ số">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Chọn vai trò --</option>
                                    <?php foreach ($roles as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                                <?php echo (isset($_POST['role']) && $_POST['role'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo escapeHtml($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="alert alert-info" id="infoAlert">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0" id="infoList">
                                    <li>Username phải là duy nhất và không được trùng với tài khoản khác</li>
                                    <li>Mật khẩu tối thiểu 6 ký tự</li>
                                    <li>Email và điện thoại là tùy chọn</li>
                                    <li id="studentNote" style="display: none;">
                                        <strong>Khi chọn vai trò "Sinh viên":</strong> Hệ thống sẽ tự động tạo record trong bảng students với mã sinh viên tự động (format: SV001, SV002, SV003...). Mã sẽ tự động tăng dựa trên mã sinh viên lớn nhất hiện có. Sinh viên có thể đăng nhập ngay và cập nhật thông tin chi tiết sau.
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="../users.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Tạo tài khoản
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateForm() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const fullName = document.getElementById('full_name').value;
            const role = document.getElementById('role').value;
            
            if (!username || !password || !fullName || !role) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Mật khẩu phải có ít nhất 6 ký tự!');
                return false;
            }
            
            return true;
        }
        
        // Hiển thị/ẩn thông báo cho student
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const studentNote = document.getElementById('studentNote');
            
            roleSelect.addEventListener('change', function() {
                if (this.value === 'student') {
                    studentNote.style.display = 'list-item';
                } else {
                    studentNote.style.display = 'none';
                }
            });
            
            // Kiểm tra giá trị ban đầu
            if (roleSelect.value === 'student') {
                studentNote.style.display = 'list-item';
            }
        });
    </script>
</body>
</html>

