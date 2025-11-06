<?php
/**
 * Helper functions - Các hàm tiện ích
 */

/**
 * Hàm khởi động session an toàn
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Hàm set thông báo thành công
 * @param string $message
 */
function setSuccessMessage($message) {
    startSession();
    $_SESSION['success'] = $message;
}

/**
 * Hàm set thông báo lỗi
 * @param string $message
 */
function setErrorMessage($message) {
    startSession();
    $_SESSION['error'] = $message;
}

/**
 * Hàm lấy và xóa thông báo thành công
 * @return string|null
 */
function getSuccessMessage() {
    startSession();
    if (isset($_SESSION['success'])) {
        $message = $_SESSION['success'];
        unset($_SESSION['success']);
        return $message;
    }
    return null;
}

/**
 * Hàm lấy và xóa thông báo lỗi
 * @return string|null
 */
function getErrorMessage() {
    startSession();
    if (isset($_SESSION['error'])) {
        $message = $_SESSION['error'];
        unset($_SESSION['error']);
        return $message;
    }
    return null;
}

/**
 * Hàm chuyển hướng
 * @param string $url
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Hàm format số tiền VNĐ
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

/**
 * Hàm format ngày tháng
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Hàm format ngày giờ
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Hàm escape HTML để tránh XSS
 * @param string $string
 * @return string
 */
function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Hàm kiểm tra request method
 * @param string $method
 * @return bool
 */
function isMethod($method) {
    return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
}

/**
 * Hàm lấy action từ GET/POST
 * @return string|null
 */
function getAction() {
    return $_GET['action'] ?? $_POST['action'] ?? null;
}

?>

