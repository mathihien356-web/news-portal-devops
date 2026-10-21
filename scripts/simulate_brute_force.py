#!/usr/bin/env python3
# ==============================================================================
# KỊCH BẢN THỰC NGHIỆM AN TOÀN THÔNG TIN: TẤN CÔNG DÒ MẬT KHẨU (BRUTE-FORCE)
# KÍCH HOẠT CẢNH BÁO ALERT FIRING TRÊN GRAFANA VÀ KIỂM TRA RATE LIMITING NGINX
# Sinh viên: Ma Thị Hiền (MSSV: DTC245200948 - Lớp: CNTTK23C)
# ==============================================================================

import time
import urllib.request
import urllib.parse
import ssl
import sys

TARGET_URL = "https://localhost/login.php"
NUM_REQUESTS = 20
USER_TARGET = "admin"

print("=" * 70)
print("🚀 BẮT ĐẦU KỊCH BẢN KIỂM THỬ: TẤN CÔNG DÒ MẬT KHẨU QUẢN TRỊ (BRUTE-FORCE)")
print(f"🎯 Mục tiêu: {TARGET_URL} | Tài khoản nhắm tới: '{USER_TARGET}'")
print(f"🔢 Số lần thử nghiệm liên tiếp: {NUM_REQUESTS}")
print("=" * 70)

# Bỏ qua xác thực chứng chỉ tự ký khi thử nghiệm nội bộ
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

success_count = 0
rate_limited_count = 0
failed_login_count = 0

for i in range(1, NUM_REQUESTS + 1):
    fake_password = f"WrongPassword_{i}_{int(time.time())}"
    post_data = urllib.parse.urlencode({
        'username': USER_TARGET,
        'password': fake_password,
        'csrf_token': 'test_simulation_probe'
    }).encode('utf-8')

    req = urllib.request.Request(
        TARGET_URL,
        data=post_data,
        headers={
            'User-Agent': 'Security-Audit-BruteForce-Simulator/1.0',
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    )

    try:
        with urllib.request.urlopen(req, context=ctx, timeout=5) as response:
            status_code = response.getcode()
            if status_code == 200:
                failed_login_count += 1
                print(f"[{i:02d}/{NUM_REQUESTS}] Thử mật khẩu '{fake_password[:16]}...' -> HTTP {status_code} (Đăng nhập thất bại - Đã ghi log)")
    except urllib.error.HTTPError as e:
        if e.code == 429:
            rate_limited_count += 1
            print(f"[{i:02d}/{NUM_REQUESTS}] 🛑 NGINX RATE LIMITING CHẶN THÀNH CÔNG! -> HTTP 429 Too Many Requests")
        else:
            print(f"[{i:02d}/{NUM_REQUESTS}] -> HTTP {e.code}")
    except Exception as ex:
        print(f"[{i:02d}/{NUM_REQUESTS}] Lỗi kết nối: {ex}")

    time.sleep(0.1)

print("\n" + "=" * 70)
print("📊 KẾT QUẢ THỰC NGHIỆM BẢO MẬT:")
print(f" - Số lần gửi yêu cầu thử mật khẩu sai: {NUM_REQUESTS}")
print(f" - Số lần ghi nhận 'LOGIN_FAILED' hợp lệ: {failed_login_count}")
print(f" - Số lần Nginx kích hoạt chặn ngưỡng HTTP 429: {rate_limited_count}")
print("=" * 70)
print("🔍 BƯỚC TIẾP THEO ĐỂ KIỂM CHỨNG BÁO CÁO:")
print("1. Truy cập Grafana (https://localhost/grafana/) -> Mục Alerting:")
print("   => Cảnh báo 'NewsPortalBruteForceLogin' sẽ chuyển sang trạng thái [FIRING].")
print("2. Truy cập Grafana Explore -> Nguồn Loki -> Chạy câu truy vấn LogQL:")
print("   {job=\"webapp\"} |= \"LOGIN_FAILED\"")
print("   => Thấy rõ danh sách các lần thử xuất phát từ cùng một địa chỉ IP.")
print("3. Tra cứu tần suất trong 1 phút:")
print("   sum(count_over_time({job=\"webapp\"} |= \"LOGIN_FAILED\" [1m]))")
print("=" * 70)
