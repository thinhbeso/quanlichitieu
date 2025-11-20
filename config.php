<?php
// config.php - Cấu hình database và session

session_start();

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expenditure_management');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Kết nối database thất bại: ' . $conn->connect_error
        ]));
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]));
}

// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm lấy user ID hiện tại
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Hàm trả về JSON response
function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Hàm validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hàm format số tiền
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.');
}
?>