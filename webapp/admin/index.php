<?php
// ==============================================================================
// BÀN QUẢN TRỊ TỔNG QUAN - ADMIN DASHBOARD
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Chỉ cho phép admin hoặc author truy cập
require_role(['admin', 'author']);
$user = current_user();
$db = get_db_connection();

// 1. Thống kê nhanh
$totalArticles = $db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$totalViews    = $db->query("SELECT SUM(views) FROM articles")->fetchColumn() ?: 0;
$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalComments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// 2. Lấy 5 bài viết gần đây
$stmt = $db->prepare("
    SELECT a.*, c.name AS category_name, u.full_name AS author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.author_id = u.id
    " . ($user['role'] !== 'admin' ? "WHERE a.author_id = ?" : "") . "
    ORDER BY a.created_at DESC
    LIMIT 5
");
if ($user['role'] !== 'admin') {
    $stmt->execute([$user['id']]);
} else {
    $stmt->execute();
}
$recentArticles = $stmt->fetchAll();

// 3. Lấy 5 nhật ký bảo mật gần nhất (chỉ admin)
$recentLogs = [];
if ($user['role'] === 'admin') {
    $stmt = $db->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5");
    $recentLogs = $stmt->fetchAll();
}

$pageTitle = 'Bàn Quản Trị Hệ Thống';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <!-- Sidebar điều hướng Quản trị -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">Quản Lý Nội Dung</div>
        <a href="/admin/index.php" class="admin-nav-item active">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Tổng Quan
        </a>
        <a href="/admin/articles.php" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Bài Viết Tin Tức
        </a>
        <a href="/admin/article_edit.php" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Viết Bài Mới
        </a>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-sidebar-title" style="margin-top: 16px;">Hệ Thống & Bảo Mật</div>
            <a href="/admin/categories.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                Chuyên Mục Tin
            </a>
            <a href="/admin/users.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Người Dùng & Phân Quyền
            </a>
            <a href="/admin/audit_logs.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Nhật Ký An Toàn & Audit
            </a>
            <a href="/grafana/" target="_blank" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Grafana Giám Sát &nearr;
            </a>
        <?php endif; ?>
    </aside>

    <!-- Khu vực nội dung chính của Bàn Quản trị -->
    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title">Bàn Quản Trị Hệ Thống</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                    Xin chào <strong><?= e($user['full_name']) ?></strong> (Vai trò: <span style="color: var(--primary); font-weight:700;"><?= strtoupper(e($user['role'])) ?></span>)
                </p>
            </div>
            <div>
                <a href="/admin/article_edit.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Soạn Bài Viết Mới
                </a>
            </div>
        </div>

        <!-- Thẻ số liệu thống kê (Stat Cards) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($totalArticles) ?></div>
                    <div class="stat-label">Tổng bài viết</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($totalViews) ?></div>
                    <div class="stat-label">Tổng lượt đọc tin</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($totalUsers) ?></div>
                    <div class="stat-label">Tài khoản người dùng</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-amber">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($totalComments) ?></div>
                    <div class="stat-label">Bình luận độc giả</div>
                </div>
            </div>
        </div>

        <!-- Bảng bài viết gần đây -->
        <div class="table-card">
            <div class="table-card-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Bài Viết Gần Đây</h3>
                <a href="/admin/articles.php" style="color: var(--primary); font-size: 0.85rem; font-weight: 600;">Xem tất cả &rarr;</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tiêu đề bài viết</th>
                        <th>Chuyên mục</th>
                        <th>Tác giả</th>
                        <th>Lượt xem</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentArticles as $art): ?>
                        <tr>
                            <td>
                                <a href="/article.php?slug=<?= e($art['slug']) ?>" target="_blank" style="font-weight: 600;">
                                    <?= e(mb_strimwidth($art['title'], 0, 50, '...')) ?>
                                </a>
                            </td>
                            <td><span style="color: var(--text-secondary);"><?= e($art['category_name']) ?></span></td>
                            <td><?= e($art['author_name']) ?></td>
                            <td><?= number_format($art['views']) ?></td>
                            <td>
                                <span class="status-badge badge-<?= e($art['status']) ?>">
                                    <?= strtoupper(e($art['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/admin/article_edit.php?id=<?= $art['id'] ?>" class="btn btn-outline btn-sm">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bảng Nhật ký sự kiện bảo mật (Dành cho Quản trị viên) -->
        <?php if ($user['role'] === 'admin'): ?>
            <div class="table-card">
                <div class="table-card-header">
                    <h3 style="font-size: 1.1rem; font-weight: 700; display:flex; align-items:center; gap:8px;">
                        <span style="color: #f43f5e;">🛡️</span> Sự Kiện An Toàn Hệ Thống Gần Nhất
                    </h3>
                    <a href="/admin/audit_logs.php" style="color: var(--primary); font-size: 0.85rem; font-weight: 600;">Xem toàn bộ Audit Log &rarr;</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Hành động (Action)</th>
                            <th>Địa chỉ IP</th>
                            <th>Chi tiết sự kiện</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?= e($log['created_at']) ?></td>
                                <td>
                                    <code style="background: var(--bg-surface-elevated); padding: 2px 6px; border-radius: 4px; font-size: 0.82rem; color: <?= strpos($log['action'], 'FAILED') !== false ? '#f43f5e' : '#38bdf8' ?>;">
                                        <?= e($log['action']) ?>
                                    </code>
                                </td>
                                <td><code><?= e($log['ip_address']) ?></code></td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;"><?= e($log['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
