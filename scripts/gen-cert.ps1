# ==============================================================================
# POWERSHELL SCRIPT TỰ ĐỘNG TẠO CHỨNG CHỈ HTTPS CHO MÔI TRƯỜNG WINDOWS
# Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
# ==============================================================================

$sslDir = Join-Path $PSScriptRoot "..\nginx\ssl"
if (!(Test-Path $sslDir)) {
    New-Item -ItemType Directory -Path $sslDir -Force | Out-Null
}

$keyFile = Join-Path $sslDir "server.key"
$crtFile = Join-Path $sslDir "server.crt"
$cnfFile = Join-Path $sslDir "openssl.cnf"
$csrFile = Join-Path $sslDir "server.csr"

$opensslPath = "C:\Program Files\Git\usr\bin\openssl.exe"
if (!(Test-Path $opensslPath)) {
    $opensslCmd = Get-Command openssl -ErrorAction SilentlyContinue
    if ($opensslCmd) {
        $opensslPath = $opensslCmd.Source
    } else {
        Write-Error "Không tìm thấy openssl.exe. Vui lòng cài đặt Git for Windows hoặc OpenSSL."
        exit 1
    }
}

@"
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
"@ | Out-File -FilePath $cnfFile -Encoding ascii

Write-Host "[INFO] Đang tạo khóa riêng Private Key 2048-bit..." -ForegroundColor Cyan
& $opensslPath genrsa -out $keyFile 2048

Write-Host "[INFO] Đang tạo CSR và ký chứng chỉ X.509..." -ForegroundColor Cyan
& $opensslPath req -new -key $keyFile -out $csrFile -config $cnfFile
& $opensslPath x509 -req -in $csrFile -signkey $keyFile -out $crtFile -days 365 -extfile $cnfFile -extensions req_ext

Remove-Item $csrFile, $cnfFile -Force -ErrorAction SilentlyContinue

Write-Host "[SUCCESS] Đã tạo thành công chứng chỉ HTTPS tự ký tại $sslDir" -ForegroundColor Green
