<?php
/**
 * Danh sách hợp đồng - Manager
 */

require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/contracts.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$statusFilter = $_GET['status'] ?? null;
$contracts = getAllContracts($statusFilter);
$statuses = getContractStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hợp đồng - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="contracts.php">Hợp đồng</a>
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
            <h2><i class="bi bi-file-earmark-text me-2"></i>Quản lý Hợp đồng</h2>
            <a href="contracts/create_contract.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tạo hợp đồng mới
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

        <!-- Bộ lọc -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="contracts.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Lọc theo trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($statusFilter == $key) ? 'selected' : ''; ?>>
                                    <?php echo escapeHtml($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="contracts.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách hợp đồng -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh sách hợp đồng (<?php echo count($contracts); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($contracts)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Chưa có hợp đồng nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã hợp đồng</th>
                                    <th>Sinh viên</th>
                                    <th>Phòng</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Phí/tháng</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escapeHtml($contract['contract_code']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo escapeHtml($contract['student_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo escapeHtml($contract['student_code']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo escapeHtml($contract['building_code'] . ' - ' . $contract['room_code']); ?></strong><br>
                                                <small class="text-muted"><?php echo escapeHtml($contract['room_type']); ?></small>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><?php echo formatDate($contract['start_date']); ?></td>
                                        <td class="text-nowrap"><?php echo formatDate($contract['end_date']); ?></td>
                                        <td class="text-nowrap"><?php echo formatCurrency($contract['monthly_fee']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'active' => 'success',
                                                'expired' => 'warning',
                                                'terminated' => 'danger'
                                            ];
                                            $statusLabel = $statuses[$contract['status']] ?? $contract['status'];
                                            $class = $statusClass[$contract['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>"><?php echo escapeHtml($statusLabel); ?></span>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <a href="contracts/view_contract.php?id=<?php echo $contract['id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($contract['status'] != 'terminated'): ?>
                                                <a href="contracts/edit_contract.php?id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
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

