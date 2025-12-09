SET
  NAMES utf8mb4;

SET
  time_zone = '+00:00';

SET
  FOREIGN_KEY_CHECKS = 0;

SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- Tạo Database
CREATE DATABASE IF NOT EXISTS `bookon_web_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

USE `bookon_web_db`;

-- =================================================================
-- PHẦN 1: QUẢN LÝ NGƯỜI DÙNG & XÁC THỰC
-- =================================================================
-- Bảng Roles: Quản lý vai trò và quyền hạn
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` bigint unsigned NOT NULL,
  `updated_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Users: Lưu thông tin người dùng
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `address` text,
  `avatar_url` varchar(255) DEFAULT 'default_avatar.png',
  `status` enum('unverified', 'active', 'banned') NOT NULL DEFAULT 'unverified',
  `created_at` bigint unsigned NOT NULL,
  `updated_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_role` (`role_id`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB AUTO_INCREMENT = 26 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Sessions: Lưu session trong database
DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` bigint unsigned NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `fk_session_user` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng User Tokens: Quản lý token xác thực/reset pass
DROP TABLE IF EXISTS `user_tokens`;

CREATE TABLE `user_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `type` enum('email_verification', 'password_reset') NOT NULL,
  `expires_at` bigint unsigned NOT NULL,
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_usertoken_user` (`user_id`),
  CONSTRAINT `fk_usertoken_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 25 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Persistent Logins: Ghi nhớ đăng nhập
DROP TABLE IF EXISTS `persistent_logins`;

CREATE TABLE `persistent_logins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `selector` varchar(255) NOT NULL,
  `validator_hash` varchar(255) NOT NULL,
  `expires_at` bigint unsigned NOT NULL,
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `fk_persistentlogin_user` (`user_id`),
  CONSTRAINT `fk_persistentlogin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 13 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- =================================================================
-- PHẦN 2: QUẢN LÝ NỘI DUNG & SẢN PHẨM
-- =================================================================
-- Bảng Categories: Danh mục sách
DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text,
  `parent_id` int unsigned DEFAULT NULL,
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Books: Thông tin sách
DROP TABLE IF EXISTS `books`;

CREATE TABLE `books` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(270) NOT NULL,
  `description` text,
  `cover_image_url` varchar(255) NOT NULL,
  `price` decimal(10, 2) NOT NULL,
  `stock_quantity` int unsigned NOT NULL DEFAULT '0',
  `author` varchar(100) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `created_at` bigint unsigned NOT NULL,
  `updated_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_book_category` (`category_id`),
  KEY `idx_book_title` (`title`),
  CONSTRAINT `fk_book_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 22 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Posts: Tin tức, bài viết
DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(270) NOT NULL,
  `content` longtext NOT NULL,
  `thumbnail_image_url` varchar(255) DEFAULT NULL,
  `status` enum('draft', 'published') NOT NULL DEFAULT 'draft',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` bigint unsigned NOT NULL,
  `updated_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_post_user` (`user_id`),
  CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng FAQs: Câu hỏi thường gặp
DROP TABLE IF EXISTS `faqs`;

CREATE TABLE `faqs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `display_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` bigint unsigned NOT NULL,
  `updated_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Settings: Cấu hình website
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `setting_type` text,
  PRIMARY KEY (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- =================================================================
-- PHẦN 3: QUẢN LÝ TƯƠNG TÁC
-- =================================================================
-- Bảng Reviews: Đánh giá sách
DROP TABLE IF EXISTS `reviews`;

CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `book_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text,
  `status` enum('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_book_review` (`user_id`, `book_id`),
  KEY `fk_review_book` (`book_id`),
  CONSTRAINT `fk_review_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Post Comments: Bình luận bài viết
DROP TABLE IF EXISTS `post_comments`;

CREATE TABLE `post_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `content` text NOT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `status` enum('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_comment_post` (`post_id`),
  KEY `fk_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Contacts: Form liên hệ
DROP TABLE IF EXISTS `contacts`;

CREATE TABLE `contacts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new', 'read', 'replied') NOT NULL DEFAULT 'new',
  `created_at` bigint unsigned NOT NULL,
  `destination_email` varchar(100) NOT NULL DEFAULT 'admin@bookon.com',
  PRIMARY KEY (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- =================================================================
-- PHẦN 4: QUẢN LÝ ĐƠN HÀNG & THANH TOÁN
-- =================================================================
-- Bảng Orders: Đơn hàng
DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `total_amount` decimal(12, 2) NOT NULL,
  `status` enum(
    'pending',
    'processing',
    'shipped',
    'completed',
    'cancelled',
    'refunded'
  ) NOT NULL DEFAULT 'pending',
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(15) NOT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB AUTO_INCREMENT = 17 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Order Items: Chi tiết đơn hàng
DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `price_at_purchase` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_orderitem_order` (`order_id`),
  KEY `fk_orderitem_book` (`book_id`),
  CONSTRAINT `fk_orderitem_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 12 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Order Reviews: Đánh giá đơn hàng
DROP TABLE IF EXISTS `order_reviews`;

CREATE TABLE `order_reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text,
  `status` enum('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `fk_orderreview_user` (`user_id`),
  CONSTRAINT `fk_orderreview_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderreview_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Bảng Transactions: Giao dịch
DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `transaction_code` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(12, 2) NOT NULL,
  `status` enum('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `created_at` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_transaction_order` (`order_id`),
  KEY `idx_transaction_code` (`transaction_code`),
  CONSTRAINT `fk_transaction_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- =================================================================
-- PHẦN 5: DỮ LIỆU MẪU (DATA DUMP)
-- =================================================================
-- Dumping data for table `books`
LOCK TABLES `books` WRITE;

INSERT INTO
  `books`
VALUES
  (
    1,
    4,
    'Số Đỏ',
    'so-do',
    'Tác phẩm kinh điển của Vũ Trọng Phụng.',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    85000.00,
    46,
    'Vũ Trọng Phụng',
    'NXB Văn Học',
    2020,
    1763322178,
    1765264490
  ),
(
    2,
    5,
    'Nhà Giả Kim',
    'nha-gia-kim',
    'Cuốn sách bán chạy nhất của Paulo Coelho.',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    79000.00,
    120,
    'Paulo Coelho',
    'NXB Nhã Nam',
    2020,
    1763322178,
    1763322178
  ),
(
    3,
    6,
    'Content Hay Nói Thay Nước Bọt',
    'content-hay-noi-thay-nuoc-bot',
    'Bí quyết tạo content thu hút.',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    120000.00,
    30,
    'Zone Media',
    'NXB Trẻ',
    2023,
    1763322178,
    1763322178
  ),
(
    4,
    3,
    'Đắc Nhân Tâm',
    'dac-nhan-tam',
    'Nghệ thuật đối nhân xử thế.',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    99000.00,
    200,
    'Dale Carnegie',
    'NXB Tổng Hợp TPHCM',
    2021,
    1763322178,
    1763322178
  ),
(
    5,
    5,
    'Giết Con Chim Nhại',
    'giet-con-chim-nhai',
    'Một tác phẩm văn học kinh điển của Mỹ.',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    150000.00,
    45,
    'Harper Lee',
    'NXB Văn Học',
    2019,
    1763322178,
    1763322178
  );

UNLOCK TABLES;

-- Dumping data for table `categories`
LOCK TABLES `categories` WRITE;

INSERT INTO
  `categories`
VALUES
  (
    1,
    'Sách Văn Học',
    'sach-van-hoc',
    'Các tác phẩm văn học trong và ngoài nước',
    NULL,
    1763322178
  ),
(
    2,
    'Sách Kinh Tế',
    'sach-kinh-te',
    'Sách về quản trị, kinh doanh, marketing',
    NULL,
    1763322178
  ),
(
    3,
    'Sách Kỹ Năng',
    'sach-ky-nang',
    'Sách phát triển bản thân và kỹ năng mềm',
    NULL,
    1763322178
  ),
(
    4,
    'Văn Học Việt Nam',
    'van-hoc-viet-nam',
    'Tác phẩm của các tác giả Việt Nam',
    1,
    1763322178
  ),
(
    5,
    'Văn Học Nước Ngoài',
    'van-hoc-nuoc-ngoai',
    'Các tác phẩm kinh điển và hiện đại thế giới',
    1,
    1763322178
  ),
(
    6,
    'Marketing & Bán Hàng',
    'marketing-ban-hang',
    'Sách chuyên sâu về lĩnh vực marketing',
    2,
    1763322178
  );

UNLOCK TABLES;

-- Dumping data for table `contacts`
LOCK TABLES `contacts` WRITE;

INSERT INTO
  `contacts`
VALUES
  (
    1,
    'Khách Hàng A',
    'khachhang.a@email.com',
    'Hỏi về đơn hàng',
    'Xin chào, tôi muốn hỏi về tình trạng đơn hàng #12345.',
    'new',
    1763322178,
    'admin@bookon.com'
  ),
(
    2,
    'Khách Hàng B',
    'khachhang.b@email.com',
    'Góp ý về website',
    'Website của bạn rất dễ sử dụng, tôi rất thích!',
    'replied',
    1763322178,
    'admin@bookon.com'
  ),
(
    3,
    'Quản Trị Viên',
    'admin@bookon.com',
    'Re: Góp ý về website',
    'Thankyou',
    'new',
    1765094157,
    'admin@bookon.com'
  ),
(
    5,
    'fankv2',
    'testuser@example.com',
    NULL,
    'abc',
    'new',
    1765125803,
    'admin@bookon.com'
  );

UNLOCK TABLES;

-- Dumping data for table `faqs`
LOCK TABLES `faqs` WRITE;

INSERT INTO
  `faqs`
VALUES
  (
    1,
    'Làm thế nào để đặt hàng?',
    'Bạn chỉ cần thêm sách vào giỏ hàng, sau đó vào trang thanh toán và điền đầy đủ thông tin nhận hàng.',
    1,
    1,
    1763322178,
    1763322178
  ),
(
    2,
    'Phí vận chuyển được tính như thế nào?',
    'Phí vận chuyển là 30.000đ cho tất cả các đơn hàng trên toàn quốc. Miễn phí vận chuyển cho đơn hàng từ 500.000đ.',
    2,
    1,
    1763322178,
    1763322178
  );

UNLOCK TABLES;

-- Dumping data for table `order_items`
LOCK TABLES `order_items` WRITE;

INSERT INTO
  `order_items`
VALUES
  (1, 1, 2, 1, 79000.00),
(2, 1, 4, 1, 99000.00),
(3, 2, 5, 1, 150000.00),
(4, 3, 1, 2, 85000.00),
(5, 3, 3, 1, 120000.00),
(7, 5, 1, 1, 85000.00),
(8, 1, 1, 1, 85000.00),
(9, 1, 1, 1, 85000.00),
(10, 1, 1, 3, 85000.00),
(11, 1, 1, 1, 85000.00);

UNLOCK TABLES;

-- Dumping data for table `order_reviews`
LOCK TABLES `order_reviews` WRITE;

UNLOCK TABLES;

-- Dumping data for table `orders`
LOCK TABLES `orders` WRITE;

INSERT INTO
  `orders`
VALUES
  (
    1,
    7,
    178000.00,
    'completed',
    'Nguyễn Văn An',
    'nguyen.an@email.com',
    '0912345678',
    '456 User Avenue, Hanoi',
    'COD',
    1762890178
  ),
(
    2,
    7,
    150000.00,
    'shipped',
    'Trần Thị Bình',
    'tran.binh@email.com',
    '0987654321',
    '789 Customer Road, Danang',
    'Online Payment',
    1763149378
  ),
(
    3,
    7,
    290000.00,
    'processing',
    'Nguyễn Văn An',
    'nguyen.an@email.com',
    '0912345678',
    '456 User Avenue, Hanoi',
    'COD',
    1763235778
  ),
(
    4,
    7,
    79000.00,
    'cancelled',
    'Khách Vãng Lai',
    'guest@example.com',
    '0999888777',
    '111 Guest Street, HCMC',
    'COD',
    1763322178
  ),
(
    5,
    7,
    85000.00,
    'completed',
    'Trần Thị Bình',
    'tran.binh@email.com',
    '0987654321',
    '789 Customer Road, Danang',
    'COD',
    1763322178
  ),
(
    6,
    1,
    170000.00,
    'pending',
    'Quản Trị Viên',
    'admin@bookon.com',
    '0987654321',
    '123 Admin Street',
    'COD',
    1765261100
  ),
(
    8,
    7,
    85000.00,
    'pending',
    'Test User',
    'testuser@example.com',
    '0123456789',
    '123 User Street',
    'COD',
    1765261347
  ),
(
    13,
    7,
    85000.00,
    'pending',
    'Test User',
    'testuser@example.com',
    '0123456789',
    '123 User Street',
    'COD',
    1765261464
  ),
(
    15,
    1,
    255000.00,
    'pending',
    'Quản Trị Viên',
    'admin@bookon.com',
    '0987654321',
    '123 Admin Street',
    'COD',
    1765263176
  ),
(
    16,
    7,
    85000.00,
    'pending',
    'Test User',
    'testuser@example.com',
    '0123456789',
    '123 User Street',
    'COD',
    1765264489
  );

UNLOCK TABLES;

-- Dumping data for table `persistent_logins`
LOCK TABLES `persistent_logins` WRITE;

INSERT INTO
  `persistent_logins`
VALUES
  (
    4,
    7,
    'ad079f66d44fac5de47cc0f43ab6ac86',
    '769cd3826fbdf0eb6c2915d77b3d5063bd1d2546759466c773cdab536397a8bd',
    1765924862,
    1763332862
  ),
(
    6,
    7,
    'b5672a8459b96a44e7d058354ff65130',
    '51f6ba804d7c935769ed618769a51cc0be403df9263b5957aad5f9bf5ac22de4',
    1765941695,
    1763349695
  ),
(
    7,
    7,
    '8d5eb4ebacb46dd47fc5f33939d0a54b',
    'a92bf42971a607e8a8b5d4bf1865355d2699af4ad9daf4d8198846a0cc3405b0',
    1765944361,
    1763352361
  ),
(
    8,
    7,
    '875ad0b1542173f4226684bf338c2cee',
    '1dc9c24e5f93e9d3236e6bc5e54c376a2ff7914fdda472a9139badb6502f378d',
    1766246045,
    1763654045
  ),
(
    9,
    1,
    '459f9176ef403912b76cb1c10b1ab648',
    '72f6801cf0c31ee11d54c261ebbb326002fe8baca0eefcfde6c7e99fa2b7e2b2',
    1766590914,
    1763998914
  ),
(
    10,
    1,
    '241974d728597bde14ff8b9031e7b524',
    'bb3e8f77ca777955f927186d137b033cd7e2c5f8381d70291344cafb61ed198f',
    1766596373,
    1764004373
  );

UNLOCK TABLES;

-- Dumping data for table `post_comments`
LOCK TABLES `post_comments` WRITE;

INSERT INTO
  `post_comments`
VALUES
  (
    3,
    1,
    1,
    'Cảm ơn bạn đã ủng hộ!',
    1,
    'approved',
    1763322178
  );

UNLOCK TABLES;

-- Dumping data for table `posts`
LOCK TABLES `posts` WRITE;

INSERT INTO
  `posts`
VALUES
  (
    1,
    1,
    'Top 5 Cuốn Sách Văn Học Nên Đọc Mùa Hè Này',
    'top-5-sach-van-hoc-mua-he',
    'Hè là thời điểm tuyệt vời để thư giãn cùng những cuốn sách hay. Dưới đây là 5 gợi ý không thể bỏ qua...',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    'published',
    'Top 5 Sách Văn Học Mùa Hè',
    'Khám phá 5 cuốn sách văn học hay nhất để đọc trong mùa hè này.',
    1763322178,
    1765254920
  ),
(
    2,
    1,
    'Làm Thế Nào Để Xây Dựng Thói Quen Đọc Sách?',
    'xay-dung-thoi-quen-doc-sach',
    '<p>Đọc sách mang lại nhiều lợi ích, nhưng không phải ai cũng dễ dàng duy trì thói quen này. Hãy cùng tìm hiểu các bí quyết sau...</p>',
    'public/uploads/posts/1e4737a5ee0dfdea1a7bd0204c3c12a6daa0a4d4.jpg',
    'draft',
    'Bí Quyết Xây Dựng Thói Quen Đọc Sách',
    'Hướng dẫn chi tiết các bước để hình thành và duy trì thói quen đọc sách mỗi.',
    1763322178,
    1765260522
  ),
(
    3,
    1,
    'Bài Viết Nháp Về Sách Mới',
    'bai-viet-nhap-ve-sach-moi',
    '<p>Đây là nội dung của bài viết nháp, chưa được xuất bản...</p>, yes sơ',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    'draft',
    NULL,
    NULL,
    1763322178,
    1765102626
  );

UNLOCK TABLES;

-- Dumping data for table `reviews`
LOCK TABLES `reviews` WRITE;

UNLOCK TABLES;

-- Dumping data for table `roles`
LOCK TABLES `roles` WRITE;

INSERT INTO
  `roles`
VALUES
  (
    1,
    'Admin',
    '{\"faqs\": [\"create\", \"read\", \"update\", \"delete\"], \"books\": [\"create\", \"update\", \"delete\"], \"files\": [\"upload\"], \"posts\": [\"create\", \"read\", \"update\", \"delete\"], \"roles\": [\"create\", \"read\", \"update\", \"delete\"], \"users\": [\"create\", \"read\", \"update\", \"delete\", \"ban\"], \"orders\": [\"read\", \"create\", \"update\", \"delete\"], \"tokens\": [\"read\", \"create\", \"delete\"], \"reviews\": [\"read\", \"update\", \"delete\"], \"contacts\": [\"create\", \"read\", \"update\", \"delete\"], \"sessions\": [\"read\", \"delete\"], \"settings\": [\"manage\"], \"categories\": [\"create\", \"read\", \"update\", \"delete\"], \"transactions\": [\"create\", \"read\", \"update\", \"delete\"], \"post_comments\": [\"create\", \"read_own\", \"update_own\", \"delete_own\"]}',
    1763322177,
    1763322177
  ),
(
    2,
    'Customer',
    '{\"files\": [\"upload\"], \"posts\": [\"read\"], \"orders\": [\"create\", \"read_own\", \"update_own\", \"delete_own\"], \"reviews\": [\"create\", \"read_own\", \"update_own\", \"delete_own\"], \"post_comments\": [\"create\", \"read_own\", \"update_own\", \"delete_own\"]}',
    1763322177,
    1763322177
  );

UNLOCK TABLES;

-- Dumping data for table `sessions`
LOCK TABLES `sessions` WRITE;

INSERT INTO
  `sessions`
VALUES
  (
    'gc1kufjsdnlc2ps99k8mhn6dl6',
    NULL,
    '::1',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
    '',
    1765264438
  ),
(
    'koban1npcsnlkn5sbvl2c364tg',
    1,
    '::1',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
    'user_id|i:1;user_email|s:16:\"admin@bookon.com\";user_name|s:18:\"Quản Trị Viên\";logged_in_at|i:1765261494;',
    1765263543
  ),
(
    'smig0eroa90c0bkcvap2to95a1',
    7,
    '::1',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
    'user_id|i:7;user_email|s:20:\"testuser@example.com\";user_name|s:9:\"Test User\";logged_in_at|i:1765264459;',
    1765264951
  ),
(
    'u6ejs9p99qbolslgtbpsl6kkh4',
    1,
    '::1',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
    'user_id|i:1;user_email|s:16:\"admin@bookon.com\";user_name|s:18:\"Quản Trị Viên\";logged_in_at|i:1765264392;',
    1765264954
  );

UNLOCK TABLES;

-- Dumping data for table `settings`
LOCK TABLES `settings` WRITE;

INSERT INTO
  `settings`
VALUES
  (
    'about_page_content',
    '<h1>Về Chúng Tôi</h1><p>Đây là nội dung trang giới thiệu. Bạn có thể thay đổi trong trang quản trị.</p>',
    'string'
  ),
(
    'company_address',
    '123 Đường Sách, Phường B, Quận A, TP.HCM',
    'string'
  ),
('company_email', 'support@bookon.com', 'string'),
(
    'company_logo_url',
    'public/uploads/books/c32b4059f135779d359fae36793cad2aa85b2457.png',
    'file'
  ),
('company_name', 'BOOKON', 'string'),
('company_phone', '0987-654-333', 'string'),
('facebook', 'facebook', 'string'),
('google_maps_iframe', NULL, NULL),
('instagram', 'instagram', 'string'),
('twitter', 'twitter', 'string');

UNLOCK TABLES;

-- Dumping data for table `transactions`
LOCK TABLES `transactions` WRITE;

INSERT INTO
  `transactions`
VALUES
  (
    1,
    1,
    'COD_ORDER_1',
    'COD',
    178000.00,
    'completed',
    1762890178
  ),
(
    2,
    2,
    'VNPAY_XYZ123',
    'Online Payment',
    150000.00,
    'completed',
    1763149378
  ),
(
    3,
    4,
    'COD_ORDER_4',
    'COD',
    79000.00,
    'failed',
    1763322178
  );

UNLOCK TABLES;

-- Dumping data for table `user_tokens`
LOCK TABLES `user_tokens` WRITE;

UNLOCK TABLES;

-- Dumping data for table `users`
LOCK TABLES `users` WRITE;

INSERT INTO
  `users`
VALUES
  (
    1,
    1,
    'Quản Trị Viên',
    'admin@bookon.com',
    '$2y$10$A1FFKoaXJ7ZOW5okl/SRquzIyth46DiqDdLjNN6oLFko5ELPPDcye',
    '0987654321',
    '123 Admin Street',
    'public/uploads/avatars/1e4737a5ee0dfdea1a7bd0204c3c12a6daa0a4d4.png',
    'active',
    1763322177,
    1765256606
  ),
(
    7,
    2,
    'Test User',
    'testuser@example.com',
    '$2y$10$GQUryGmgrOpe2ZAVvmIhTOoD3O2EgJw.xkrNRD0TOpX2LVhsNivwW',
    '0123456789',
    '123 User Street',
    'public/uploads/avatars/1e4737a5ee0dfdea1a7bd0204c3c12a6daa0a4d4.png',
    'active',
    1763327784,
    1763327799
  );

UNLOCK TABLES;

SET
  FOREIGN_KEY_CHECKS = 1;