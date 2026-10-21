<?php
// ==============================================================================
// TRANG ĐĂNG KÝ TÀI KHOẢN ĐỘC GIẢ
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: /");
    exit;
}

$error = '';
$success = '';
$fullName = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Mã bảo vệ CSRF không hợp lệ hoặc đã hết hạn.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            $error = 'Vui lòng điền đầy đủ các trường thông tin bắt buộc.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Địa chỉ email không đúng định dạng.';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải có độ dài tối thiểu từ 6 ký tự trở lên.';
        } elseif ($password !== $confirm) {
            $error = 'Mật khẩu xác nhận không trùng khớp.';
        } else {
            $db = get_db_connection();
            // Kiểm tra username hoặc email đã tồn tại chưa
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Tên đăng nhập hoặc địa chỉ email đã được sử dụng.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at)
                    VALUES (?, ?, ?, ?, 'reader', 1, NOW())
                ");
                $stmt->execute([$username, $email, $passwordHash, $fullName]);
                $newUserId = $db->lastInsertId();

                system_log('AUDIT', 'INFO', 'USER_REGISTERED', "Đăng ký thành viên mới: {$username}", $newUserId);
                $success = 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.';
            }
        }
    }
}

$pageTitle = 'Đăng Ký Thành Viên Mới';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Đăng Ký Tài Khoản</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Trở thành độc giả của Cổng Thông Tin Số</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-message alert-success"><?= e($success) ?></div>
            <div style="text-align: center; margin-top: 16px;">
                <a href="/login.php" class="btn btn-primary">Đến trang Đăng Nhập</a>
            </div>
        <?php else: ?>
            <form method="POST" action="/register.php">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="full_name">Họ và tên của bạn</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" 
                           value="<?= e($fullName) ?>" required placeholder="Ví dụ: Nguyễn Văn A">
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= e($username) ?>" required placeholder="Chỉ gồm chữ và số...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= e($email) ?>" required placeholder="name@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           required placeholder="Tối thiểu 6 ký tự...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           required placeholder="Nhập lại mật khẩu...">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                    Tạo Tài Khoản
                </button>
            </form>

            <div style="margin-top: 24px; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
                Đã có tài khoản? <a href="/login.php" style="color: var(--primary); font-weight: 600;">Đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
