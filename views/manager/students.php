<?php
/**
 * Danh sách sinh viên - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/students.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Lọc theo status
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;
$students = getAllStudents($filterStatus);
$statuses = getStudentStatuses();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sinh viên - Quản lý KTX</title>
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
                <a class="nav-link active" href="students.php">Sinh viên</a>
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
            <h2><i class="bi bi-people me-2"></i>Quản lý Sinh viên</h2>
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
                <form method="GET" action="students.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Lọc theo trạng thái</label>
                        <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($filterStatus == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="students.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách sinh viên -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách sinh viên</h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có sinh viên nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Mã SV</th>
                                    <th class="text-nowrap">Họ tên</th>
                                    <th class="text-nowrap">Giới tính</th>
                                    <th class="text-nowrap">SĐT</th>
                                    <th class="text-nowrap">Email</th>
                                    <th class="text-nowrap">Trường</th>
                                    <th class="text-nowrap">Ngành</th>
                                    <th class="text-nowrap text-center">Trạng thái</th>
                                    <th class="text-nowrap text-center" style="width: 100px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $genders = getGenders();
                                foreach ($students as $student): 
                                    $currentRoom = getStudentCurrentRoom($student['id']);
                                ?>
                                    <tr>
                                        <td class="text-nowrap align-middle"><strong><?php echo escapeHtml($student['student_code']); ?></strong></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($student['full_name']); ?></td>
                                        <td class="text-nowrap align-middle">
                                            <?php echo $student['gender'] ? escapeHtml($genders[$student['gender']] ?? $student['gender']) : '-'; ?>
                                        </td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($student['phone'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($student['email'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($student['university'] ?: '-'); ?></td>
                                        <td class="text-nowrap align-middle"><?php echo escapeHtml($student['major'] ?: '-'); ?></td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $statusClass = [
                                                'active' => 'bg-success',
                                                'inactive' => 'bg-secondary',
                                                'graduated' => 'bg-info'
                                            ];
                                            $statusLabel = $statuses[$student['status']] ?? $student['status'];
                                            $class = $statusClass[$student['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="students/view_student.php?id=<?php echo $student['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

