<?php
// ==============================================================================
// TRANG TÌM KIẾM BÀI VIẾT TIN TỨC
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';

$query = trim($_GET['q'] ?? '');
$articles = [];
$db = get_db_connection();

if ($query !== '') {
    $searchParam = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT a.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.status = 'published' AND (a.title LIKE ? OR a.summary LIKE ? OR a.content LIKE ?)
        ORDER BY a.published_at DESC
        LIMIT 20
    ");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $articles = $stmt->fetchAll();
}

$pageTitle = 'Tìm kiếm: ' . $query;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 30px 0;">
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;">Kết quả tìm kiếm</h1>
        <p style="color: var(--text-secondary);">
            Tìm thấy <strong><?= count($articles) ?></strong> kết quả phù hợp với từ khóa: "<span style="color: var(--primary); font-weight: 600;"><?= e($query) ?></span>"
        </p>
    </div>

    <?php if (empty($articles)): ?>
        <div style="text-align: center; padding: 60px 0; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 12px;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <p style="color: var(--text-muted); font-size: 1.05rem;">Không tìm thấy bài viết nào khớp với từ khóa của bạn.</p>
            <a href="/" class="btn btn-outline" style="margin-top: 16px;">Về trang chủ</a>
        </div>
    <?php else: ?>
        <div class="article-cards-grid">
            <?php foreach ($articles as $art): ?>
                <article class="article-card">
                    <a href="/article.php?slug=<?= e($art['slug']) ?>" class="card-thumb-wrap">
                        <img src="<?= e($art['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                             alt="<?= e($art['title']) ?>" class="card-thumb"
                             onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600&auto=format&fit=crop&q=80'">
                        <span class="card-category-badge"><?= e($art['category_name']) ?></span>
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
