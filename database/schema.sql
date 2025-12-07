-- UniConnect Database Schema
-- MySQL Database for University Book & Material Exchange Platform

CREATE DATABASE
IF NOT EXISTS uniconnect CHARACTER
SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE uniconnect;

-- Users table
CREATE TABLE
IF NOT EXISTS users
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR
(100) NOT NULL,
    email VARCHAR
(100) UNIQUE NOT NULL,
    password VARCHAR
(255) NOT NULL,
    department VARCHAR
(100) NOT NULL,
    phone VARCHAR
(20),
    profile_image VARCHAR
(255) DEFAULT NULL,
    role ENUM
('user', 'admin') DEFAULT 'user',
    status ENUM
('active', 'inactive') DEFAULT 'active',
    remember_token VARCHAR
(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_email
(email),
    INDEX idx_role
(role),
    INDEX idx_status
(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Books table
CREATE TABLE
IF NOT EXISTS books
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR
(255) NOT NULL,
    author VARCHAR
(255) NOT NULL,
    department VARCHAR
(100) NOT NULL,
    course VARCHAR
(100) NOT NULL,
    description TEXT,
    condition_type ENUM
('new', 'like_new', 'good', 'fair', 'poor') NOT NULL,
    exchange_type ENUM
('borrow', 'buy', 'donate') NOT NULL,
    price DECIMAL
(10, 2) DEFAULT NULL,
    image_path VARCHAR
(255) DEFAULT NULL,
    file_path VARCHAR
(255) DEFAULT NULL,
    status ENUM
('pending', 'approved', 'blocked') DEFAULT 'pending',
    availability ENUM
('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON
UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY
(user_id) REFERENCES users
(id) ON
DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status
(status),
    INDEX idx_exchange_type
(exchange_type),
    INDEX idx_department
(department),
    FULLTEXT INDEX idx_search
(title, author, course)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Requests table
CREATE TABLE
IF NOT EXISTS requests
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    book_id INT NOT NULL,
    message TEXT,
    status ENUM
('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON
UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY
(requester_id) REFERENCES users
(id) ON
DELETE CASCADE,
    FOREIGN KEY (book_id)
REFERENCES books
(id) ON
DELETE CASCADE,
    INDEX idx_requester (requester_id),
    INDEX idx_book
(book_id),
    INDEX idx_status
(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE
IF NOT EXISTS messages
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    book_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    subject VARCHAR
(255) DEFAULT NULL,
    is_read TINYINT
(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY
(sender_id) REFERENCES users
(id) ON
DELETE CASCADE,
    FOREIGN KEY (receiver_id)
REFERENCES users
(id) ON
DELETE CASCADE,
    FOREIGN KEY (book_id)
REFERENCES books
(id) ON
DELETE
SET NULL
,
    INDEX idx_sender
(sender_id),
    INDEX idx_receiver
(receiver_id),
    INDEX idx_book
(book_id),
    INDEX idx_read
(is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin logs table
CREATE TABLE
IF NOT EXISTS admin_logs
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    action VARCHAR
(50) NOT NULL,
    target_id INT DEFAULT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY
(admin_id) REFERENCES users
(id) ON
DELETE
SET NULL
,
    INDEX idx_admin
(admin_id),
    INDEX idx_action
(action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: admin123 (you should change this)
INSERT INTO users
    (full_name, email, password, department, phone, role, status)
VALUES
    ('Admin', 'admin@uniconnect.edu.et', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administration', '0911234567', 'admin', 'active')
ON DUPLICATE KEY
UPDATE email = email;

-- Downloads log table
CREATE TABLE
IF NOT EXISTS downloads
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    book_id INT NOT NULL,
    action ENUM
('view','download') NOT NULL,
    file_path VARCHAR
(255) NOT NULL,
    ip VARCHAR
(45) DEFAULT NULL,
    user_agent VARCHAR
(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_book
(book_id),
    INDEX idx_user
(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
