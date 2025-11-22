<?php
// api/categories.php - Quản lý danh mục
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        getCategories();
        break;
    case 'add':
        addCategory();
        break;
    case 'update':
        updateCategory();
        break;
    case 'delete':
        deleteCategory();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action không hợp lệ']);
}

function getCategories() {
    global $conn;
    $user_id = getCurrentUserId();
    $type = $_GET['type'] ?? '';

    if ($type !== '' && !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Loại không hợp lệ']);
    }

    if ($type !== '') {
        $stmt = $conn->prepare('SELECT * FROM categories WHERE user_id = ? AND type = ? ORDER BY name');
        $stmt->bind_param('is', $user_id, $type);
    } else {
        $stmt = $conn->prepare('SELECT * FROM categories WHERE user_id = ? ORDER BY name');
        $stmt->bind_param('i', $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    jsonResponse([
        'success' => true,
        'categories' => $categories
    ]);
}

function addCategory() {
    global $conn;
    $user_id = getCurrentUserId();

    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '⭐');
    $color = trim($_POST['color'] ?? '#999999');
    $type = $_POST['type'] ?? 'expense';

    if ($name === '') {
        jsonResponse(['success' => false, 'message' => 'Tên danh mục không được để trống']);
    }
    if (!in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Loại không hợp lệ']);
    }

    $stmt = $conn->prepare('INSERT INTO categories (user_id, name, icon, color, type) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $user_id, $name, $icon, $color, $type);

    if ($stmt->execute()) {
        jsonResponse([
            'success' => true,
            'message' => 'Thêm danh mục thành công',
            'category_id' => $conn->insert_id
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Thêm danh mục thất bại']);
    }
}

function updateCategory() {
    global $conn;
    $user_id = getCurrentUserId();

    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '⭐');
    $color = trim($_POST['color'] ?? '#999999');
    $type = $_POST['type'] ?? 'expense';

    if ($id <= 0 || $name === '') {
        jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    }
    if (!in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Loại không hợp lệ']);
    }

    $check = $conn->prepare('SELECT id FROM categories WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Danh mục không tồn tại']);
    }

    $stmt = $conn->prepare('UPDATE categories SET name = ?, icon = ?, color = ?, type = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ssssii', $name, $icon, $color, $type, $id, $user_id);

    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cập nhật danh mục thất bại']);
    }
}

function deleteCategory() {
    global $conn;
    $user_id = getCurrentUserId();
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID không hợp lệ']);
    }

    $check = $conn->prepare('SELECT id FROM categories WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Danh mục không tồn tại']);
    }

    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM transactions WHERE category_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        jsonResponse(['success' => false, 'message' => 'Không thể xóa danh mục đang được sử dụng']);
    }

    $stmt = $conn->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);

    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Xóa danh mục thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Xóa danh mục thất bại']);
    }
}
?>
