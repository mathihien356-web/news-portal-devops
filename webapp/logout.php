<?php
// ==============================================================================
// XỬ LÝ ĐĂNG XUẤT VÀ HỦY PHIÊN LÀM VIỆC AN TOÀN
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

require_once __DIR__ . '/includes/auth.php';

logout_user();

header("Location: /login.php?logged_out=1");
exit;
