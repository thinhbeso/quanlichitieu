<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.html');
    exit;
}

$user_id = getCurrentUserId();
$stmt = $conn->prepare("SELECT username, full_name, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chi tiêu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-wallet"></i>
            <h2>SpendSmart</h2>
        </div>
        
        <div class="user-info">
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h3>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="#" class="menu-item active" data-page="dashboard">
                <i class="fas fa-home"></i>
                <span>Trang chủ</span>
            </a>
            <a href="#" class="menu-item" data-page="transactions">
                <i class="fas fa-exchange-alt"></i>
                <span>Giao dịch</span>
            </a>
            <a href="#" class="menu-item" data-page="statistics">
                <i class="fas fa-chart-pie"></i>
                <span>Thống kê</span>
            </a>
            <a href="#" class="menu-item" data-page="categories">
                <i class="fas fa-tags"></i>
                <span>Danh mục</span>
            </a>
            <a href="#" class="menu-item" data-page="profile">
                <i class="fas fa-user-cog"></i>
                <span>Cài đặt</span>
            </a>
            <a href="#" class="menu-item" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="pageTitle">Trang chủ</h1>
            </div>
            <div class="topbar-right">
                <div class="date-filter">
                    <select id="monthFilter">
                        <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($m == date('n')) ? 'selected' : ''; ?>>
                            Tháng <?php echo $m; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <select id="yearFilter">
                        <?php for($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="content-body">
            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page active">
                <div class="stats-grid">
                    <div class="stat-card balance">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-details">
                            <p>Số dư</p>
                            <h3 id="totalBalance"><?php echo formatMoney($user['balance']); ?> đ</h3>
                        </div>
                    </div>
                    
                    <div class="stat-card income">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-details">
                            <p>Thu nhập</p>
                            <h3 id="totalIncome">0 đ</h3>
                        </div>
                    </div>
                    
                    <div class="stat-card expense">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-details">
                            <p>Chi tiêu</p>
                            <h3 id="totalExpense">0 đ</h3>
                        </div>
                    </div>
                    
                    <div class="stat-card net">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-details">
                            <p>Chênh lệch</p>
                            <h3 id="netAmount">0 đ</h3>
                        </div>
                    </div>
                </div>
                
                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3>Giao dịch gần đây</h3>
                            <button class="btn btn-primary btn-sm" id="addTransactionBtn">
                                <i class="fas fa-plus"></i> Thêm giao dịch
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="recentTransactions"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Other pages will be loaded here -->
            <div id="transactions-page" class="page"></div>
            <div id="statistics-page" class="page"></div>
            <div id="categories-page" class="page"></div>
            <div id="profile-page" class="page"></div>
        </div>
    </div>
    
    <!-- Modal thêm/sửa giao dịch -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Thêm giao dịch</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" name="id" id="transactionId">
                    
                    <div class="form-group">
                        <label>Loại giao dịch</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="type" value="expense" checked>
                                <span class="radio-custom expense">Chi tiêu</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="type" value="income">
                                <span class="radio-custom income">Thu nhập</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Danh mục</label>
                        <select name="category_id" id="categorySelect" required></select>
                    </div>
                    
                    <div class="form-group">
                        <label>Số tiền</label>
                        <input type="number" name="amount" id="amount" placeholder="Nhập số tiền" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Ngày giao dịch</label>
                        <input type="date" name="transaction_date" id="transactionDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" id="description" rows="3" placeholder="Nhập mô tả (tùy chọn)"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>