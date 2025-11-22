<?php
// config.php - Database config + session bootstrap

// Slightly harden session cookies
ini_set('session.cookie_httponly', '1');
session_start();

// Database configuration (Docker-first, fallback to local defaults)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);
$dbName = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'expenditure_management';
$dbUser = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '';

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Connect to database
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Ket noi database that bai: ' . $conn->connect_error
        ], JSON_UNESCAPED_UNICODE));
    }
    $conn->set_charset('utf8mb4');
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Loi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}

// Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatMoney($amount) {
    return number_format($amount, 0, ',', '.');
}
?>
