<?php
// ==============================================================================
// TRANG THÔNG TIN CÁ NHÂN VÀ ĐỔI MẬT KHẨU
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();
$db = get_db_connection();

$error = '';
$success = '';

// Lấy thông tin chi tiết đầy đủ
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Mã bảo vệ CSRF không hợp lệ.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($fullName)) {
            $error = 'Họ và tên không được để trống.';
        } else {
            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    $error = 'Mật khẩu mới phải có tối thiểu 6 ký tự.';
                } elseif ($newPass !== $confirm) {
                    $error = 'Mật khẩu xác nhận không khớp.';
                } else {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$fullName, $bio, $hash, $user['id']]);
                    system_log('AUDIT', 'INFO', 'PASSWORD_CHANGED', 'Người dùng đổi mật khẩu', $user['id']);
                    $success = 'Cập nhật thông tin và mật khẩu thành công!';
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
                $stmt->execute([$fullName, $bio, $user['id']]);
                system_log('AUDIT', 'INFO', 'PROFILE_UPDATED', 'Cập nhật thông tin cá nhân', $user['id']);
                $success = 'Cập nhật thông tin thành công!';
            }
            $_SESSION['full_name'] = $fullName;
        }
    }
}

$pageTitle = 'Trang Cá Nhân';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 0; max-width: 720px;">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 36px; box-shadow: var(--shadow-md);">
        <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Hồ Sơ Cá Nhân
        </h1>

        <?php if ($error): ?>
            <div class="alert-message alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-message alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/profile.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" class="form-input" value="<?= e($userData['username']) ?>" disabled style="opacity: 0.7;">
            </div>

            <div class="form-group">
                <label class="form-label">Địa chỉ Email</label>
                <input type="email" class="form-input" value="<?= e($userData['email']) ?>" disabled style="opacity: 0.7;">
            </div>

            <div class="form-group">
                <label class="form-label">Vai trò trong hệ thống</label>
                <span class="user-role-badge role-<?= e($userData['role']) ?>" style="font-size: 0.85rem; padding: 4px 12px;">
                    <?= strtoupper(e($userData['role'])) ?>
                </span>
            </div>

            <div class="form-group">
                <label class="form-label" for="full_name">Họ và tên hiển thị</label>
                <input type="text" id="full_name" name="full_name" class="form-input" value="<?= e($userData['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="bio">Tiểu sử / Giới thiệu bản thân</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="3"><?= e($userData['bio']) ?></textarea>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 28px 0;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 16px;">Đổi mật khẩu (Bỏ trống nếu không đổi)</h3>

            <div class="form-group">
                <label class="form-label" for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Tối thiểu 6 ký tự...">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Nhập lại mật khẩu mới...">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 0.95rem;">Lưu Thay Đổi</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
