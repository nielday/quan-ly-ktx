<h2 align="center">
    <a href="https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin">
    🎓 Faculty of Information Technology (DaiNam University)
    </a>
</h2>
<h2 align="center">
    Youth Union Member Management
</h2>
<div align="center">
    <p align="center">
        <img src="docs/logo/aiotlab_logo.png" alt="AIoTLab Logo" width="170"/>
        <img src="docs/logo/fitdnu_logo.png" alt="AIoTLab Logo" width="180"/>
        <img src="docs/logo/dnu_logo.png" alt="DaiNam University Logo" width="200"/>
    </p>

[![AIoTLab](https://img.shields.io/badge/AIoTLab-green?style=for-the-badge)](https://www.facebook.com/DNUAIoTLab)
[![Faculty of Information Technology](https://img.shields.io/badge/Faculty%20of%20Information%20Technology-blue?style=for-the-badge)](https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin)
[![DaiNam University](https://img.shields.io/badge/DaiNam%20University-orange?style=for-the-badge)](https://dainam.edu.vn)

</div>
 
## 📖 1. Giới thiệu
Hệ thống Quản lý Ký túc xá được xây dựng nhằm hỗ trợ công tác quản lý ký túc xá một cách hiệu quả, từ quản lý phòng ở, sinh viên, hợp đồng, thanh toán đến các dịch vụ. Hệ thống được thiết kế cho môi trường local development, giúp tối ưu hóa trải nghiệm sinh viên và công tác quản lý. Thay vì quản lý thủ công bằng giấy tờ hay các tệp Excel rời rạc, hệ thống mang đến một giải pháp tập trung, hiện đại và dễ sử dụng.

### Tính năng chính:
- ✅ Quản lý tòa nhà và phòng ở
- ✅ Quản lý sinh viên và đơn đăng ký
- ✅ Quản lý hợp đồng và phân phòng
- ✅ Quản lý hóa đơn và thanh toán (chia đều theo đầu người)
- ✅ Quản lý đơn giá (điện, nước, phòng, dịch vụ)
- ✅ Quản lý dịch vụ phòng
- ✅ Quản lý vi phạm
- ✅ Yêu cầu chuyển phòng và sửa chữa
- ✅ Báo cáo và thống kê

## 🔧 2. Các công nghệ được sử dụng
<div align="center">

### Hệ điều hành
![macOS](https://img.shields.io/badge/macOS-000000?style=for-the-badge&logo=macos&logoColor=F0F0F0)
[![Windows](https://img.shields.io/badge/Windows-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://www.microsoft.com/en-us/windows/)
[![Ubuntu](https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white)](https://ubuntu.com/)

### Công nghệ chính
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](#)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](#)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

### Web Server & Database
[![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/) 
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://www.apachefriends.org/)

### Database Management Tools
[![MySQL Workbench](https://img.shields.io/badge/MySQL_Workbench-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://dev.mysql.com/downloads/workbench/)
</div>

### Mô tả công nghệ:
- **Backend**: PHP thuần (không framework) - Xử lý logic nghiệp vụ, kết nối database
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript - Giao diện người dùng hiện đại và responsive
- **Database**: MySQL - Lưu trữ dữ liệu với các bảng quan hệ
- **Server**: Apache (XAMPP) - Môi trường phát triển local
- **Authentication**: Session-based - Quản lý đăng nhập và phân quyền
- **Security**: Prepared statements - Bảo vệ chống SQL injection

## 🎯 3. Mô tả hệ thống

### 3.1. Về hệ thống
Hệ thống quản lý KTX hiện đại, tối ưu hóa trải nghiệm sinh viên và công tác quản lý. Hệ thống hỗ trợ quản lý toàn diện từ đăng ký phòng, phân phòng, hợp đồng, thanh toán đến các dịch vụ và vi phạm.

### 3.2. Phân quyền
- **Admin**: Quản lý tài khoản, phân quyền, xem báo cáo tổng hợp
- **Manager**: Quản lý phòng, sinh viên, hợp đồng, tạo hóa đơn, xác nhận thanh toán
- **Student**: Đăng ký ở KTX, xem hóa đơn, nộp tiền, yêu cầu chuyển phòng/sửa chữa

### 3.3. Tính năng nổi bật
- **Tạo hóa đơn thông minh**: Tự động chia đều tiền phòng, điện, nước, dịch vụ theo đầu người
- **Nguyên tắc bất biến hóa đơn**: Hóa đơn đã tạo không bị thay đổi dù đơn giá thay đổi sau này
- **Quản lý đơn giá**: Lưu lịch sử thay đổi đơn giá theo thời gian
- **Quản lý dịch vụ**: Gán dịch vụ cho phòng, tự động tính vào hóa đơn

## 🚀 4. Hình ảnh các chức năng
_(Hình ảnh sẽ được thêm sau)_

## ⚙️ 5. Cài đặt

### 5.1. Yêu cầu hệ thống
- **XAMPP** (khuyến nghị PHP 8.x)
- **MySQL** (có sẵn trong XAMPP)
- **Trình duyệt web** hiện đại (Chrome, Firefox, Edge...)
- **Text Editor** (VS Code, PhpStorm, Sublime Text...)

### 5.2. Cài đặt XAMPP
1. Tải XAMPP từ: https://www.apachefriends.org/download.html
2. Cài đặt XAMPP vào thư mục mặc định (thường là `C:\xampp`)
3. Khởi động XAMPP Control Panel
4. Start **Apache** và **MySQL**

### 5.3. Tải project
1. Clone hoặc download project vào thư mục `htdocs` của XAMPP:
```bash
cd C:\xampp\htdocs
# Hoặc đặt project vào thư mục quanlyktx
```

2. Cấu trúc thư mục:
```
quanlyktx/
├── database/          # Script SQL
├── functions/         # Business logic
├── handle/           # Controller
├── views/            # Presentation
├── image/            # Hình ảnh, logo
├── index.php         # Trang chủ (redirect đến home.php)
├── home.php          # Trang giới thiệu
├── login.php         # Trang đăng nhập
└── README.md         # File này
```

### 5.4. Setup database
1. Mở **phpMyAdmin**: http://localhost/phpmyadmin
2. Import file SQL:
   - Chọn database `quanlyktx` (hoặc tạo mới)
   - Import file `database/quanlyktx_complete.sql`
   - Hoặc chạy file SQL để tạo database và các bảng

3. Database sẽ bao gồm các bảng:
   - users, buildings, rooms, pricing, registration_periods
   - students, applications, contracts, room_assignments
   - invoices, payments, services, room_services
   - violations, maintenance_requests, room_transfer_requests
   - notifications

### 5.5. Cấu hình kết nối database
Mở file `functions/db_connection.php` và cập nhật thông tin:

```php
function getDbConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "Phong8ngon@"; // Thay đổi password nếu cần
    $dbname = "quanlyktx";
    $port = 3305; // Port MySQL (mặc định XAMPP: 3306)
    
    $conn = mysqli_connect($servername, $username, $password, $dbname, $port);
    
    if (!$conn) {
        die("Kết nối database thất bại: " . mysqli_connect_error());
    }
    
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}
```

**Lưu ý**: 
- Kiểm tra port MySQL trong XAMPP (mặc định: 3306)
- Nếu có password MySQL, cập nhật trong file `db_connection.php`
- Đảm bảo database `quanlyktx` đã được tạo

### 5.6. Chạy hệ thống
1. Khởi động XAMPP Control Panel
2. Start **Apache** và **MySQL**
3. Truy cập hệ thống:
   - Trang chủ: http://localhost:8000/ (tự động redirect đến home.php)
   - Trang giới thiệu: http://localhost:8000/home.php
   - Trang đăng nhập: http://localhost:8000/login.php

### 5.7. Tài khoản mẫu
Sau khi import database, có thể sử dụng các tài khoản mẫu:

**Admin:**
- Username: `admin`
- Password: `admin123`

**Manager:**
- Username: `manager`
- Password: `manager123`

**Student:**
- Username: `student`
- Password: `student123`

**Lưu ý**: Đảm bảo đã import file `database/seed_data.sql` để có dữ liệu mẫu.

## 📋 6. Cấu trúc dự án

```
quanlyktx/
├── database/              # Script SQL
│   ├── quanlyktx_complete.sql  # Database hoàn chỉnh
│   ├── migration_add_deposit_support.sql
│   └── seed_data.sql      # Dữ liệu mẫu
├── functions/            # Business logic layer
│   ├── db_connection.php # Kết nối database
│   ├── auth.php          # Authentication
│   ├── helpers.php       # Helper functions
│   ├── buildings.php     # Quản lý tòa nhà
│   ├── rooms.php         # Quản lý phòng
│   ├── students.php      # Quản lý sinh viên
│   ├── invoices.php      # Quản lý hóa đơn
│   └── ... (các module khác)
├── handle/               # Controller layer
│   ├── login_process.php
│   ├── logout_process.php
│   ├── buildings_process.php
│   └── ... (các handler khác)
├── views/                # Presentation layer
│   ├── admin/           # Trang admin
│   ├── manager/         # Trang manager
│   └── student/         # Trang student
├── image/                # Hình ảnh, logo
│   ├── Logo_DAI_NAM.png
│   └── ảnh kí túc xá/
├── index.php            # Trang chủ (redirect)
├── home.php             # Trang giới thiệu
├── login.php            # Trang đăng nhập
├── test_db_connection.php # Test kết nối DB
├── README.md            # File này
└── PHAN_TICH_NGHIEP_VU.md # Phân tích nghiệp vụ
```

## 🔐 7. Bảo mật

- ✅ **Prepared statements**: Bảo vệ chống SQL injection
- ✅ **HTML escaping**: Bảo vệ chống XSS
- ✅ **Session-based authentication**: Quản lý đăng nhập an toàn
- ✅ **Role-based access control**: Phân quyền theo role
- ✅ **Password hashing**: Hỗ trợ password hash (có thể nâng cấp)

## 📝 8. Luồng xử lý

```
View → Form submit → Handle (controller) → Functions (business logic) → Database
```

- **Routing**: Sử dụng parameter `action` (GET/POST)
- **Error handling**: Thông báo qua session (success/error)
- **Separation of concerns**: Tách biệt logic, controller, view

## 🎨 9. Giao diện

- **Trang giới thiệu**: http://localhost:8000/home.php
- **Trang đăng nhập**: http://localhost:8000/login.php
- **Dashboard**: Tùy theo role (admin/manager/student)
- **Màu sắc**: Cam (#FF6B35), Xanh (#00B4D8), Trắng
- **Responsive**: Hỗ trợ mobile, tablet, desktop

## 📞 10. Liên hệ

- **Email**: dnu@dainam.edu.vn
- **Điện thoại**: 02435577799
- **Hotline**: 0961595599 | 0931595599
- **Địa chỉ**: Số 1, Phố Xốm, Phường Phú Lương, Thành phố Hà Nội
- **Website**: https://dainam.edu.vn

## 📄 11. License

Copyright © 2025. Trường Đại học Đại Nam.
