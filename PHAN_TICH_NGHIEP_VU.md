# PHÂN TÍCH NGHIỆP VỤ HỆ THỐNG QUẢN LÝ KÝ TÚC XÁ

## MỤC TIÊU VÀ YÊU CẦU DỰ ÁN

### Mục tiêu
Xây dựng hệ thống quản lý ký túc xá đơn giản cho môi trường **local development**, phục vụ quản lý phòng ở, sinh viên, hợp đồng, thanh toán và dịch vụ.

### Phạm vi dự án
- **Môi trường**: Local (XAMPP)
- **Database**: MySQL
- **Backend**: PHP thuần
- **Frontend**: Bootstrap 5, HTML/CSS/JavaScript
- **Không cần**: Email, PDF generation, File upload phức tạp, Payment gateway

### Yêu cầu kỹ thuật
- **Cấu trúc dự án**: Theo mẫu folder `test` (đã có sẵn)
  - `functions/`: Business logic layer (các hàm xử lý database)
  - `handle/`: Controller layer (xử lý request, routing)
  - `views/`: Presentation layer (giao diện người dùng)
  - `database/`: Script SQL tạo database
  - `css/`: File CSS
  - `images/`: Hình ảnh
- **Logic xử lý**: 
  - View → Form submit → Handle (xử lý action) → Functions (business logic) → Database
  - Sử dụng `action` parameter (GET/POST) để routing
  - Session-based authentication
  - Prepared statements cho SQL queries
- **Nguyên tắc**:
  - Separation of concerns: tách biệt logic, controller, view
  - Mỗi module độc lập: buildings, rooms, students, applications, contracts, invoices, payments, violations, services
  - Code tái sử dụng: functions cho các thao tác CRUD
  - Error handling qua session messages (success/error)

### Các module chính cần xây dựng
1. **Authentication**: Login, Logout, Phân quyền (admin, manager, student)
2. **Buildings**: Quản lý tòa nhà
3. **Rooms**: Quản lý phòng (CRUD, trạng thái)
4. **Students**: Quản lý sinh viên
5. **Applications**: Đơn đăng ký ở KTX (sinh viên tạo, manager duyệt)
6. **Contracts**: Hợp đồng thuê phòng
7. **Room_Assignments**: Phân phòng cho sinh viên
8. **Invoices**: Hóa đơn hàng tháng (Manager tạo, ghi rõ từng khoản)
9. **Payments**: Giao dịch thanh toán (Sinh viên nộp, Manager xác nhận)
10. **Violations**: Quản lý vi phạm
11. **Services**: Dịch vụ (giặt ủi, internet...)
12. **Service_Registrations**: Đăng ký dịch vụ

### Quy trình quan trọng
- **Tạo hóa đơn**: Chỉ Manager tạo, sinh viên KHÔNG được tạo
- **Hóa đơn bao gồm**: Phòng + Điện + Nước + Dịch vụ + Vi phạm (nếu có)
- **Thanh toán**: Sinh viên nộp tiền → Manager xác nhận
- **Nguyên tắc bất biến hóa đơn**: 
  - Hóa đơn đã tạo **KHÔNG BỊ THAY ĐỔI** dù Manager có thay đổi đơn giá sau này
  - Đơn giá được lưu cố định vào hóa đơn tại thời điểm tạo
  - Ví dụ: Hóa đơn tháng 11 với đơn giá tháng 11 sẽ giữ nguyên, không bị ảnh hưởng khi tháng 12 đổi đơn giá

---

## 1. CÁC LOẠI USER (ROLES)

### 1.1. ADMIN (Quản trị viên)
- **Quyền cao nhất**, quản lý toàn bộ hệ thống
- Quản lý tài khoản, phân quyền
- Xem báo cáo tổng hợp, thống kê

### 1.2. QUẢN LÝ KTX (Manager)
- Quản lý phòng ở, sinh viên, hợp đồng
- Duyệt đơn đăng ký ở KTX
- Xử lý vi phạm, thanh toán
- Xem báo cáo chi tiết

### 1.3. SINH VIÊN (Student)
- Đăng ký ở KTX
- Xem thông tin phòng, bạn cùng phòng
- Thanh toán phí
- Đăng ký dịch vụ (giặt ủi, internet...)
- Xem lịch sử thanh toán, vi phạm

---

## 2. CHỨC NĂNG CHI TIẾT THEO TỪNG USER

### 2.1. ADMIN (Quản trị viên)

#### A. Quản lý tài khoản
- **Thêm/Sửa/Xóa tài khoản** cho các role khác
- **Phân quyền** cho từng user
- **Reset mật khẩu** khi cần
- **Khóa/Mở khóa** tài khoản

#### B. Quản lý hệ thống
- **Cấu hình hệ thống**: cài đặt cơ bản của website
- **Backup/Restore database**
- **Xem log hệ thống**
- **Lưu ý**: Admin KHÔNG quản lý giá phòng, đơn giá điện/nước, phí dịch vụ - những thứ này do Manager quản lý

#### C. Báo cáo & Thống kê
- **Báo cáo tổng hợp**: tổng số phòng, sinh viên, doanh thu
- **Thống kê đơn giản**: xem số liệu cơ bản

---

### 2.2. QUẢN LÝ KTX (Manager)

#### A. Quản lý đơn giá (QUAN TRỌNG)
- **Quản lý đơn giá điện**: cài đặt giá tiền/kWh (ví dụ: 2,500 VNĐ/kWh)
- **Quản lý đơn giá nước**: cài đặt giá tiền/m³ (ví dụ: 15,000 VNĐ/m³)
- **Quản lý giá phòng**: cài đặt giá phòng theo từng loại phòng (đơn, đôi, 4 người, 6 người)
- **Quản lý phí dịch vụ**: cài đặt giá các dịch vụ (giặt ủi, internet...)
- **Cập nhật đơn giá**: có thể thay đổi đơn giá theo thời gian (lưu lịch sử)
- **Xem lịch sử thay đổi**: xem các mức giá đã thay đổi qua các thời kỳ

#### B. Quản lý tòa nhà (Buildings)
- **Xem danh sách tòa nhà**: tất cả tòa nhà trong KTX
- **Thêm/Sửa/Xóa tòa nhà**: mã tòa, tên tòa, địa chỉ, số tầng
- **Quản lý thông tin tòa**: mô tả, tiện ích chung của tòa

#### C. Quản lý phòng ở
- **Xem danh sách phòng**: tất cả phòng, trạng thái (trống/đầy/đang sửa chữa)
- **Thêm/Sửa/Xóa phòng**: mã phòng, số người ở, giá, tiện ích
- **Phân loại phòng**: phòng đơn, phòng đôi, phòng 4 người, phòng 6 người
- **Quản lý tiện ích phòng**: điều hòa, tủ lạnh, wifi, máy nước nóng...
- **Xem phòng theo tòa**: lọc phòng theo tòa nhà

#### D. Quản lý sinh viên
- **Duyệt đơn đăng ký**: xem, duyệt/từ chối đơn đăng ký ở KTX
- **Phân phòng**: gán sinh viên vào phòng phù hợp
- **Chuyển phòng**: chuyển sinh viên từ phòng này sang phòng khác
- **Xem thông tin sinh viên**: thông tin cá nhân, phòng đang ở, lịch sử
- **Gia hạn hợp đồng**: gia hạn hợp đồng cho sinh viên
- **Chấm dứt hợp đồng**: khi sinh viên ra khỏi KTX

#### E. Quản lý hợp đồng
- **Tạo hợp đồng**: tạo hợp đồng thuê phòng cho sinh viên
- **Xem danh sách hợp đồng**: tất cả hợp đồng, hợp đồng sắp hết hạn
- **Gia hạn hợp đồng**: gia hạn hợp đồng
- **Thanh lý hợp đồng**: khi sinh viên ra khỏi KTX

#### F. Quản lý thanh toán
- **Tạo hóa đơn hàng tháng**: 
  - Chỉ Manager mới được tạo hóa đơn
  - **Tạo hóa đơn theo từng cá nhân** (mỗi sinh viên trong phòng có hóa đơn riêng)
  - **Chia tiền phòng theo đầu người**:
    * Lấy tổng giá phòng/tháng
    * Đếm số người đang ở trong phòng (từ bảng Room_Assignments)
    * Chia đều: Tiền phòng mỗi người = Tổng giá phòng / Số người trong phòng
  - Hóa đơn cho mỗi sinh viên bao gồm các khoản chi tiết:
    * **Tiền phòng (chia đều)**: Tổng giá phòng / Số người trong phòng
    * **Tiền điện (chia đều)**: Tổng tiền điện / Số người trong phòng
    * **Tiền nước (chia đều)**: Tổng tiền nước / Số người trong phòng
    * **Tiền dịch vụ (chia đều)**: 
      - Lấy tất cả dịch vụ của phòng (từ Room_Services, status = active)
      - Tổng tiền dịch vụ = SUM(giá của tất cả dịch vụ)
      - Tiền dịch vụ mỗi người = Tổng tiền dịch vụ / Số người trong phòng
    * **Phí vi phạm**: nếu sinh viên đó có vi phạm trong tháng (KHÔNG chia, tính riêng)
  - Ghi rõ từng khoản và số tiền
  - Ghi rõ: Tổng giá phòng, Số người trong phòng, Tiền phòng mỗi người
  - Tự động tính tổng tiền cho từng sinh viên
  - Tạo hóa đơn riêng cho từng sinh viên trong phòng
- **Xem danh sách hóa đơn**: tất cả hóa đơn đã tạo
- **Xem danh sách thanh toán**: các giao dịch thanh toán của sinh viên
- **Xác nhận thanh toán**: xác nhận khi sinh viên đã nộp tiền (cập nhật status)
- **Xem công nợ**: sinh viên nào chưa thanh toán, nợ bao nhiêu

#### G. Quản lý vi phạm
- **Ghi nhận vi phạm**: ghi nhận vi phạm nội quy của sinh viên
- **Xem danh sách vi phạm**: tất cả vi phạm, vi phạm của từng sinh viên
- **Xử lý vi phạm**: cảnh báo, phạt tiền (ghi chú thủ công)
- **Lịch sử vi phạm**: xem lịch sử vi phạm của sinh viên

#### H. Quản lý dịch vụ
- **Quản lý dịch vụ**: WiFi, máy giặt, tủ lạnh, giặt ủi, internet, các dịch vụ khác
- **Cấu hình dịch vụ**:
  - Tạo dịch vụ mới: tên dịch vụ, giá/phòng, đơn vị tính (tháng, phòng...)
  - Ví dụ: WiFi 100,000 VNĐ/phòng/tháng, Máy giặt 200,000 VNĐ/phòng/tháng
- **Gán dịch vụ cho phòng**: Manager gán dịch vụ cho từng phòng (ví dụ: Phòng 101 có WiFi, máy giặt)
- **Cách tính tiền dịch vụ**:
  - Tất cả dịch vụ đều tính theo phòng (giá/phòng)
  - Khi tạo hóa đơn: Lấy tổng tiền dịch vụ của phòng → Chia đều cho số người trong phòng
  - Ví dụ: Phòng có WiFi (100k) + Máy giặt (200k) = 300k → Phòng 4 người = 75k/người
- **Xem danh sách dịch vụ của phòng**: xem phòng nào có dịch vụ gì

#### I. Quản lý yêu cầu chuyển phòng
- **Xem yêu cầu chuyển phòng**: danh sách yêu cầu từ sinh viên
- **Duyệt/Từ chối yêu cầu**: xem lý do, kiểm tra phòng trống, quyết định
- **Xử lý chuyển phòng**: cập nhật Room_Assignments, hợp đồng khi duyệt

#### J. Quản lý yêu cầu sửa chữa
- **Xem yêu cầu sửa chữa**: danh sách yêu cầu từ sinh viên
- **Xử lý yêu cầu**: phân công người sửa, cập nhật trạng thái
- **Hoàn thành**: đánh dấu đã sửa xong
- **Xem lịch sử**: các yêu cầu đã xử lý

#### K. Báo cáo
- **Báo cáo phòng**: phòng trống, phòng đầy, tỷ lệ sử dụng
- **Báo cáo tài chính**: doanh thu theo tháng (đơn giản)
- **Báo cáo sinh viên**: số lượng sinh viên, sinh viên mới, sinh viên ra khỏi KTX

---

### 2.3. SINH VIÊN (Student)

#### A. Đăng ký ở KTX
- **Đăng ký ở KTX**: điền form đăng ký (đơn giản, không cần upload giấy tờ)
- **Xem trạng thái đơn**: đơn đang chờ duyệt, đã duyệt, bị từ chối
- **Xem thông tin phòng**: phòng được phân, bạn cùng phòng

#### B. Thông tin cá nhân
- **Xem thông tin cá nhân**: thông tin cá nhân, phòng đang ở
- **Cập nhật thông tin**: cập nhật số điện thoại, email...
- **Xem bạn cùng phòng**: thông tin bạn cùng phòng

#### C. Quản lý phòng
- **Xem thông tin phòng**: tiện ích, quy định phòng
- **Yêu cầu chuyển phòng**: 
  - Gửi yêu cầu chuyển phòng (ghi lý do)
  - Xem trạng thái yêu cầu (đang chờ, đã duyệt, bị từ chối)
- **Yêu cầu sửa chữa**: 
  - Báo hỏng hóc, yêu cầu sửa chữa
  - Chọn loại sửa chữa (điện, nước, nội thất, khác)
  - Xem trạng thái yêu cầu (đang chờ, đang sửa, đã hoàn thành)

#### D. Thanh toán
- **Xem hóa đơn**: 
  - Xem các hóa đơn mà Manager đã tạo
  - Xem chi tiết từng khoản: phòng, điện, nước, dịch vụ, vi phạm
  - Xem tổng tiền phải nộp
  - **KHÔNG được tạo hóa đơn** - chỉ Manager mới tạo được
- **Nộp tiền theo hóa đơn**: 
  - Chọn hóa đơn cần thanh toán
  - Xác nhận đã nộp tiền (ghi chú thủ công)
  - Manager sẽ xác nhận và cập nhật trạng thái
- **Xem lịch sử thanh toán**: các giao dịch đã thanh toán
- **Xem công nợ**: xem các hóa đơn chưa thanh toán

#### E. Dịch vụ
- **Xem dịch vụ của phòng**: xem phòng đang ở có dịch vụ gì (WiFi, máy giặt, tủ lạnh...)
- **Lưu ý**: Dịch vụ được Manager gán cho phòng, sinh viên không cần đăng ký riêng

#### F. Vi phạm & Thông báo
- **Xem vi phạm**: xem các vi phạm đã mắc phải
- **Xem thông báo**: thông báo từ quản lý, nhắc nhở thanh toán
- **Phản hồi**: phản hồi về vi phạm hoặc thông báo

---

## 3. CẤU TRÚC DATABASE ĐỀ XUẤT

### 3.1. Bảng Users (Người dùng)
```sql
- id (PK)
- username
- password (hashed)
- full_name
- email
- phone
- role (admin, manager, student)
- status (active, inactive)
- created_at
- updated_at
```

### 3.2. Bảng Buildings (Tòa nhà)
```sql
- id (PK)
- building_code (mã tòa)
- building_name (tên tòa)
- address (địa chỉ)
- floors (số tầng)
- description
- created_at
```

### 3.3. Bảng Rooms (Phòng)
```sql
- id (PK)
- building_id (FK)
- room_code (mã phòng)
- room_number
- floor (tầng)
- capacity (sức chứa - số người)
- current_occupancy (số người hiện tại)
- price_per_month (giá/tháng - lấy từ bảng Pricing)
- room_type (đơn, đôi, 4 người, 6 người)
- amenities (tiện ích: JSON hoặc text)
- status (available, occupied, maintenance)
- created_at
- updated_at
```

### 3.4. Bảng Pricing (Đơn giá) - Manager quản lý
```sql
- id (PK)
- price_type (electricity, water, room_single, room_double, room_4people, room_6people, service_xxx)
- price_value (giá trị - số tiền)
- unit (kWh, m³, tháng, lần...)
- effective_from (có hiệu lực từ ngày)
- effective_to (có hiệu lực đến ngày, null = đang áp dụng)
- description (mô tả)
- created_by (FK -> users, Manager)
- status (active, inactive)
- created_at
- updated_at
```

**Lưu ý**: 
- Bảng này lưu lịch sử thay đổi đơn giá
- Khi Manager cập nhật đơn giá mới, tạo record mới với effective_from = ngày hiện tại
- Record cũ sẽ có effective_to = ngày trước đó
- Khi tạo hóa đơn, lấy đơn giá active và effective_from <= tháng hóa đơn
- **QUAN TRỌNG**: Đơn giá được LƯU CỐ ĐỊNH vào hóa đơn, không bị thay đổi dù sau này Manager cập nhật đơn giá mới

### 3.5. Bảng Registration_Periods (Đợt đăng ký) - Manager mở đợt đăng ký
```sql
- id (PK)
- period_name (Tên đợt: "Đăng ký học kỳ 1 năm 2024-2025")
- start_date (Ngày bắt đầu nhận đơn)
- end_date (Ngày kết thúc nhận đơn)
- semester (Học kỳ)
- academic_year (Năm học)
- total_rooms_available (Tổng số phòng có sẵn - tùy chọn)
- status (upcoming, open, closed)
- created_by (FK -> users, Manager tạo)
- created_at
- updated_at
```

**Lưu ý**:
- Manager tạo đợt đăng ký mới với thời gian bắt đầu và kết thúc
- Sinh viên chỉ có thể đăng ký trong khoảng thời gian này
- Status: "upcoming" (sắp tới), "open" (đang mở), "closed" (đã đóng)

### 3.6. Bảng Students (Sinh viên)
```sql
- id (PK)
- user_id (FK -> users)
- student_code (mã sinh viên)
- full_name
- date_of_birth
- gender
- phone
- email
- address
- university (trường đại học)
- major (ngành học)
- year (khóa học)
- id_card (CCCD/CMND)
- status (active, inactive, graduated)
- created_at
- updated_at
```

### 3.7. Bảng Applications (Đơn đăng ký)
```sql
- id (PK)
- student_id (FK)
- registration_period_id (FK -> registration_periods) - Thuộc đợt đăng ký nào
- application_date
- semester (học kỳ)
- academic_year (năm học)
- preferred_room_type (loại phòng mong muốn)
- status (pending, approved, rejected, waiting_list)
  * pending: Chờ duyệt
  * approved: Đã duyệt
  * rejected: Bị từ chối
  * waiting_list: Hết phòng, vào danh sách chờ
- rejection_reason
- approved_by (FK -> users)
- approved_at
- created_at
```

### 3.8. Bảng Contracts (Hợp đồng)
```sql
- id (PK)
- student_id (FK)
- room_id (FK)
- contract_code (mã hợp đồng)
- start_date
- end_date
- monthly_fee (phí hàng tháng)
- deposit (tiền đặt cọc)
- status (active, expired, terminated)
- signed_at
- created_at
- terminated_at
```

### 3.9. Bảng Room_Assignments (Phân phòng)
```sql
- id (PK)
- contract_id (FK)
- student_id (FK)
- room_id (FK)
- assigned_date
- end_date (null nếu đang ở)
- status (active, moved_out)
- created_at
```

### 3.9.1. Bảng Room_Transfer_Requests (Yêu cầu chuyển phòng)
```sql
- id (PK)
- student_id (FK)
- current_room_id (FK) - Phòng hiện tại
- requested_room_id (FK) - Phòng muốn chuyển đến (null = chỉ muốn chuyển đi)
- reason (lý do chuyển phòng)
- status (pending, approved, rejected)
- reviewed_by (FK -> users, Manager)
- reviewed_at
- created_at
- updated_at
```

### 3.10. Bảng Invoices (Hóa đơn) - Manager tạo (theo từng cá nhân)
```sql
- id (PK)
- student_id (FK) - Sinh viên này
- contract_id (FK)
- room_id (FK) - Phòng đang ở
- invoice_code (mã hóa đơn - tự động tạo)
- invoice_month (tháng hóa đơn: YYYY-MM)
- room_total_fee (TỔNG tiền phòng của cả phòng/tháng - LƯU CỐ ĐỊNH)
- room_occupancy_count (Số người trong phòng tại thời điểm tạo hóa đơn - LƯU CỐ ĐỊNH)
- room_fee_per_person (Tiền phòng chia đều cho mỗi người = room_total_fee / room_occupancy_count - LƯU CỐ ĐỊNH)
- electricity_total_room (TỔNG tiền điện của cả phòng - LƯU CỐ ĐỊNH)
- electricity_amount_per_person (Tiền điện chia đều cho mỗi người - LƯU CỐ ĐỊNH)
- electricity_amount (số kWh điện của cả phòng - LƯU CỐ ĐỊNH)
- electricity_unit_price (đơn giá điện/kWh - LƯU CỐ ĐỊNH)
- water_total_room (TỔNG tiền nước của cả phòng - LƯU CỐ ĐỊNH)
- water_amount_per_person (Tiền nước chia đều cho mỗi người - LƯU CỐ ĐỊNH)
- water_amount (số m³ nước của cả phòng - LƯU CỐ ĐỊNH)
- water_unit_price (đơn giá nước/m³ - LƯU CỐ ĐỊNH)
- service_total_room (TỔNG tiền dịch vụ của cả phòng - LƯU CỐ ĐỊNH)
- service_amount_per_person (Tiền dịch vụ chia đều cho mỗi người - LƯU CỐ ĐỊNH)
- service_details (Chi tiết dịch vụ: JSON hoặc text - LƯU CỐ ĐỊNH)
- violation_fee (phí vi phạm của sinh viên này - KHÔNG chia, tính riêng - LƯU CỐ ĐỊNH)
- subtotal (tổng các khoản trước - LƯU CỐ ĐỊNH)
- total_amount (tổng tiền phải trả của sinh viên này - LƯU CỐ ĐỊNH)
- due_date (hạn thanh toán)
- status (pending, paid, overdue, cancelled)
- created_by (FK -> users, Manager tạo)
- created_at
- paid_at
- notes (ghi chú)
```

**QUAN TRỌNG - Nguyên tắc bất biến của hóa đơn**:
- Khi Manager tạo hóa đơn, hệ thống sẽ:
  1. Lấy đơn giá hiện tại từ bảng Pricing (theo tháng hóa đơn)
  2. Đếm số người đang ở trong phòng (từ Room_Assignments, status = active)
  3. Tính tổng tiền phòng, điện, nước của cả phòng
  4. Lấy tất cả dịch vụ của phòng (từ Room_Services, status = active)
  5. Tính tổng tiền dịch vụ = SUM(price của tất cả dịch vụ)
  6. Chia đều tiền phòng, điện, nước, dịch vụ cho từng người trong phòng
  7. **LƯU CỐ ĐỊNH** tất cả đơn giá, số tiền, số người, dịch vụ vào hóa đơn
  8. Tạo hóa đơn riêng cho từng sinh viên trong phòng
  9. Sau khi hóa đơn đã tạo, dù Manager có thay đổi đơn giá, số người trong phòng, hoặc dịch vụ, hóa đơn cũ **KHÔNG BỊ ẢNH HƯỞNG**
  
**Ví dụ**:
- Phòng có 4 người, giá phòng 2,000,000 VNĐ/tháng
- Điện: 100 kWh × 3,500 VNĐ/kWh = 350,000 VNĐ
- Nước: 20 m³ × 30,000 VNĐ/m³ = 600,000 VNĐ
- Dịch vụ của phòng (Manager đã gán):
  * WiFi: 100,000 VNĐ/phòng/tháng
  * Máy giặt: 200,000 VNĐ/phòng/tháng
  * Tổng tiền dịch vụ: 100,000 + 200,000 = 300,000 VNĐ
- **Chia đều**:
  - Tiền phòng mỗi người: 2,000,000 / 4 = 500,000 VNĐ
  - Tiền điện mỗi người: 350,000 / 4 = 87,500 VNĐ
  - Tiền nước mỗi người: 600,000 / 4 = 150,000 VNĐ
  - Tiền dịch vụ mỗi người: 300,000 / 4 = 75,000 VNĐ
- Phí vi phạm (tính riêng):
  * Sinh viên A: 200,000 VNĐ (vi phạm nội quy)
  * Sinh viên B, C, D: 0 VNĐ
- Tổng tiền mỗi người:
  * Sinh viên A: 500,000 + 87,500 + 150,000 + 75,000 + 200,000 = 1,012,500 VNĐ
  * Sinh viên B, C, D: 500,000 + 87,500 + 150,000 + 75,000 + 0 = 812,500 VNĐ
- Tạo 4 hóa đơn riêng, mỗi hóa đơn có phần chia đều trên
- Nếu sau đó có người chuyển phòng hoặc thêm người, hóa đơn đã tạo vẫn giữ nguyên số người và số tiền đã chia

### 3.11. Bảng Payments (Thanh toán) - Giao dịch nộp tiền
```sql
- id (PK)
- invoice_id (FK -> invoices) - Thanh toán cho hóa đơn nào
- student_id (FK)
- payment_code (mã giao dịch)
- amount (số tiền nộp)
- payment_date (ngày nộp tiền)
- payment_method (cash, bank_transfer)
- transaction_code (mã giao dịch - tùy chọn)
- status (pending, confirmed) - Manager xác nhận
- confirmed_by (FK -> users, Manager xác nhận)
- notes (ghi chú)
- created_at
- confirmed_at
```

### 3.12. Bảng Services (Dịch vụ)
```sql
- id (PK)
- service_code (mã dịch vụ)
- service_name (tên dịch vụ: WiFi, máy giặt, tủ lạnh, giặt ủi...)
- description (mô tả)
- price (giá dịch vụ/phòng - VNĐ/phòng/tháng)
- unit (đơn vị: tháng, phòng...)
- status (active, inactive)
- created_at
- updated_at
```

### 3.13. Bảng Room_Services (Dịch vụ của phòng) - Manager gán dịch vụ cho phòng
```sql
- id (PK)
- room_id (FK) - Phòng này
- service_id (FK) - Dịch vụ này
- start_date (bắt đầu từ ngày)
- end_date (kết thúc ngày, null = đang áp dụng)
- status (active, inactive)
- created_at
- updated_at
```

**Lưu ý**: 
- Dịch vụ được gán cho phòng, không phải cho từng sinh viên
- Tất cả dịch vụ đều tính theo phòng (giá/phòng)
- Khi tạo hóa đơn:
  * Lấy tất cả dịch vụ của phòng (Room_Services, status = active)
  * Tổng tiền dịch vụ = SUM(price của tất cả dịch vụ)
  * Tiền dịch vụ mỗi người = Tổng tiền dịch vụ / Số người trong phòng

### 3.14. Bảng Violations (Vi phạm)
```sql
- id (PK)
- student_id (FK)
- room_id (FK)
- violation_type (noise, alcohol, late_night, damage...)
- description
- violation_date
- reported_by (FK -> users)
- penalty_amount (phạt tiền)
- penalty_type (warning, fine, suspension)
- status (pending, resolved)
- evidence (ghi chú - text)
- resolved_at
- created_at
```

### 3.15. Bảng Maintenance_Requests (Yêu cầu sửa chữa)
```sql
- id (PK)
- student_id (FK)
- room_id (FK)
- request_type (electrical, plumbing, furniture, other)
- description
- priority (low, medium, high, urgent)
- status (pending, in_progress, completed, cancelled)
- assigned_to (FK -> users, Manager phân công)
- completed_at
- created_at
- updated_at
```

### 3.16. Bảng Notifications (Thông báo) - Tùy chọn
```sql
- id (PK)
- user_id (FK -> users, null = gửi tất cả)
- title
- content
- type (payment_reminder, violation, maintenance, general)
- is_read (boolean)
- created_at
```

---

## 4. LUỒNG NGHIỆP VỤ CHÍNH

### 4.1. Luồng đăng ký ở KTX (Đơn giản)
```
1. Sinh viên đăng ký tài khoản
2. Đăng nhập
3. Điền form đăng ký ở KTX
4. Gửi đơn đăng ký
5. Quản lý xem đơn đăng ký
6. Quản lý duyệt/từ chối đơn
7. Nếu duyệt:
   - Quản lý phân phòng
   - Tạo hợp đồng (ghi chú thủ công)
   - Sinh viên xem thông tin phòng
```

### 4.2. Luồng thanh toán (Chi tiết)
```
1. Manager tạo hóa đơn hàng tháng:
   - Chọn phòng (hoặc chọn sinh viên)
   - Chọn tháng
   - Nhập số kWh điện, số m³ nước của cả phòng
   - Hệ thống tự động:
     * Đếm số người đang ở trong phòng (từ Room_Assignments, status = active)
     * Lấy đơn giá từ bảng **Pricing** (theo tháng hóa đơn):
       - Đơn giá điện (VNĐ/kWh)
       - Đơn giá nước (VNĐ/m³)
       - Giá phòng (theo loại phòng)
     * Tính tổng tiền phòng, điện, nước của cả phòng
     * Chia đều cho số người:
       - Tiền phòng mỗi người = Tổng giá phòng / Số người
       - Tiền điện mỗi người = Tổng tiền điện / Số người
       - Tiền nước mỗi người = Tổng tiền nước / Số người
   - Tính tiền dịch vụ của phòng:
     * Lấy tất cả dịch vụ của phòng (từ Room_Services, status = active)
     * Tổng tiền dịch vụ = SUM(price của tất cả dịch vụ)
     * Tiền dịch vụ mỗi người = Tổng tiền dịch vụ / Số người trong phòng
   - Với mỗi sinh viên trong phòng:
     * Lấy phí vi phạm của sinh viên đó (nếu có, KHÔNG chia, tính riêng)
     * Tính tổng: (phòng + điện + nước + dịch vụ chia đều) + vi phạm
     * **LƯU CỐ ĐỊNH** tất cả: tổng tiền phòng, số người, tiền chia đều, đơn giá, dịch vụ vào hóa đơn
     * Tạo hóa đơn riêng cho sinh viên đó
   - **Lưu ý**: 
     * Sau khi hóa đơn đã tạo, dù Manager thay đổi đơn giá hoặc số người trong phòng, hóa đơn này KHÔNG bị ảnh hưởng
     * Mỗi sinh viên có hóa đơn riêng, thanh toán riêng
   
2. Sinh viên xem hóa đơn:
   - Xem danh sách hóa đơn đã được tạo
   - Xem chi tiết từng khoản và tổng tiền
   
3. Sinh viên nộp tiền:
   - Chọn hóa đơn cần thanh toán
   - Ghi chú đã nộp tiền (thủ công)
   - Status chuyển sang "pending" (chờ xác nhận)
   
4. Manager xác nhận thanh toán:
   - Xem danh sách thanh toán đang chờ
   - Xác nhận khi đã nhận tiền
   - Cập nhật status hóa đơn thành "paid"
   - Cập nhật status payment thành "confirmed"
   
5. Xem công nợ:
   - Sinh viên: xem các hóa đơn chưa thanh toán
   - Manager: xem tổng công nợ của tất cả sinh viên
```

### 4.3. Luồng vi phạm (Đơn giản)
```
1. Quản lý ghi nhận vi phạm
2. Ghi chú mô tả vi phạm
3. Quản lý xử lý vi phạm (cảnh báo/phạt tiền)
4. Sinh viên xem vi phạm
5. Tạo hóa đơn phạt (nếu có) - ghi chú thủ công
```

### 4.4. Luồng chuyển phòng
```
1. Sinh viên gửi yêu cầu chuyển phòng:
   - Điền form yêu cầu (ghi lý do)
   - Chọn phòng muốn chuyển đến (hoặc chỉ gửi yêu cầu chuyển đi)
   - Gửi yêu cầu
   
2. Manager xem yêu cầu:
   - Xem danh sách yêu cầu chuyển phòng
   - Xem chi tiết: sinh viên, phòng hiện tại, phòng muốn chuyển, lý do
   
3. Manager xử lý:
   - Kiểm tra phòng trống (nếu có phòng muốn chuyển)
   - Duyệt/Từ chối yêu cầu
   
4. Nếu duyệt:
   - Cập nhật Room_Assignments: end_date phòng cũ, tạo assignment mới cho phòng mới
   - Cập nhật Contract: thay đổi room_id
   - Cập nhật Rooms: giảm current_occupancy phòng cũ, tăng phòng mới
   - Cập nhật status yêu cầu thành "approved"
   
5. Nếu từ chối:
   - Ghi lý do từ chối
   - Cập nhật status yêu cầu thành "rejected"
```

### 4.5. Luồng yêu cầu sửa chữa
```
1. Sinh viên gửi yêu cầu sửa chữa:
   - Chọn loại sửa chữa (điện, nước, nội thất, khác)
   - Mô tả chi tiết vấn đề
   - Chọn mức độ ưu tiên (nếu có)
   - Gửi yêu cầu
   
2. Manager xem yêu cầu:
   - Xem danh sách yêu cầu sửa chữa
   - Xem chi tiết: sinh viên, phòng, loại sửa chữa, mô tả
   
3. Manager xử lý:
   - Phân công người sửa (nếu có)
   - Cập nhật status thành "in_progress"
   
4. Hoàn thành:
   - Manager đánh dấu "completed"
   - Ghi chú về việc sửa chữa
   - Sinh viên có thể xem trạng thái
```

---

## 5. CÁC TRANG CHÍNH CẦN THIẾT

### 5.1. Trang chung
- **index.php**: Trang đăng nhập
- **dashboard.php**: Trang chủ (khác nhau theo role)

### 5.2. Trang Admin
#### Dashboard Admin
- Tổng số tài khoản (admin, manager, student)
- Tổng số phòng, sinh viên
- Thống kê tổng quan hệ thống

#### Quản lý tài khoản
- **users.php**: Danh sách tài khoản
- **users/create_user.php**: Tạo tài khoản mới
- **users/edit_user.php**: Sửa thông tin tài khoản
- **users/delete_user.php**: Xóa tài khoản

#### Cấu hình hệ thống
- **settings.php**: Cài đặt hệ thống
- **backup.php**: Backup/Restore database

#### Báo cáo
- **reports.php**: Báo cáo tổng hợp

### 5.3. Trang Quản lý (Manager)
#### Dashboard Manager
- Tổng số phòng (trống/đầy)
- Tổng số sinh viên
- Số đơn đăng ký chờ duyệt
- Số hóa đơn chưa thanh toán
- Số yêu cầu chuyển phòng
- Số yêu cầu sửa chữa

#### Quản lý đơn giá
- **pricing.php**: Danh sách đơn giá
- **pricing/create.php**: Tạo đơn giá mới
- **pricing/edit.php**: Sửa đơn giá
- **pricing/history.php**: Lịch sử thay đổi đơn giá

#### Quản lý tòa nhà
- **buildings.php**: Danh sách tòa nhà
- **buildings/create_building.php**: Tạo tòa nhà mới
- **buildings/edit_building.php**: Sửa tòa nhà

#### Quản lý phòng
- **rooms.php**: Danh sách phòng
- **rooms/create_room.php**: Tạo phòng mới
- **rooms/edit_room.php**: Sửa phòng

#### Quản lý sinh viên
- **students.php**: Danh sách sinh viên
- **students/view_student.php**: Xem chi tiết sinh viên

#### Duyệt đơn đăng ký
- **applications.php**: Danh sách đơn đăng ký
- **applications/view.php**: Xem chi tiết đơn
- **applications/approve.php**: Duyệt đơn
- **applications/reject.php**: Từ chối đơn

#### Quản lý hợp đồng
- **contracts.php**: Danh sách hợp đồng
- **contracts/create_contract.php**: Tạo hợp đồng
- **contracts/edit_contract.php**: Sửa hợp đồng

#### Quản lý thanh toán
- **invoices.php**: Danh sách hóa đơn
- **invoices/create_invoice.php**: Tạo hóa đơn (QUAN TRỌNG)
- **invoices/view_invoice.php**: Xem chi tiết hóa đơn
- **payments.php**: Danh sách thanh toán
- **payments/confirm.php**: Xác nhận thanh toán
- **payments/debt.php**: Xem công nợ

#### Quản lý vi phạm
- **violations.php**: Danh sách vi phạm
- **violations/create.php**: Ghi nhận vi phạm
- **violations/edit.php**: Sửa vi phạm

#### Quản lý dịch vụ
- **services.php**: Danh sách dịch vụ
- **services/create_service.php**: Tạo dịch vụ mới (nhập tên, giá/phòng, đơn vị)
- **services/edit_service.php**: Sửa dịch vụ
- **room_services.php**: Gán dịch vụ cho phòng (Manager chọn phòng và gán dịch vụ)
- **room_services/assign.php**: Gán dịch vụ cho phòng cụ thể
- **room_services/view.php**: Xem dịch vụ của từng phòng

#### Yêu cầu chuyển phòng
- **room_transfers.php**: Danh sách yêu cầu
- **room_transfers/view.php**: Xem chi tiết
- **room_transfers/approve.php**: Duyệt chuyển phòng

#### Yêu cầu sửa chữa
- **maintenance.php**: Danh sách yêu cầu
- **maintenance/view.php**: Xem chi tiết
- **maintenance/assign.php**: Phân công sửa chữa
- **maintenance/complete.php**: Hoàn thành sửa chữa

#### Báo cáo
- **reports.php**: Báo cáo phòng, tài chính, sinh viên

### 5.4. Trang Sinh viên (Student)
#### Dashboard Sinh viên
- Thông tin phòng đang ở
- Bạn cùng phòng
- Hóa đơn chưa thanh toán
- Vi phạm (nếu có)
- Thông báo mới

#### Đăng ký ở KTX
- **applications/create.php**: Tạo đơn đăng ký
- **applications/view.php**: Xem trạng thái đơn

#### Thông tin cá nhân
- **profile.php**: Xem/sửa thông tin cá nhân
- **roommates.php**: Xem bạn cùng phòng

#### Quản lý phòng
- **room.php**: Thông tin phòng
- **room_transfers/create.php**: Yêu cầu chuyển phòng
- **maintenance/create.php**: Yêu cầu sửa chữa

#### Thanh toán
- **invoices.php**: Danh sách hóa đơn
- **invoices/view.php**: Xem chi tiết hóa đơn
- **payments/create.php**: Tạo thanh toán
- **payments/history.php**: Lịch sử thanh toán
- **payments/debt.php**: Xem công nợ

#### Dịch vụ
- **room_services.php**: Xem dịch vụ của phòng đang ở (WiFi, máy giặt, tủ lạnh...)
- **Lưu ý**: Dịch vụ được Manager gán cho phòng, sinh viên không cần đăng ký riêng

#### Vi phạm & Thông báo
- **violations.php**: Xem vi phạm
- **notifications.php**: Xem thông báo

---

## 6. YÊU CẦU KỸ THUẬT (Đơn giản cho Local)

- **PHP**: Backend xử lý logic
- **MySQL**: Database
- **Bootstrap**: UI framework (hoặc CSS đơn giản)
- **Session**: Quản lý đăng nhập
- **Không cần**: Email, PDF, File Upload phức tạp

---

## 7. GHI CHÚ ĐƠN GIẢN HÓA

### Bỏ/Đơn giản hóa:
- ❌ **Bỏ role Bảo vệ** - Quản lý tự ghi nhận vi phạm
- ❌ **Bỏ nhật ký ra vào** - Không cần theo dõi chi tiết
- ❌ **Bỏ quản lý khách** - Đơn giản hóa
- ❌ **Bỏ upload ảnh** - Chỉ cần text input
- ❌ **Bỏ thanh toán online** - Chỉ ghi chú thủ công
- ❌ **Bỏ email** - Không cần gửi email
- ✅ **Giữ lại**: Quản lý phòng, sinh viên, hợp đồng, thanh toán cơ bản, vi phạm đơn giản

### Database tối thiểu cần:
1. Users
2. Buildings
3. Rooms
4. **Pricing** (Đơn giá - Manager quản lý) - **QUAN TRỌNG**
5. **Registration_Periods** (Đợt đăng ký - Manager mở đợt đăng ký) - **QUAN TRỌNG**
6. Students
7. Applications (ĐÃ CẬP NHẬT - thêm registration_period_id, status waiting_list)
8. Contracts
9. Room_Assignments
10. **Room_Transfer_Requests** (Yêu cầu chuyển phòng)
11. **Invoices** (Hóa đơn - Manager tạo, dịch vụ chia đều) - **QUAN TRỌNG**
12. **Payments** (Giao dịch thanh toán - Sinh viên nộp tiền)
13. Services (Dịch vụ - giá/phòng, chia đều cho số người)
14. **Room_Services** (Dịch vụ của phòng - Manager gán dịch vụ cho phòng) - **QUAN TRỌNG**
15. Service_Registrations (Giữ lại để tương lai - nếu cần)
16. Violations
17. Maintenance_Requests (Yêu cầu sửa chữa)
18. Notifications (Thông báo - tùy chọn)

