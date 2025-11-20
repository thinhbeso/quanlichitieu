<?php
// api/transactions.php - API quản lý giao dịch
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        getTransactions();
        break;
    case 'add':
        addTransaction();
        break;
    case 'update':
        updateTransaction();
        break;
    case 'delete':
        deleteTransaction();
        break;
    case 'search':
        searchTransactions();
        break;
    case 'export':
        exportTransactions();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action không hợp lệ']);
}

// Lấy danh sách giao dịch
function getTransactions() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    $type = $_GET['type'] ?? '';
    
    $sql = "SELECT t.*, c.name as category_name, c.icon, c.color 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?";
    
    if ($type) {
        $sql .= " AND t.type = ?";
    }
    
    $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";
    
    if ($type) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $user_id, $month, $year, $type);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $month, $year);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    jsonResponse([
        'success' => true,
        'transactions' => $transactions
    ]);
}

// Thêm giao dịch
function addTransaction() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    // Validate
    if ($category_id <= 0 || $amount <= 0 || !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    }
    
    // Kiểm tra category thuộc user
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Danh mục không hợp lệ']);
    }
    
    // Thêm giao dịch
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $user_id, $category_id, $amount, $type, $description, $transaction_date);
    
    if ($stmt->execute()) {
        // Cập nhật balance
        $balance_change = ($type === 'income') ? $amount : -$amount;
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $balance_change, $user_id);
        $stmt->execute();
        
        jsonResponse([
            'success' => true,
            'message' => 'Thêm giao dịch thành công',
            'transaction_id' => $conn->insert_id
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Thêm giao dịch thất bại']);
    }
}

// Cập nhật giao dịch
function updateTransaction() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $transaction_id = intval($_POST['id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    // Validate
    if ($transaction_id <= 0 || $category_id <= 0 || $amount <= 0 || !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    }
    
    // Lấy giao dịch cũ để cập nhật balance
    $stmt = $conn->prepare("SELECT amount, type FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $old_transaction = $stmt->get_result()->fetch_assoc();
    
    if (!$old_transaction) {
        jsonResponse(['success' => false, 'message' => 'Giao dịch không tồn tại']);
    }
    
    // Cập nhật giao dịch
    $stmt = $conn->prepare("UPDATE transactions SET category_id = ?, amount = ?, type = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("idsssii", $category_id, $amount, $type, $description, $transaction_date, $transaction_id, $user_id);
    
    if ($stmt->execute()) {
        // Hoàn trả balance cũ
        $old_change = ($old_transaction['type'] === 'income') ? -$old_transaction['amount'] : $old_transaction['amount'];
        // Thêm balance mới
        $new_change = ($type === 'income') ? $amount : -$amount;
        $balance_change = $old_change + $new_change;
        
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $balance_change, $user_id);
        $stmt->execute();
        
        jsonResponse(['success' => true, 'message' => 'Cập nhật giao dịch thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cập nhật giao dịch thất bại']);
    }
}

// Xóa giao dịch
function deleteTransaction() {
    global $conn;
    $user_id = getCurrentUserId();
    $transaction_id = intval($_POST['id'] ?? 0);
    
    if ($transaction_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID không hợp lệ']);
    }
    
    // Lấy thông tin giao dịch
    $stmt = $conn->prepare("SELECT amount, type FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    if (!$transaction) {
        jsonResponse(['success' => false, 'message' => 'Giao dịch không tồn tại']);
    }
    
    // Xóa giao dịch
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    
    if ($stmt->execute()) {
        // Hoàn trả balance
        $balance_change = ($transaction['type'] === 'income') ? -$transaction['amount'] : $transaction['amount'];
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $balance_change, $user_id);
        $stmt->execute();
        
        jsonResponse(['success' => true, 'message' => 'Xóa giao dịch thành công']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Xóa giao dịch thất bại']);
    }
}

// Tìm kiếm giao dịch
function searchTransactions() {
    global $conn;
    $user_id = getCurrentUserId();
    $keyword = trim($_GET['keyword'] ?? '');
    
    if (empty($keyword)) {
        jsonResponse(['success' => false, 'message' => 'Vui lòng nhập từ khóa']);
    }
    
    $keyword_param = "%$keyword%";
    $stmt = $conn->prepare("SELECT t.*, c.name as category_name, c.icon, c.color 
                           FROM transactions t 
                           JOIN categories c ON t.category_id = c.id 
                           WHERE t.user_id = ? AND (t.description LIKE ? OR c.name LIKE ?)
                           ORDER BY t.transaction_date DESC LIMIT 50");
    $stmt->bind_param("iss", $user_id, $keyword_param, $keyword_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    jsonResponse([
        'success' => true,
        'transactions' => $transactions,
        'count' => count($transactions)
    ]);
}

// Xuất dữ liệu (CSV)
function exportTransactions() {
    global $conn;
    $user_id = getCurrentUserId();
    
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    $stmt = $conn->prepare("SELECT t.*, c.name as category_name 
                           FROM transactions t 
                           JOIN categories c ON t.category_id = c.id 
                           WHERE t.user_id = ? AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
                           ORDER BY t.transaction_date DESC");
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="chi_tieu_' . $month . '_' . $year . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    fputcsv($output, ['Ngày', 'Danh mục', 'Loại', 'Số tiền', 'Mô tả']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['transaction_date'],
            $row['category_name'],
            $row['type'] === 'income' ? 'Thu' : 'Chi',
            $row['amount'],
            $row['description']
        ]);
    }
    
    fclose($output);
    exit;
}
?>