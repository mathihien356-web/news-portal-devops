<?php
// ==============================================================================
// TRANG DANH SÁCH BÀI VIẾT THEO CHUYÊN MỤC
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';

$slug = $_GET['slug'] ?? '';
$db = get_db_connection();

// 1. Tìm thông tin chuyên mục
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: /");
    exit;
}

// 2. Lấy danh sách bài viết thuộc chuyên mục này
$stmt = $db->prepare("
    SELECT a.*, u.full_name AS author_name
    FROM articles a
    JOIN users u ON a.author_id = u.id
    WHERE a.category_id = ? AND a.status = 'published'
    ORDER BY a.published_at DESC
");
$stmt->execute([$category['id']]);
$articles = $stmt->fetchAll();

$pageTitle = 'Chuyên mục ' . $category['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 30px 0;">
    <div style="margin-bottom: 30px; padding: 24px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <nav class="article-breadcrumb">
            <a href="/">Trang chủ</a> &rsaquo;
            <span style="color: var(--primary); font-weight: 600;"><?= e($category['name']) ?></span>
        </nav>
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 8px;"><?= e($category['name']) ?></h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;"><?= e($category['description']) ?></p>
    </div>

    <?php if (empty($articles)): ?>
        <div style="text-align: center; padding: 60px 0; color: var(--text-muted);">
            <p style="font-size: 1.1rem;">Chưa có bài viết nào trong chuyên mục này.</p>
        </div>
    <?php else: ?>
        <div class="article-cards-grid">
            <?php foreach ($articles as $art): ?>
                <article class="article-card">
                    <a href="/article.php?slug=<?= e($art['slug']) ?>" class="card-thumb-wrap">
                        <img src="<?= e($art['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                             alt="<?= e($art['title']) ?>" class="card-thumb"
                             onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600&auto=format&fit=crop&q=80'">
                        <span class="card-category-badge"><?= e($category['name']) ?></span>
                    </a>
                    <div class="card-body">
                        <h3 class="card-title">
                            <a href="/article.php?slug=<?= e($art['slug']) ?>"><?= e($art['title']) ?></a>
                        </h3>
                        <p class="card-summary"><?= e($art['summary']) ?></p>
                        <div class="card-footer">
                            <span>✍️ <?= e($art['author_name']) ?></span>
                            <span>🕒 <?= date('d/m/Y', strtotime($art['published_at'])) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
