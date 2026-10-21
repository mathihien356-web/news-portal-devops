<?php
// ==============================================================================
// TRANG ĐĂNG NHẬP HỆ THỐNG TIN TỨC & GHI NHẬT KÝ BẢO MẬT
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Nếu đã đăng nhập thì chuyển hướng
if (is_logged_in()) {
    header("Location: /");
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        system_log('SECURITY', 'WARN', 'CSRF_FAILED', 'Gửi form đăng nhập không hợp lệ hoặc token hết hạn');
        $error = 'Phiên đăng nhập đã hết hạn hoặc mã bảo vệ không hợp lệ. Vui lòng thử lại.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Vui lòng điền đầy đủ tài khoản và mật khẩu.';
        } else {
            $db = get_db_connection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (!$user['is_active']) {
                    system_log('SECURITY', 'WARN', 'LOGIN_BLOCKED', "Tài khoản bị khóa: {$username}", $user['id']);
                    $error = 'Tài khoản của bạn hiện đang bị tạm khóa. Vui lòng liên hệ ban quản trị.';
                } else {
                    // Đăng nhập thành công
                    login_user($user);

                    $redirect = $_GET['redirect'] ?? '';
                    if ($redirect && strpos($redirect, '/') === 0) {
                        header("Location: " . $redirect);
                    } elseif ($user['role'] === 'admin' || $user['role'] === 'author') {
                        header("Location: /admin/index.php");
                    } else {
                        header("Location: /");
                    }
                    exit;
                }
            } else {
                // Đăng nhập thất bại - GHI RÕ TỪ KHÓA LOGIN_FAILED RA STDERR PHỤC VỤ LOKI & PROMTAIL
                system_log('SECURITY', 'WARN', 'LOGIN_FAILED', "Tài khoản đăng nhập sai: {$username}");
                $error = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
            }
        }
    }
}

$pageTitle = 'Đăng Nhập Hệ Thống';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon" style="margin: 0 auto 16px auto; width: 52px; height: 52px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 class="auth-title">Đăng Nhập Cổng Tin Tức</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Hệ thống xác thực bảo mật & phân quyền RBAC</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="username">Tên đăng nhập hoặc Email</label>
                <input type="text" id="username" name="username" class="form-input" 
                       value="<?= e($username) ?>" required autocomplete="username" placeholder="admin hoặc email...">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Mật khẩu</label>
                </div>
                <input type="password" id="password" name="password" class="form-input" 
                       required autocomplete="current-password" placeholder="Nhập mật khẩu...">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Đăng Nhập
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
            Chưa có tài khoản độc giả? <a href="/register.php" style="color: var(--primary); font-weight: 600;">Đăng ký ngay</a>
        </div>

        <!-- Hộp thông tin tài khoản mẫu phục vụ chấm điểm và thực nghiệm -->
        <div style="margin-top: 28px; padding: 16px; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px dashed var(--border-color); font-size: 0.8rem; color: var(--text-muted);">
            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">📋 Tài khoản kiểm thử mặc định:</div>
            <div><strong>Quản trị viên (Admin):</strong> <code>admin</code> / <code>Admin@123456</code></div>
            <div><strong>Biên tập viên (Author):</strong> <code>mathihien</code> / <code>Hien@123456</code></div>
            <div><strong>Độc giả (Reader):</strong> <code>reader1</code> / <code>Reader@123456</code></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
