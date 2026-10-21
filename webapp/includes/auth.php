<?php
// ==============================================================================
// MODULE XÁC THỰC, PHÂN QUYỀN (RBAC) VÀ BẢO VỆ PHIÊN (SESSION SECURITY)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/logger.php';

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 ngày
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

start_secure_session();

// Bảo vệ chống tấn công CSRF (Cross-Site Request Forgery)
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Xử lý XSS (Cross-Site Scripting) khi hiển thị dữ liệu ra HTML
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Các hàm kiểm tra trạng thái đăng nhập và quyền hạn
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'] ?? '',
        'avatar'    => $_SESSION['avatar'] ?? '/assets/images/default-avatar.png'
    ];
}

function is_admin() {
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function is_author() {
    $user = current_user();
    return $user && ($user['role'] === 'author' || $user['role'] === 'admin');
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $user = current_user();
    if (!in_array($user['role'], $roles)) {
        system_log('SECURITY', 'WARN', 'ACCESS_DENIED', 'Truy cập trái phép trang quản trị vai trò: ' . implode(',', $roles), $user['id']);
        http_response_code(403);
        die("403 - Bạn không có quyền truy cập vào khu vực này.");
    }
}

function login_user($user) {
    session_regenerate_id(true); // Ngăn chặn Session Fixation
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['avatar']    = $user['avatar'];
    $_SESSION['logged_at'] = time();

    system_log('SECURITY', 'INFO', 'LOGIN_SUCCESS', "Đăng nhập thành công với vai trò {$user['role']}", $user['id']);
}

function logout_user() {
    if (is_logged_in()) {
        $user = current_user();
        system_log('SECURITY', 'INFO', 'LOGOUT', "Người dùng đăng xuất", $user['id']);
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
