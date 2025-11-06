<?php
/**
 * Chi tiết hóa đơn - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$invoiceId = intval($_GET['id'] ?? 0);

if ($invoiceId <= 0) {
    setErrorMessage('ID hóa đơn không hợp lệ!');
    redirect('invoices.php');
}

$invoice = getInvoiceById($invoiceId);

if (!$invoice) {
    setErrorMessage('Hóa đơn không tồn tại!');
    redirect('invoices.php');
}

$statuses = getInvoiceStatuses();
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();

// Parse service_details
$serviceDetails = [];
if (!empty($invoice['service_details'])) {
    $serviceDetails = json_decode($invoice['service_details'], true);
    if (!is_array($serviceDetails)) {
        $serviceDetails = [];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Hóa đơn - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .invoice-detail-card {
            border-left: 4px solid #667eea;
        }
        .amount-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="invoices.php">Hóa đơn</a>
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
            <h2><i class="bi bi-receipt me-2"></i>Chi tiết Hóa đơn</h2>
            <a href="invoices.php" class="btn btn-secondary">
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

        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-3">HÓA ĐƠN THANH TOÁN</h3>
                    <p class="mb-1"><strong>Mã hóa đơn:</strong> <?php echo escapeHtml($invoice['invoice_code']); ?></p>
                    <p class="mb-1"><strong>Tháng:</strong> <?php echo escapeHtml($invoice['invoice_month']); ?></p>
                    <p class="mb-0"><strong>Ngày tạo:</strong> <?php echo formatDateTime($invoice['created_at']); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php
                    $statusClass = [
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'secondary'
                    ];
                    $statusLabel = $statuses[$invoice['status']] ?? $invoice['status'];
                    $class = $statusClass[$invoice['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $class; ?> fs-6 px-3 py-2">
                        <?php echo escapeHtml($statusLabel); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin sinh viên và phòng -->
            <div class="col-md-6 mb-4">
                <div class="card invoice-detail-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Thông tin Sinh viên</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Họ tên:</strong> <?php echo escapeHtml($invoice['student_name']); ?></p>
                        <p class="mb-2"><strong>Mã sinh viên:</strong> <?php echo escapeHtml($invoice['student_code']); ?></p>
                        <?php if ($invoice['phone']): ?>
                            <p class="mb-2"><strong>Điện thoại:</strong> <?php echo escapeHtml($invoice['phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['email']): ?>
                            <p class="mb-0"><strong>Email:</strong> <?php echo escapeHtml($invoice['email']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card invoice-detail-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-door-open me-2"></i>Thông tin Phòng</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Phòng:</strong> 
                            <?php 
                            if ($invoice['building_code']) {
                                echo escapeHtml($invoice['building_code'] . ' - ');
                            }
                            echo escapeHtml($invoice['room_code']); 
                            ?>
                        </p>
                        <p class="mb-2"><strong>Loại phòng:</strong> <?php echo escapeHtml($invoice['room_type']); ?></p>
                        <p class="mb-0"><strong>Số người trong phòng:</strong> <?php echo $invoice['room_occupancy_count']; ?> người</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chi tiết hóa đơn -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Chi tiết Hóa đơn</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Khoản mục</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Tổng (cả phòng)</th>
                                <th class="text-end">Chia đều (mỗi người)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Tiền phòng -->
                            <tr>
                                <td><strong>Tiền phòng</strong></td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['room_total_fee'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['room_fee_per_person'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                            </tr>
                            
                            <!-- Tiền điện -->
                            <tr>
                                <td><strong>Tiền điện</strong></td>
                                <td class="text-end"><?php echo number_format($invoice['electricity_amount'], 2, ',', '.'); ?> kWh</td>
                                <td class="text-end"><?php echo number_format($invoice['electricity_unit_price'], 0, ',', '.'); ?> VNĐ/kWh</td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['electricity_total_room'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['electricity_amount_per_person'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                            </tr>
                            
                            <!-- Tiền nước -->
                            <tr>
                                <td><strong>Tiền nước</strong></td>
                                <td class="text-end"><?php echo number_format($invoice['water_amount'], 2, ',', '.'); ?> m³</td>
                                <td class="text-end"><?php echo number_format($invoice['water_unit_price'], 0, ',', '.'); ?> VNĐ/m³</td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['water_total_room'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($invoice['water_amount_per_person'], 0, ',', '.'); ?> VNĐ</strong>
                                </td>
                            </tr>
                            
                            <!-- Tiền dịch vụ -->
                            <?php if ($invoice['service_total_room'] > 0): ?>
                                <tr>
                                    <td><strong>Tiền dịch vụ</strong>
                                        <?php if (!empty($serviceDetails)): ?>
                                            <br><small class="text-muted">
                                                <?php 
                                                $serviceNames = array_map(function($s) {
                                                    return $s['service_name'] . ' (' . number_format($s['price'], 0, ',', '.') . ' VNĐ)';
                                                }, $serviceDetails);
                                                echo implode(', ', $serviceNames);
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($invoice['service_total_room'], 0, ',', '.'); ?> VNĐ</strong>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($invoice['service_amount_per_person'], 0, ',', '.'); ?> VNĐ</strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <!-- Phí vi phạm (KHÔNG chia) -->
                            <?php if ($invoice['violation_fee'] > 0): ?>
                                <tr class="table-warning">
                                    <td><strong>Phí vi phạm</strong> <small class="text-muted">(tính riêng)</small></td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">
                                        <strong class="text-danger"><?php echo number_format($invoice['violation_fee'], 0, ',', '.'); ?> VNĐ</strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <!-- Tổng cộng -->
                            <tr class="table-primary">
                                <td colspan="4" class="text-end"><strong>TỔNG CỘNG:</strong></td>
                                <td class="text-end">
                                    <strong class="fs-5 text-danger">
                                        <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Tất cả đơn giá và số tiền đã được lưu cố định vào hóa đơn này, 
                    không bị ảnh hưởng bởi các thay đổi sau này.
                </div>
            </div>
        </div>

        <!-- Thông tin thanh toán -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar me-2"></i>Thông tin Thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Hạn thanh toán:</strong> 
                            <span class="text-danger"><?php echo formatDate($invoice['due_date']); ?></span>
                        </p>
                        <?php if ($invoice['paid_at']): ?>
                            <p class="mb-2"><strong>Ngày thanh toán:</strong> <?php echo formatDateTime($invoice['paid_at']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Người tạo:</strong> <?php echo escapeHtml($invoice['created_by_name'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Ghi chú</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($invoice['notes']): ?>
                            <p class="mb-0"><?php echo nl2br(escapeHtml($invoice['notes'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-0">Không có ghi chú</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thao tác -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="invoices.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Quay lại
                    </a>
                    <?php if ($invoice['status'] !== 'paid'): ?>
                        <button type="button" class="btn btn-success" 
                                onclick="updateStatus(<?php echo $invoice['id']; ?>, 'paid')">
                            <i class="bi bi-check-circle me-2"></i>Đánh dấu đã thanh toán
                        </button>
                        <a href="../../../handle/invoices_process.php?action=delete&id=<?php echo $invoice['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')">
                            <i class="bi bi-trash me-2"></i>Xóa hóa đơn
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form ẩn để cập nhật status -->
    <form id="updateStatusForm" method="POST" action="../../../handle/invoices_process.php" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="invoice_id" id="updateInvoiceId">
        <input type="hidden" name="status" id="updateStatus">
        <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(invoiceId, status) {
            if (confirm('Bạn có chắc chắn muốn cập nhật trạng thái hóa đơn này?')) {
                document.getElementById('updateInvoiceId').value = invoiceId;
                document.getElementById('updateStatus').value = status;
                document.getElementById('updateStatusForm').submit();
            }
        }
    </script>
</body>
</html>

