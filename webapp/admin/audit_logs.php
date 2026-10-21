<?php
// ==============================================================================
// TRANG ĐIỀU TRA AN TOÀN THÔNG TIN & TRUY VẾT NHẬT KÝ (AUDIT LOGS & FORENSICS)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$user = current_user();
$db = get_db_connection();

$filterAction = trim($_GET['action'] ?? '');
$filterIp     = trim($_GET['ip'] ?? '');

$sql = "
    SELECT l.*, u.username, u.full_name
    FROM audit_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($filterAction !== '') {
    $sql .= " AND l.action LIKE ? ";
    $params[] = '%' . $filterAction . '%';
}

if ($filterIp !== '') {
    $sql .= " AND l.ip_address LIKE ? ";
    $params[] = '%' . $filterIp . '%';
}

$sql .= " ORDER BY l.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Đếm số lần đăng nhập sai để cảnh báo
$failedCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'LOGIN_FAILED'")->fetchColumn();

$pageTitle = 'Nhật Ký An Toàn & Điều Tra Số';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">Quản Lý Nội Dung</div>
        <a href="/admin/index.php" class="admin-nav-item">Tổng Quan</a>
        <a href="/admin/articles.php" class="admin-nav-item">Bài Viết Tin Tức</a>
        <a href="/admin/article_edit.php" class="admin-nav-item">Viết Bài Mới</a>
        <div class="admin-sidebar-title" style="margin-top: 16px;">Hệ Thống & Bảo Mật</div>
        <a href="/admin/categories.php" class="admin-nav-item">Chuyên Mục Tin</a>
        <a href="/admin/users.php" class="admin-nav-item">Người Dùng & Phân Quyền</a>
        <a href="/admin/audit_logs.php" class="admin-nav-item active">Nhật Ký An Toàn & Audit</a>
        <a href="/grafana/" target="_blank" class="admin-nav-item">Grafana Loki Logs &nearr;</a>
    </aside>

    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title">Nhật Ký An Toàn Hệ Thống (Audit Trail)</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Ghi vết hành vi người dùng, phát hiện tấn công dò mật khẩu và phân tích bảo mật
                </p>
            </div>
            <?php if ($failedCount > 5): ?>
                <div style="background: rgba(244, 63, 94, 0.2); border: 1px solid #f43f5e; color: #f87171; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700;">
                    ⚠️ CẢNH BÁO: Phát hiện <?= $failedCount ?> lượt đăng nhập sai (LOGIN_FAILED)!
                </div>
            <?php endif; ?>
        </div>

        <!-- Bộ lọc tìm kiếm nhanh -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 24px;">
            <form method="GET" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex-grow: 1; min-width: 200px;">
                    <label class="form-label" style="margin-bottom: 4px;">Lọc theo Hành động (Action)</label>
                    <input type="text" name="action" class="form-input" placeholder="Ví dụ: LOGIN_FAILED, ARTICLE..." value="<?= e($filterAction) ?>">
                </div>
                <div style="flex-grow: 1; min-width: 180px;">
                    <label class="form-label" style="margin-bottom: 4px;">Lọc theo Địa chỉ IP nguồn</label>
                    <input type="text" name="ip" class="form-input" placeholder="192.168.1..." value="<?= e($filterIp) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Lọc Dữ Liệu</button>
                <a href="/admin/audit_logs.php" class="btn btn-outline" style="padding: 10px 16px;">Đặt Lại</a>
            </form>
        </div>

        <div class="table-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 150px;">Thời gian</th>
                        <th>Hành động (Action)</th>
                        <th>Tài khoản liên quan</th>
                        <th>Địa chỉ IP</th>
                        <th>Chi tiết sự kiện</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">Không có bản ghi nhật ký phù hợp.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace;"><?= e($l['created_at']) ?></td>
                                <td>
                                    <code style="background: var(--bg-surface-elevated); padding: 3px 8px; border-radius: 4px; font-size: 0.82rem; font-weight: 700; color: <?= strpos($l['action'], 'FAILED') !== false ? '#f43f5e' : (strpos($l['action'], 'SUCCESS') !== false ? '#34d399' : '#38bdf8') ?>;">
                                        <?= e($l['action']) ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if ($l['username']): ?>
                                        <strong><?= e($l['full_name']) ?></strong> (<code><?= e($l['username']) ?></code>)
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">Khách / Ẩn danh</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= e($l['ip_address']) ?></code></td>
                                <td style="font-size: 0.88rem; color: var(--text-secondary);"><?= e($l['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
