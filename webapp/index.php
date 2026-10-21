<?php
// ==============================================================================
// TRANG CHỦ CỔNG THÔNG TIN ĐIỆN TỬ (NEWS PORTAL HOMEPAGE)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';
$pageTitle = 'Trang Chủ - Cổng Thông Tin Điện Tử 24/7';

$db = get_db_connection();

// 1. Lấy bài viết tiêu điểm (Hero Featured Story)
$stmt = $db->query("
    SELECT a.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name, u.avatar AS author_avatar
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.author_id = u.id
    WHERE a.status = 'published' AND a.is_featured = 1
    ORDER BY a.published_at DESC
    LIMIT 1
");
$heroArticle = $stmt->fetch();

// Nếu không có bài featured thì lấy bài mới nhất làm hero
if (!$heroArticle) {
    $stmt = $db->query("
        SELECT a.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name, u.avatar AS author_avatar
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.status = 'published'
        ORDER BY a.published_at DESC
        LIMIT 1
    ");
    $heroArticle = $stmt->fetch();
}

// 2. Lấy 3 bài viết nổi bật bên cạnh Hero
$excludeId = $heroArticle ? $heroArticle['id'] : 0;
$stmt = $db->prepare("
    SELECT a.*, c.name AS category_name, c.slug AS category_slug
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' AND a.id != ?
    ORDER BY a.views DESC
    LIMIT 3
");
$stmt->execute([$excludeId]);
$trendingArticles = $stmt->fetchAll();

// 3. Lấy danh sách tin mới nhất (Recent Articles Grid)
$stmt = $db->prepare("
    SELECT a.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.author_id = u.id
    WHERE a.status = 'published' AND a.id != ?
    ORDER BY a.published_at DESC
    LIMIT 6
");
$stmt->execute([$excludeId]);
$recentArticles = $stmt->fetchAll();

// 4. Lấy danh mục và số lượng bài viết cho Sidebar
$stmt = $db->query("
    SELECT c.*, COUNT(a.id) AS article_count
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
    GROUP BY c.id
    ORDER BY c.display_order ASC
");
$allCategories = $stmt->fetchAll();

// 5. Lấy top 5 bài đọc nhiều nhất
$stmt = $db->query("
    SELECT a.id, a.title, a.slug, a.views, a.published_at
    FROM articles a
    WHERE a.status = 'published'
    ORDER BY a.views DESC
    LIMIT 5
");
$topViewed = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Phần Tiêu Điểm (Hero Section) -->
    <?php if ($heroArticle): ?>
        <section class="hero-section">
            <div class="hero-grid">
                <!-- Thẻ Hero chính -->
                <a href="/article.php?slug=<?= e($heroArticle['slug']) ?>" class="hero-main-card">
                    <img src="<?= e($heroArticle['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                         alt="<?= e($heroArticle['title']) ?>" class="hero-main-bg" 
                         onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&auto=format&fit=crop&q=80'">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <span class="category-tag"><?= e($heroArticle['category_name']) ?></span>
                        <h1 class="hero-title"><?= e($heroArticle['title']) ?></h1>
                        <p class="hero-summary"><?= e($heroArticle['summary']) ?></p>
                        <div class="meta-info">
                            <span class="meta-item">✍️ <?= e($heroArticle['author_name']) ?></span>
                            <span class="meta-item">🕒 <?= date('d/m/Y H:i', strtotime($heroArticle['published_at'])) ?></span>
                            <span class="meta-item">👁️ <?= number_format($heroArticle['views']) ?> lượt xem</span>
                        </div>
                    </div>
                </a>

                <!-- Cột tin xu hướng bên cạnh -->
                <div class="hero-side-column">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; display:flex; align-items:center; gap:8px;">
                        🔥 Đọc Nhiều Nhất
                    </h3>
                    <?php foreach ($trendingArticles as $trend): ?>
                        <a href="/article.php?slug=<?= e($trend['slug']) ?>" class="side-card">
                            <img src="<?= e($trend['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                                 alt="<?= e($trend['title']) ?>" class="side-thumb"
                                 onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=200&auto=format&fit=crop&q=80'">
                            <div class="side-card-content">
                                <span style="font-size: 0.72rem; color: var(--primary); font-weight: 700; text-transform: uppercase;">
                                    <?= e($trend['category_name']) ?>
                                </span>
                                <h4 class="side-title"><?= e($trend['title']) ?></h4>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    👁️ <?= number_format($trend['views']) ?> xem
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Nội dung chính & Sidebar -->
    <div class="main-layout">
        <!-- Cột bài viết mới nhất -->
        <div class="content-primary">
            <div class="section-header">
                <h2 class="section-title">Tin Mới Cập Nhật</h2>
                <span style="font-size: 0.88rem; color: var(--text-muted);">Cập nhật liên tục 24/7</span>
            </div>

            <div class="article-cards-grid">
                <?php foreach ($recentArticles as $art): ?>
                    <article class="article-card">
                        <a href="/article.php?slug=<?= e($art['slug']) ?>" class="card-thumb-wrap">
                            <img src="<?= e($art['thumbnail'] ?: '/assets/images/default-news.jpg') ?>" 
                                 alt="<?= e($art['title']) ?>" class="card-thumb"
                                 onerror="this.src='https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=500&auto=format&fit=crop&q=80'">
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
        </div>

        <!-- Cột Sidebar bên phải -->
        <aside class="sidebar">
            <!-- Widget Chuyên mục -->
            <div class="widget">
                <h3 class="widget-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    Chuyên Mục Tin Tức
                </h3>
                <ul class="categories-list">
                    <?php foreach ($allCategories as $cat): ?>
                        <li>
                            <a href="/category.php?slug=<?= e($cat['slug']) ?>" class="category-item-link">
                                <span><?= e($cat['name']) ?></span>
                                <span class="category-count"><?= $cat['article_count'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Widget Tin quan tâm nhiều nhất -->
            <div class="widget">
                <h3 class="widget-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    Bảng Xếp Hạng Đọc Nhiều
                </h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php $rank = 1; foreach ($topViewed as $tv): ?>
                        <div style="display:flex; align-items:flex-start; gap:12px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            <span style="font-size:1.3rem; font-weight:800; color:<?= $rank <= 3 ? 'var(--primary)' : 'var(--text-muted)' ?>; width:24px; text-align:center;">
                                <?= $rank++ ?>
                            </span>
                            <div style="flex-grow:1;">
                                <a href="/article.php?slug=<?= e($tv['slug']) ?>" style="font-size:0.9rem; font-weight:600; line-height:1.3; color:var(--text-primary); display:block; margin-bottom:4px;">
                                    <?= e($tv['title']) ?>
                                </a>
                                <span style="font-size:0.75rem; color:var(--text-muted);">
                                    👁️ <?= number_format($tv['views']) ?> lượt xem
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Widget Hạ tầng Công nghệ -->
            <div class="widget" style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.1), rgba(56, 189, 248, 0.05)); border-color: rgba(2, 132, 199, 0.3);">
                <h3 class="widget-title" style="color: var(--primary);">Hạ Tầng Vận Hành</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 12px;">
                    Hệ thống được đóng gói hoàn toàn bằng Docker Compose, bảo vệ bằng Nginx SSL Reverse Proxy, thu thập log bằng Promtail + Loki và theo dõi hiệu năng tức thời qua Prometheus + Grafana.
                </p>
                <a href="/admin/index.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 0.85rem;">
                    Vào Bàn Quản Trị
                </a>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
