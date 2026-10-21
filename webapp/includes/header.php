<?php
// ==============================================================================
// GIAO DIỆN HEADER & THANH ĐIỀU HƯỚNG CHÍNH
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Lấy danh sách chuyên mục cho thanh điều hướng
$headerCategories = [];
try {
    $db = get_db_connection();
    $stmt = $db->query("SELECT id, name, slug FROM categories ORDER BY display_order ASC LIMIT 6");
    $headerCategories = $stmt->fetchAll();
} catch (Exception $e) {
    // Dự phòng nếu chưa có CSDL
}

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'Cổng Thông Tin Điện Tử & Tin Tức Thời Sự 24/7';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | NewsPortal</title>
    <meta name="description" content="Cổng thông tin tin tức đa phương tiện, cập nhật thời sự, công nghệ, kinh tế số liên tục 24/7.">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
        <link rel="stylesheet" href="/assets/css/admin.css">
    <?php endif; ?>
</head>
<body>

<!-- Thanh Breaking News trên cùng -->
<div class="top-bar">
    <div class="container top-bar-content">
        <div class="breaking-news">
            <span class="breaking-badge">NÓNG</span>
            <span>Chuyển đổi số và an toàn thông tin hạ tầng đám mây trở thành ưu tiên chiến lược quốc gia</span>
        </div>
        <div style="font-size: 0.82rem; opacity: 0.95;">
            <?= date('l, d/m/Y') ?> | Hà Nội 28°C
        </div>
    </div>
</div>

<!-- Header & Navbar chính -->
<header class="site-header">
    <div class="container navbar">
        <!-- Logo thương hiệu -->
        <a href="/" class="logo-brand">
            <div class="logo-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                    <path d="M18 14h-8"></path>
                    <path d="M15 18h-5"></path>
                    <path d="M10 6h8v4h-8V6Z"></path>
                </svg>
            </div>
            <div>
                <div class="logo-text">NEWS<span>PORTAL</span></div>
                <div class="logo-subtitle">Cổng Thông Tin Số</div>
            </div>
        </a>

        <!-- Menu Chuyên mục -->
        <ul class="nav-links">
            <li><a href="/" class="nav-link <?= empty($_GET['cat']) && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Trang chủ</a></li>
            <?php foreach ($headerCategories as $cat): ?>
                <li>
                    <a href="/category.php?slug=<?= e($cat['slug']) ?>" 
                       class="nav-link <?= isset($_GET['slug']) && $_GET['slug'] === $cat['slug'] ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Hành động: Tìm kiếm, Theme & Tài khoản -->
        <div class="nav-actions">
            <!-- Ô tìm kiếm nhỏ -->
            <form action="/search.php" method="GET" class="search-box-mini">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="q" placeholder="Tìm kiếm tin tức..." value="<?= e($_GET['q'] ?? '') ?>">
            </form>

            <!-- Nút chuyển đổi Dark/Light mode -->
            <button id="themeToggleBtn" class="theme-toggle-btn" title="Chuyển chế độ sáng/tối">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>

            <!-- Trạng thái xác thực Người dùng -->
            <?php if ($currentUser): ?>
                <div class="user-menu-wrapper" style="position: relative;">
                    <div class="user-menu-btn">
                        <div class="user-avatar-mini" style="display:flex;align-items:center;justify-content:center;background:#0284c7;color:#fff;font-weight:700;">
                            <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                        </div>
                        <span style="font-size: 0.88rem; font-weight: 600; color: var(--text-primary);">
                            <?= e($currentUser['full_name']) ?>
                        </span>
                        <span class="user-role-badge role-<?= e($currentUser['role']) ?>">
                            <?= strtoupper(e($currentUser['role'])) ?>
                        </span>
                    </div>

                    <!-- Dropdown danh sách hành động -->
                    <div class="dropdown-menu">
                        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'author'): ?>
                            <a href="/admin/index.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                Bàn Quản trị Portal
                            </a>
                            <a href="/admin/article_edit.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                Viết bài mới
                            </a>
                        <?php endif; ?>
                        <a href="/profile.php" class="dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Trang cá nhân
                        </a>
                        <a href="/logout.php" class="dropdown-item" style="color: #f43f5e;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; gap: 8px;">
                    <a href="/login.php" class="btn btn-outline" style="padding: 7px 14px; font-size: 0.85rem;">Đăng nhập</a>
                    <a href="/register.php" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem;">Đăng ký</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<main>
