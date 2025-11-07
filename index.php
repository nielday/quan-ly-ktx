<?php
session_start();

// Nếu đã đăng nhập, chuyển hướng đến dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    require_once __DIR__ . '/functions/auth.php';
    $dashboardPath = getDashboardPath($_SESSION['role']);
    header('Location: ' . $dashboardPath);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Ký túc xá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-right {
            padding: 60px 40px;
        }
        .login-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <div class="col-md-5 login-left">
                    <i class="bi bi-building login-icon"></i>
                    <h2 class="mb-3">Hệ thống Quản lý</h2>
                    <h3 class="mb-4">Ký túc xá</h3>
                    <p class="text-center">Quản lý phòng ở, sinh viên, hợp đồng và thanh toán một cách hiệu quả</p>
                </div>
                <div class="col-md-7 login-right">
                    <h3 class="mb-4 text-center">Đăng nhập</h3>
                    
                    <!-- Thông báo lỗi -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['error']); 
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Thông báo thành công -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['success']); 
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="./handle/login_process.php" method="POST" autocomplete="on">
                        <!-- Username input -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person-fill me-2"></i>Tên đăng nhập
                            </label>
                            <input 
                                type="text" 
                                name="username" 
                                id="username" 
                                class="form-control form-control-lg" 
                                placeholder="Nhập username" 
                                autocomplete="username"
                                required 
                                autofocus
                            />
                        </div>

                        <!-- Password input -->
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill me-2"></i>Mật khẩu
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                class="form-control form-control-lg" 
                                placeholder="Nhập mật khẩu" 
                                autocomplete="current-password"
                                required
                            />
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary btn-lg btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Hệ thống quản lý ký túc xá - Version 1.0
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

