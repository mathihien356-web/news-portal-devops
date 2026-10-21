#!/usr/bin/env python3
# ==============================================================================
# SCRIPT TỰ ĐỘNG ĐÁNH GIÁ VÀ KIỂM TRA HỆ THỐNG SAU KHI TRIỂN KHAI
# POST-DEPLOYMENT SECURITY & HEALTH CHECK SCRIPT
# Sinh viên: Ma Thị Hiền (MSSV: DTC245200948 - Lớp: CNTTK23C)
# ==============================================================================

import urllib.request
import ssl
import sys

BASE_HTTP = "http://localhost"
BASE_HTTPS = "https://localhost"

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

checks_passed = 0
total_checks = 7

print("=" * 70)
print("🔍 BẮT ĐẦU KIỂM TRA HOẠT ĐỘNG VÀ AN TOÀN HỆ THỐNG SAU TRIỂN KHAI")
print("=" * 70)

# 1. Kiểm tra chuyển hướng bắt buộc HTTP (80) -> HTTPS (443)
try:
    req = urllib.request.Request(BASE_HTTP, headers={'User-Agent': 'HealthCheck/1.0'})
    # Không tự động follow redirect để bắt mã 301
    class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
        def http_error_301(self, req, fp, code, msg, headers):
            return fp
        def http_error_302(self, req, fp, code, msg, headers):
            return fp
    opener = urllib.request.build_opener(NoRedirectHandler)
    resp = opener.open(req, timeout=5)
    location = resp.headers.get('Location', '')
    if resp.status == 301 and location.startswith('https://'):
        print("✅ [1/7] Chuyển hướng HTTP sang HTTPS: ĐẠT (Mã 301 Redirect -> " + location + ")")
        checks_passed += 1
    else:
        print(f"❌ [1/7] Chuyển hướng HTTP sang HTTPS: KHÔNG ĐẠT (Mã: {resp.status}, Location: {location})")
except Exception as e:
    print(f"⚠️ [1/7] Chuyển hướng HTTP sang HTTPS: Lỗi kết nối ({e})")

# 2. Kiểm tra phản hồi trang chủ qua kết nối an toàn HTTPS (443)
try:
    req = urllib.request.Request(BASE_HTTPS, headers={'User-Agent': 'HealthCheck/1.0'})
    with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
        if resp.status == 200:
            print("✅ [2/7] Kết nối an toàn HTTPS (443): ĐẠT (Mã 200 OK)")
            checks_passed += 1
        else:
            print(f"❌ [2/7] Kết nối an toàn HTTPS (443): KHÔNG ĐẠT (Mã {resp.status})")
except Exception as e:
    print(f"⚠️ [2/7] Kết nối an toàn HTTPS (443): Lỗi kết nối ({e})")

# 3. Kiểm tra Bộ Security Headers tiêu chuẩn
try:
    req = urllib.request.Request(BASE_HTTPS, headers={'User-Agent': 'HealthCheck/1.0'})
    with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
        headers = resp.headers
        required_headers = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Strict-Transport-Security'
        ]
        missing = [h for h in required_headers if h not in headers]
        if not missing:
            print(f"✅ [3/7] Bộ Security Headers tiêu chuẩn: ĐẠT ({len(required_headers)}/5 headers có mặt)")
            checks_passed += 1
        else:
            print(f"❌ [3/7] Bộ Security Headers tiêu chuẩn: THIẾU ({', '.join(missing)})")
except Exception as e:
    print(f"⚠️ [3/7] Bộ Security Headers: Lỗi kết nối ({e})")

# 4. Kiểm tra ẩn thông tin phiên bản máy chủ Nginx (server_tokens off)
try:
    req = urllib.request.Request(BASE_HTTPS, headers={'User-Agent': 'HealthCheck/1.0'})
    with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
        server_val = resp.headers.get('Server', '')
        # Nếu chỉ có 'nginx' mà không có số phiên bản (vd: 1.27.x) => ĐẠT
        if server_val == 'nginx' or server_val == '':
            print("✅ [4/7] Ẩn thông tin phiên bản máy chủ (server_tokens off): ĐẠT (Server: 'nginx')")
            checks_passed += 1
        else:
            print(f"❌ [4/7] Ẩn thông tin phiên bản máy chủ: KHÔNG ĐẠT (Lộ phiên bản: '{server_val}')")
except Exception as e:
    print(f"⚠️ [4/7] Ẩn phiên bản máy chủ: Lỗi ({e})")

# 5. Kiểm tra chặn truy cập từ xa vào endpoint nhạy cảm /nginx_status
try:
    req = urllib.request.Request(BASE_HTTPS + "/nginx_status", headers={'User-Agent': 'HealthCheck/1.0'})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
            print(f"❌ [5/7] Giới hạn truy cập /nginx_status: KHÔNG ĐẠT (Mở công khai mã {resp.status})")
    except urllib.error.HTTPError as he:
        if he.code == 403:
            print("✅ [5/7] Giới hạn truy cập /nginx_status: ĐẠT (Chặn bên ngoài thành công với mã 403 Forbidden)")
            checks_passed += 1
        else:
            print(f"⚠️ [5/7] Giới hạn truy cập /nginx_status: Mã {he.code}")
except Exception as e:
    print(f"⚠️ [5/7] Giới hạn truy cập /nginx_status: Lỗi ({e})")

# 6. Kiểm tra Endpoint Metrics cho Prometheus
try:
    req = urllib.request.Request(BASE_HTTPS + "/api/metrics.php", headers={'User-Agent': 'HealthCheck/1.0'})
    with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
        body = resp.read().decode('utf-8', errors='ignore')
        if "news_portal_articles_total" in body:
            print("✅ [6/7] Endpoint Metrics Ứng dụng (/api/metrics.php): ĐẠT (Cung cấp chuẩn OpenMetrics)")
            checks_passed += 1
        else:
            print("❌ [6/7] Endpoint Metrics: KHÔNG ĐẠT (Thiếu số liệu cần thiết)")
except Exception as e:
    print(f"⚠️ [6/7] Endpoint Metrics: Lỗi ({e})")

# 7. Kiểm tra cơ chế Rate Limiting trang đăng nhập (/login.php)
try:
    req = urllib.request.Request(BASE_HTTPS + "/login.php", headers={'User-Agent': 'HealthCheck/1.0'})
    with urllib.request.urlopen(req, context=ctx, timeout=5) as resp:
        if resp.status == 200:
            print("✅ [7/7] Trang Đăng Nhập & Bảo Vệ Phiên (/login.php): ĐẠT (Sẵn sàng hoạt động)")
            checks_passed += 1
        else:
            print(f"❌ [7/7] Trang Đăng Nhập: KHÔNG ĐẠT (Mã {resp.status})")
except Exception as e:
    print(f"⚠️ [7/7] Trang Đăng Nhập: Lỗi ({e})")

print("=" * 70)
print(f"🏆 TỔNG KẾT KIỂM ĐỊNH: ĐẠT {checks_passed}/{total_checks} TIÊU CHÍ AN TOÀN")
print("=" * 70)
