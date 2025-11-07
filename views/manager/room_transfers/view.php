<?php
/**
 * Chi tiết yêu cầu chuyển phòng - Manager
 */

require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';
require_once __DIR__ . '/../../../functions/room_transfers.php';
require_once __DIR__ . '/../../../functions/rooms.php';

// Kiểm tra đăng nhập và quyền manager
checkRole('manager');

$currentUser = getCurrentUser();
$requestId = intval($_GET['id'] ?? 0);

if ($requestId <= 0) {
    setErrorMessage('ID yêu cầu không hợp lệ!');
    redirect('../room_transfers.php');
}

$request = getRoomTransferRequestById($requestId);

if (!$request) {
    setErrorMessage('Yêu cầu không tồn tại!');
    redirect('../room_transfers.php');
}

$statuses = getRoomTransferStatuses();
$availableRooms = getAllRooms(); // Lấy danh sách phòng để chọn
$successMsg = getSuccessMessage();
$errorMsg = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Yêu cầu Chuyển phòng - Quản lý KTX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .request-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .detail-card {
            border-left: 4px solid #667eea;
        }
        .room-card {
            border: 2px solid #dee2e6;
            transition: all 0.3s;
        }
        .room-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                <a class="nav-link" href="../room_transfers.php">Chuyển phòng</a>
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
            <h2><i class="bi bi-arrow-left-right me-2"></i>Chi tiết Yêu cầu Chuyển phòng</h2>
            <a href="../room_transfers.php" class="btn btn-secondary">
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
        <div class="request-header">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">YÊU CẦU CHUYỂN PHÒNG</h3>
                    <p class="mb-1"><strong>Ngày tạo:</strong> <?php echo formatDateTime($request['created_at']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php
                    $statusBadge = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger'
                    ];
                    $badge = $statusBadge[$request['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $badge; ?> fs-6 px-3 py-2">
                        <?php echo escapeHtml($statuses[$request['status']] ?? $request['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Thông tin sinh viên -->
        <div class="card detail-card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Thông tin Sinh viên</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Họ tên:</strong> <?php echo escapeHtml($request['student_name']); ?></p>
                        <p class="mb-2"><strong>Mã sinh viên:</strong> <?php echo escapeHtml($request['student_code']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($request['student_phone']): ?>
                            <p class="mb-2"><strong>Điện thoại:</strong> <?php echo escapeHtml($request['student_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($request['student_email']): ?>
                            <p class="mb-2"><strong>Email:</strong> <?php echo escapeHtml($request['student_email']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- So sánh phòng -->
        <div class="row mb-4">
            <!-- Phòng hiện tại -->
            <div class="col-md-6">
                <div class="card room-card h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-house me-2"></i>Phòng Hiện tại</h5>
                    </div>
                    <div class="card-body">
                        <h4 class="text-primary">
                            <?php 
                            if ($request['current_building_code']) {
                                echo escapeHtml($request['current_building_code'] . ' - ');
                            }
                            echo escapeHtml($request['current_room_code']); 
                            ?>
                        </h4>
                        <?php if ($request['current_building_name']): ?>
                            <p class="mb-2"><strong>Tòa:</strong> <?php echo escapeHtml($request['current_building_name']); ?></p>
                        <?php endif; ?>
                        <p class="mb-2"><strong>Sức chứa:</strong> <?php echo $request['current_room_capacity']; ?> người</p>
                        <p class="mb-2"><strong>Đang ở:</strong> <?php echo $request['current_room_occupancy']; ?> người</p>
                        <p class="mb-0">
                            <span class="badge bg-info">
                                Còn: <?php echo ($request['current_room_capacity'] - $request['current_room_occupancy']); ?> chỗ
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Phòng muốn chuyển -->
            <div class="col-md-6">
                <div class="card room-card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-house-door me-2"></i>Phòng Muốn chuyển</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($request['requested_room_id']): ?>
                            <h4 class="text-success">
                                <?php 
                                if ($request['requested_building_code']) {
                                    echo escapeHtml($request['requested_building_code'] . ' - ');
                                }
                                echo escapeHtml($request['requested_room_code']); 
                                ?>
                            </h4>
                            <?php if ($request['requested_building_name']): ?>
                                <p class="mb-2"><strong>Tòa:</strong> <?php echo escapeHtml($request['requested_building_name']); ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Sức chứa:</strong> <?php echo $request['requested_room_capacity']; ?> người</p>
                            <p class="mb-2"><strong>Đang ở:</strong> <?php echo $request['requested_room_occupancy']; ?> người</p>
                            <p class="mb-0">
                                <?php if ($request['requested_room_occupancy'] < $request['requested_room_capacity']): ?>
                                    <span class="badge bg-success">
                                        Còn: <?php echo ($request['requested_room_capacity'] - $request['requested_room_occupancy']); ?> chỗ
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Đã đầy!</span>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-question-circle" style="font-size: 3rem;"></i>
                                <p class="mt-2">Sinh viên chưa chọn phòng cụ thể</p>
                                <small>Manager có thể chọn phòng phù hợp</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lý do chuyển phòng -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Lý do Chuyển phòng</h5>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(escapeHtml($request['reason'])); ?>
                </div>
            </div>
        </div>

        <!-- Thông tin xử lý -->
        <?php if ($request['status'] !== 'pending'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Thông tin Xử lý</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Người duyệt:</strong> <?php echo escapeHtml($request['reviewed_by_name'] ?? 'N/A'); ?></p>
                <p class="mb-2"><strong>Ngày xử lý:</strong> <?php echo formatDateTime($request['reviewed_at']); ?></p>
                <p class="mb-0"><strong>Trạng thái:</strong> 
                    <span class="badge bg-<?php echo $badge; ?>">
                        <?php echo escapeHtml($statuses[$request['status']] ?? $request['status']); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form xử lý (chỉ hiện nếu status = pending) -->
        <?php if ($request['status'] === 'pending'): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Xử lý Yêu cầu</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="actionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="approve-tab" data-bs-toggle="tab" data-bs-target="#approve" type="button">
                            <i class="bi bi-check-circle me-1"></i> Duyệt
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reject-tab" data-bs-toggle="tab" data-bs-target="#reject" type="button">
                            <i class="bi bi-x-circle me-1"></i> Từ chối
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="actionTabsContent">
                    <!-- Tab Duyệt -->
                    <div class="tab-pane fade show active" id="approve" role="tabpanel">
                        <form action="../../../handle/room_transfers_process.php" method="POST" onsubmit="return confirmApprove()">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="new_room_id" class="form-label">Chọn phòng mới <span class="text-danger">*</span></label>
                                <select class="form-select" id="new_room_id" name="new_room_id" required onchange="showRoomInfo()">
                                    <option value="">-- Chọn phòng --</option>
                                    <?php foreach ($availableRooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>" 
                                                data-capacity="<?php echo $room['capacity']; ?>"
                                                data-occupancy="<?php echo $room['current_occupancy']; ?>"
                                                <?php 
                                                // Pre-select nếu sinh viên đã chọn
                                                if ($request['requested_room_id'] == $room['id']) {
                                                    echo 'selected';
                                                }
                                                // Disable nếu phòng đã đầy
                                                if ($room['current_occupancy'] >= $room['capacity']) {
                                                    echo ' disabled';
                                                }
                                                ?>>
                                            <?php 
                                            $roomDisplay = $room['building_code'] ? $room['building_code'] . ' - ' : '';
                                            $roomDisplay .= $room['room_code'];
                                            $roomDisplay .= ' (' . $room['current_occupancy'] . '/' . $room['capacity'] . ' người)';
                                            if ($room['current_occupancy'] >= $room['capacity']) {
                                                $roomDisplay .= ' - ĐẦY';
                                            }
                                            echo escapeHtml($roomDisplay); 
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Có thể chọn phòng khác phòng sinh viên yêu cầu nếu phù hợp hơn</small>
                                <div id="room-info" class="mt-2"></div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Lưu ý quan trọng:</strong>
                                <ul class="mb-0">
                                    <li>Sinh viên sẽ chuyển phòng từ ngày mai</li>
                                    <li>Hệ thống tự động:
                                        <ul>
                                            <li><strong>Terminate hợp đồng cũ</strong> (status = terminated)</li>
                                            <li><strong>Tạo hợp đồng mới</strong> cho phòng mới với <strong>giá phòng mới</strong> (tự động lấy từ Pricing theo loại phòng)</li>
                                            <li>Cập nhật Room_Assignments (end phòng cũ, tạo mới cho phòng mới)</li>
                                            <li>Cập nhật Occupancy của cả 2 phòng</li>
                                        </ul>
                                    </li>
                                    <li><strong>HÓA ĐƠN CŨ KHÔNG THAY ĐỔI</strong> (nguyên tắc bất biến)</li>
                                    <li>Hóa đơn tháng sau sẽ tính theo phòng mới và hợp đồng mới</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-2"></i>Duyệt Chuyển phòng
                            </button>
                        </form>
                    </div>

                    <!-- Tab Từ chối -->
                    <div class="tab-pane fade" id="reject" role="tabpanel">
                        <form action="../../../handle/room_transfers_process.php" method="POST" onsubmit="return confirmReject()">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="rejection_reason" class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" 
                                          placeholder="Nhập lý do từ chối yêu cầu chuyển phòng..." required></textarea>
                                <small class="text-muted">Lý do này sẽ được gửi cho sinh viên</small>
                            </div>

                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle me-2"></i>Từ chối Yêu cầu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showRoomInfo() {
            const select = document.getElementById('new_room_id');
            const infoDiv = document.getElementById('room-info');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                const capacity = selectedOption.getAttribute('data-capacity');
                const occupancy = selectedOption.getAttribute('data-occupancy');
                const available = capacity - occupancy;
                
                if (available > 0) {
                    infoDiv.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle me-2"></i>Phòng còn <strong>' + available + '</strong> chỗ trống. Có thể duyệt.</div>';
                } else {
                    infoDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-2"></i>Phòng đã đầy. Không thể duyệt.</div>';
                }
            } else {
                infoDiv.innerHTML = '';
            }
        }
        
        function confirmApprove() {
            return confirm('Bạn có chắc chắn muốn DUYỆT yêu cầu chuyển phòng này?\n\nSinh viên sẽ chuyển phòng từ ngày mai.');
        }
        
        function confirmReject() {
            return confirm('Bạn có chắc chắn muốn TỪ CHỐI yêu cầu này?');
        }
        
        // Show room info on page load if room already selected
        document.addEventListener('DOMContentLoaded', function() {
            showRoomInfo();
        });
    </script>
</body>
</html>

