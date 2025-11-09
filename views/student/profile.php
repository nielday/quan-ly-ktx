<?php
/**
 * Thông tin cá nhân - Student
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage('Không tìm thấy thông tin sinh viên!');
    redirect('dashboard.php');
}

$statuses = getStudentStatuses();
$genders = getGenders();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin Cá nhân - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="profile.php">Thông tin cá nhân</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-circle me-2"></i>Thông tin Cá nhân
                        </h5>
                    </div>
                    <div class="card-body">
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
                        
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Hướng dẫn:</strong> Vui lòng điền đầy đủ thông tin cá nhân. Các trường có dấu <span class="text-danger">*</span> là bắt buộc. Thông tin sẽ được sử dụng để quản lý và liên hệ với bạn.
                        </div>
                        
                        <form method="POST" action="../../handle/students_process.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="student_code" class="form-label">
                                    Mã sinh viên <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="student_code" 
                                       name="student_code" 
                                       value="<?php echo escapeHtml($student['student_code']); ?>"
                                       required
                                       maxlength="20"
                                       readonly>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Mã sinh viên đã được hệ thống cấp, không thể thay đổi.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">
                                    Họ tên <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?php echo escapeHtml($student['full_name']); ?>"
                                       required
                                       maxlength="100"
                                       placeholder="Ví dụ: Nguyễn Văn A">
                                <small class="text-muted">
                                    <i class="bi bi-person-badge me-1"></i>Nhập đầy đủ họ và tên của bạn (không dấu hoặc có dấu đều được)
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Ngày sinh</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="date_of_birth" 
                                           name="date_of_birth" 
                                           value="<?php echo $student['date_of_birth'] ? $student['date_of_birth'] : ''; ?>">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>Chọn ngày sinh của bạn (định dạng: DD/MM/YYYY)
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Giới tính</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">-- Chọn giới tính --</option>
                                        <?php foreach ($genders as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo ($student['gender'] == $key) ? 'selected' : ''; ?>>
                                                <?php echo escapeHtml($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>Chọn giới tính của bạn
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo escapeHtml($student['phone'] ?? ''); ?>"
                                           maxlength="20"
                                           pattern="[0-9]{10,11}"
                                           placeholder="Ví dụ: 0123456789">
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i>Số điện thoại để liên hệ (10-11 chữ số, chỉ nhập số)
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo escapeHtml($student['email'] ?? ''); ?>"
                                           maxlength="100"
                                           placeholder="Ví dụ: sinhvien@example.com">
                                    <small class="text-muted">
                                        <i class="bi bi-envelope me-1"></i>Email để nhận thông báo từ hệ thống
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ</label>
                                <textarea class="form-control" 
                                          id="address" 
                                          name="address" 
                                          rows="2"
                                          placeholder="Ví dụ: Số nhà, Tên đường, Phường/Xã, Quận/Huyện, Tỉnh/Thành phố"><?php echo escapeHtml($student['address'] ?? ''); ?></textarea>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>Địa chỉ thường trú hoặc địa chỉ liên hệ (đầy đủ: số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố)
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="university" class="form-label">Trường đại học</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="university" 
                                           name="university" 
                                           value="<?php echo escapeHtml($student['university'] ?? ''); ?>"
                                           maxlength="200"
                                           placeholder="Ví dụ: Đại học Bách Khoa Hà Nội">
                                    <small class="text-muted">
                                        <i class="bi bi-building me-1"></i>Tên trường đại học bạn đang theo học
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="major" class="form-label">Ngành học</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="major" 
                                           name="major" 
                                           value="<?php echo escapeHtml($student['major'] ?? ''); ?>"
                                           maxlength="100"
                                           placeholder="Ví dụ: Công nghệ Thông tin">
                                    <small class="text-muted">
                                        <i class="bi bi-book me-1"></i>Ngành/chuyên ngành bạn đang học
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="year" class="form-label">Khóa học</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="year" 
                                           name="year" 
                                           value="<?php echo escapeHtml($student['year'] ?? ''); ?>"
                                           maxlength="20"
                                           placeholder="Ví dụ: K64, 2024, K2024">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-check me-1"></i>Khóa học bạn đang theo học (ví dụ: K64, 2024)
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="id_card" class="form-label">CCCD/CMND</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="id_card" 
                                           name="id_card" 
                                           value="<?php echo escapeHtml($student['id_card'] ?? ''); ?>"
                                           maxlength="20"
                                           pattern="[0-9]{9,12}"
                                           placeholder="Ví dụ: 001234567890">
                                    <small class="text-muted">
                                        <i class="bi bi-card-text me-1"></i>Số Căn cước công dân (12 chữ số) hoặc Chứng minh nhân dân (9 chữ số), chỉ nhập số
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Cập nhật thông tin
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

