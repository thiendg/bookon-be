-- =================================================================
-- SCRIPT SINH DỮ LIỆU MẪU (SEEDING SCRIPT) CHO `bookon_web_db`
-- PHIÊN BẢN HOÀN CHỈNH - ĐÃ SỬA TẤT CẢ LỖI
-- =================================================================
-- Thiết lập môi trường và chọn database
SET
    FOREIGN_KEY_CHECKS = 0;

SET
    NAMES utf8mb4;

USE `bookon_web_db`;

-- Xóa dữ liệu cũ trong các bảng để đảm bảo script có thể chạy lại nhiều lần
TRUNCATE TABLE `transactions`;

TRUNCATE TABLE `order_reviews`;

TRUNCATE TABLE `order_items`;

TRUNCATE TABLE `orders`;

TRUNCATE TABLE `contacts`;

TRUNCATE TABLE `post_comments`;

TRUNCATE TABLE `reviews`;

TRUNCATE TABLE `settings`;

TRUNCATE TABLE `faqs`;

TRUNCATE TABLE `posts`;

TRUNCATE TABLE `books`;

TRUNCATE TABLE `categories`;

TRUNCATE TABLE `persistent_logins`;

TRUNCATE TABLE `user_tokens`;

TRUNCATE TABLE `sessions`;

TRUNCATE TABLE `users`;

TRUNCATE TABLE `roles`;

-- =================================================================
-- PHẦN 1: DỮ LIỆU CƠ BẢN (ROLES, SETTINGS)
-- =================================================================
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
        '{"users": ["create", "read", "update", "delete", "ban"], "roles": ["create", "read", "update", "delete"], "sessions": ["read", "delete"], "tokens": ["read", "create", "delete"], "files": ["upload"], "books": ["create", "read", "update", "delete"], "categories": ["create", "read", "update", "delete"], "orders": ["read", "update", "delete"], "posts": ["create", "read", "update", "delete"], "reviews": ["read", "update", "delete"], "faqs": ["create", "read", "update", "delete"], "settings": ["update"]}',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        'Customer',
        '{"orders": ["create", "read_own"], "reviews": ["create", "read_own", "update_own", "delete_own"], "post_comments": ["create", "read_own", "update_own", "delete_own"], "books": ["read"], "posts": ["read"], "files": ["upload"]}',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

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
-- PHẦN 2: DỮ LIỆU NGƯỜI DÙNG
-- Mật khẩu cho tất cả tài khoản là: 'password123'
-- =================================================================
INSERT INTO
    `users` (
        `id`,
        `role_id`,
        `full_name`,
        `email`,
        `password_hash`,
        `phone_number`,
        `address`,
        `avatar_url`,
        `status`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        'Quản Trị Viên',
        'admin@bookon.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0901234567',
        '123 Admin Street, HCMC',
        'https://placehold.co/150x150/EFEFEF/AAAAAA&text=Admin',
        'active',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        2,
        'Nguyễn Văn An',
        'nguyen.an@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0912345678',
        '456 User Avenue, Hanoi',
        'https://placehold.co/150x150/FFC107/FFFFFF&text=NA',
        'active',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        3,
        2,
        'Trần Thị Bình',
        'tran.binh@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0987654321',
        '789 Customer Road, Danang',
        'default_avatar.png',
        'active',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        4,
        2,
        'Lê Minh Cường',
        'le.cuong@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0934567890',
        '101 Guest Lane, Cantho',
        'https://placehold.co/150x150/3498DB/FFFFFF&text=LC',
        'unverified',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        5,
        2,
        'Phạm Thuỳ Duyên',
        'pham.duyen@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0945678901',
        '202 Banned Path, Haiphong',
        'default_avatar.png',
        'banned',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

-- =================================================================
-- PHẦN 3: DỮ LIỆU SẢN PHẨM VÀ NỘI DUNG
-- =================================================================
INSERT INTO
    `categories` (
        `id`,
        `name`,
        `slug`,
        `description`,
        `parent_id`,
        `created_at`
    )
VALUES
    (
        1,
        'Sách Văn Học',
        'sach-van-hoc',
        'Các tác phẩm văn học trong và ngoài nước',
        NULL,
        UNIX_TIMESTAMP()
    ),
    (
        2,
        'Sách Kinh Tế',
        'sach-kinh-te',
        'Sách về quản trị, kinh doanh, marketing',
        NULL,
        UNIX_TIMESTAMP()
    ),
    (
        3,
        'Sách Kỹ Năng',
        'sach-ky-nang',
        'Sách phát triển bản thân và kỹ năng mềm',
        NULL,
        UNIX_TIMESTAMP()
    ),
    (
        4,
        'Văn Học Việt Nam',
        'van-hoc-viet-nam',
        'Tác phẩm của các tác giả Việt Nam',
        1,
        UNIX_TIMESTAMP()
    ),
    (
        5,
        'Văn Học Nước Ngoài',
        'van-hoc-nuoc-ngoai',
        'Các tác phẩm kinh điển và hiện đại thế giới',
        1,
        UNIX_TIMESTAMP()
    ),
    (
        6,
        'Marketing & Bán Hàng',
        'marketing-ban-hang',
        'Sách chuyên sâu về lĩnh vực marketing',
        2,
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `books` (
        `id`,
        `category_id`,
        `title`,
        `slug`,
        `description`,
        `cover_image_url`,
        `price`,
        `stock_quantity`,
        `author`,
        `publisher`,
        `publication_year`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        4,
        'Số Đỏ',
        'so-do',
        'Tác phẩm kinh điển của Vũ Trọng Phụng.',
        'https://placehold.co/300x450/2ECC71/FFFFFF&text=So+Do',
        85000.00,
        50,
        'Vũ Trọng Phụng',
        'NXB Văn Học',
        2022,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        5,
        'Nhà Giả Kim',
        'nha-gia-kim',
        'Cuốn sách bán chạy nhất của Paulo Coelho.',
        'https://placehold.co/300x450/3498DB/FFFFFF&text=Nha+Gia+Kim',
        79000.00,
        120,
        'Paulo Coelho',
        'NXB Nhã Nam',
        2020,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        3,
        6,
        'Content Hay Nói Thay Nước Bọt',
        'content-hay-noi-thay-nuoc-bot',
        'Bí quyết tạo content thu hút.',
        'https://placehold.co/300x450/E74C3C/FFFFFF&text=Content',
        120000.00,
        30,
        'Zone Media',
        'NXB Trẻ',
        2023,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        4,
        3,
        'Đắc Nhân Tâm',
        'dac-nhan-tam',
        'Nghệ thuật đối nhân xử thế.',
        'https://placehold.co/300x450/9B59B6/FFFFFF&text=Dac+Nhan+Tam',
        99000.00,
        200,
        'Dale Carnegie',
        'NXB Tổng Hợp TPHCM',
        2021,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        5,
        5,
        'Giết Con Chim Nhại',
        'giet-con-chim-nhai',
        'Một tác phẩm văn học kinh điển của Mỹ.',
        'https://placehold.co/300x450/F1C40F/FFFFFF&text=Giet+Con+Chim',
        150000.00,
        45,
        'Harper Lee',
        'NXB Văn Học',
        2019,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `posts` (
        `id`,
        `user_id`,
        `title`,
        `slug`,
        `content`,
        `thumbnail_image_url`,
        `status`,
        `meta_title`,
        `meta_description`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        'Top 5 Cuốn Sách Văn Học Nên Đọc Mùa Hè Này',
        'top-5-sach-van-hoc-mua-he',
        '<p>Mùa hè là thời điểm tuyệt vời để thư giãn cùng những cuốn sách hay. Dưới đây là 5 gợi ý không thể bỏ qua...</p>',
        'https://placehold.co/800x400/1ABC9C/FFFFFF&text=Blog',
        'published',
        'Top 5 Sách Văn Học Mùa Hè',
        'Khám phá 5 cuốn sách văn học hay nhất để đọc trong mùa hè này.',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        1,
        'Làm Thế Nào Để Xây Dựng Thói Quen Đọc Sách?',
        'xay-dung-thoi-quen-doc-sach',
        '<p>Đọc sách mang lại nhiều lợi ích, nhưng không phải ai cũng dễ dàng duy trì thói quen này. Hãy cùng tìm hiểu các bí quyết sau...</p>',
        'https://placehold.co/800x400/E67E22/FFFFFF&text=Blog',
        'published',
        'Bí Quyết Xây Dựng Thói Quen Đọc Sách',
        'Hướng dẫn chi tiết các bước để hình thành và duy trì thói quen đọc sách mỗi ngày.',
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        3,
        1,
        'Bài Viết Nháp Về Sách Mới',
        'bai-viet-nhap-ve-sach-moi',
        '<p>Đây là nội dung của bài viết nháp, chưa được xuất bản...</p>',
        NULL,
        'draft',
        NULL,
        NULL,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `faqs` (
        `id`,
        `question`,
        `answer`,
        `display_order`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'Làm thế nào để đặt hàng?',
        'Bạn chỉ cần thêm sách vào giỏ hàng, sau đó vào trang thanh toán và điền đầy đủ thông tin nhận hàng.',
        1,
        1,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    ),
    (
        2,
        'Phí vận chuyển được tính như thế nào?',
        'Phí vận chuyển là 30.000đ cho tất cả các đơn hàng trên toàn quốc. Miễn phí vận chuyển cho đơn hàng từ 500.000đ.',
        2,
        1,
        UNIX_TIMESTAMP(),
        UNIX_TIMESTAMP()
    );

-- =================================================================
-- PHẦN 4: DỮ LIỆU TƯƠNG TÁC
-- =================================================================
INSERT INTO
    `reviews` (
        `id`,
        `book_id`,
        `user_id`,
        `rating`,
        `comment`,
        `status`,
        `created_at`
    )
VALUES
    (
        1,
        2,
        2,
        5,
        'Sách rất hay và ý nghĩa, đã thay đổi cuộc đời tôi!',
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        2,
        4,
        2,
        4,
        'Cuốn sách này rất hữu ích, nhưng cần thời gian để áp dụng.',
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        3,
        1,
        3,
        5,
        'Tác phẩm kinh điển, đọc lại vẫn thấy hay.',
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        4,
        2,
        3,
        3,
        'Nội dung hơi khó hiểu với người mới.',
        'pending',
        UNIX_TIMESTAMP()
    ),
    (
        5,
        5,
        2,
        1,
        'Sách bị giao sai, chất lượng in kém.',
        'rejected',
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `post_comments` (
        `id`,
        `post_id`,
        `user_id`,
        `content`,
        `parent_id`,
        `status`,
        `created_at`
    )
VALUES
    (
        1,
        1,
        2,
        'Cảm ơn bài viết, mình sẽ tìm đọc cuốn Nhà Giả Kim!',
        NULL,
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        2,
        1,
        3,
        'Bài viết rất hữu ích. Cảm ơn admin.',
        NULL,
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        3,
        1,
        1,
        'Cảm ơn bạn đã ủng hộ!',
        1,
        'approved',
        UNIX_TIMESTAMP()
    ),
    (
        4,
        2,
        3,
        'Một mẹo rất hay, mình sẽ thử áp dụng.',
        NULL,
        'pending',
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `contacts` (
        `id`,
        `name`,
        `email`,
        `subject`,
        `message`,
        `status`,
        `created_at`
    )
VALUES
    (
        1,
        'Khách Hàng A',
        'khachhang.a@email.com',
        'Hỏi về đơn hàng',
        'Xin chào, tôi muốn hỏi về tình trạng đơn hàng #12345.',
        'new',
        UNIX_TIMESTAMP()
    ),
    (
        2,
        'Khách Hàng B',
        'khachhang.b@email.com',
        'Góp ý về website',
        'Website của bạn rất dễ sử dụng, tôi rất thích!',
        'read',
        UNIX_TIMESTAMP()
    );

-- =================================================================
-- PHẦN 5: DỮ LIỆU ĐƠN HÀNG VÀ THANH TOÁN
-- =================================================================
INSERT INTO
    `orders` (
        `id`,
        `user_id`,
        `total_amount`,
        `status`,
        `customer_name`,
        `customer_email`,
        `customer_phone`,
        `shipping_address`,
        `payment_method`,
        `created_at`
    )
VALUES
    (
        1,
        2,
        0,
        'completed',
        'Nguyễn Văn An',
        'nguyen.an@email.com',
        '0912345678',
        '456 User Avenue, Hanoi',
        'COD',
        UNIX_TIMESTAMP() - 86400 * 5
    ),
    (
        2,
        3,
        0,
        'shipped',
        'Trần Thị Bình',
        'tran.binh@email.com',
        '0987654321',
        '789 Customer Road, Danang',
        'Online Payment',
        UNIX_TIMESTAMP() - 86400 * 2
    ),
    (
        3,
        2,
        0,
        'processing',
        'Nguyễn Văn An',
        'nguyen.an@email.com',
        '0912345678',
        '456 User Avenue, Hanoi',
        'COD',
        UNIX_TIMESTAMP() - 86400
    ),
    (
        4,
        NULL,
        0,
        'cancelled',
        'Khách Vãng Lai',
        'guest@example.com',
        '0999888777',
        '111 Guest Street, HCMC',
        'COD',
        UNIX_TIMESTAMP()
    ),
    (
        5,
        3,
        0,
        'pending',
        'Trần Thị Bình',
        'tran.binh@email.com',
        '0987654321',
        '789 Customer Road, Danang',
        'COD',
        UNIX_TIMESTAMP()
    );

INSERT INTO
    `order_items` (
        `order_id`,
        `book_id`,
        `quantity`,
        `price_at_purchase`
    )
VALUES
    (1, 2, 1, 79000.00),
    (1, 4, 1, 99000.00),
    (2, 5, 1, 150000.00),
    (3, 1, 2, 85000.00),
    (3, 3, 1, 120000.00),
    (4, 2, 1, 79000.00),
    (5, 1, 1, 85000.00);

-- Tạm thời tắt chế độ an toàn để cập nhật tổng tiền
SET
    SQL_SAFE_UPDATES = 0;

-- <<<<<<<<<<<<<<<<< ĐÃ SỬA LỖI Ở ĐÂY
-- Cập nhật lại tổng tiền cho các đơn hàng dựa trên chi tiết đơn hàng
UPDATE
    `orders`
SET
    `total_amount` = (
        SELECT
            SUM(`quantity` * `price_at_purchase`)
        FROM
            `order_items`
        WHERE
            `order_id` = `orders`.`id`
    );

-- Bật lại chế độ an toàn
SET
    SQL_SAFE_UPDATES = 1;

-- <<<<<<<<<<<<<<<<< VÀ Ở ĐÂY
INSERT INTO
    `order_reviews` (
        `id`,
        `order_id`,
        `user_id`,
        `rating`,
        `comment`,
        `status`,
        `created_at`
    )
VALUES
    (
        1,
        1,
        2,
        5,
        'Giao hàng nhanh, sách được đóng gói cẩn thận. Rất hài lòng!',
        'approved',
        UNIX_TIMESTAMP() - 86400 * 4
    );

INSERT INTO
    `transactions` (
        `id`,
        `order_id`,
        `transaction_code`,
        `payment_method`,
        `amount`,
        `status`,
        `created_at`
    )
VALUES
    (
        1,
        1,
        'COD_ORDER_1',
        'COD',
        178000.00,
        'completed',
        UNIX_TIMESTAMP() - 86400 * 5
    ),
    (
        2,
        2,
        'VNPAY_XYZ123',
        'Online Payment',
        150000.00,
        'completed',
        UNIX_TIMESTAMP() - 86400 * 2
    ),
    (
        3,
        4,
        'COD_ORDER_4',
        'COD',
        79000.00,
        'failed',
        UNIX_TIMESTAMP()
    );

-- =================================================================
-- HOÀN TẤT
-- =================================================================
SET
    FOREIGN_KEY_CHECKS = 1;