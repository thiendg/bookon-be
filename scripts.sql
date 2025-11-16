SET
    NAMES utf8mb4;

SET
    time_zone = '+00:00';

-- Luôn sử dụng múi giờ UTC cho server
SET
    FOREIGN_KEY_CHECKS = 0;

-- Tạo Database
CREATE DATABASE IF NOT EXISTS `web_bansach` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `web_bansach`;

-- =================================================================
-- PHẦN 1: QUẢN LÝ NGƯỜI DÙNG & XÁC THỰC
-- =================================================================
-- Bảng Roles: Quản lý vai trò và quyền hạn (sử dụng JSON)
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `permissions` JSON,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL
) ENGINE = InnoDB;

-- Bảng Users: Lưu thông tin người dùng
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone_number` VARCHAR(15) NULL,
    `address` TEXT NULL,
    `avatar_url` VARCHAR(255) DEFAULT 'default_avatar.png',
    `status` ENUM('unverified', 'active', 'banned') NOT NULL DEFAULT 'unverified',
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB;

-- Bảng Sessions: Lưu session trong database
DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
    `session_id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `payload` TEXT NOT NULL,
    `last_activity` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Bảng User Tokens: Quản lý token dùng 1 lần (xác thực email, quên mật khẩu)
DROP TABLE IF EXISTS `user_tokens`;

CREATE TABLE `user_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL UNIQUE,
    `type` ENUM('email_verification', 'password_reset') NOT NULL,
    `expires_at` BIGINT UNSIGNED NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_usertoken_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Bảng Persistent Logins: Cho tính năng "Ghi nhớ đăng nhập"
DROP TABLE IF EXISTS `persistent_logins`;

CREATE TABLE `persistent_logins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `selector` VARCHAR(255) NOT NULL UNIQUE,
    `validator_hash` VARCHAR(255) NOT NULL,
    `expires_at` BIGINT UNSIGNED NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_persistentlogin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- =================================================================
-- PHẦN 2: QUẢN LÝ NỘI DUNG & SẢN PHẨM
-- =================================================================
-- Bảng Categories: Danh mục sản phẩm (sách)
DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL
) ENGINE = InnoDB;

-- Bảng Books: Thông tin sản phẩm sách
DROP TABLE IF EXISTS `books`;

CREATE TABLE `books` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(270) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `cover_image_url` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    `author` VARCHAR(100) NULL,
    `publisher` VARCHAR(100) NULL,
    `publication_year` INT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_book_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    INDEX `idx_book_title` (`title`)
) ENGINE = InnoDB;

-- Bảng Posts: Tin tức, bài viết blog
DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    -- ID của admin/editor viết bài
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(270) NOT NULL UNIQUE,
    `content` LONGTEXT NOT NULL,
    `thumbnail_image_url` VARCHAR(255) NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE = InnoDB;

-- Bảng FAQs: Câu hỏi thường gặp
DROP TABLE IF EXISTS `faqs`;

CREATE TABLE `faqs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL
) ENGINE = InnoDB;

-- Bảng Settings: Lưu cấu hình website (dạng key-value)
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT
) ENGINE = InnoDB;

-- =================================================================
-- PHẦN 3: QUẢN LÝ TƯƠNG TÁC
-- =================================================================
-- Bảng Reviews: Đánh giá cho SẢN PHẨM (Sách)
DROP TABLE IF EXISTS `reviews`;

CREATE TABLE `reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `book_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_review_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE `uq_user_book_review` (`user_id`, `book_id`)
) ENGINE = InnoDB;

-- Bảng Post Comments: Bình luận cho bài viết
DROP TABLE IF EXISTS `post_comments`;

CREATE TABLE `post_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Bảng Contacts: Lưu form liên hệ
DROP TABLE IF EXISTS `contacts`;

CREATE TABLE `contacts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied') NOT NULL DEFAULT 'new',
    `created_at` BIGINT UNSIGNED NOT NULL
) ENGINE = InnoDB;

-- =================================================================
-- PHẦN 4: QUẢN LÝ ĐƠN HÀNG & THANH TOÁN
-- =================================================================
-- Bảng Orders: Đơn hàng
DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    -- Có thể là khách vãng lai
    `total_amount` DECIMAL(12, 2) NOT NULL,
    `status` ENUM(
        'pending',
        'processing',
        'shipped',
        'completed',
        'cancelled',
        'refunded'
    ) NOT NULL DEFAULT 'pending',
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(15) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'COD',
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB;

-- Bảng Order Items: Chi tiết sản phẩm trong đơn hàng
DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `book_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `price_at_purchase` DECIMAL(10, 2) NOT NULL,
    -- Lưu lại giá tại thời điểm mua
    CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orderitem_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Bảng Order Reviews: Đánh giá cho DỊCH VỤ của đơn hàng
DROP TABLE IF EXISTS `order_reviews`;

CREATE TABLE `order_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL UNIQUE,
    -- Mỗi đơn hàng chỉ được review 1 lần
    `user_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_orderreview_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orderreview_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Bảng Transactions: Giao dịch thanh toán
DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `transaction_code` VARCHAR(100) NULL,
    -- Mã giao dịch từ cổng thanh toán
    `payment_method` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `created_at` BIGINT UNSIGNED NOT NULL,
    CONSTRAINT `fk_transaction_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    INDEX `idx_transaction_code` (`transaction_code`)
) ENGINE = InnoDB;

-- =================================================================
-- PHẦN 5: THÊM DỮ LIỆU MẪU
-- =================================================================
-- Thêm vai trò Admin và Customer
INSERT INTO
    `roles` (
        `id`,
        `name`,
        `permissions`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'Admin',
        '{
  "users": ["create", "read", "update", "delete", "ban"],
  "books": ["create", "read", "update", "delete"],
  "categories": ["create", "read", "update", "delete"],
  "orders": ["read", "update", "delete"],
  "posts": ["create", "read", "update", "delete"],
  "reviews": ["read", "update", "delete"],
  "faqs": ["create", "read", "update", "delete"],
  "settings": ["update"]
}',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        'Customer',
        '{
  "profile": ["read_own", "update_own"],
  "orders": ["create", "read_own"],
  "reviews": ["create", "read_own", "update_own", "delete_own"],
  "post_comments": ["create", "read_own", "update_own", "delete_own"],
  "books": ["read"],
  "posts": ["read"]
}',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

-- Thêm một số cấu hình mặc định
INSERT INTO
    `settings` (`setting_key`, `setting_value`)
VALUES
    ('company_name', 'Nhà Sách Tri Thức'),
    ('company_logo_url', '/uploads/logo.png'),
    (
        'company_address',
        '123 Đường Sách, Phường B, Quận A, TP.HCM'
    ),
    ('company_phone', '0987-654-321'),
    ('company_email', 'support@nhasachtrithuc.com'),
    (
        'about_page_content',
        '<h1>Về Chúng Tôi</h1><p>Đây là nội dung trang giới thiệu. Bạn có thể thay đổi trong trang quản trị.</p>'
    ),
    ('google_maps_iframe', NULL);

-- =================================================================
-- HOÀN TẤT
-- =================================================================
SET
    FOREIGN_KEY_CHECKS = 1;