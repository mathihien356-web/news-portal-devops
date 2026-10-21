<?php
// ==============================================================================
// QUẢN LÝ DANH SÁCH BÀI VIẾT (CRUD ARTICLES)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'author']);
$user = current_user();
$db = get_db_connection();

// Xử lý Xóa bài viết
if (isset($_GET['delete'])) {
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        die("CSRF Token không hợp lệ.");
    }
    $deleteId = (int)$_GET['delete'];
    
    // Kiểm tra quyền: admin xóa được tất cả, author chỉ xóa bài của mình
    if ($user['role'] === 'admin') {
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$deleteId]);
    } else {
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ? AND author_id = ?");
        $stmt->execute([$deleteId, $user['id']]);
    }

    system_log('AUDIT', 'WARN', 'ARTICLE_DELETED', "Xóa bài viết ID #{$deleteId}", $user['id']);
    header("Location: /admin/articles.php?deleted=1");
    exit;
}

// Lấy danh sách bài viết
$sql = "
    SELECT a.*, c.name AS category_name, u.full_name AS author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.author_id = u.id
";
$params = [];

if ($user['role'] !== 'admin') {
    $sql .= " WHERE a.author_id = ? ";
    $params[] = $user['id'];
}

$sql .= " ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

$pageTitle = 'Quản Lý Bài Viết';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">Quản Lý Nội Dung</div>
        <a href="/admin/index.php" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Tổng Quan
        </a>
        <a href="/admin/articles.php" class="admin-nav-item active">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Bài Viết Tin Tức
        </a>
        <a href="/admin/article_edit.php" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Viết Bài Mới
        </a>
        <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-sidebar-title" style="margin-top: 16px;">Hệ Thống & Bảo Mật</div>
            <a href="/admin/categories.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line></svg>
                Chuyên Mục Tin
            </a>
            <a href="/admin/users.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                Người Dùng & Phân Quyền
            </a>
            <a href="/admin/audit_logs.php" class="admin-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Nhật Ký An Toàn & Audit
            </a>
        <?php endif; ?>
    </aside>

    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title">Quản Lý Danh Sách Bài Viết</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Tổng số: <?= count($articles) ?> bài viết trong hệ thống</p>
            </div>
            <a href="/admin/article_edit.php" class="btn btn-primary">
                + Viết Bài Viết Mới
            </a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert-message alert-success">Đã xóa bài viết thành công khỏi cơ sở dữ liệu!</div>
        <?php endif; ?>

        <div class="table-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Tiêu đề bài viết</th>
                        <th>Chuyên mục</th>
                        <th>Tác giả</th>
                        <th>Lượt đọc</th>
                        <th>Trạng thái</th>
                        <th>Ngày xuất bản</th>
                        <th style="width: 140px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $art): ?>
                        <tr>
                            <td>#<?= $art['id'] ?></td>
                            <td>
                                <a href="/article.php?slug=<?= e($art['slug']) ?>" target="_blank" style="font-weight: 600; color: var(--text-primary);">
                                    <?= e($art['title']) ?>
                                </a>
                                <?php if ($art['is_featured']): ?>
                                    <span style="font-size: 0.7rem; background: #f43f5e; color: #fff; padding: 1px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700;">NỔI BẬT</span>
                                <?php endif; ?>
                            </td>
                            <td><span style="color: var(--primary); font-weight: 500;"><?= e($art['category_name']) ?></span></td>
                            <td><?= e($art['author_name']) ?></td>
                            <td><?= number_format($art['views']) ?></td>
                            <td>
                                <span class="status-badge badge-<?= e($art['status']) ?>">
                                    <?= strtoupper(e($art['status'])) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($art['published_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/admin/article_edit.php?id=<?= $art['id'] ?>" class="btn btn-outline btn-sm">Sửa</a>
                                    <a href="/admin/articles.php?delete=<?= $art['id'] ?>&token=<?= generate_csrf_token() ?>" 
                                       class="btn btn-sm" style="background: rgba(244, 63, 94, 0.2); color: #f87171;"
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này không?');">
                                        Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
