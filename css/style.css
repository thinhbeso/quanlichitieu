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
// Load categories page với đầy đủ tính năng
function loadCategoriesPage() {
    $('#categories-page').html(`
        <div class="card">
            <div class="card-header">
                <h3>Quản lý Danh mục</h3>
                <button class="btn btn-primary btn-sm" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Thêm danh mục
                </button>
            </div>
            <div class="card-body">
                <div class="tabs" style="border-bottom: 2px solid #e0e0e0; margin-bottom: 20px;">
                    <div class="tab active" data-type="expense" style="flex: 1; padding: 15px; text-align: center; cursor: pointer; border-bottom: 3px solid #FF6B6B; color: #FF6B6B; font-weight: 600;">
                        Chi tiêu
                    </div>
                    <div class="tab" data-type="income" style="flex: 1; padding: 15px; text-align: center; cursor: pointer; border-bottom: 3px solid transparent; color: #999; font-weight: 600;">
                        Thu nhập
                    </div>
                </div>
                <div id="categoriesGrid"></div>
            </div>
        </div>
    `);
    
    // Tab switching
    $('#categories-page .tab').click(function() {
        const type = $(this).data('type');
        $('#categories-page .tab').removeClass('active').css({
            'border-bottom-color': 'transparent',
            'color': '#999'
        });
        $(this).addClass('active').css({
            'border-bottom-color': type === 'expense' ? '#FF6B6B' : '#00B894',
            'color': type === 'expense' ? '#FF6B6B' : '#00B894'
        });
        loadCategoriesGrid(type);
    });
    
    loadCategoriesGrid('expense');
}

// Load categories grid
function loadCategoriesGrid(type) {
    const filtered = App.categories.filter(c => c.type === type);
    
    if (filtered.length === 0) {
        $('#categoriesGrid').html(`
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Chưa có danh mục ${type === 'expense' ? 'chi tiêu' : 'thu nhập'} nào</p>
            </div>
        `);
        return;
    }
    
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
    
    filtered.forEach(cat => {
        html += `
            <div class="category-card" style="background: ${cat.color}15; padding: 20px; border-radius: 10px; border: 2px solid ${cat.color}; position: relative; transition: transform 0.3s;">
                <div style="text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">${cat.icon}</div>
                    <h4 style="margin-bottom: 5px; font-size: 16px;">${cat.name}</h4>
                    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
                        ${cat.type === 'income' ? 'Thu nhập' : 'Chi tiêu'}
                    </p>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button class="btn-icon" onclick="editCategory(${cat.id})" style="background: white;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="deleteCategory(${cat.id})" style="background: white;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $('#categoriesGrid').html(html);
    
    // Hover effect
    $('.category-card').hover(
        function() { $(this).css('transform', 'translateY(-5px)'); },
        function() { $(this).css('transform', 'translateY(0)'); }
    );
}

// Open category modal
window.openCategoryModal = function(id = null) {
    const isEdit = id !== null;
    const cat = isEdit ? App.categories.find(c => c.id == id) : null;
    
    const modalHtml = `
        <div class="modal active" id="categoryModal">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>${isEdit ? 'Sửa danh mục' : 'Thêm danh mục'}</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <input type="hidden" name="id" value="${id || ''}">
                        
                        <div class="form-group">
                            <label>Loại danh mục</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="type" value="expense" ${!isEdit || cat.type === 'expense' ? 'checked' : ''}>
                                    <span class="radio-custom expense">Chi tiêu</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="type" value="income" ${isEdit && cat.type === 'income' ? 'checked' : ''}>
                                    <span class="radio-custom income">Thu nhập</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Tên danh mục</label>
                            <input type="text" name="name" value="${cat ? cat.name : ''}" placeholder="VD: Ăn uống, Lương..." required>
                        </div>
                        
                        <div class="form-group">
                            <label>Icon (Emoji)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="iconInput" name="icon" value="${cat ? cat.icon : '📝'}" style="flex: 1;" placeholder="📝" required>
                                <div id="iconPreview" style="font-size: 48px; padding: 10px; background: #f5f5f5; border-radius: 8px; min-width: 70px; text-align: center;">
                                    ${cat ? cat.icon : '📝'}
                                </div>
                            </div>
                            <small style="color: #888; margin-top: 5px; display: block;">
                                Gợi ý: 🍔 🚗 🛍️ 🎮 💊 📚 💰 🎁 ☕ 🏠 💳 ✈️ 🎬 🏋️
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Màu sắc</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="color" name="color" value="${cat ? cat.color : '#667eea'}" style="width: 60px; height: 40px; border: none; border-radius: 8px; cursor: pointer;">
                                <input type="text" id="colorHex" value="${cat ? cat.color : '#667eea'}" style="flex: 1;" placeholder="#667eea">
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                ${['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#6C5CE7', '#00B894', '#FDCB6E', '#667eea', '#764ba2'].map(color => 
                                    `<div class="color-preset" data-color="${color}" style="width: 40px; height: 40px; background: ${color}; border-radius: 8px; cursor: pointer; border: 2px solid transparent;"></div>`
                                ).join('')}
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary close-modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
    
    // Icon preview
    $('#iconInput').on('input', function() {
        $('#iconPreview').text($(this).val() || '📝');
    });
    
    // Color sync
    $('input[name="color"]').on('input', function() {
        $('#colorHex').val($(this).val());
    });
    
    $('#colorHex').on('input', function() {
        $('input[name="color"]').val($(this).val());
    });
    
    // Color presets
    $('.color-preset').click(function() {
        const color = $(this).data('color');
        $('input[name="color"]').val(color);
        $('#colorHex').val(color);
        $('.color-preset').css('border-color', 'transparent');
        $(this).css('border-color', '#333');
    });
    
    // Form submit
    $('#categoryForm').submit(function(e) {
        e.preventDefault();
        saveCategoryData(isEdit);
    });
    
    // Close modal
    $('.close-modal').click(function() {
        $('#categoryModal').remove();
    });
}

// Save category
function saveCategoryData(isEdit) {
    const formData = $('#categoryForm').serialize();
    const action = isEdit ? 'update' : 'add';
    
    $.ajax({
        url: 'api/categories.php',
        type: 'POST',
        data: formData + '&action=' + action,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#categoryModal').remove();
                loadCategories(); // Reload categories
                loadCategoriesPage(); // Refresh page
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Có lỗi xảy ra!');
        }
    });
}

// Edit category
window.editCategory = function(id) {
    openCategoryModal(id);
}

// Delete category
window.deleteCategory = function(id) {
    if (!confirm('Bạn có chắc muốn xóa danh mục này?\n\nLưu ý: Không thể xóa nếu đang có giao dịch sử dụng danh mục này.')) {
        return;
    }
    
    $.ajax({
        url: 'api/categories.php',
        type: 'POST',
        data: { action: 'delete', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadCategories();
                loadCategoriesPage();
            } else {
                alert(response.message);
            }
        }
    });
}
// ==================== DARK MODE ====================

// Initialize Dark Mode
function initDarkMode() {
    // Check saved preference
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) {
        enableDarkMode();
    }
}

// Toggle dark mode
function toggleDarkMode() {
    const isDark = document.body.classList.contains('dark-mode');
    if (isDark) {
        disableDarkMode();
    } else {
        enableDarkMode();
    }
}

// Enable dark mode
function enableDarkMode() {
    document.body.classList.add('dark-mode');
    localStorage.setItem('darkMode', 'true');
    $('#darkModeIcon').removeClass('fa-moon').addClass('fa-sun');
}

// Disable dark mode
function disableDarkMode() {
    document.body.classList.remove('dark-mode');
    localStorage.setItem('darkMode', 'false');
    $('#darkModeIcon').removeClass('fa-sun').addClass('fa-moon');
}

// Add dark mode toggle to topbar
function addDarkModeToggle() {
    const darkModeBtn = `
        <button id="darkModeToggle" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666; margin-right: 15px; transition: color 0.3s;">
            <i id="darkModeIcon" class="fas fa-moon"></i>
        </button>
    `;
    
    $('.topbar-right').prepend(darkModeBtn);
    
    $('#darkModeToggle').click(function() {
        toggleDarkMode();
    });
}

// ==================== AVATAR UPLOAD ====================

// Load profile page with avatar upload
function loadProfilePage() {
    $('#profile-page').html(`
        <div class="card">
            <div class="card-header">
                <h3>Thông tin cá nhân</h3>
            </div>
            <div class="card-body">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="position: relative; display: inline-block;">
                        <div id="avatarPreview" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 48px; color: white; margin: 0 auto 15px; position: relative; overflow: hidden;">
                            <i class="fas fa-user"></i>
                        </div>
                        <label for="avatarInput" style="position: absolute; bottom: 15px; right: 0; background: #667eea; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    </div>
                    <p id="userName" style="font-size: 18px; font-weight: 600; margin-bottom: 5px;"></p>
                    <p id="userEmail" style="color: #888; font-size: 14px;"></p>
                </div>
                
                <form id="updateProfileForm">
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="full_name" id="fullName" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" id="phone" placeholder="VD: 0912345678">
                    </div>
                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                </form>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
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
        
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3>Cài đặt giao diện</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f5f7fa; border-radius: 8px;">
                    <div>
                        <h4 style="margin-bottom: 5px;">Chế độ tối (Dark Mode)</h4>
                        <p style="color: #888; font-size: 14px;">Giảm mỏi mắt khi sử dụng ban đêm</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="darkModeSwitch" ${localStorage.getItem('darkMode') === 'true' ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
    `);
    
    // Load user info
    loadUserInfo();
    
    // Avatar upload
    $('#avatarInput').change(function(e) {
        uploadAvatar(e.target.files[0]);
    });
    
    // Update profile form
    $('#updateProfileForm').submit(function(e) {
        e.preventDefault();
        updateProfile();
    });
    
    // Change password form
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
    
    // Dark mode switch
    $('#darkModeSwitch').change(function() {
        toggleDarkMode();
    });
}

// Load user info
function loadUserInfo() {
    $.get('api/auth.php', { action: 'check' }, function(response) {
        if (response.success && response.user) {
            const user = response.user;
            $('#userName').text(user.full_name || user.username);
            $('#userEmail').text(user.email);
            $('#fullName').val(user.full_name || '');
            $('#phone').val(user.phone || '');
            
            // Load avatar if exists
            if (user.avatar) {
                $('#avatarPreview').html(`<img src="${user.avatar}" style="width: 100%; height: 100%; object-fit: cover;">`);
                $('.user-info .avatar').html(`<img src="${user.avatar}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`);
            }
        }
    }, 'json');
}

// Upload avatar
function uploadAvatar(file) {
    if (!file) return;
    
    // Validate file
    if (!file.type.startsWith('image/')) {
        alert('Vui lòng chọn file ảnh!');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) { // 2MB
        alert('Kích thước ảnh không được vượt quá 2MB!');
        return;
    }
    
    // Preview
    const reader = new FileReader();
    reader.onload = function(e) {
        $('#avatarPreview').html(`<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`);
        $('.user-info .avatar').html(`<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`);
    };
    reader.readAsDataURL(file);
    
    // Upload to server
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('action', 'upload_avatar');
    
    $.ajax({
        url: 'api/profile.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Cập nhật ảnh đại diện thành công!');
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Có lỗi xảy ra khi upload ảnh!');
        }
    });
}

// Update profile
function updateProfile() {
    const formData = $('#updateProfileForm').serialize() + '&action=update_profile';
    
    $.ajax({
        url: 'api/profile.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Cập nhật thông tin thành công!');
                loadUserInfo();
            } else {
                alert(response.message);
            }
        }
    }); 
}

// ==================== INITIALIZE ====================

// Update initApp function
function initApp() {
    loadCategories();
    loadDashboard();
    setupEventListeners();
    $('#transactionDate').val(new Date().toISOString().split('T')[0]);
    
    // Initialize new features
    initDarkMode();
    addDarkModeToggle();
}
