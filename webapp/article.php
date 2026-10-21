<?php
// ==============================================================================
// CHI TIẾT BÀI VIẾT TIN TỨC & BÌNH LUẬN ĐỘC GIẢ
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$slug = $_GET['slug'] ?? '';
$id = $_GET['id'] ?? 0;

$db = get_db_connection();

// 1. Tìm bài viết theo slug hoặc ID
if ($slug) {
    $stmt = $db->prepare("
        SELECT a.*, c.name AS category_name, c.slug AS category_slug, 
               u.full_name AS author_name, u.avatar AS author_avatar, u.bio AS author_bio
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.slug = ? AND a.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
} else {
    $stmt = $db->prepare("
        SELECT a.*, c.name AS category_name, c.slug AS category_slug, 
               u.full_name AS author_name, u.avatar AS author_avatar, u.bio AS author_bio
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.id = ? AND a.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$id]);
}

$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle = '404 Không tìm thấy bài viết';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 80px 0; text-align: center;">';
    echo '<h1 style="font-size: 3rem; margin-bottom: 16px;">404</h1>';
    echo '<p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 24px;">Bài viết bạn tìm kiếm không tồn tại hoặc đã được gỡ bỏ.</p>';
    echo '<a href="/" class="btn btn-primary">Quay lại Trang Chủ</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2. Tăng số lượt xem bài viết
$updateStmt = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
$updateStmt->execute([$article['id']]);

// 3. Lấy danh sách bình luận đã duyệt
$stmt = $db->prepare("
    SELECT c.*, u.full_name AS user_name, u.avatar AS user_avatar
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
");
$stmt->execute([$article['id']]);
$comments = $stmt->fetchAll();

// 4. Lấy 3 bài viết liên quan cùng chuyên mục
$stmt = $db->prepare("
    SELECT a.*, c.name AS category_name, c.slug AS category_slug
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? AND a.id != ? AND a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT 3
");
$stmt->execute([$article['category_id'], $article['id']]);
$relatedArticles = $stmt->fetchAll();

$pageTitle = $article['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 30px 0;">
    <div class="main-layout">
        <div class="content-primary">
            <article class="article-detail-wrap">
                <header class="article-header">
                    <!-- Đường dẫn Breadcrumbs -->
                    <nav class="article-breadcrumb">
                        <a href="/">Trang chủ</a> &rsaquo;
                        <a href="/category.php?slug=<?= e($article['category_slug']) ?>" style="color: var(--primary);">
                            <?= e($article['category_name']) ?>
                        </a> &rsaquo;
                        <span style="color: var(--text-muted);"><?= e(mb_strimwidth($article['title'], 0, 40, '...')) ?></span>
                    </nav>

                    <h1 class="article-main-title"><?= e($article['title']) ?></h1>

                    <!-- Tác giả & Ngày đăng -->
                    <div class="author-meta-box">
                        <div class="author-info">
                            <div class="user-avatar-mini" style="width: 44px; height: 44px; font-size: 1.1rem; background: #0284c7; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                <?= strtoupper(substr($article['author_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="author-name"><?= e($article['author_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    Xuất bản: <?= date('d/m/Y H:i', strtotime($article['published_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; gap: 16px;">
                            <span>👁️ <?= number_format($article['views'] + 1) ?> lượt xem</span>
                            <span>💬 <?= count($comments) ?> bình luận</span>
                        </div>
                    </div>
                </header>

                <!-- Tóm tắt bài viết -->
                <div class="article-summary-lead">
                    <?= e($article['summary']) ?>
                </div>

                <!-- Ảnh bìa bài viết nếu có -->
                <?php if ($article['thumbnail']): ?>
                    <div style="margin-bottom: 28px; border-radius: var(--radius-md); overflow: hidden;">
                        <img src="<?= e($article['thumbnail']) ?>" alt="<?= e($article['title']) ?>" style="width: 100%; max-height: 480px; object-fit: cover;"
                             onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1000&auto=format&fit=crop&q=80'">
                    </div>
                <?php endif; ?>

                <!-- Nội dung chi tiết bài viết -->
                <div class="article-content">
                    <?= $article['content'] /* Cho phép HTML nội dung đã kiểm duyệt của biên tập viên */ ?>
                </div>

                <!-- Tác giả Bio Box -->
                <div style="margin-top: 40px; padding: 20px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; gap: 16px; align-items: center;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #0284c7; color: #fff; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        <?= strtoupper(substr($article['author_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <strong style="font-size: 1.05rem; color: var(--text-primary);"><?= e($article['author_name']) ?></strong>
                        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-top: 4px;">
                            <?= e($article['author_bio'] ?: 'Biên tập viên Ban Chuyên đề & Phóng viên thời sự Cổng Thông Tin.') ?>
                        </p>
                    </div>
                </div>
            </article>

            <!-- Khu vực Bình luận độc giả -->
            <section class="comments-section">
                <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Ý Kiến Độc Giả (<?= count($comments) ?>)
                </h3>

                <div id="commentAlert" style="display: none;"></div>

                <?php if (is_logged_in()): ?>
                    <form id="commentForm" style="margin-bottom: 30px;">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label class="form-label">Chia sẻ quan điểm của bạn với tư cách: <strong><?= e($currentUser['full_name']) ?></strong></label>
                            <textarea id="commentContent" name="content" class="form-textarea" rows="3" placeholder="Viết bình luận văn minh, tôn trọng người khác..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                    </form>
                <?php else: ?>
                    <div style="padding: 16px; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 24px; text-align: center;">
                        <span style="color: var(--text-secondary); font-size: 0.95rem;">Bạn cần đăng nhập để gửi ý kiến phản hồi về bài viết này.</span>
                        <div style="margin-top: 10px;">
                            <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary" style="padding: 6px 16px; font-size: 0.85rem;">Đăng nhập ngay</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="commentsContainer">
                    <?php if (empty($comments)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 20px 0;">Chưa có bình luận nào. Hãy là người đầu tiên đưa ra nhận định!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $cm): ?>
                            <div class="comment-card">
                                <div class="comment-header">
                                    <strong style="color: var(--primary); font-size: 0.92rem;"><?= e($cm['user_name']) ?></strong>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d/m/Y H:i', strtotime($cm['created_at'])) ?></span>
                                </div>
                                <p style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.5;"><?= e($cm['content']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Cột Phải: Bài viết cùng chuyên mục -->
        <aside class="sidebar">
            <div class="widget">
                <h3 class="widget-title">Tin Cùng Chuyên Mục</h3>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($relatedArticles as $rel): ?>
                        <a href="/article.php?slug=<?= e($rel['slug']) ?>" class="side-card" style="padding: 12px;">
                            <img src="<?= e($rel['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                                 alt="<?= e($rel['title']) ?>" class="side-thumb" style="width: 80px; height: 60px;"
                                 onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=200&auto=format&fit=crop&q=80'">
                            <div class="side-card-content">
                                <h4 class="side-title" style="font-size: 0.88rem;"><?= e($rel['title']) ?></h4>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    🕒 <?= date('d/m/Y', strtotime($rel['published_at'])) ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
