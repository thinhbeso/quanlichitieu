<?php
// api/profile.php - API quản lý profile và avatar
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update_profile':
        updateProfile();
        break;
    case 'upload_avatar':
        uploadAvatar();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action không hợp lệ']);
}

// Cập nhật thông tin cá nhân
function updateProfile() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng nhập họ tên']);
    }
    
    // Validate phone number (optional)
    if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $full_name, $phone, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        jsonResponse(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cập nhật thông tin thất bại']);
    }
}

// Upload avatar
function uploadAvatar() {
    global $conn;
    $user_id = getCurrentUserId();
    
    // Kiểm tra file upload
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng chọn file ảnh']);
    }
    
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        jsonResponse(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)']);
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        jsonResponse(['success' => false, 'message' => 'Kích thước ảnh không được vượt quá 2MB']);
    }
    
    // Tạo thư mục uploads nếu chưa có
    $upload_dir = '../uploads/avatars/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Lấy avatar cũ để xóa
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $old_avatar = $result['avatar'] ?? '';
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Xóa avatar cũ nếu có
        if (!empty($old_avatar) && file_exists('../' . $old_avatar)) {
            unlink('../' . $old_avatar);
        }
        
        // Update database
        $avatar_path = 'uploads/avatars/' . $filename;
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $avatar_path, $user_id);
        
        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Cập nhật ảnh đại diện thành công',
                'avatar_url' => $avatar_path
            ]);
        } else {
            // Xóa file đã upload nếu update DB thất bại
            unlink($filepath);
            jsonResponse(['success' => false, 'message' => 'Cập nhật database thất bại']);
        }
    } else {
        jsonResponse(['success' => false, 'message' => 'Upload file thất bại']);
    }
}
?>