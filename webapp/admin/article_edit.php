<?php
// ==============================================================================
// SOẠN THẢO VÀ CHỈNH SỬA BÀI VIẾT (ARTICLE EDITOR)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'author']);
$user = current_user();
$db = get_db_connection();

$id = (int)($_GET['id'] ?? 0);
$isEditing = $id > 0;

$error = '';
$success = '';

// Lấy danh mục cho dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY display_order ASC")->fetchAll();

// Nếu chỉnh sửa, tải bài viết hiện tại
$article = [
    'title'       => '',
    'slug'        => '',
    'category_id' => $categories[0]['id'] ?? 1,
    'summary'     => '',
    'content'     => '',
    'thumbnail'   => '',
    'status'      => 'published',
    'is_featured' => 0
];

if ($isEditing) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        die("Bài viết không tồn tại.");
    }
    if ($user['role'] !== 'admin' && $existing['author_id'] != $user['id']) {
        die("Bạn không có quyền chỉnh sửa bài viết của tác giả khác.");
    }
    $article = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Mã bảo vệ CSRF không hợp lệ.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $slug        = trim($_POST['slug'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $summary     = trim($_POST['summary'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $thumbnail   = trim($_POST['thumbnail'] ?? '');
        $status      = in_array($_POST['status'] ?? '', ['draft', 'published', 'archived']) ? $_POST['status'] : 'published';
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($title) || empty($slug) || empty($summary) || empty($content)) {
            $error = 'Vui lòng điền đầy đủ Tiêu đề, Đường dẫn thân thiện (slug), Tóm tắt và Nội dung bài viết.';
        } else {
            if ($isEditing) {
                // Kiểm tra trùng slug với bài khác
                $chk = $db->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
                $chk->execute([$slug, $id]);
                if ($chk->fetch()) {
                    $error = 'Đường dẫn (slug) này đã được sử dụng bởi bài viết khác.';
                } else {
                    $stmt = $db->prepare("
                        UPDATE articles 
                        SET title = ?, slug = ?, category_id = ?, summary = ?, content = ?, thumbnail = ?, status = ?, is_featured = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $slug, $categoryId, $summary, $content, $thumbnail, $status, $isFeatured, $id]);
                    system_log('AUDIT', 'INFO', 'ARTICLE_UPDATED', "Cập nhật bài viết #{$id}: {$title}", $user['id']);
                    $success = 'Đã lưu thay đổi bài viết thành công!';
                    $article = array_merge($article, $_POST);
                }
            } else {
                // Tạo bài mới
                $chk = $db->prepare("SELECT id FROM articles WHERE slug = ?");
                $chk->execute([$slug]);
                if ($chk->fetch()) {
                    $error = 'Đường dẫn (slug) này đã tồn tại, vui lòng chọn tiêu đề khác.';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO articles (title, slug, category_id, author_id, summary, content, thumbnail, status, is_featured, published_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$title, $slug, $categoryId, $user['id'], $summary, $content, $thumbnail, $status, $isFeatured]);
                    $newId = $db->lastInsertId();
                    system_log('AUDIT', 'INFO', 'ARTICLE_CREATED', "Tạo bài viết mới #{$newId}: {$title}", $user['id']);
                    header("Location: /admin/articles.php?created=1");
                    exit;
                }
            }
        }
    }
}

$pageTitle = $isEditing ? 'Chỉnh Sửa Bài Viết #' . $id : 'Soạn Bài Viết Mới';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">Quản Lý Nội Dung</div>
        <a href="/admin/index.php" class="admin-nav-item">Tổng Quan</a>
        <a href="/admin/articles.php" class="admin-nav-item">Bài Viết Tin Tức</a>
        <a href="/admin/article_edit.php" class="admin-nav-item active"><?= $isEditing ? 'Sửa Bài' : 'Viết Bài Mới' ?></a>
    </aside>

    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title"><?= $isEditing ? 'Chỉnh Sửa Bài Viết' : 'Soạn Thảo Bài Viết Mới' ?></h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Điền đầy đủ thông tin xuất bản bài báo số</p>
            </div>
            <a href="/admin/articles.php" class="btn btn-outline">&larr; Quay Lại Danh Sách</a>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-message alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 32px; box-shadow: var(--shadow-sm);">
            <form method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="articleTitleInput">Tiêu đề bài viết (*)</label>
                    <input type="text" id="articleTitleInput" name="title" class="form-input" 
                           value="<?= e($article['title']) ?>" required placeholder="Nhập tiêu đề hấp dẫn...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="articleSlugInput">Đường dẫn thân thiện (Slug - Tự động tạo hoặc tùy chỉnh)</label>
                    <input type="text" id="articleSlugInput" name="slug" class="form-input" 
                           value="<?= e($article['slug']) ?>" required placeholder="duong-dan-bai-viet">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label" for="category_id">Chuyên mục tin (*)</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $article['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Trạng thái xuất bản</label>
                        <select id="status" name="status" class="form-select">
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Xuất bản ngay (Published)</option>
                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Bản nháp (Draft)</option>
                            <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>Lưu trữ (Archived)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="thumbnail">Đường dẫn ảnh đại diện (Thumbnail URL)</label>
                    <input type="text" id="thumbnail" name="thumbnail" class="form-input" 
                           value="<?= e($article['thumbnail']) ?>" placeholder="https://... hoặc /assets/images/...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="summary">Đoạn tóm tắt / Sapo bài viết (*)</label>
                    <textarea id="summary" name="summary" class="form-textarea" rows="3" required placeholder="Tóm tắt ngắn gọn nội dung cốt lõi của bài báo..."><?= e($article['summary']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="content">Nội dung chi tiết bài viết (Hỗ trợ HTML định dạng) (*)</label>
                    <textarea id="content" name="content" class="form-textarea" rows="12" required style="font-family: monospace; font-size: 0.95rem; line-height: 1.6;" placeholder="Nội dung bài viết..."><?= e($article['content']) ?></textarea>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $article['is_featured'] ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="is_featured" style="font-weight: 600; cursor: pointer;">Đánh dấu là Bài viết Nổi Bật / Tiêu Điểm (Hiển thị Hero Slider trên Trang chủ)</label>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 28px;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                        <?= $isEditing ? 'Cập Nhật Bài Viết' : 'Lưu & Xuất Bản' ?>
                    </button>
                    <a href="/admin/articles.php" class="btn btn-outline" style="padding: 12px 24px;">Hủy Bỏ</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
