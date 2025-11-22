<?php
// api/transactions.php - Transaction management
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui long dang nhap']);
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
        jsonResponse(['success' => false, 'message' => 'Action khong hop le']);
}

function sanitizeDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date ? $date : date('Y-m-d');
}

function getTransactions() {
    global $conn;
    $user_id = getCurrentUserId();

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));
    $type = $_GET['type'] ?? '';

    if ($month < 1 || $month > 12) {
        jsonResponse(['success' => false, 'message' => 'Thang khong hop le']);
    }
    if ($year < 1970 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => 'Nam khong hop le']);
    }
    if ($type !== '' && !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Type khong hop le']);
    }

    $sql = "SELECT t.*, c.name as category_name, c.icon, c.color 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?";

    if ($type !== '') {
        $sql .= " AND t.type = ?";
    }

    $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

    if ($type !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $user_id, $month, $year, $type);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $user_id, $month, $year);
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

function addTransaction() {
    global $conn;
    $user_id = getCurrentUserId();

    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $transaction_date = sanitizeDate($_POST['transaction_date'] ?? date('Y-m-d'));

    if ($category_id <= 0 || $amount <= 0 || !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Du lieu khong hop le']);
    }

    // Ensure category belongs to current user
    $stmt = $conn->prepare('SELECT id, type FROM categories WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $category_id, $user_id);
    $stmt->execute();
    $cat = $stmt->get_result()->fetch_assoc();

    if (!$cat) {
        jsonResponse(['success' => false, 'message' => 'Danh muc khong hop le']);
    }

    if ($type !== '' && $type !== $cat['type']) {
        jsonResponse(['success' => false, 'message' => 'Loai giao dich khong khop voi danh muc']);
    }
    // Force transaction type to match category type to keep data consistent
    $type = $cat['type'];

    $stmt = $conn->prepare('INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iidsss', $user_id, $category_id, $amount, $type, $description, $transaction_date);

    if ($stmt->execute()) {
        $balance_change = ($type === 'income') ? $amount : -$amount;
        $updateBalance = $conn->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $updateBalance->bind_param('di', $balance_change, $user_id);
        $updateBalance->execute();

        jsonResponse([
            'success' => true,
            'message' => 'Them giao dich thanh cong',
            'transaction_id' => $conn->insert_id
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Them giao dich that bai']);
    }
}

function updateTransaction() {
    global $conn;
    $user_id = getCurrentUserId();

    $transaction_id = intval($_POST['id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $transaction_date = sanitizeDate($_POST['transaction_date'] ?? date('Y-m-d'));

    if ($transaction_id <= 0 || $category_id <= 0 || $amount <= 0 || !in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Du lieu khong hop le']);
    }

    // Current transaction
    $stmt = $conn->prepare('SELECT amount, type FROM transactions WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $transaction_id, $user_id);
    $stmt->execute();
    $old_transaction = $stmt->get_result()->fetch_assoc();

    if (!$old_transaction) {
        jsonResponse(['success' => false, 'message' => 'Giao dich khong ton tai']);
    }

    // Ensure category belongs to user and get its type
    $stmt = $conn->prepare('SELECT type FROM categories WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $category_id, $user_id);
    $stmt->execute();
    $cat = $stmt->get_result()->fetch_assoc();
    if (!$cat) {
        jsonResponse(['success' => false, 'message' => 'Danh muc khong hop le']);
    }
    if ($type !== '' && $type !== $cat['type']) {
        jsonResponse(['success' => false, 'message' => 'Loai giao dich khong khop voi danh muc']);
    }
    $type = $cat['type']; // enforce consistency

    $stmt = $conn->prepare('UPDATE transactions SET category_id = ?, amount = ?, type = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('idsssii', $category_id, $amount, $type, $description, $transaction_date, $transaction_id, $user_id);

    if ($stmt->execute()) {
        $old_effect = ($old_transaction['type'] === 'income') ? $old_transaction['amount'] : -$old_transaction['amount'];
        $new_effect = ($type === 'income') ? $amount : -$amount;
        $balance_change = -$old_effect + $new_effect;

        $updateBalance = $conn->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $updateBalance->bind_param('di', $balance_change, $user_id);
        $updateBalance->execute();

        jsonResponse(['success' => true, 'message' => 'Cap nhat giao dich thanh cong']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cap nhat giao dich that bai']);
    }
}

function deleteTransaction() {
    global $conn;
    $user_id = getCurrentUserId();
    $transaction_id = intval($_POST['id'] ?? 0);

    if ($transaction_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID khong hop le']);
    }

    $stmt = $conn->prepare('SELECT amount, type FROM transactions WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $transaction_id, $user_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();

    if (!$transaction) {
        jsonResponse(['success' => false, 'message' => 'Giao dich khong ton tai']);
    }

    $stmt = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $transaction_id, $user_id);

    if ($stmt->execute()) {
        $balance_change = ($transaction['type'] === 'income') ? -$transaction['amount'] : $transaction['amount'];
        $updateBalance = $conn->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $updateBalance->bind_param('di', $balance_change, $user_id);
        $updateBalance->execute();

        jsonResponse(['success' => true, 'message' => 'Xoa giao dich thanh cong']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Xoa giao dich that bai']);
    }
}

function searchTransactions() {
    global $conn;
    $user_id = getCurrentUserId();
    $keyword = trim($_GET['keyword'] ?? '');

    if ($keyword === '') {
        jsonResponse(['success' => false, 'message' => 'Vui long nhap tu khoa']);
    }

    $keyword_param = "%$keyword%";
    $stmt = $conn->prepare("SELECT t.*, c.name as category_name, c.icon, c.color 
                           FROM transactions t 
                           JOIN categories c ON t.category_id = c.id 
                           WHERE t.user_id = ? AND (t.description LIKE ? OR c.name LIKE ?)
                           ORDER BY t.transaction_date DESC LIMIT 50");
    $stmt->bind_param('iss', $user_id, $keyword_param, $keyword_param);
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

function exportTransactions() {
    global $conn;
    $user_id = getCurrentUserId();

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));

    if ($month < 1 || $month > 12 || $year < 1970 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => 'Thoi gian khong hop le']);
    }

    $stmt = $conn->prepare("SELECT t.*, c.name as category_name 
                           FROM transactions t 
                           JOIN categories c ON t.category_id = c.id 
                           WHERE t.user_id = ? AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
                           ORDER BY t.transaction_date DESC");
    $stmt->bind_param('iii', $user_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="chi_tieu_' . $month . '_' . $year . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    fputcsv($output, ['Ngay', 'Danh muc', 'Loai', 'So tien', 'Mo ta']);

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
