<?php
// api/categories.php - API quản lý danh mục
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

// Lấy danh sách danh mục
function getCategories() {
    global $conn;
    $user_id = getCurrentUserId();
    $type = $_GET['type'] ?? '';
    
    $sql = "SELECT * FROM categories WHERE user_id = ?";
    if ($type) {
        $sql .= " AND type = ?";
    }
    $sql .= " ORDER BY name";
    
    if ($type) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $type);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
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

// Thêm danh mục
function addCategory() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📝');
    $color = trim($_POST['color'] ?? '#999999');
    $type = $_POST['type'] ?? 'expense';
    
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng nhập tên danh mục']);
    }
    
    $stmt = $conn->prepare("INSERT INTO categories (user_id, name, icon, color, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $name, $icon, $color, $type);
    
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

// Cập nhật danh mục
function updateCategory() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $color = trim($_POST['color'] ?? '');
    
    if ($id <= 0 || empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    }
    
    $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssii", $name, $icon, $color, $id, $user_id);
    
    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cập nhật danh mục thất bại']);
    }
}

// Xóa danh mục
function deleteCategory() {
    global $conn;
    $user_id = getCurrentUserId();
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID không hợp lệ']);
    }
    
    // Kiểm tra xem có giao dịch nào sử dụng danh mục này không
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        jsonResponse(['success' => false, 'message' => 'Không thể xóa danh mục đang được sử dụng']);
    }
    
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        jsonResponse(['success' => true, 'message' => 'Xóa danh mục thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Xóa danh mục thất bại']);
    }
}
?>