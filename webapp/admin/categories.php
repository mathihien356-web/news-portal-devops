<?php
// ==============================================================================
// QUẢN LÝ CHUYÊN MỤC TIN TỨC (CATEGORIES CRUD)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$user = current_user();
$db = get_db_connection();

$error = '';
$success = '';

// 1. Thêm chuyên mục mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token bảo vệ CSRF không hợp lệ.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);

        if (empty($name) || empty($slug)) {
            $error = 'Tên và đường dẫn slug chuyên mục không được để trống.';
        } else {
            $chk = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $chk->execute([$slug]);
            if ($chk->fetch()) {
                $error = 'Slug chuyên mục này đã tồn tại.';
            } else {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, description, display_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $desc, $order]);
                system_log('AUDIT', 'INFO', 'CATEGORY_CREATED', "Tạo chuyên mục mới: {$name}", $user['id']);
                $success = 'Thêm chuyên mục thành công!';
            }
        }
    }
}

// 2. Xóa chuyên mục
if (isset($_GET['delete'])) {
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        die("CSRF Token không hợp lệ.");
    }
    $delId = (int)$_GET['delete'];
    // Kiểm tra số lượng bài viết
    $cnt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
    $cnt->execute([$delId]);
    if ($cnt->fetchColumn() > 0) {
        $error = 'Không thể xóa chuyên mục đang có bài viết liên kết!';
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delId]);
        system_log('AUDIT', 'WARN', 'CATEGORY_DELETED', "Xóa chuyên mục ID #{$delId}", $user['id']);
        $success = 'Đã xóa chuyên mục thành công.';
    }
}

// Lấy danh sách chuyên mục
$categories = $db->query("
    SELECT c.*, COUNT(a.id) AS article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC
")->fetchAll();

$pageTitle = 'Quản Lý Chuyên Mục';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">Quản Lý Nội Dung</div>
        <a href="/admin/index.php" class="admin-nav-item">Tổng Quan</a>
        <a href="/admin/articles.php" class="admin-nav-item">Bài Viết Tin Tức</a>
        <a href="/admin/article_edit.php" class="admin-nav-item">Viết Bài Mới</a>
        <div class="admin-sidebar-title" style="margin-top: 16px;">Hệ Thống & Bảo Mật</div>
        <a href="/admin/categories.php" class="admin-nav-item active">Chuyên Mục Tin</a>
        <a href="/admin/users.php" class="admin-nav-item">Người Dùng & Phân Quyền</a>
        <a href="/admin/audit_logs.php" class="admin-nav-item">Nhật Ký An Toàn & Audit</a>
    </aside>

    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title">Quản Lý Chuyên Mục Tin Tức</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Cấu trúc phân loại các bài báo điện tử</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-message alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 28px;">
            <!-- Form thêm chuyên mục mới -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; height: fit-content;">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 18px;">Thêm Chuyên Mục Mới</h3>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="add_category" value="1">

                    <div class="form-group">
                        <label class="form-label" for="cat_name">Tên chuyên mục (*)</label>
                        <input type="text" id="cat_name" name="name" class="form-input" required placeholder="Ví dụ: Khoa học & Vũ trụ">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cat_slug">Slug đường dẫn (*)</label>
                        <input type="text" id="cat_slug" name="slug" class="form-input" required placeholder="khoa-hoc-vu-tru">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cat_desc">Mô tả tóm tắt</label>
                        <textarea id="cat_desc" name="description" class="form-textarea" rows="2" placeholder="Mô tả chủ đề..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cat_order">Thứ tự hiển thị</label>
                        <input type="number" id="cat_order" name="display_order" class="form-input" value="10">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Tạo Chuyên Mục</button>
                </form>
            </div>

            <!-- Bảng danh sách chuyên mục -->
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Thứ tự</th>
                            <th>Tên Chuyên Mục</th>
                            <th>Slug</th>
                            <th>Số bài viết</th>
                            <th style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--text-muted);"><?= $cat['display_order'] ?></td>
                                <td style="font-weight: 600; color: var(--text-primary);"><?= e($cat['name']) ?></td>
                                <td><code><?= e($cat['slug']) ?></code></td>
                                <td><span style="background: var(--bg-surface-elevated); padding: 3px 10px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.8rem;"><?= $cat['article_count'] ?></span></td>
                                <td>
                                    <?php if ($cat['article_count'] == 0): ?>
                                        <a href="/admin/categories.php?delete=<?= $cat['id'] ?>&token=<?= generate_csrf_token() ?>" 
                                           class="btn btn-sm" style="background: rgba(244, 63, 94, 0.2); color: #f87171;"
                                           onclick="return confirm('Bạn chắc chắn muốn xóa chuyên mục này?');">Xóa</a>
                                    <?php else: ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Đang dùng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
