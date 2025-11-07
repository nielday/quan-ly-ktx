<?php
/**
 * Nộp tiền - Student
 * Tạo thanh toán cho hóa đơn
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/students.php';
require_once __DIR__ . '/../../../functions/invoices.php';

// Kiểm tra đăng nhập và quyền student
checkRole('student');

$currentUser = getCurrentUser();
$student = getStudentByUserId($currentUser['id']);

if (!$student) {
    setErrorMessage("Không tìm thấy thông tin sinh viên");
    redirect('../dashboard.php');
    exit;
}

// Lấy invoice_id từ query string
$invoiceId = intval($_GET['invoice_id'] ?? 0);

if ($invoiceId <= 0) {
    setErrorMessage('Vui lòng chọn hóa đơn cần thanh toán!');
    redirect('../invoices.php');
    exit;
}

// Lấy thông tin hóa đơn
$invoice = getInvoiceById($invoiceId);

if (!$invoice) {
    setErrorMessage('Hóa đơn không tồn tại!');
    redirect('../invoices.php');
    exit;
}

// Kiểm tra hóa đơn thuộc về sinh viên này
if ($invoice['student_id'] != $student['id']) {
    setErrorMessage('Bạn không có quyền thanh toán hóa đơn này!');
    redirect('../invoices.php');
    exit;
}

// Kiểm tra hóa đơn có thể thanh toán không
if ($invoice['status'] === 'paid') {
    setErrorMessage('Hóa đơn này đã được thanh toán rồi!');
    redirect('invoices/view.php?id=' . $invoiceId);
    exit;
}

if ($invoice['status'] === 'cancelled') {
    setErrorMessage('Hóa đơn này đã bị hủy!');
    redirect('../invoices.php');
    exit;
}

$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp tiền - Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-building me-2"></i>Quản lý KTX - Sinh viên
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../invoices.php">Hóa đơn</a>
                <a class="nav-link active" href="history.php">Thanh toán</a>
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
            <h2><i class="bi bi-cash-coin me-2"></i>Nộp tiền</h2>
            <a href="../invoices/view.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
        </div>

        <?php
        if ($successMsg): ?>
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
            <!-- Thông tin hóa đơn -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Thông tin Hóa đơn</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Mã hóa đơn</label>
                            <div><strong><?php echo escapeHtml($invoice['invoice_code']); ?></strong></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Tháng</label>
                            <div>
                                <?php 
                                $date = DateTime::createFromFormat('Y-m', $invoice['invoice_month']);
                                echo $date ? $date->format('m/Y') : $invoice['invoice_month'];
                                ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Phòng</label>
                            <div>
                                <?php echo escapeHtml($invoice['building_code']); ?> - 
                                <?php echo escapeHtml($invoice['room_code']); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Hạn thanh toán</label>
                            <div class="<?php echo ($invoice['status'] === 'overdue') ? 'text-danger' : ''; ?>">
                                <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?>
                            </div>
                        </div>
                        <hr>
                        <div>
                            <label class="text-muted small">Tổng tiền phải trả</label>
                            <div class="fs-4 text-danger fw-bold">
                                <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form nộp tiền -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Thông tin Thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../handle/payments_process.php" id="paymentForm">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoiceId; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Số tiền nộp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           name="amount" 
                                           id="amount"
                                           value="<?php echo $invoice['total_amount']; ?>"
                                           min="1" 
                                           max="<?php echo $invoice['total_amount'] * 2; ?>"
                                           step="1000"
                                           required>
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                                <small class="text-muted">
                                    Tổng tiền hóa đơn: 
                                    <strong><?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ</strong>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày nộp tiền <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       required>
                                <small class="text-muted">Không được chọn ngày trong tương lai</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash" selected>Tiền mặt</option>
                                    <option value="bank_transfer">Chuyển khoản</option>
                                </select>
                            </div>

                            <div class="mb-3" id="transactionCodeGroup" style="display: none;">
                                <label class="form-label">Mã giao dịch</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="transaction_code" 
                                       placeholder="Nhập mã giao dịch nếu chuyển khoản">
                                <small class="text-muted">Ví dụ: NAP123456789</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" 
                                          name="notes" 
                                          rows="3" 
                                          placeholder="Ghi chú thêm (nếu có)"></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Lưu ý:</strong> Sau khi nộp tiền, vui lòng chờ quản lý xác nhận. 
                                Trạng thái hóa đơn sẽ được cập nhật sau khi quản lý xác nhận.
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Xác nhận nộp tiền
                                </button>
                                <a href="../invoices/view.php?id=<?php echo $invoiceId; ?>" class="btn btn-outline-secondary">
                                    Hủy
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hiển thị/ẩn trường mã giao dịch khi chọn phương thức
        document.querySelector('select[name="payment_method"]').addEventListener('change', function() {
            const transactionCodeGroup = document.getElementById('transactionCodeGroup');
            if (this.value === 'bank_transfer') {
                transactionCodeGroup.style.display = 'block';
                transactionCodeGroup.querySelector('input').setAttribute('required', 'required');
            } else {
                transactionCodeGroup.style.display = 'none';
                transactionCodeGroup.querySelector('input').removeAttribute('required');
            }
        });

        // Format số tiền khi nhập
        document.getElementById('amount').addEventListener('input', function() {
            const value = parseFloat(this.value);
            const max = parseFloat(this.getAttribute('max'));
            if (value > max) {
                this.value = max;
            }
        });

        // Confirm trước khi submit
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            const totalAmount = <?php echo $invoice['total_amount']; ?>;
            
            if (amount < totalAmount) {
                if (!confirm('Bạn đang nộp ít hơn số tiền hóa đơn. Bạn có chắc chắn muốn tiếp tục?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            if (!confirm('Xác nhận nộp tiền cho hóa đơn này?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>

