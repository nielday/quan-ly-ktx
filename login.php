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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #00B4D8 0%, #FF6B35 50%, #00B4D8 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            position: relative;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            position: relative;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-left {
            background: linear-gradient(135deg, #00B4D8 0%, #0096C7 50%, #FF6B35 100%);
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            min-height: 600px;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .logo-container {
            position: relative;
            z-index: 2;
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 200px;
            height: auto;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .login-left h2 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .login-left h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #FFE5D9;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .login-left p {
            font-size: 1rem;
            text-align: center;
            line-height: 1.6;
            opacity: 0.95;
            position: relative;
            z-index: 2;
        }

        .login-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #f8f9fa;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h3 {
            font-size: 2rem;
            font-weight: bold;
            color: #004E89;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .btn-home {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #00B4D8;
            border: 2px solid #00B4D8;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .btn-home:hover {
            background: #00B4D8;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 180, 216, 0.3);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            color: #00B4D8;
            margin-right: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00B4D8;
            box-shadow: 0 0 0 0.2rem rgba(0, 180, 216, 0.25);
            outline: none;
        }

        .form-control-lg {
            padding: 14px 18px;
            font-size: 1.05rem;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #00B4D8;
            z-index: 3;
        }

        .btn-login {
            background: linear-gradient(135deg, #00B4D8 0%, #FF6B35 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #FF6B35 0%, #00B4D8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: #efe;
            color: #2c5;
            border-left: 4px solid #28a745;
        }

        .footer-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .footer-info small {
            color: #888;
            font-size: 0.9rem;
        }

        .footer-info i {
            color: #00B4D8;
        }

        /* Main Footer */
        .main-footer {
            width: 100%;
            max-width: 1100px;
            margin-top: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: flex-start;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-logo {
            flex-shrink: 0;
        }

        .footer-logo img {
            height: 50px;
            width: auto;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
        }

        .footer-content {
            flex: 1;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            min-width: 0;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
        }

        .footer-section h6 {
            color: #004E89;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer-section p {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .footer-section .contact-item {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.8;
            display: flex;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .footer-section .contact-item i {
            color: #FF6B35;
            margin-right: 8px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .footer-section .contact-item span {
            flex: 1;
        }

        .footer-copyright {
            width: 100%;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            color: #888;
            font-size: 0.85rem;
        }

        /* Decorative elements */
        .decoration-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .circle-1 {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .circle-2 {
            width: 150px;
            height: 150px;
            bottom: 15%;
            right: 15%;
            animation-delay: 2s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.1);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                min-height: 300px;
                padding: 40px 30px;
            }

            .login-right {
                padding: 40px 30px;
            }

            .logo-container img {
                max-width: 150px;
            }

            .login-left h2 {
                font-size: 1.5rem;
            }

            .login-left h3 {
                font-size: 1.2rem;
            }

            .btn-home {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 20px;
                display: inline-block;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .login-container {
                border-radius: 15px;
            }

            .login-left,
            .login-right {
                padding: 30px 20px;
            }

            .login-header h3 {
                font-size: 1.5rem;
            }

            .main-footer {
                padding: 20px 15px;
                gap: 20px;
            }

            .footer-content {
                flex-direction: column;
                gap: 20px;
            }

            .footer-section {
                min-width: 100%;
            }

            .footer-logo {
                text-align: center;
                width: 100%;
            }

            .footer-logo img {
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <a href="home.php" class="btn-home">
            <i class="bi bi-house-door me-2"></i>Về trang chủ
        </a>

        <div class="login-container">
            <div class="col-md-5 login-left">
                <div class="decoration-circle circle-1"></div>
                <div class="decoration-circle circle-2"></div>
                
                <div class="logo-container">
                    <img src="image/Logo_DAI_NAM.png" alt="Logo Đại Nam" class="logo-img">
                </div>
                
                <h2>Hệ thống Quản lý</h2>
                <h3>Ký túc xá</h3>
                <p>
                    Quản lý phòng ở, sinh viên, hợp đồng và thanh toán một cách hiệu quả.
                    <br>
                    Hệ thống hiện đại, tiện lợi và dễ sử dụng.
                </p>
            </div>

            <div class="col-md-7 login-right">
                <div class="login-header">
                    <h3>Đăng nhập</h3>
                    <p>Vui lòng đăng nhập để tiếp tục</p>
                </div>
                
                <!-- Thông báo lỗi -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Lỗi:</strong>
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Thông báo thành công -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Thành công:</strong>
                        <?php 
                        echo htmlspecialchars($_SESSION['success']); 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form action="./handle/login_process.php" method="POST" autocomplete="on">
                    <!-- Username input -->
                    <div class="mb-4">
                        <label for="username" class="form-label">
                            <i class="bi bi-person-fill"></i>
                            Tên đăng nhập
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="form-control form-control-lg" 
                            placeholder="Nhập tên đăng nhập của bạn" 
                            autocomplete="username"
                            required 
                            autofocus
                        />
                    </div>

                    <!-- Password input -->
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill"></i>
                            Mật khẩu
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control form-control-lg" 
                            placeholder="Nhập mật khẩu của bạn" 
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" name="login" class="btn btn-primary btn-lg btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Đăng nhập
                        </button>
                    </div>
                </form>
                
                <div class="footer-info">
                    <small>
                        <i class="bi bi-shield-check me-1"></i>
                        Hệ thống quản lý ký túc xá - Version 1.0
                        <br>
                        <i class="bi bi-info-circle me-1"></i>
                        Bảo mật thông tin người dùng
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="footer-logo">
            <img src="image/Logo_DAI_NAM.png" alt="Logo Đại Nam">
        </div>
        <div class="footer-content">
            <div class="footer-section">
                <h6>Giới thiệu</h6>
                <p>Hệ thống quản lý KTX hiện đại, tối ưu hóa trải nghiệm sinh viên và công tác quản lý.</p>
            </div>
            <div class="footer-section">
                <h6>Công nghệ</h6>
                <p>PHP, MySQL, Bootstrap 5, HTML/CSS/JS</p>
            </div>
            <div class="footer-section">
                <h6>Liên hệ</h6>
                <div class="contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span>dnu@dainam.edu.vn</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-phone-fill"></i>
                    <span>02435577799</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span>0961595599 | 0931595599</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Số 1, Phố Xốm, Phường Phú Lương, Thành phố Hà Nội</span>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            Copyright &copy; 2025. Trường Đại học Đại Nam.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Thêm hiệu ứng khi focus vào input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
