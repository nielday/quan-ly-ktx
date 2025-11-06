# HƯỚNG DẪN ĐƯỜNG DẪN TƯƠNG ĐỐI

## QUY TẮC QUAN TRỌNG - GHI NHỚ!

### Cấu trúc thư mục:
```
quanlyktx/
├── functions/          (root level)
├── handle/            (root level)
├── views/
│   ├── manager/
│   │   ├── dashboard.php          (2 cấp: ../../)
│   │   ├── buildings.php          (2 cấp: ../../)
│   │   └── buildings/
│   │       ├── create_building.php (3 cấp: ../../../)
│   │       └── edit_building.php   (3 cấp: ../../../)
│   ├── admin/
│   └── student/
```

### Quy tắc đường dẫn:

1. **File trong `views/manager/`** (ví dụ: `buildings.php`, `dashboard.php`):
   - Đến `functions/`: `../../functions/`
   - Đến `handle/`: `../../handle/`
   - Đến `index.php`: `../../index.php`

2. **File trong `views/manager/buildings/`** (ví dụ: `create_building.php`):
   - Đến `functions/`: `../../../functions/` (3 cấp lên!)
   - Đến `handle/`: `../../../../handle/` (4 cấp lên!)
   - Đến `views/manager/buildings.php`: `../buildings.php` (1 cấp lên)

3. **File trong `views/admin/` hoặc `views/student/`**:
   - Tương tự như `views/manager/`: dùng `../../`

### Sử dụng `__DIR__` trong PHP:

- `__DIR__` trỏ đến thư mục chứa file hiện tại
- Luôn dùng `__DIR__` cho `require_once` để tránh lỗi
- Ví dụ: `require_once __DIR__ . '/../../../functions/auth.php';`

### Ví dụ cụ thể:

**File: `views/manager/buildings/create_building.php`**
```php
// ✅ ĐÚNG - 3 cấp lên để về root
require_once __DIR__ . '/../../../functions/auth.php';
require_once __DIR__ . '/../../../functions/helpers.php';

// ✅ ĐÚNG - 4 cấp lên để đến handle/
<form action="../../../../handle/buildings_process.php">

// ✅ ĐÚNG - 1 cấp lên để về manager/
<a href="../buildings.php">
```

**File: `views/manager/buildings.php`**
```php
// ✅ ĐÚNG - 2 cấp lên để về root
require_once __DIR__ . '/../../functions/auth.php';

// ✅ ĐÚNG - 2 cấp lên để đến handle/
<form action="../../handle/buildings_process.php">
```

### LƯU Ý QUAN TRỌNG:

⚠️ **KHÔNG BAO GIỜ** dùng đường dẫn tương đối sai số cấp!
- File trong thư mục con cần đi lên **NHIỀU CẤP HƠN**
- Luôn đếm số cấp thư mục từ file hiện tại đến thư mục đích
- Test ngay sau khi tạo file mới để tránh lỗi

