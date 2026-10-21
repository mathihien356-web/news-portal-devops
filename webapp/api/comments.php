<?php
// ==============================================================================
// API TIẾP NHẬN BÌNH LUẬN ĐỘC GIẢ (AJAX ENDPOINT)
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để gửi bình luận.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ.']);
    exit;
}

$token = $data['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF không hợp lệ.']);
    exit;
}

$articleId = intval($data['article_id'] ?? 0);
$content = trim($data['content'] ?? '');

if ($articleId <= 0 || empty($content)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nội dung bình luận không được để trống.']);
    exit;
}

$user = current_user();
$db = get_db_connection();

try {
    $stmt = $db->prepare("
        INSERT INTO comments (article_id, user_id, content, status, created_at)
        VALUES (?, ?, ?, 'approved', NOW())
    ");
    $stmt->execute([$articleId, $user['id'], $content]);
    $commentId = $db->lastInsertId();

    system_log('AUDIT', 'INFO', 'COMMENT_ADDED', "Bình luận trên bài viết #{$articleId}", $user['id']);

    echo json_encode([
        'success' => true,
        'message' => 'Gửi bình luận thành công!',
        'comment' => [
            'id' => $commentId,
            'user_name' => $user['full_name'],
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi lưu bình luận.']);
}
