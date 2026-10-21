<?php
// ==============================================================================
// MODULE GHI NHẬT KÝ HỆ THỐNG CÓ CẤU TRÚC (LOGGING & AUDITING)
// Phục vụ thu thập bởi Promtail và phân tích trên Grafana Loki
// ==============================================================================

require_once __DIR__ . '/../config/database.php';

function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function system_log($category, $level, $action, $details = '', $userId = null) {
    $ip = get_client_ip();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');

    // 1. Ghi log chuẩn định dạng ra STDERR để Promtail thu thập trực tiếp từ Docker logs
    // Format: [TIMESTAMP] [CATEGORY] [LEVEL] ACTION ip=... user_id=... details="..."
    $logLine = sprintf(
        "[%s] [%s] [%s] %s ip=%s user_id=%s details=\"%s\"",
        $timestamp,
        strtoupper($category),
        strtoupper($level),
        $action,
        $ip,
        $userId ?? 'anonymous',
        str_replace('"', '\"', $details)
    );
    error_log($logLine);

    // 2. Lưu vào bảng audit_logs trong MySQL để quản trị viên tra cứu trên giao diện
    try {
        $db = get_db_connection();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $ip, substr($userAgent, 0, 250), $details]);
    } catch (Exception $e) {
        error_log("[SYSTEM] [ERROR] AUDIT_LOG_INSERT_FAILED: " . $e->getMessage());
    }
}
