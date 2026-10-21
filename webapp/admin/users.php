<?php
// ==============================================================================
// QUẢN LÝ NGƯỜI DÙNG & PHÂN QUYỀN TRUY CẬP (RBAC MANAGEMENT)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$user = current_user();
$db = get_db_connection();

$error = '';
$success = '';

// 1. Thay đổi vai trò (Role)
if (isset($_POST['change_role'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF không hợp lệ.';
    } else {
        $targetUserId = (int)$_POST['user_id'];
        $newRole = $_POST['new_role'] ?? 'reader';
        if (in_array($newRole, ['admin', 'author', 'reader'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            system_log('SECURITY', 'WARN', 'USER_ROLE_CHANGED', "Thay đổi quyền người dùng #{$targetUserId} thành {$newRole}", $user['id']);
            $success = "Cập nhật vai trò người dùng #{$targetUserId} thành công!";
        }
    }
}

// 2. Khóa / Mở khóa tài khoản (Active toggle)
if (isset($_GET['toggle_active'])) {
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        die("CSRF Token không hợp lệ.");
    }
    $targetUserId = (int)$_GET['toggle_active'];
    if ($targetUserId === $user['id']) {
        $error = 'Không thể tự khóa tài khoản của chính mình!';
    } else {
        $stmt = $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$targetUserId]);
        system_log('SECURITY', 'WARN', 'USER_STATUS_TOGGLED', "Thay đổi trạng thái kích hoạt tài khoản #{$targetUserId}", $user['id']);
        $success = "Cập nhật trạng thái kích hoạt tài khoản thành công!";
    }
}

// Lấy danh sách toàn bộ người dùng
$usersList = $db->query("
    SELECT u.*, COUNT(a.id) AS article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id 
    GROUP BY u.id 
    ORDER BY u.id ASC
")->fetchAll();

$pageTitle = 'Quản Lý Người Dùng & Phân Quyền';
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
        <a href="/admin/users.php" class="admin-nav-item active">Người Dùng & Phân Quyền</a>
        <a href="/admin/audit_logs.php" class="admin-nav-item">Nhật Ký An Toàn & Audit</a>
    </aside>

    <main class="admin-main">
        <div class="admin-header-row">
            <div>
                <h1 class="admin-page-title">Quản Lý Người Dùng & Phân Quyền (RBAC)</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Kiểm soát tài khoản, vai trò tác vụ và trạng thái kích hoạt</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-message alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="table-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Họ và Tên</th>
                        <th>Tên đăng nhập / Email</th>
                        <th>Vai trò hiện tại</th>
                        <th>Số bài viết</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th style="width: 220px;">Thao tác quản trị</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= e($u['full_name']) ?></td>
                            <td>
                                <div><code><?= e($u['username']) ?></code></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="change_role" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="new_role" class="form-select" style="padding: 4px 8px; font-size: 0.8rem; width: 110px;">
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="author" <?= $u['role'] === 'author' ? 'selected' : '' ?>>Author</option>
                                        <option value="reader" <?= $u['role'] === 'reader' ? 'selected' : '' ?>>Reader</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline btn-sm" title="Lưu vai trò">Lưu</button>
                                </form>
                            </td>
                            <td><?= $u['article_count'] ?></td>
                            <td>
                                <span class="status-badge <?= $u['is_active'] ? 'badge-active' : 'badge-locked' ?>">
                                    <?= $u['is_active'] ? 'HOẠT ĐỘNG' : 'ĐÃ KHÓA' ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== $user['id']): ?>
                                    <a href="/admin/users.php?toggle_active=<?= $u['id'] ?>&token=<?= generate_csrf_token() ?>" 
                                       class="btn btn-sm <?= $u['is_active'] ? 'btn-outline' : 'btn-primary' ?>"
                                       onclick="return confirm('Bạn có muốn <?= $u['is_active'] ? 'khóa' : 'mở khóa' ?> tài khoản này?');">
                                        <?= $u['is_active'] ? '🔒 Khóa tài khoản' : '🔓 Kích hoạt' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Tài khoản hiện tại</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
