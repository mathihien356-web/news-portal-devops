#!/bin/bash
# ==============================================================================
# SCRIPT TỰ ĐỘNG TẠO CHỨNG CHỈ SỐ HTTPS TỰ KÝ (SELF-SIGNED SSL CERTIFICATE)
# Chuẩn RSA 2048-bit với SAN (Subject Alternative Names) cho Nginx Reverse Proxy
# Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
# ==============================================================================

set -e

SSL_DIR="./nginx/ssl"
mkdir -p "$SSL_DIR"

KEY_FILE="$SSL_DIR/server.key"
CRT_FILE="$SSL_DIR/server.crt"
CSR_FILE="$SSL_DIR/server.csr"
CONF_FILE="$SSL_DIR/openssl.cnf"

echo "[INFO] Đang tạo tệp cấu hình OpenSSL với SAN (localhost, 127.0.0.1)..."

cat > "$CONF_FILE" <<EOF
[req]
default_bits        = 2048
prompt              = no
default_md          = sha256
distinguished_name  = req_distinguished_name
req_extensions      = req_ext

[req_distinguished_name]
C                   = VN
ST                  = Thai Nguyen
L                   = Thai Nguyen
O                   = Dai hoc CNTT va Truyen thong - ICTU
OU                  = Khoa Cong nghe Thong tin
CN                  = localhost

[req_ext]
subjectAltName      = @alt_names

[alt_names]
DNS.1               = localhost
DNS.2               = newsportal.local
IP.1                = 127.0.0.1
EOF

echo "[INFO] Đang tạo khóa riêng Private Key RSA 2048-bit..."
openssl genrsa -out "$KEY_FILE" 2048

echo "[INFO] Đang tạo Certificate Signing Request (CSR)..."
openssl req -new -key "$KEY_FILE" -out "$CSR_FILE" -config "$CONF_FILE"

echo "[INFO] Đang ký chứng chỉ số X.509 có hiệu lực 365 ngày..."
openssl x509 -req -in "$CSR_FILE" -signkey "$KEY_FILE" -out "$CRT_FILE" -days 365 -extfile "$CONF_FILE" -extensions req_ext

# Dọn dẹp tệp tạm
rm -f "$CSR_FILE" "$CONF_FILE"

chmod 600 "$KEY_FILE"
chmod 644 "$CRT_FILE"

echo "[SUCCESS] Đã tạo thành công chứng chỉ HTTPS tự ký tại:"
echo "          - Private Key: $KEY_FILE"
echo "          - Certificate: $CRT_FILE"
