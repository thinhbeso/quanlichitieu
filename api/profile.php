<?php
// api/profile.php - Profile and avatar management
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui long dang nhap']);
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
        jsonResponse(['success' => false, 'message' => 'Action khong hop le']);
}

function updateProfile() {
    global $conn;
    $user_id = getCurrentUserId();

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($full_name === '') {
        jsonResponse(['success' => false, 'message' => 'Ho ten khong duoc de trong']);
    }

    if ($phone !== '' && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'So dien thoai khong hop le']);
    }

    $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
    $stmt->bind_param('ssi', $full_name, $phone, $user_id);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        jsonResponse(['success' => true, 'message' => 'Cap nhat thong tin thanh cong']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cap nhat thong tin that bai']);
    }
}

function uploadAvatar() {
    global $conn;
    $user_id = getCurrentUserId();

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'Vui long chon file anh']);
    }

    $file = $_FILES['avatar'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if ($file['size'] > $max_size) {
        jsonResponse(['success' => false, 'message' => 'Anh khong duoc vuot qua 2MB']);
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        jsonResponse(['success' => false, 'message' => 'File khong phai anh hop le']);
    }

    $mime = $imageInfo['mime'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes, true)) {
        jsonResponse(['success' => false, 'message' => 'Chi ho tro JPG, PNG, GIF, WEBP']);
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $extension = $extensionMap[$mime];

    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Remove old avatar path if any
    $stmt = $conn->prepare('SELECT avatar FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $old_avatar = $result['avatar'] ?? '';

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        if (!empty($old_avatar) && file_exists('../' . $old_avatar)) {
            unlink('../' . $old_avatar);
        }

        $avatar_path = 'uploads/avatars/' . $filename;
        $stmt = $conn->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        $stmt->bind_param('si', $avatar_path, $user_id);

        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Cap nhat anh dai dien thanh cong',
                'avatar_url' => $avatar_path
            ]);
        } else {
            unlink($filepath);
            jsonResponse(['success' => false, 'message' => 'Cap nhat database that bai']);
        }
    } else {
        jsonResponse(['success' => false, 'message' => 'Upload file that bai']);
    }
}
?>
