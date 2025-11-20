// Web state
const App = {
    currentMonth: new Date().getMonth() + 1,
    currentYear: new Date().getFullYear(),
    categories: [],
    transactions: [],
    editingId: null
};

$(document).ready(function() {
    initApp();
});

// Khởi tạo web
function initApp() {
    // Load categories
    loadCategories();
    
    // Load dashboard
    loadDashboard();
    
    // Event listeners
    setupEventListeners();
    
    // Set default date
    $('#transactionDate').val(new Date().toISOString().split('T')[0]);
}

// Setup event listeners
function setupEventListeners() {
    // Menu navigation
    $('.menu-item').click(function(e) {
        if ($(this).attr('id') !== 'logoutBtn') {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                switchPage(page);
            }
        }
    });
    
    // Logout
    $('#logoutBtn').click(function(e) {
        e.preventDefault();
        if (confirm('Bạn có chắc muốn đăng xuất?')) {
            $.post('api/auth.php', { action: 'logout' }, function() {
                window.location.href = 'login.html';
            });
        }
    });
    
    // Date filter
    $('#monthFilter, #yearFilter').change(function() {
        App.currentMonth = parseInt($('#monthFilter').val());
        App.currentYear = parseInt($('#yearFilter').val());
        loadDashboard();
    });
    
    // Add transaction button
    $('#addTransactionBtn').click(function() {
        openTransactionModal();
    });
    
    // Transaction form
    $('#transactionForm').submit(function(e) {
        e.preventDefault();
        saveTransaction();
    });
    
    // Type change -> load matching categories
    $('input[name="type"]').change(function() {
        loadCategoriesByType($(this).val());
    });
    
    // Close modal
    $('.close-modal').click(function() {
        closeModal();
    });
    
    // Click outside modal to close
    $('.modal').click(function(e) {
        if ($(e.target).is('.modal')) {
            closeModal();
        }
    });
    
    // Menu toggle (mobile)
    $('.menu-toggle').click(function() {
        $('.sidebar').toggleClass('active');
    });
}

// Switch page
function switchPage(page) {
    $('.menu-item').removeClass('active');
    $(`.menu-item[data-page="${page}"]`).addClass('active');
    
    $('.page').removeClass('active');
    $(`#${page}-page`).addClass('active');
    
    // Update page title
    const titles = {
        'dashboard': 'Trang chủ',
        'transactions': 'Giao dịch',
        'statistics': 'Thống kê',
        'categories': 'Danh mục',
        'profile': 'Cài đặt'
    };
    $('#pageTitle').text(titles[page] || 'Trang chủ');
    
    // Load page content
    switch(page) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'transactions':
            loadTransactionsPage();
            break;
        case 'statistics':
            loadStatisticsPage();
            break;
        case 'categories':
            loadCategoriesPage();
            break;
        case 'profile':
            loadProfilePage();
            break;
    }
}

// Load categories
function loadCategories() {
    $.get('api/categories.php', { action: 'list' }, function(response) {
        if (response.success) {
            App.categories = response.categories;
            loadCategoriesByType('expense'); // Default
        }
    });
}

// Load categories by type
function loadCategoriesByType(type) {
    const filtered = App.categories.filter(c => c.type === type);
    let html = '<option value="">-- Chọn danh mục --</option>';
    
    filtered.forEach(cat => {
        html += `<option value="${cat.id}">${cat.icon} ${cat.name}</option>`;
    });
    
    $('#categorySelect').html(html);
}

// Load dashboard
function loadDashboard() {
    // Load summary
    $.get('api/statistics.php', {
        action: 'summary',
        month: App.currentMonth,
        year: App.currentYear
    }, function(response) {
        if (response.success) {
            const data = response.data;
            $('#totalIncome').text(formatMoney(data.total_income) + ' đ');
            $('#totalExpense').text(formatMoney(data.total_expense) + ' đ');
            $('#netAmount').text(formatMoney(data.net) + ' đ');
            $('#totalBalance').text(formatMoney(data.balance) + ' đ');
        }
    });
    
    // Load recent transactions
    loadRecentTransactions();
}

// Load recent transactions
function loadRecentTransactions() {
    $.get('api/transactions.php', {
        action: 'list',
        month: App.currentMonth,
        year: App.currentYear
    }, function(response) {
        if (response.success) {
            App.transactions = response.transactions;
            displayTransactions(response.transactions.slice(0, 10), '#recentTransactions');
        }
    });
}

// Display transactions
function displayTransactions(transactions, container) {
    if (!transactions || transactions.length === 0) {
        $(container).html(`
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Chưa có giao dịch nào</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    transactions.forEach(trans => {
        const amountClass = trans.type === 'income' ? 'income' : 'expense';
        const amountPrefix = trans.type === 'income' ? '+' : '-';
        
        html += `
            <div class="transaction-item">
                <div class="transaction-icon" style="background: ${trans.color}20; color: ${trans.color}">
                    ${trans.icon}
                </div>
                <div class="transaction-info">
                    <h4>${trans.category_name}</h4>
                    <p>${trans.description || 'Không có mô tả'} • ${formatDate(trans.transaction_date)}</p>
                </div>
                <div class="transaction-amount ${amountClass}">
                    <h4>${amountPrefix}${formatMoney(trans.amount)} đ</h4>
                </div>
                <div class="transaction-actions">
                    <button class="btn-icon" onclick="editTransaction(${trans.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="deleteTransaction(${trans.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    $(container).html(html);
}

// Open transaction modal
function openTransactionModal(id = null) {
    App.editingId = id;
    
    if (id) {
        // Edit mode
        const trans = App.transactions.find(t => t.id == id);
        if (trans) {
            $('#modalTitle').text('Sửa giao dịch');
            $('#transactionId').val(trans.id);
            $(`input[name="type"][value="${trans.type}"]`).prop('checked', true).trigger('change');
            $('#categorySelect').val(trans.category_id);
            $('#amount').val(trans.amount);
            $('#transactionDate').val(trans.transaction_date);
            $('#description').val(trans.description);
        }
    } else {
        // Add mode
        $('#modalTitle').text('Thêm giao dịch');
        $('#transactionForm')[0].reset();
        $('#transactionDate').val(new Date().toISOString().split('T')[0]);
        $('input[name="type"][value="expense"]').prop('checked', true).trigger('change');
    }
    
    $('#transactionModal').addClass('active');
}

// Close modal
function closeModal() {
    $('#transactionModal').removeClass('active');
    $('#transactionForm')[0].reset();
    App.editingId = null;
}

// Save transaction
function saveTransaction() {
    const formData = $('#transactionForm').serialize();
    const action = App.editingId ? 'update' : 'add';
    
    $.ajax({
        url: 'api/transactions.php',
        type: 'POST',
        data: formData + '&action=' + action,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                closeModal();
                loadDashboard();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Có lỗi xảy ra!');
        }
    });
}

// Edit transaction
window.editTransaction = function(id) {
    openTransactionModal(id);
}

// Delete transaction
window.deleteTransaction = function(id) {
    if (!confirm('Bạn có chắc muốn xóa giao dịch này?')) {
        return;
    }
    
    $.ajax({
        url: 'api/transactions.php',
        type: 'POST',
        data: { action: 'delete', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadDashboard();
            } else {
                alert(response.message);
            }
        }
    });
}

// Load transactions page
function loadTransactionsPage() {
    $('#transactions-page').html(`
        <div class="card">
            <div class="card-header">
                <h3>Tất cả giao dịch</h3>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="searchInput" placeholder="Tìm kiếm..." style="padding: 8px 15px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <button class="btn btn-primary btn-sm" onclick="openTransactionModal()">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="allTransactions"></div>
            </div>
        </div>
    `);
    
    displayTransactions(App.transactions, '#allTransactions');
    
    // Search
    $('#searchInput').on('input', function() {
        const keyword = $(this).val();
        if (keyword.length >= 2) {
            $.get('api/transactions.php', {
                action: 'search',
                keyword: keyword
            }, function(response) {
                if (response.success) {
                    displayTransactions(response.transactions, '#allTransactions');
                }
            });
        } else if (keyword.length === 0) {
            displayTransactions(App.transactions, '#allTransactions');
        }
    });
}

// Load statistics page
function loadStatisticsPage() {
    $('#statistics-page').html(`
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Chi tiêu theo danh mục</h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Xu hướng theo tháng</h3>
                </div>
                <div class="card-body">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    `);
    
    loadCategoryChart();
    loadTrendChart();
}

// Load category chart
function loadCategoryChart() {
    $.get('api/statistics.php', {
        action: 'by_category',
        month: App.currentMonth,
        year: App.currentYear,
        type: 'expense'
    }, function(response) {
        if (response.success && response.categories.length > 0) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: response.categories.map(c => c.name),
                    datasets: [{
                        data: response.categories.map(c => c.total),
                        backgroundColor: response.categories.map(c => c.color)
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
}

// Load trend chart
function loadTrendChart() {
    $.get('api/statistics.php', {
        action: 'by_period',
        year: App.currentYear
    }, function(response) {
        if (response.success) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: response.data.map(d => `Tháng ${d.month}`),
                    datasets: [
                        {
                            label: 'Thu nhập',
                            data: response.data.map(d => d.income),
                            borderColor: '#00B894',
                            backgroundColor: 'rgba(0, 184, 148, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Chi tiêu',
                            data: response.data.map(d => d.expense),
                            borderColor: '#FF6B6B',
                            backgroundColor: 'rgba(255, 107, 107, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
}

// Load categories page
function loadCategoriesPage() {
    $('#categories-page').html(`
        <div class="card">
            <div class="card-header">
                <h3>Danh mục</h3>
                <button class="btn btn-primary btn-sm" onclick="alert('Tính năng đang phát triển')">
                    <i class="fas fa-plus"></i> Thêm danh mục
                </button>
            </div>
            <div class="card-body">
                <div id="categoriesList"></div>
            </div>
        </div>
    `);
    
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
    App.categories.forEach(cat => {
        html += `
            <div style="padding: 20px; border: 2px solid ${cat.color}; border-radius: 10px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 10px;">${cat.icon}</div>
                <h4>${cat.name}</h4>
                <p style="color: #888; font-size: 13px; margin-top: 5px;">
                    ${cat.type === 'income' ? 'Thu nhập' : 'Chi tiêu'}
                </p>
            </div>
        `;
    });
    html += '</div>';
    
    $('#categoriesList').html(html);
}

// Load profile page
function loadProfilePage() {
    $('#profile-page').html(`
        <div class="card">
            <div class="card-header">
                <h3>Đổi mật khẩu</h3>
            </div>
            <div class="card-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label>Mật khẩu cũ</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    `);
    
    $('#changePasswordForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=change_password';
        
        $.post('api/auth.php', formData, function(response) {
            alert(response.message);
            if (response.success) {
                $('#changePasswordForm')[0].reset();
            }
        }, 'json');
    });
}

// Utility functions
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Hôm nay';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Hôm qua';
    } else {
        return date.toLocaleDateString('vi-VN');
    }
}