SET NAMES utf8mb4;

-- Tao database
CREATE DATABASE IF NOT EXISTS expenditure_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expenditure_management;

-- Bang users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    balance DECIMAL(15,2) DEFAULT 0,
    language VARCHAR(10) DEFAULT 'vi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bang categories (danh muc chi tieu)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20),
    type ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bang transactions (giao dich)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, transaction_date),
    INDEX idx_category (category_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bang password_resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed user demo (password: 123456, balance khop voi du lieu mau)
INSERT INTO users (username, email, password, full_name, balance) VALUES
('demo_user', 'demo@example.com', '$2y$10$0LF5RfTyQtWyhf3G3TQhHuyl9QJv6Uk10fh25.2AfNnN4jpDw/kF2', 'Nguyen Van Demo', 9100000)
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Seed categories mac dinh cho user demo
INSERT INTO categories (user_id, name, icon, color, type) VALUES
(1, 'Ăn uống', '🍽', '#FF6B6B', 'expense'),
(1, 'Di chuyển', '🚗', '#4ECDC4', 'expense'),
(1, 'Mua sắm', '🛍', '#45B7D1', 'expense'),
(1, 'Giải trí', '🎉', '#FFA07A', 'expense'),
(1, 'Y tế', '🏥', '#98D8C8', 'expense'),
(1, 'Học tập', '📚', '#6C5CE7', 'expense'),
(1, 'Lương', '💰', '#00B894', 'income'),
(1, 'Thưởng', '🎁', '#FDCB6E', 'income');

-- Seed giao dich mau
INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES
(1, 1, 150000, 'expense', 'Ăn trưa với bạn bè', CURDATE()),
(1, 2, 50000, 'expense', 'Tiền xăng', CURDATE()),
(1, 7, 10000000, 'income', 'Lương tháng 11', CURDATE() - INTERVAL 5 DAY),
(1, 3, 500000, 'expense', 'Mua quần áo', CURDATE() - INTERVAL 2 DAY),
(1, 4, 200000, 'expense', 'Xem phim', CURDATE() - INTERVAL 1 DAY);
