-- ============================================
-- FILE CHÈN DỮ LIỆU MẪU
-- Chạy file này sau khi đã chạy quanlyktx.sql
-- ============================================

USE quanlyktx;

-- ============================================
-- 1. CHÈN DỮ LIỆU MẪU CHO BẢNG USERS
-- ============================================

-- Admin (mật khẩu: admin123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', 'admin123', 'Quản trị viên', 'admin@ktx.edu.vn', '0123456789', 'admin', 'active');

-- Manager (mật khẩu: manager123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('manager', 'manager123', 'Quản lý KTX', 'manager@ktx.edu.vn', '0987654321', 'manager', 'active');

-- Student (mật khẩu: student123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('student', 'student123', 'Sinh viên Test', 'student@ktx.edu.vn', '0912345678', 'student', 'active');

-- ============================================
-- 2. CHÈN DỮ LIỆU MẪU CHO BẢNG STUDENTS
-- ============================================

-- Lấy user_id của student vừa tạo
SET @student_user_id = (SELECT id FROM users WHERE username = 'student' LIMIT 1);

INSERT INTO students (user_id, student_code, full_name, date_of_birth, gender, phone, email, address, university, major, year, id_card, status) VALUES
(@student_user_id, 'SV001', 'Sinh viên Test', '2000-01-01', 'male', '0912345678', 'student@ktx.edu.vn', '123 Đường ABC, Quận XYZ, TP.HCM', 'Đại học Test', 'Công nghệ thông tin', '2020', '123456789012', 'active');

-- ============================================
-- 3. CHÈN DỮ LIỆU MẪU CHO BẢNG BUILDINGS
-- ============================================

INSERT INTO buildings (building_code, building_name, address, floors, description) VALUES
('A', 'Tòa nhà A', '123 Đường KTX, Quận 1, TP.HCM', 5, 'Tòa nhà dành cho sinh viên nam'),
('B', 'Tòa nhà B', '123 Đường KTX, Quận 1, TP.HCM', 5, 'Tòa nhà dành cho sinh viên nữ');

-- ============================================
-- 4. CHÈN DỮ LIỆU MẪU CHO BẢNG ROOMS
-- ============================================

SET @building_a_id = (SELECT id FROM buildings WHERE building_code = 'A' LIMIT 1);
SET @building_b_id = (SELECT id FROM buildings WHERE building_code = 'B' LIMIT 1);

-- Phòng 4 người
INSERT INTO rooms (building_id, room_code, room_number, floor, capacity, current_occupancy, room_type, amenities, status) VALUES
(@building_a_id, 'A101', '101', 1, 4, 0, '4 người', 'Điều hòa, Tủ lạnh, WiFi', 'available'),
(@building_a_id, 'A102', '102', 1, 4, 0, '4 người', 'Điều hòa, Tủ lạnh, WiFi', 'available'),
(@building_a_id, 'A201', '201', 2, 4, 0, '4 người', 'Điều hòa, Tủ lạnh, WiFi', 'available'),
(@building_b_id, 'B101', '101', 1, 4, 0, '4 người', 'Điều hòa, Tủ lạnh, WiFi', 'available');

-- Phòng 6 người
INSERT INTO rooms (building_id, room_code, room_number, floor, capacity, current_occupancy, room_type, amenities, status) VALUES
(@building_a_id, 'A301', '301', 3, 6, 0, '6 người', 'Điều hòa, WiFi', 'available'),
(@building_b_id, 'B201', '201', 2, 6, 0, '6 người', 'Điều hòa, WiFi', 'available');

-- ============================================
-- 5. CHÈN DỮ LIỆU MẪU CHO BẢNG PRICING
-- ============================================

SET @manager_id = (SELECT id FROM users WHERE username = 'manager' LIMIT 1);

-- Đơn giá điện
INSERT INTO pricing (price_type, price_value, unit, effective_from, effective_to, description, created_by, status) VALUES
('electricity', 3500.00, 'kWh', '2024-01-01', NULL, 'Đơn giá điện', @manager_id, 'active');

-- Đơn giá nước
INSERT INTO pricing (price_type, price_value, unit, effective_from, effective_to, description, created_by, status) VALUES
('water', 30000.00, 'm³', '2024-01-01', NULL, 'Đơn giá nước', @manager_id, 'active');

-- Giá phòng
INSERT INTO pricing (price_type, price_value, unit, effective_from, effective_to, description, created_by, status) VALUES
('room_4people', 2000000.00, 'tháng', '2024-01-01', NULL, 'Giá phòng 4 người/tháng', @manager_id, 'active'),
('room_6people', 1500000.00, 'tháng', '2024-01-01', NULL, 'Giá phòng 6 người/tháng', @manager_id, 'active'),
('room_double', 3000000.00, 'tháng', '2024-01-01', NULL, 'Giá phòng đôi/tháng', @manager_id, 'active'),
('room_single', 5000000.00, 'tháng', '2024-01-01', NULL, 'Giá phòng đơn/tháng', @manager_id, 'active');

-- ============================================
-- 6. CHÈN DỮ LIỆU MẪU CHO BẢNG SERVICES
-- ============================================

INSERT INTO services (service_code, service_name, description, price, unit, status) VALUES
('WIFI', 'WiFi', 'Dịch vụ WiFi tốc độ cao', 100000.00, 'tháng', 'active'),
('WASHING', 'Máy giặt', 'Dịch vụ máy giặt', 200000.00, 'tháng', 'active'),
('FRIDGE', 'Tủ lạnh', 'Dịch vụ tủ lạnh', 150000.00, 'tháng', 'active');

-- ============================================
-- KẾT THÚC
-- ============================================

SELECT 'Dữ liệu mẫu đã được chèn thành công!' as message;

