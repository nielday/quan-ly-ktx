<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới thiệu - Ký túc xá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00B4D8 0%, #0096C7 50%, #FF6B35 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
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

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Header với logo và nút đăng nhập */
        .header {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid #FF6B35;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            height: 50px;
            width: auto;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .header-logo:hover {
            transform: scale(1.05);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text .logo-title {
            font-size: 20px;
            font-weight: bold;
            color: #004E89;
            line-height: 1.2;
            margin: 0;
        }

        .logo-text .logo-subtitle {
            font-size: 12px;
            color: #666;
            line-height: 1.2;
            margin: 0;
        }

        .btn-login-header {
            background: linear-gradient(135deg, #00B4D8 0%, #FF6B35 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 3px 10px rgba(0, 180, 216, 0.3);
        }

        .btn-login-header:hover {
            background: linear-gradient(135deg, #FF6B35 0%, #00B4D8 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(0, 180, 216, 0.95) 0%, rgba(0, 150, 199, 0.95) 50%, rgba(255, 107, 53, 0.95) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
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

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Content Section */
        .content-section {
            background: white;
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 50px;
            position: relative;
            padding-bottom: 20px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, #00B4D8 0%, #FF6B35 100%);
            border-radius: 2px;
        }

        /* Image Gallery */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .image-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .image-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
            border: 2px solid #00B4D8;
        }

        .image-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-card:hover img {
            transform: scale(1.05);
        }

        .image-card-body {
            padding: 25px;
            text-align: center;
        }

        .image-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .image-card-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Introduction Text */
        .intro-text {
            background: #f8f9fa;
            padding: 60px 0;
            text-align: center;
        }

        .intro-text h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
        }

        .intro-text p {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Footer */
        .footer {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            padding: 40px 0;
            margin-top: 0;
        }

        .main-footer {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 0;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
        }

        .footer-content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .footer-logo {
            flex-shrink: 0;
        }

        .footer-logo img {
            height: 50px;
            width: auto;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
        }

        .footer-sections {
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

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1.1rem;
            }

            .image-gallery {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }

            .header .container {
                flex-direction: column;
                gap: 15px;
            }

            .header-logo-section {
                width: 100%;
                justify-content: center;
            }

            .btn-login-header {
                width: 100%;
                justify-content: center;
            }

            .logo-text {
                text-align: center;
            }

            .footer-content {
                flex-direction: column;
                gap: 20px;
            }

            .footer-sections {
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

        @media (max-width: 576px) {
            .header-logo {
                height: 40px;
            }

            .logo-text .logo-title {
                font-size: 16px;
            }

            .logo-text .logo-subtitle {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-logo-section">
                <img src="image/Logo_DAI_NAM.png" alt="Logo Đại Nam" class="header-logo">
                <div class="logo-text">
                    <h3 class="logo-title">Ký túc xá</h3>
                    <p class="logo-subtitle">Hệ thống quản lý</p>
                </div>
            </div>
            <a href="login.php" class="btn-login-header">
                <i class="bi bi-box-arrow-in-right"></i>
                Đăng nhập
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1><i class="bi bi-building"></i> Ký túc xá</h1>
            <p>Nơi an cư lý tưởng cho sinh viên</p>
            <p style="font-size: 1.1rem; opacity: 0.9;">
                Không gian sống hiện đại, tiện nghi với đầy đủ dịch vụ và tiện ích
            </p>
        </div>
    </section>

    <!-- Introduction Text -->
    <section class="intro-text">
        <div class="container">
            <h2>Giới thiệu về ký túc xá</h2>
            <p>
                Ký túc xá của chúng tôi là nơi lý tưởng cho sinh viên với không gian sống hiện đại, 
                tiện nghi và đầy đủ dịch vụ. Với hệ thống quản lý chuyên nghiệp, chúng tôi cam kết 
                mang đến cho sinh viên một môi trường sống an toàn, thoải mái và thuận tiện nhất.
            </p>
        </div>
    </section>

    <!-- Image Gallery Section -->
    <section class="content-section">
        <div class="container">
            <h2 class="section-title">Hình ảnh ký túc xá</h2>
            
            <div class="image-gallery">
                <!-- Image 1 -->
                <div class="image-card">
                    <img src="image/ảnh kí túc xá/toàn-cảnh-ktx.jpg" 
                         alt="Toàn cảnh ký túc xá" 
                         onerror="this.src='https://via.placeholder.com/400x300?text=Toàn+cảnh+KTX'">
                    <div class="image-card-body">
                        <h3 class="image-card-title">Toàn cảnh ký túc xá</h3>
                        <p class="image-card-description">
                            Không gian tổng thể của ký túc xá với kiến trúc hiện đại, 
                            được bao quanh bởi cây xanh tạo môi trường sống trong lành.
                        </p>
                    </div>
                </div>

                <!-- Image 2 -->
                <div class="image-card">
                    <img src="image/ảnh kí túc xá/toàn-cảnh-ktx2.jpg" 
                         alt="Toàn cảnh ký túc xá 2" 
                         onerror="this.src='https://via.placeholder.com/400x300?text=Toàn+cảnh+KTX+2'">
                    <div class="image-card-body">
                        <h3 class="image-card-title">Toàn cảnh ký túc xá 2</h3>
                        <p class="image-card-description">
                            Góc nhìn khác về ký túc xá, thể hiện quy mô và không gian 
                            rộng rãi, phù hợp cho sinh viên học tập và sinh hoạt.
                        </p>
                    </div>
                </div>

                <!-- Image 3 -->
                <div class="image-card">
                    <img src="image/ảnh kí túc xá/bên-trong-ktx_phòng-của-sinh-vien.jpg" 
                         alt="Bên trong ký túc xá - Phòng của sinh viên" 
                         onerror="this.src='https://via.placeholder.com/400x300?text=Phòng+sinh+viên'">
                    <div class="image-card-body">
                        <h3 class="image-card-title">Phòng của sinh viên</h3>
                        <p class="image-card-description">
                            Phòng ở được thiết kế hiện đại, đầy đủ tiện nghi với không gian 
                            thoáng mát, tạo môi trường học tập và nghỉ ngơi lý tưởng.
                        </p>
                    </div>
                </div>

                <!-- Image 4 -->
                <div class="image-card">
                    <img src="image/ảnh kí túc xá/bên-trong-ktx_phòng-của-sinh-vien2.jpg" 
                         alt="Bên trong ký túc xá - Phòng của sinh viên 2" 
                         onerror="this.src='https://via.placeholder.com/400x300?text=Phòng+sinh+viên+2'">
                    <div class="image-card-body">
                        <h3 class="image-card-title">Phòng của sinh viên 2</h3>
                        <p class="image-card-description">
                            Góc nhìn khác về phòng ở, thể hiện sự tiện nghi và không gian 
                            sống thoải mái cho sinh viên trong suốt quá trình học tập.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content-wrapper">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="image/Logo_DAI_NAM.png" alt="Logo Đại Nam">
                </div>
                <div class="footer-sections">
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
            </div>
            <div class="footer-copyright">
                Copyright &copy; 2025. Trường Đại học Đại Nam.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

