#!/bin/bash
# ==============================================================================
# SCRIPT KIỂM ĐỊNH AN TOÀN HỆ THỐNG QUA CÂU LỆNH CURL (LINUX / BASH)
# Tương thích kiểm tra tại Hình 2.2.1 của Báo cáo đồ án
# Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
# ==============================================================================

echo "========================================================================"
echo "1. KIỂM TRA CHUYỂN HƯỚNG HTTP SANG HTTPS (HTTP 301 REDIRECT)"
echo "========================================================================"
curl -I http://localhost

echo ""
echo "========================================================================"
echo "2. KIỂM TRA CÁC SECURITY HEADERS VÀ ẨN PHIÊN BẢN NGINX (HTTPS 200)"
echo "========================================================================"
curl -k -I https://localhost

echo ""
echo "========================================================================"
echo "3. KIỂM TRA CHẶN TRUY CẬP ĐIỂM GIÁM SÁT /nginx_status TỪ NGOÀI (HTTP 403)"
echo "========================================================================"
curl -k -I https://localhost/nginx_status

echo ""
echo "========================================================================"
echo "4. KIỂM TRA METRICS ỨNG DỤNG CHO PROMETHEUS (/api/metrics.php)"
echo "========================================================================"
curl -k -s https://localhost/api/metrics.php | head -n 15

echo ""
echo "========================================================================"
echo "5. KIỂM TRA TRẠNG THÁI CÁC DỊCH VỤ DOCKER COMPOSE"
echo "========================================================================"
docker compose ps
