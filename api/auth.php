<?php
// api/auth.php - API xác thực người dùng
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'check':
        checkAuth();
        break;
    case 'change_password':
        changePassword();
        break;
    case 'forgot_password':
        forgotPassword();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action không hợp lệ']);
}

// Đăng ký
function register() {
    global $conn;
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validate
    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    }
    
    if (!isValidEmail($email)) {
        jsonResponse(['success' => false, 'message' => 'Email không hợp lệ']);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    }
    
    // Kiểm tra username/email đã tồn tại
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Username hoặc email đã tồn tại']);
    }
    
    // Tạo tài khoản
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Tạo categories mặc định cho user mới
        $default_categories = [
            ['Ăn uống', '🍔', '#FF6B6B', 'expense'],
            ['Di chuyển', '🚗', '#4ECDC4', 'expense'],
            ['Mua sắm', '🛍️', '#45B7D1', 'expense'],
            ['Giải trí', '🎮', '#FFA07A', 'expense'],
            ['Y tế', '💊', '#98D8C8', 'expense'],
            ['Học tập', '📚', '#6C5CE7', 'expense'],
            ['Lương', '💰', '#00B894', 'income'],
            ['Thưởng', '🎁', '#FDCB6E', 'income']
        ];
        
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name, icon, color, type) VALUES (?, ?, ?, ?, ?)");
        foreach ($default_categories as $cat) {
            $stmt->bind_param("issss", $user_id, $cat[0], $cat[1], $cat[2], $cat[3]);
            $stmt->execute();
        }
        
        jsonResponse(['success' => true, 'message' => 'Đăng ký thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Đăng ký thất bại']);
    }
}

// Đăng nhập
function login() {
    global $conn;
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, password, full_name FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Tài khoản không tồn tại']);
    }
    
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        
        jsonResponse([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu không đúng']);
    }
}

// Đăng xuất
function logout() {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Đăng xuất thành công']);
}

// Kiểm tra đăng nhập
function checkAuth() {
    if (isLoggedIn()) {
        global $conn;
        $user_id = getCurrentUserId();
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, avatar, balance, language FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        jsonResponse([
            'success' => true,
            'logged_in' => true,
            'user' => $user
        ]);
    } else {
        jsonResponse([
            'success' => true,
            'logged_in' => false
        ]);
    }
}

// Đổi mật khẩu
function changePassword() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    }
    
    global $conn;
    $user_id = getCurrentUserId();
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($old_password) || empty($new_password)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    }
    
    if (strlen($new_password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
    }
    
    // Lấy mật khẩu hiện tại
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($old_password, $result['password'])) {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu cũ không đúng']);
    }
    
    // Cập nhật mật khẩu mới
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Đổi mật khẩu thất bại']);
    }
}

// Quên mật khẩu
function forgotPassword() {
    global $conn;
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng nhập email']);
    }
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Email không tồn tại']);
    }
    
    // Tạo token reset (demo - không gửi email thật)
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();
    
    jsonResponse([
        'success' => true,
        'message' => 'Link reset mật khẩu đã được gửi (demo)',
        'token' => $token
    ]);
}
?>