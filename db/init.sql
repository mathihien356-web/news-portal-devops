-- ==============================================================================
-- HỆ THỐNG CỔNG THÔNG TIN ĐIỆN TỬ & WEBSITE TIN TỨC
-- Script khởi tạo cơ sở dữ liệu MySQL 8.0 & Phân quyền bảo mật
-- Tác giả: Ma Thị Hiền (MSSV: DTC245200948 - Lớp: CNTTK23C)
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `news_portal` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `news_portal`;

-- ------------------------------------------------------------------------------
-- 1. BẢNG NGƯỜI DÙNG & TÁC GIẢ (users)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'author', 'reader') NOT NULL DEFAULT 'reader',
    `avatar` VARCHAR(255) DEFAULT '/assets/images/default-avatar.png',
    `bio` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_role` (`role`),
    INDEX `idx_user_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. BẢNG DANH MỤC TIN TỨC (categories)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `icon` VARCHAR(50) DEFAULT 'newspaper',
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. BẢNG BÀI VIẾT TIN TỨC (articles)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `author_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `summary` TEXT NOT NULL,
    `content` LONGTEXT NOT NULL,
    `thumbnail` VARCHAR(255) NULL,
    `views` INT NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    `published_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_articles_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_art_status_pub` (`status`, `published_at`),
    INDEX `idx_art_featured` (`is_featured`),
    INDEX `idx_art_category` (`category_id`),
    INDEX `idx_art_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. BẢNG BÌNH LUẬN (comments)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('approved', 'pending', 'spam') NOT NULL DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_comments_article` (`article_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. BẢNG NHẬT KÝ AN TOÀN HỆ THỐNG (audit_logs)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(60) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_ip` (`ip_address`),
    INDEX `idx_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- DỮ LIỆU KHỞI TẠO (SEED DATA)
-- Mật khẩu mặc định:
-- admin: Admin@123456
-- mathihien: Hien@123456
-- nguyenvanan: An@123456
-- reader1: Reader@123456
-- ==============================================================================

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `avatar`, `bio`, `is_active`) VALUES
(1, 'admin', 'admin@newsportal.local', '$2y$10$Np9Jrv/PEzHAM0tUJFcgIuADkbRA0g.TdlR6meqiijRWWSoEA0A2a', 'Quản trị viên Hệ thống', 'admin', '/assets/images/avatar-admin.png', 'Quản trị viên cao cấp của Cổng thông tin điện tử.', 1),
(2, 'mathihien', 'mathihien@newsportal.local', '$2y$10$YxvQ/vkl3PEAm8ZEO5w8KefSsgzEiNEu69WlBiHONVW2Mr0UExc2K', 'Ma Thị Hiền', 'author', '/assets/images/avatar-hien.png', 'Biên tập viên chuyên mục Công nghệ và Đổi mới sáng tạo.', 1),
(3, 'nguyenvanan', 'vanan@newsportal.local', '$2y$10$vzMClgLx.ttg2SkZuj1MzOTsC2368M/UwDxJpijew39o86iWTVEPi', 'Nguyễn Văn An', 'author', '/assets/images/avatar-an.png', 'Phóng viên thường trú chuyên trách Thời sự & Kinh tế.', 1),
(4, 'reader1', 'reader1@gmail.com', '$2y$10$QoX8NI2pMeEQSVipoPWyt.IrxFWMKBrjj66fyafu2BG3Gm3yDYtzW', 'Trần Minh Đức', 'reader', '/assets/images/avatar-reader.png', 'Độc giả trung thành theo dõi tin tức công nghệ.', 1);

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `display_order`) VALUES
(1, 'Thời sự', 'thoi-su', 'Tin tức chính trị, xã hội, sự kiện nổi bật trong nước và quốc tế', 'globe', 1),
(2, 'Công nghệ', 'cong-nghe', 'Đổi mới sáng tạo, AI, an toàn thông tin, chuyển đổi số', 'cpu', 2),
(3, 'Kinh tế', 'kinh-te', 'Thị trường tài chính, kinh doanh, doanh nghiệp và đầu tư', 'trending-up', 3),
(4, 'Đời sống', 'doi-song', 'Văn hóa, giáo dục, sức khỏe và phong cách sống hiện đại', 'heart', 4),
(5, 'Thể thao', 'the-thao', 'Bóng đá, thể thao trong nước và thế giới sôi động', 'award', 5),
(6, 'Giải trí', 'giai-tri', 'Điện ảnh, âm nhạc, nghệ thuật và xu hướng giới trẻ', 'film', 6);

INSERT INTO `articles` (`id`, `category_id`, `author_id`, `title`, `slug`, `summary`, `content`, `thumbnail`, `views`, `is_featured`, `status`, `published_at`) VALUES
(1, 2, 2, 'Kỷ nguyên AI và Chuyển đổi số: Xu hướng công nghệ đột phá định hình tương lai', 'ky-nguyen-ai-va-chuyen-doi-so-2026', 
'Trí tuệ nhân tạo (AI) đang phát triển với tốc độ chưa từng có, mở ra những cơ hội to lớn cho các doanh nghiệp và tổ chức trong việc tự động hóa và nâng cao năng suất.', 
'<h3>Cách mạng Trí tuệ Nhân tạo và Ứng dụng thực tế</h3><p>Trong những năm gần đây, trí tuệ nhân tạo (AI) không còn là khái niệm xa vời mà đã trở thành công cụ thiết yếu trong mọi hoạt động sản xuất kinh doanh. Từ các mô hình ngôn ngữ lớn đến thị giác máy tính, AI đang giúp các doanh nghiệp tối ưu hóa chuỗi cung ứng, cải thiện dịch vụ khách hàng và phân tích dữ liệu chuyên sâu.</p><p>Tại Việt Nam, chiến lược quốc gia về nghiên cứu, phát triển và ứng dụng Trí tuệ nhân tạo đã tạo hành lang pháp lý và nguồn lực thúc đẩy các trung tâm đổi mới sáng tạo, vườn ươm công nghệ và doanh nghiệp công nghệ số Make in Viet Nam vươn tầm thế giới.</p><h3>An toàn thông tin trong thời đại số</h3><p>Bên cạnh cơ hội, AI cũng đặt ra những thách thức lớn về an toàn thông tin, bảo vệ dữ liệu người dùng và nguy cơ tấn công mạng sử dụng deepfake hay mã độc tự động hóa. Việc áp dụng các chuẩn mực bảo mật tiên tiến, cô lập mạng và giám sát tập trung là yêu cầu bắt buộc đối với mọi hệ sinh thái phần mềm hiện đại.</p>', 
'/assets/images/news-ai-tech.jpg', 1520, 1, 'published', NOW() - INTERVAL 2 HOUR),

(2, 1, 3, 'Thái Nguyên đẩy mạnh phát triển hạ tầng số và đô thị thông minh kiểu mẫu', 'thai-nguyen-day-manh-ha-tang-so', 
'Tỉnh Thái Nguyên tiếp tục khẳng định vị thế dẫn đầu trong tiến trình chuyển đổi số với hàng loạt dự án ứng dụng công nghệ thông tin vào quản trị đô thị và phục vụ người dân.', 
'<h3>Hiện đại hóa nền hành chính công</h3><p>Với mục tiêu lấy người dân và doanh nghiệp làm trung tâm phục vụ, tỉnh Thái Nguyên đã triển khai đồng bộ nền tảng tích hợp, chia sẻ dữ liệu (LGSP), kết nối với Trục liên thông văn bản quốc gia. Hơn 95% thủ tục hành chính đủ điều kiện đã được cung cấp trực tuyến toàn trình.</p><p>Trung tâm Điều hành thông minh (IOC) của tỉnh hoạt động liên tục 24/7, giám sát tình hình giao thông, trật tự xã hội và môi trường, hỗ trợ lãnh đạo các cấp đưa ra quyết định nhanh chóng và chính xác.</p>', 
'/assets/images/news-smart-city.jpg', 840, 1, 'published', NOW() - INTERVAL 5 HOUR),

(3, 3, 3, 'Kinh tế số Việt Nam bứt phá mạnh mẽ, ghi nhận mức tăng trưởng kỷ lục', 'kinh-te-so-viet-nam-but-pha-manh-me', 
'Thương mại điện tử, thanh toán không dùng tiền mặt và các dịch vụ trực tuyến đóng góp ngày càng lớn vào tỷ trọng GDP cả nước.', 
'<h3>Động lực từ người tiêu dùng trẻ</h3><p>Báo cáo thường niên về kinh tế số Đông Nam Á cho thấy Việt Nam tiếp tục là một trong những quốc gia có tốc độ tăng trưởng kinh tế số nhanh nhất khu vực. Sự bùng nổ của các ứng dụng ngân hàng số, ví điện tử và các sàn thương mại điện tử đã thay đổi thói quen tiêu dùng của hàng chục triệu người.</p><p>Các chuyên gia kinh tế dự báo, tỷ trọng kinh tế số sẽ đạt trên 25% GDP trong giai đoạn tới, khẳng định vai trò trụ cột trong chiến lược tăng trưởng bền vững.</p>', 
'/assets/images/news-economy.jpg', 610, 0, 'published', NOW() - INTERVAL 12 HOUR),

(4, 2, 2, 'Xu hướng Containerization và DevOps: Tối ưu quy trình triển khai phần mềm hiện đại', 'xu-huong-containerization-va-devops', 
'Sự kết hợp giữa Docker, Kubernetes và các công cụ CI/CD tự động hóa giúp rút ngắn chu kỳ phát hành sản phẩm và đảm bảo tính nhất quán trên mọi môi trường.', 
'<h3>Tại sao Docker và Microservices là lựa chọn hàng đầu?</h3><p>Trước đây, bài toán "chạy được trên máy tôi nhưng lỗi trên server" là nỗi ám ảnh của các lập trình viên. Nhờ công nghệ container hóa (Docker), toàn bộ mã nguồn, thư viện phụ thuộc và biến môi trường được đóng gói độc lập, mang lại sự ổn định tuyệt đối.</p><p>Khi kết hợp cùng các công cụ giám sát như Prometheus, Grafana và hệ thống quản lý log tập trung Loki, đội ngũ kỹ sư có thể phát hiện sự cố từ sớm, tự động cảnh báo và truy vết nguồn gốc tấn công trong tích tắc.</p>', 
'/assets/images/news-devops.jpg', 1290, 1, 'published', NOW() - INTERVAL 1 DAY),

(5, 4, 2, 'Bí quyết cân bằng công việc và cuộc sống dành cho giới lập trình viên', 'can-bang-cong-viec-cuoc-song-developer', 
'Làm việc trong ngành công nghệ với áp lực cao đòi hỏi các kỹ sư phần mềm phải có chế độ dinh dưỡng, thể thao và phương pháp quản lý thời gian khoa học.', 
'<h3>Chăm sóc sức khỏe thể chất và tinh thần</h3><p>Áp lực deadline và những giờ ngồi liên tục trước màn hình máy tính có thể dẫn đến nhiều vấn đề sức khỏe. Các chuyên gia khuyến nghị áp dụng quy tắc 20-20-20 cho mắt, duy trì ít nhất 30 phút vận động mỗi ngày và dành thời gian ngắt kết nối công nghệ vào cuối tuần.</p>', 
'/assets/images/news-lifestyle.jpg', 450, 0, 'published', NOW() - INTERVAL 2 DAY),

(6, 5, 3, 'Thể thao học đường: Sân chơi rèn luyện bản lĩnh và tinh thần đồng đội cho sinh viên', 'the-thao-hoc-duong-san-choi-ren-luyen', 
'Giải bóng đá và các hội thao sinh viên diễn ra sôi nổi tại các trường đại học, lan tỏa tinh thần rèn luyện thân thể và kết nối cộng đồng trẻ.', 
'<h3>Khí thế tưng bừng tại ngày hội thể thao</h3><p>Hàng ngàn sinh viên đã tham gia tranh tài ở nhiều bộ môn như bóng đá, bóng rổ, cầu lông và cờ vua. Hội thao không chỉ là nơi so tài thể thao mà còn là dịp thắt chặt tình đoàn kết, rèn luyện ý chí và lối sống tích cực.</p>', 
'/assets/images/news-sports.jpg', 380, 0, 'published', NOW() - INTERVAL 3 DAY);

INSERT INTO `comments` (`article_id`, `user_id`, `content`, `status`, `created_at`) VALUES
(1, 4, 'Bài viết phân tích rất sâu sắc về xu hướng AI hiện nay! Tôi rất quan tâm đến giải pháp bảo mật khi triển khai các hệ thống lớn.', 'approved', NOW() - INTERVAL 1 HOUR),
(1, 3, 'Đúng vậy, bảo mật và quan sát hệ thống (Observability) là hai yếu tố sống còn!', 'approved', NOW() - INTERVAL 45 MINUTE),
(4, 4, 'DevOps kết hợp bộ đôi Prometheus + Grafana quả thực là tiêu chuẩn vàng cho các kỹ sư hệ thống.', 'approved', NOW() - INTERVAL 10 HOUR);

INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`, `user_agent`, `details`) VALUES
(1, 'SYSTEM_INIT', '127.0.0.1', 'System/1.0', 'Khởi tạo cơ sở dữ liệu hệ thống tin tức news_portal thành công.'),
(1, 'LOGIN_SUCCESS', '192.168.1.10', 'Mozilla/5.0 Chrome/130.0', 'Quản trị viên đăng nhập hệ thống.');

-- ------------------------------------------------------------------------------
-- CẤU HÌNH TÀI KHOẢN GIÁM SÁT & NGUYÊN TẮC ĐẶC QUYỀN TỐI THIỂU (LEAST PRIVILEGE)
-- ------------------------------------------------------------------------------
-- Tạo người dùng exporter cho mysqld-exporter (chỉ quyền giám sát read-only)
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY 'ExporterSecure20Char!2' WITH MAX_USER_CONNECTIONS 3;
GRANT PROCESS, REPLICATION CLIENT, SELECT ON performance_schema.* TO 'exporter'@'%';

-- Cấp quyền tối thiểu cho tài khoản ứng dụng web news_user
-- Chỉ cho phép SELECT, INSERT, UPDATE, DELETE trên database news_portal
-- Không cấp quyền DROP, ALTER, CREATE USER để chống leo thang đặc quyền
GRANT SELECT, INSERT, UPDATE, DELETE ON `news_portal`.* TO 'news_user'@'%';

FLUSH PRIVILEGES;
