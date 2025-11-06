<?php
/**
 * Hàm kết nối database
 * @return mysqli Kết nối database
 */
function getDbConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "Phong8ngon@"; // Mặc định XAMPP không có password
    $dbname = "quanlyktx";
    $port = 3305; // Port mặc định của MySQL

    // Tạo kết nối
    $conn = mysqli_connect($servername, $username, $password, $dbname, $port);

    // Kiểm tra kết nối
    if (!$conn) {
        die("Kết nối database thất bại: " . mysqli_connect_error());
    }
    
    // Thiết lập charset cho kết nối (quan trọng để hiển thị tiếng Việt đúng)
    mysqli_set_charset($conn, "utf8mb4");
    
    return $conn;
}

?>

