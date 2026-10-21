<?php
// ==============================================================================
// CẤU HÌNH KẾT NỐI CƠ SỞ DỮ LIỆU MYSQL BẰNG PDO
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

define('DB_HOST', getenv('MYSQL_HOST') ?: 'db');
define('DB_PORT', getenv('MYSQL_PORT') ?: '3306');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'news_portal');
define('DB_USER', getenv('MYSQL_USER') ?: 'news_user');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: 'NewsUserSecure20Char!1');

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Ghi log lỗi cấu trúc ra STDERR phục vụ Promtail -> Loki
            error_log(sprintf(
                "[DATABASE] [ERROR] DB_CONNECT_FAILED host=%s db=%s message=%s",
                DB_HOST, DB_NAME, $e->getMessage()
            ));
            http_response_code(500);
            die("Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra lại dịch vụ MySQL.");
        }
    }
    return $pdo;
}
