<?php
// ==============================================================================
// PROMETHEUS METRICS ENDPOINT CHO ỨNG DỤNG WEB TIN TỨC
// Cung cấp số liệu thời gian thực theo định dạng chuẩn Time-series cho Prometheus
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = get_db_connection();

    // 1. Thống kê bài viết theo trạng thái
    $stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM articles GROUP BY status");
    $articlesByStatus = ['published' => 0, 'draft' => 0, 'archived' => 0];
    while ($row = $stmt->fetch()) {
        $articlesByStatus[$row['status']] = (int)$row['cnt'];
    }

    // 2. Tổng số lượt xem tin tức
    $stmt = $db->query("SELECT SUM(views) AS total_views FROM articles");
    $totalViews = (int)($stmt->fetch()['total_views'] ?? 0);

    // 3. Thống kê người dùng theo vai trò
    $stmt = $db->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role");
    $usersByRole = ['admin' => 0, 'author' => 0, 'reader' => 0];
    while ($row = $stmt->fetch()) {
        $usersByRole[$row['role']] = (int)$row['cnt'];
    }

    // 4. Tổng số bình luận
    $stmt = $db->query("SELECT COUNT(*) AS total_comments FROM comments");
    $totalComments = (int)($stmt->fetch()['total_comments'] ?? 0);

    // 5. Tổng số bản ghi audit logs và số lần đăng nhập thất bại
    $stmt = $db->query("SELECT COUNT(*) AS total_logs FROM audit_logs");
    $totalLogs = (int)($stmt->fetch()['total_logs'] ?? 0);

    $stmt = $db->query("SELECT COUNT(*) AS failed_logins FROM audit_logs WHERE action = 'LOGIN_FAILED'");
    $failedLogins = (int)($stmt->fetch()['failed_logins'] ?? 0);

    // In theo chuẩn OpenMetrics / Prometheus text representation
    echo "# HELP news_portal_articles_total Tong so bai viet theo trang thai\n";
    echo "# TYPE news_portal_articles_total gauge\n";
    foreach ($articlesByStatus as $status => $count) {
        echo sprintf("news_portal_articles_total{status=\"%s\"} %d\n", $status, $count);
    }

    echo "\n# HELP news_portal_views_total Tong so luot xem tat ca bai viet\n";
    echo "# TYPE news_portal_views_total counter\n";
    echo sprintf("news_portal_views_total %d\n", $totalViews);

    echo "\n# HELP news_portal_users_total Tong so nguoi dung he thong theo vai tro\n";
    echo "# TYPE news_portal_users_total gauge\n";
    foreach ($usersByRole as $role => $count) {
        echo sprintf("news_portal_users_total{role=\"%s\"} %d\n", $role, $count);
    }

    echo "\n# HELP news_portal_comments_total Tong so binh luan cua doc gia\n";
    echo "# TYPE news_portal_comments_total counter\n";
    echo sprintf("news_portal_comments_total %d\n", $totalComments);

    echo "\n# HELP news_portal_audit_logs_total Tong so ban ghi nhat ky an toan\n";
    echo "# TYPE news_portal_audit_logs_total counter\n";
    echo sprintf("news_portal_audit_logs_total %d\n", $totalLogs);

    echo "\n# HELP news_portal_failed_logins_total So lan dang nhap sai ghi nhan boi ung dung\n";
    echo "# TYPE news_portal_failed_logins_total counter\n";
    echo sprintf("news_portal_failed_logins_total %d\n", $failedLogins);

    echo "\n# HELP news_portal_app_info Thong tin phien ban ung dung\n";
    echo "# TYPE news_portal_app_info gauge\n";
    echo "news_portal_app_info{version=\"1.0.0\",author=\"MaThiHien\",student_id=\"DTC245200948\"} 1\n";

} catch (Exception $e) {
    echo "# Lỗi kết nối cơ sở dữ liệu khi thu thập metrics: " . $e->getMessage() . "\n";
}
