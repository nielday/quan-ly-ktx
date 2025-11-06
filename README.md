# HỆ THỐNG QUẢN LÝ KÝ TÚC XÁ

## Hướng dẫn cài đặt và chạy

### Bước 1: Tạo Database
1. Mở phpMyAdmin (http://localhost/phpmyadmin)
2. Import file `database/quanlyktx.sql` để tạo database và các bảng
3. Import file `database/seed_data.sql` để chèn dữ liệu mẫu

### Bước 2: Cấu hình Database
Nếu XAMPP của bạn có cấu hình khác (password, port), sửa file:
- `functions/db_connection.php`

### Bước 3: Test kết nối Database
Truy cập: `http://localhost/quanlyktx/test_db_connection.php`

### Bước 4: Đăng nhập
Truy cập: `http://localhost/quanlyktx/index.php`

**Tài khoản mẫu:**
- **Admin**: 
  - Username: `admin`
  - Password: `admin123`
  
- **Manager**: 
  - Username: `manager`
  - Password: `manager123`
  
- **Student**: 
  - Username: `student`
  - Password: `student123`

## Cấu trúc dự án

```
quanlyktx/
├── database/              # Script SQL
│   ├── quanlyktx.sql     # Tạo database và bảng
│   └── seed_data.sql     # Dữ liệu mẫu
├── functions/            # Business logic layer
│   ├── db_connection.php # Kết nối database
│   ├── helpers.php       # Helper functions
│   └── auth.php          # Authentication functions
├── handle/               # Controller layer
│   ├── login_process.php # Xử lý đăng nhập
│   └── logout_process.php # Xử lý đăng xuất
├── views/                # Presentation layer
│   ├── admin/           # Trang admin
│   ├── manager/         # Trang manager
│   └── student/         # Trang student
├── index.php            # Trang đăng nhập
├── test_db_connection.php # Test kết nối DB
└── README.md            # File này
```

## Các bước đã hoàn thành

✅ **BƯỚC 1**: Tạo cấu trúc thư mục và file config database
✅ **BƯỚC 2**: Tạo helper functions (session, redirect, messages)
✅ **BƯỚC 3**: Tạo Authentication (login, logout, check role)
✅ **BƯỚC 4**: Tạo trang đăng nhập (index.php)
✅ **BƯỚC 5**: Tạo Dashboard theo role

## Các bước tiếp theo

- [ ] Quản lý tòa nhà (Buildings)
- [ ] Quản lý phòng (Rooms)
- [ ] Quản lý đơn giá (Pricing)
- [ ] Quản lý đợt đăng ký (Registration Periods)
- [ ] Quản lý sinh viên (Students)
- [ ] Đơn đăng ký (Applications)
- [ ] Hợp đồng (Contracts)
- [ ] Hóa đơn (Invoices)
- [ ] Thanh toán (Payments)
- [ ] Dịch vụ (Services)
- [ ] Vi phạm (Violations)

## Lưu ý

- Hệ thống sử dụng session-based authentication
- Tất cả SQL queries sử dụng prepared statements để tránh SQL injection
- Mật khẩu trong dữ liệu mẫu là plain text (có thể nâng cấp dùng password_hash sau)

