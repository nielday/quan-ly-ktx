-- ============================================
-- SQL SCRIPT TẠO DATABASE HỆ THỐNG QUẢN LÝ KÝ TÚC XÁ
-- Dựa trên file PHAN_TICH_NGHIEP_VU.md
-- ============================================

-- Xóa database nếu đã tồn tại (để tạo mới)
DROP DATABASE IF EXISTS quanlyktx;

-- Tạo database mới
CREATE DATABASE quanlyktx 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Sử dụng database
USE quanlyktx;

-- ============================================
-- 1. BẢNG USERS (Người dùng)
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã hash',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'student') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. BẢNG BUILDINGS (Tòa nhà)
-- ============================================
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Mã tòa',
    building_name VARCHAR(100) NOT NULL COMMENT 'Tên tòa',
    address TEXT,
    floors INT DEFAULT 1 COMMENT 'Số tầng',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_building_code (building_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. BẢNG ROOMS (Phòng)
-- ============================================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    room_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Mã phòng',
    room_number VARCHAR(20) NOT NULL,
    floor INT DEFAULT 1 COMMENT 'Tầng',
    capacity INT NOT NULL COMMENT 'Sức chứa - số người',
    current_occupancy INT DEFAULT 0 COMMENT 'Số người hiện tại',
    price_per_month DECIMAL(15,2) COMMENT 'Giá/tháng - lấy từ bảng Pricing',
    room_type ENUM('đơn', 'đôi', '4 người', '6 người') NOT NULL,
    amenities TEXT COMMENT 'Tiện ích: JSON hoặc text',
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    INDEX idx_building_id (building_id),
    INDEX idx_room_code (room_code),
    INDEX idx_status (status),
    INDEX idx_room_type (room_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. BẢNG PRICING (Đơn giá) - Manager quản lý
-- ============================================
CREATE TABLE pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price_type VARCHAR(50) NOT NULL COMMENT 'electricity, water, room_single, room_double, room_4people, room_6people, service_xxx',
    price_value DECIMAL(15,2) NOT NULL COMMENT 'Giá trị - số tiền',
    unit VARCHAR(20) NOT NULL COMMENT 'kWh, m³, tháng, lần...',
    effective_from DATE NOT NULL COMMENT 'Có hiệu lực từ ngày',
    effective_to DATE NULL COMMENT 'Có hiệu lực đến ngày, null = đang áp dụng',
    description TEXT,
    created_by INT NOT NULL COMMENT 'Manager tạo',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_price_type (price_type),
    INDEX idx_status (status),
    INDEX idx_effective_from (effective_from),
    INDEX idx_effective_to (effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. BẢNG REGISTRATION_PERIODS (Đợt đăng ký) - Manager mở đợt đăng ký
-- ============================================
CREATE TABLE registration_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(200) NOT NULL COMMENT 'Tên đợt: "Đăng ký học kỳ 1 năm 2024-2025"',
    start_date DATE NOT NULL COMMENT 'Ngày bắt đầu nhận đơn',
    end_date DATE NOT NULL COMMENT 'Ngày kết thúc nhận đơn',
    semester VARCHAR(20) COMMENT 'Học kỳ',
    academic_year VARCHAR(20) COMMENT 'Năm học',
    total_rooms_available INT NULL COMMENT 'Tổng số phòng có sẵn (tùy chọn)',
    status ENUM('upcoming', 'open', 'closed') DEFAULT 'upcoming',
    created_by INT NOT NULL COMMENT 'Manager tạo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_status (status),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. BẢNG STUDENTS (Sinh viên)
-- ============================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Mã sinh viên',
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    university VARCHAR(200) COMMENT 'Trường đại học',
    major VARCHAR(100) COMMENT 'Ngành học',
    year VARCHAR(20) COMMENT 'Khóa học',
    id_card VARCHAR(20) COMMENT 'CCCD/CMND',
    status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_student_code (student_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. BẢNG APPLICATIONS (Đơn đăng ký)
-- ============================================
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    registration_period_id INT NULL COMMENT 'Thuộc đợt đăng ký nào',
    application_date DATE NOT NULL,
    semester VARCHAR(20) COMMENT 'Học kỳ',
    academic_year VARCHAR(20) COMMENT 'Năm học',
    preferred_room_type ENUM('đơn', 'đôi', '4 người', '6 người') COMMENT 'Loại phòng mong muốn',
    status ENUM('pending', 'approved', 'rejected', 'waiting_list') DEFAULT 'pending' COMMENT 'waiting_list = hết phòng, chờ',
    rejection_reason TEXT,
    approved_by INT NULL COMMENT 'Manager duyệt',
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_period_id) REFERENCES registration_periods(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_registration_period_id (registration_period_id),
    INDEX idx_status (status),
    INDEX idx_application_date (application_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. BẢNG CONTRACTS (Hợp đồng)
-- ============================================
CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    contract_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã hợp đồng',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_fee DECIMAL(15,2) COMMENT 'Phí hàng tháng',
    deposit DECIMAL(15,2) DEFAULT 0 COMMENT 'Tiền đặt cọc',
    status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
    signed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    terminated_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_room_id (room_id),
    INDEX idx_contract_code (contract_code),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. BẢNG ROOM_ASSIGNMENTS (Phân phòng)
-- ============================================
CREATE TABLE room_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'null nếu đang ở',
    status ENUM('active', 'moved_out') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    INDEX idx_contract_id (contract_id),
    INDEX idx_student_id (student_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. BẢNG ROOM_TRANSFER_REQUESTS (Yêu cầu chuyển phòng)
-- ============================================
CREATE TABLE room_transfer_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    current_room_id INT NOT NULL COMMENT 'Phòng hiện tại',
    requested_room_id INT NULL COMMENT 'Phòng muốn chuyển đến (null = chỉ muốn chuyển đi)',
    reason TEXT COMMENT 'Lý do chuyển phòng',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL COMMENT 'Manager duyệt',
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (current_room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (requested_room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_current_room_id (current_room_id),
    INDEX idx_requested_room_id (requested_room_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. BẢNG INVOICES (Hóa đơn) - Manager tạo (theo từng cá nhân)
-- ============================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL COMMENT 'Sinh viên này',
    contract_id INT NOT NULL,
    room_id INT NOT NULL COMMENT 'Phòng đang ở',
    invoice_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã hóa đơn - tự động tạo',
    invoice_month VARCHAR(7) NOT NULL COMMENT 'Tháng hóa đơn: YYYY-MM',
    -- Tiền phòng (chia đều)
    room_total_fee DECIMAL(15,2) NOT NULL COMMENT 'TỔNG tiền phòng của cả phòng/tháng - LƯU CỐ ĐỊNH',
    room_occupancy_count INT NOT NULL COMMENT 'Số người trong phòng tại thời điểm tạo hóa đơn - LƯU CỐ ĐỊNH',
    room_fee_per_person DECIMAL(15,2) NOT NULL COMMENT 'Tiền phòng chia đều cho mỗi người = room_total_fee / room_occupancy_count - LƯU CỐ ĐỊNH',
    -- Tiền điện (chia đều)
    electricity_total_room DECIMAL(15,2) NOT NULL COMMENT 'TỔNG tiền điện của cả phòng - LƯU CỐ ĐỊNH',
    electricity_amount_per_person DECIMAL(15,2) NOT NULL COMMENT 'Tiền điện chia đều cho mỗi người - LƯU CỐ ĐỊNH',
    electricity_amount DECIMAL(10,2) NOT NULL COMMENT 'Số kWh điện của cả phòng - LƯU CỐ ĐỊNH',
    electricity_unit_price DECIMAL(15,2) NOT NULL COMMENT 'Đơn giá điện/kWh - LƯU CỐ ĐỊNH',
    -- Tiền nước (chia đều)
    water_total_room DECIMAL(15,2) NOT NULL COMMENT 'TỔNG tiền nước của cả phòng - LƯU CỐ ĐỊNH',
    water_amount_per_person DECIMAL(15,2) NOT NULL COMMENT 'Tiền nước chia đều cho mỗi người - LƯU CỐ ĐỊNH',
    water_amount DECIMAL(10,2) NOT NULL COMMENT 'Số m³ nước của cả phòng - LƯU CỐ ĐỊNH',
    water_unit_price DECIMAL(15,2) NOT NULL COMMENT 'Đơn giá nước/m³ - LƯU CỐ ĐỊNH',
    -- Tiền dịch vụ (chia đều)
    service_total_room DECIMAL(15,2) DEFAULT 0 COMMENT 'TỔNG tiền dịch vụ của cả phòng - LƯU CỐ ĐỊNH',
    service_amount_per_person DECIMAL(15,2) DEFAULT 0 COMMENT 'Tiền dịch vụ chia đều cho mỗi người - LƯU CỐ ĐỊNH',
    service_details TEXT COMMENT 'Chi tiết dịch vụ: JSON hoặc text - LƯU CỐ ĐỊNH',
    -- Phí vi phạm (KHÔNG chia, tính riêng)
    violation_fee DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí vi phạm của sinh viên này - KHÔNG chia, tính riêng - LƯU CỐ ĐỊNH',
    -- Tổng tiền
    subtotal DECIMAL(15,2) NOT NULL COMMENT 'Tổng các khoản trước - LƯU CỐ ĐỊNH',
    total_amount DECIMAL(15,2) NOT NULL COMMENT 'Tổng tiền phải trả của sinh viên này - LƯU CỐ ĐỊNH',
    due_date DATE NOT NULL COMMENT 'Hạn thanh toán',
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL COMMENT 'Manager tạo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    notes TEXT COMMENT 'Ghi chú',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_contract_id (contract_id),
    INDEX idx_room_id (room_id),
    INDEX idx_invoice_code (invoice_code),
    INDEX idx_invoice_month (invoice_month),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. BẢNG PAYMENTS (Thanh toán) - Giao dịch nộp tiền
-- ============================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL COMMENT 'Thanh toán cho hóa đơn nào',
    student_id INT NOT NULL,
    payment_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã giao dịch',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền nộp',
    payment_date DATE NOT NULL COMMENT 'Ngày nộp tiền',
    payment_method ENUM('cash', 'bank_transfer') DEFAULT 'cash',
    transaction_code VARCHAR(100) COMMENT 'Mã giao dịch - tùy chọn',
    status ENUM('pending', 'confirmed') DEFAULT 'pending' COMMENT 'Manager xác nhận',
    confirmed_by INT NULL COMMENT 'Manager xác nhận',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_student_id (student_id),
    INDEX idx_payment_code (payment_code),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. BẢNG SERVICES (Dịch vụ)
-- ============================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Mã dịch vụ',
    service_name VARCHAR(100) NOT NULL COMMENT 'Tên dịch vụ: WiFi, máy giặt, tủ lạnh, giặt ủi...',
    description TEXT COMMENT 'Mô tả',
    price DECIMAL(15,2) NOT NULL COMMENT 'Giá dịch vụ/phòng - VNĐ/phòng/tháng',
    unit VARCHAR(20) NOT NULL COMMENT 'Đơn vị: tháng, phòng...',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_code (service_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. BẢNG ROOM_SERVICES (Dịch vụ của phòng) - Manager gán dịch vụ cho phòng
-- ============================================
CREATE TABLE room_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL COMMENT 'Phòng này',
    service_id INT NOT NULL COMMENT 'Dịch vụ này',
    start_date DATE NOT NULL COMMENT 'Bắt đầu từ ngày',
    end_date DATE NULL COMMENT 'Kết thúc ngày, null = đang áp dụng',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_room_id (room_id),
    INDEX idx_service_id (service_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    UNIQUE KEY unique_room_service (room_id, service_id, start_date) COMMENT 'Mỗi phòng chỉ có 1 dịch vụ tại 1 thời điểm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. BẢNG SERVICE_REGISTRATIONS (Đăng ký dịch vụ) - Giữ lại để tương lai (nếu cần)
-- ============================================
CREATE TABLE service_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    service_id INT NOT NULL,
    registration_date DATE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_service_id (service_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. BẢNG VIOLATIONS (Vi phạm)
-- ============================================
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    violation_type VARCHAR(50) NOT NULL COMMENT 'noise, alcohol, late_night, damage...',
    description TEXT,
    violation_date DATE NOT NULL,
    reported_by INT NOT NULL COMMENT 'Người báo cáo',
    penalty_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Phạt tiền',
    penalty_type ENUM('warning', 'fine', 'suspension') DEFAULT 'warning',
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    evidence TEXT COMMENT 'Ghi chú - text',
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_room_id (room_id),
    INDEX idx_violation_date (violation_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. BẢNG MAINTENANCE_REQUESTS (Yêu cầu sửa chữa)
-- ============================================
CREATE TABLE maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    request_type ENUM('electrical', 'plumbing', 'furniture', 'other') NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT NULL COMMENT 'Manager phân công',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. BẢNG NOTIFICATIONS (Thông báo) - Tùy chọn
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'null = gửi tất cả',
    title VARCHAR(200) NOT NULL,
    content TEXT,
    type ENUM('payment_reminder', 'violation', 'maintenance', 'general') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- KẾT THÚC
-- ============================================
-- Database đã được tạo thành công với 18 bảng:
-- 1. users
-- 2. buildings
-- 3. rooms
-- 4. pricing
-- 5. registration_periods (MỚI - Đợt đăng ký)
-- 6. students
-- 7. applications (ĐÃ CẬP NHẬT - thêm registration_period_id, status waiting_list)
-- 8. contracts
-- 9. room_assignments
-- 10. room_transfer_requests
-- 11. invoices (ĐÃ CẬP NHẬT - dịch vụ chia đều)
-- 12. payments
-- 13. services (ĐÃ CẬP NHẬT - thêm updated_at)
-- 14. room_services (MỚI - Dịch vụ của phòng)
-- 15. service_registrations (Giữ lại để tương lai)
-- 16. violations
-- 17. maintenance_requests
-- 18. notifications
-- Tất cả các bảng đã có đầy đủ foreign keys, indexes và constraints

