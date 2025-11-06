<?php
/**
 * File test kết nối database
 * Chạy file này để kiểm tra kết nối database có hoạt động không
 */

require_once __DIR__ . '/functions/db_connection.php';

echo "<h2>Test kết nối database</h2>";

try {
    $conn = getDbConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Kết nối database thành công!</p>";
        
        // Test query đơn giản
        $result = mysqli_query($conn, "SELECT DATABASE() as db_name");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p>Database hiện tại: <strong>" . htmlspecialchars($row['db_name']) . "</strong></p>";
        }
        
        // Kiểm tra bảng users có tồn tại không
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<p style='color: green;'>✓ Bảng 'users' đã tồn tại</p>";
            
            // Đếm số user
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                echo "<p>Số lượng user trong database: <strong>" . $row['count'] . "</strong></p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Bảng 'users' chưa tồn tại. Vui lòng chạy file SQL để tạo database.</p>";
        }
        
        mysqli_close($conn);
    } else {
        echo "<p style='color: red;'>✗ Kết nối database thất bại!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Quay lại trang đăng nhập</a></p>";

?>

