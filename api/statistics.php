<?php
// api/statistics.php - Reporting
require_once '../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Vui long dang nhap']);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'summary':
        getSummary();
        break;
    case 'by_category':
        getByCategory();
        break;
    case 'by_period':
        getByPeriod();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Action khong hop le']);
}

function getSummary() {
    global $conn;
    $user_id = getCurrentUserId();

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));

    if ($month < 1 || $month > 12) {
        jsonResponse(['success' => false, 'message' => 'Thang khong hop le']);
    }
    if ($year < 1970 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => 'Nam khong hop le']);
    }

    $stmt = $conn->prepare("SELECT 
                            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                            COUNT(*) as total_transactions
                           FROM transactions 
                           WHERE user_id = ? AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
    $stmt->bind_param('iii', $user_id, $month, $year);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare('SELECT balance FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    jsonResponse([
        'success' => true,
        'data' => [
            'total_income' => floatval($summary['total_income'] ?? 0),
            'total_expense' => floatval($summary['total_expense'] ?? 0),
            'balance' => floatval($user['balance'] ?? 0),
            'total_transactions' => intval($summary['total_transactions'] ?? 0),
            'net' => floatval($summary['total_income'] ?? 0) - floatval($summary['total_expense'] ?? 0)
        ]
    ]);
}

function getByCategory() {
    global $conn;
    $user_id = getCurrentUserId();

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));
    $type = $_GET['type'] ?? 'expense';

    if (!in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Type khong hop le']);
    }
    if ($month < 1 || $month > 12 || $year < 1970 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => 'Thoi gian khong hop le']);
    }

    $stmt = $conn->prepare("SELECT c.name, c.icon, c.color, 
                            SUM(t.amount) as total,
                            COUNT(t.id) as count
                           FROM transactions t
                           JOIN categories c ON t.category_id = c.id
                           WHERE t.user_id = ? AND t.type = ? 
                           AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
                           GROUP BY c.id
                           ORDER BY total DESC");
    $stmt->bind_param('isii', $user_id, $type, $month, $year);
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

function getByPeriod() {
    global $conn;
    $user_id = getCurrentUserId();

    $year = intval($_GET['year'] ?? date('Y'));

    if ($year < 1970 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => 'Nam khong hop le']);
    }

    $stmt = $conn->prepare("SELECT 
                            MONTH(transaction_date) as month,
                            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                           FROM transactions 
                           WHERE user_id = ? AND YEAR(transaction_date) = ?
                           GROUP BY MONTH(transaction_date)
                           ORDER BY month");
    $stmt->bind_param('ii', $user_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    for ($m = 1; $m <= 12; $m++) {
        $data[$m] = [
            'month' => $m,
            'income' => 0.0,
            'expense' => 0.0
        ];
    }

    while ($row = $result->fetch_assoc()) {
        $month = intval($row['month']);
        $data[$month] = [
            'month' => $month,
            'income' => floatval($row['income']),
            'expense' => floatval($row['expense'])
        ];
    }

    jsonResponse([
        'success' => true,
        'data' => array_values($data)
    ]);
}
?>
