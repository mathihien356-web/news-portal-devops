# HỆ THỐNG WEBSITE TIN TỨC & CỔNG THÔNG TIN ĐIỆN TỬ
## BÁO CÁO BÀI TẬP LỚN: TRIỂN KHAI VÀ QUẢN TRỊ HỆ THỐNG PHẦN MỀM

---

### 🎓 THÔNG TIN SINH VIÊN THỰC HIỆN
- **Họ và tên sinh viên:** Ma Thị Hiền
- **Mã số sinh viên (MSSV):** DTC245200948
- **Lớp / Khóa:** CNTTK23C
- **Khoa:** Công nghệ Thông tin
- **Trường:** Đại học Công nghệ Thông tin và Truyền thông - Đại học Thái Nguyên (ICTU)
- **Tài khoản GitHub:** [`mathihien356-web`](https://github.com/mathihien356-web)
- **Repository dự án:** [https://github.com/mathihien356-web/news-portal-devops](https://github.com/mathihien356-web/news-portal-devops)

---

## 📑 MỤC LỤC
1. [Tổng Quan Kiến Trúc Hệ Thống](#1-tổng-quan-kiến-trúc-hệ-thống)
2. [Cấu Trúc Thư Mục Dự Án](#2-cấu-trúc-thư-mục-dự-án)
3. [Hướng Dẫn Cài Đặt & Khởi Chạy (Quickstart)](#3-hướng-dẫn-cài-đặt--khởi-chạy-quickstart)
4. [Thông Tin Cổng Dịch Vụ & Tài Khoản Mặc Định](#4-thông-tin-cổng-dịch-vụ--tài-khoản-mặc-định)
5. [Cơ Sở Dữ Liệu & Chức Năng Ứng Dụng Web](#5-cơ-sở-dữ-liệu--chức-năng-ứng-dụng-web)
6. [Nginx Reverse Proxy & Cấu Hình HTTPS/SSL](#6-nginx-reverse-proxy--cấu-hình-httpsssl)
7. [Hạ Tầng Giám Sát Prometheus & Grafana](#7-hạ-tầng-giám-sát-prometheus--grafana)
8. [Quản Lý Nhật Ký Tập Trung Loki & Promtail](#8-quản-lý-nhật-ký-tập-trung-loki--promtail)
9. [Kịch Bản Thực Nghiệm An Toàn: Tấn Công Dò Mật Khẩu (Brute-Force)](#9-kịch-bản-thực-nghiệm-an-toàn-tấn-công-dò-mật-khẩu-brute-force)
10. [Các Biện Pháp Hardening & Kiểm Tra Sau Triển Khai](#10-các-biện-pháp-hardening--kiểm-tra-sau-triển-khai)
11. [Lịch Sử Commit & Quản Lý Nhánh Trên GitHub](#11-lịch-sử-commit--quản-lý-nhánh-trên-github)

---

## 1. Tổng Quan Kiến Trúc Hệ Thống

Hệ thống được thiết kế theo mô hình kiến trúc container hóa hoàn chỉnh (Microservices) điều phối tập trung thông qua **Docker Compose**, tích hợp đầy đủ cơ chế định tuyến mã hóa ngược (Reverse Proxy), giám sát hiệu năng thời gian thực (Prometheus + Grafana) và quản trị nhật ký tập trung (Loki + Promtail).

```mermaid
graph TD
    User["🌐 Người dùng ngoài Internet (Port 80 / 443)"] -->|HTTPS :443| Nginx["🛡️ Nginx Reverse Proxy (SSL/TLS, Security Headers, Rate Limiting)"]
    Nginx -->|HTTP 301 Redirect| Nginx

    subgraph "Mạng Docker Frontend (news-frontend-net)"
        Nginx -->|Định tuyến / | WebApp["📰 Web Tin tức (PHP 8.2 + Apache)"]
        Nginx -->|Định tuyến /phpmyadmin/| PMA["🗄️ phpMyAdmin 5.2"]
        Nginx -->|Định tuyến /grafana/ (WebSocket)| Grafana["📊 Grafana 11.2 (Dashboards)"]
    end

    subgraph "Mạng Docker Backend (news-backend-net - Cô lập hoàn toàn)"
        WebApp -->|Kết nối CSDL| MySQL["💾 MySQL 8.0 (Bcrypt, Least Privilege)"]
        PMA -->|Quản trị| MySQL
    end

    subgraph "Mạng Giám sát & Quản trị Log (news-monitoring-net)"
        Prometheus["📈 Prometheus 2.54 (TSDB Scraper)"] -->|Scrape| cAdvisor["🐳 cAdvisor (Container CPU/RAM)"]
        Prometheus -->|Scrape| NginxExp["⚡ Nginx Exporter"]
        Prometheus -->|Scrape| MySQLExp["🐬 MySQLd Exporter"]
        Prometheus -->|Scrape /api/metrics.php| WebApp

        Promtail["📝 Promtail 3.1 (Log Shipper)"] -->|Đọc log container & Nginx| WebApp
        Promtail -->|Đọc log| Nginx
        Promtail -->|Đẩy log gán nhãn| Loki["🗂️ Grafana Loki 3.1 (Log Aggregator)"]

        Grafana -->|Query Metrics| Prometheus
        Grafana -->|Query LogQL| Loki
    end
```

### Phân tách mạng nội bộ (Network Isolation):
1. **`news-frontend-net`**: Vùng mạng công cộng tiếp nhận lưu lượng qua Nginx Reverse Proxy, chuyển tiếp tới Web Tin tức, phpMyAdmin và Grafana.
2. **`news-backend-net`** (`internal: true`): Vùng mạng cô lập cơ sở dữ liệu tuyệt đối. Chỉ container `webapp` và `phpmyadmin` được phép kết nối với `db`. Không có bất kỳ cổng CSDL nào (3306) được mở ra máy chủ chủ (host).
3. **`news-monitoring-net`**: Vùng mạng riêng biệt cho các tác tử thu thập dữ liệu (cAdvisor, Exporters, Promtail) và máy chủ lưu trữ (Prometheus, Loki, Grafana).

---

## 2. Cấu Trúc Thư Mục Dự Án

```
Website Tin tức  Cổng thông tin/
├── .env.example                     # Mẫu biến môi trường an toàn (không chứa mật khẩu thật)
├── .env                             # Biến môi trường chính thức (được bảo vệ qua .gitignore)
├── .gitignore                       # Danh sách loại trừ Git (bảo vệ bí mật, keys, data)
├── docker-compose.yml               # File điều phối toàn bộ 11 container microservices
├── README.md                        # Tài liệu hướng dẫn triển khai toàn diện
├── package.json                     # Scripts kiểm thử và chạy preview nhanh
│
├── webapp/                          # [ỨNG DỤNG WEB TIN TỨC]
│   ├── Dockerfile                   # Dockerfile đóng gói PHP 8.2-apache + pdo_mysql
│   ├── config/database.php          # Kết nối CSDL MySQL bằng PDO UTF-8mb4
│   ├── includes/                    # Module lõi (Auth, RBAC, Logger, Header, Footer)
│   ├── assets/                      # CSS Tokens, Typography Plus Jakarta Sans, JS
│   ├── index.php                    # Trang chủ (Hero slider, tin đọc nhiều, danh sách tin)
│   ├── article.php                  # Chi tiết bài viết, tăng view, bình luận
│   ├── category.php                 # Lọc bài viết theo chuyên mục
│   ├── search.php                   # Tìm kiếm bài viết chống SQL Injection
│   ├── login.php                    # Đăng nhập bảo vệ chống Brute-Force & ghi log Loki
│   ├── logout.php                   # Đăng xuất và hủy phiên an toàn
│   ├── register.php                 # Đăng ký tài khoản độc giả
│   ├── profile.php                  # Quản lý hồ sơ và đổi mật khẩu
│   ├── admin/                       # Bàn quản trị tin tức, danh mục, người dùng & audit logs
│   └── api/                         # Endpoint comments (AJAX) & metrics (Prometheus)
│
├── db/                              # [CƠ SỞ DỮ LIỆU]
│   ├── init.sql                     # Schema 5 bảng, phân quyền least privilege & seed data
│   └── custom.cnf                   # Cấu hình MySQL 8.0 bảo mật (vô hiệu local_infile)
│
├── nginx/                           # [NGINX REVERSE PROXY]
│   ├── nginx.conf                   # Cấu hình chính: Ẩn server_tokens, Rate limiting login
│   ├── conf.d/default.conf          # Virtual host: Redirect 80->443, SSL TLS 1.2/1.3, Headers
│   └── ssl/                         # Thư mục chứa chứng chỉ RSA 2048-bit (server.crt, server.key)
│
├── prometheus/                      # [GIÁM SÁT PROMETHEUS]
│   ├── prometheus.yml               # Cấu hình Scrape 5 jobs (cadvisor, nginx, mysql, webapp)
│   └── alert_rules.yml              # Quy tắc cảnh báo tự động (Brute-force, RAM cao, 5xx)
│
├── grafana/                         # [TRỰC QUAN HÓA GRAFANA]
│   └── provisioning/
│       ├── datasources/             # Nạp tự động nguồn dữ liệu Prometheus & Loki
│       └── dashboards/              # Tự động nạp 4 Dashboards (Containers, Nginx, MySQL, Logs)
│
├── loki/                            # [MÁY CHỦ LƯU TRỮ LOG]
│   └── loki-config.yaml             # Cấu hình Grafana Loki v3.1.0 lưu trữ tsdb
│
├── promtail/                        # [TÁC TỬ THU THẬP LOG]
│   └── promtail-config.yaml         # Thu thập log từ Docker, gán nhãn job="nginx", "webapp", "mysql"
│
├── scripts/                         # [SCRIPTS VẬN HÀNH & KIỂM THỬ]
│   ├── gen-cert.sh                  # Tạo chứng chỉ SSL tự ký (Linux/Bash)
│   ├── gen-cert.ps1                 # Tạo chứng chỉ SSL tự ký (Windows PowerShell)
│   ├── simulate_brute_force.py      # Kịch bản mô phỏng tấn công Brute-force kích hoạt Alert
│   ├── post_deployment_check.py     # Kiểm tra tự động 7 tiêu chí bảo mật sau triển khai
│   └── post_deployment_check.sh     # Kiểm tra nhanh Security Headers bằng curl
│
└── preview/                         # [MÔI TRƯỜNG XEM TRƯỚC CỤC BỘ]
    └── preview-server.js            # Máy chủ Node.js phục vụ xem trước giao diện nhanh
```

---

## 3. Hướng Dẫn Cài Đặt & Khởi Chạy (Quickstart)

### Bước 1: Sao chép mã nguồn từ GitHub
```bash
git clone https://github.com/mathihien356-web/news-portal-devops.git
cd news-portal-devops
```

### Bước 2: Thiết lập cấu hình biến môi trường
```bash
# Tạo file .env từ file mẫu .env.example
cp .env.example .env

# Chỉnh sửa mật khẩu an toàn theo nhu cầu (đã có sẵn mật khẩu mặc định an toàn)
```

### Bước 3: Tạo chứng chỉ số HTTPS tự ký
Chứng chỉ số SSL RSA 2048-bit có thể được sinh tự động thông qua script:
- **Trên Linux / macOS:**
  ```bash
  chmod +x scripts/gen-cert.sh
  ./scripts/gen-cert.sh
  ```
- **Trên Windows (PowerShell):**
  ```powershell
  powershell -ExecutionPolicy Bypass -File .\scripts\gen-cert.ps1
  ```

### Bước 4: Khởi chạy toàn bộ hệ thống bằng Docker Compose
```bash
docker compose up -d --build
```

### Bước 5: Kiểm tra trạng thái các container dịch vụ
```bash
docker compose ps
```
*Tất cả 11 dịch vụ phải ở trạng thái `Up` hoặc `Up (healthy)`.*

---

## 4. Thông Tin Cổng Dịch Vụ & Tài Khoản Mặc Định

### 🔗 Danh Sách Điểm Truy Cập Hệ Thống:
| Dịch vụ | Giao thức / URL | Mô tả chức năng |
| :--- | :--- | :--- |
| **Website Tin Tức** | `https://localhost` | Giao diện báo chí số, đọc tin, bình luận, quản trị bài viết |
| **Chuyển hướng HTTP** | `http://localhost` | Tự động chuyển hướng (HTTP 301 Redirect) sang `https://localhost` |
| **phpMyAdmin** | `https://localhost/phpmyadmin/` | Quản trị CSDL MySQL 8.0 qua giao diện web trực quan |
| **Grafana Dashboards** | `https://localhost/grafana/` | Bảng điều khiển giám sát tài nguyên và phân tích nhật ký |
| **Prometheus Metrics** | `https://localhost/api/metrics.php` | Endpoint cung cấp số liệu nghiệp vụ ứng dụng cho Prometheus |

---

### 🔑 Tài Khoản Đăng Nhập Mặc Định:
| Hệ thống / Phân quyền | Tên đăng nhập (Username) | Mật khẩu (Password) | Vai trò & Đặc quyền |
| :--- | :--- | :--- | :--- |
| **Quản trị viên Portal (Admin)** | `admin` | `Admin@123456` | Toàn quyền quản trị bài viết, chuyên mục, tài khoản, audit logs |
| **Biên tập viên (Author)** | `mathihien` | `Hien@123456` | Tác giả Ma Thị Hiền: Viết, sửa, xuất bản bài viết của mình |
| **Phóng viên (Author)** | `nguyenvanan` | `An@123456` | Tác giả Nguyễn Văn An: Viết bài và biên tập nội dung |
| **Độc giả (Reader)** | `reader1` | `Reader@123456` | Xem tin tức, gửi bình luận ý kiến bài viết |
| **phpMyAdmin (DB Root)** | `root` | *Xem trong file .env* | Quản trị cấp cao nhất cơ sở dữ liệu MySQL |
| **phpMyAdmin (App User)** | `news_user` | *Xem trong file .env* | Tài khoản ứng dụng (Least Privilege: chỉ SELECT/INSERT/UPDATE/DELETE) |
| **Grafana** | `admin` | `GrafanaAdminPass20Char!` | Quản trị viên theo dõi dashboard và tra cứu LogQL |

---

## 5. Cơ Sở Dữ Liệu & Chức Năng Ứng Dụng Web

### Chuẩn hóa CSDL (MySQL 8.0 InnoDB):
1. **`users`**: Lưu trữ tài khoản, mật khẩu băm theo chuẩn **Bcrypt**, phân quyền vai trò (`admin`, `author`, `reader`) và trạng thái kích hoạt (`is_active`).
2. **`categories`**: Quản lý danh mục báo chí (`thoi-su`, `cong-nghe`, `kinh-te`, `doi-song`, `the-thao`, `giai-tri`).
3. **`articles`**: Bài viết tin tức với tiêu đề, đường dẫn thân thiện (slug), ảnh đại diện (thumbnail), tóm tắt, nội dung HTML, lượt xem (`views`), trạng thái (`published`, `draft`, `archived`) và đánh dấu tin tiêu điểm (`is_featured`).
4. **`comments`**: Bình luận của độc giả gắn với bài viết, hỗ trợ gửi AJAX mượt mà và chống spam.
5. **`audit_logs`**: Ghi vết sự kiện an toàn thông tin (đăng nhập thành công/thất bại, sửa xóa bài viết, địa chỉ IP nguồn, User-Agent).

### Cơ chế Bảo mật trong Mã Nguồn:
- **Chống SQL Injection 100%:** Toàn bộ truy vấn cơ sở dữ liệu đều sử dụng **PDO Prepared Statements** kết hợp tham số ràng buộc (Binding Parameters).
- **Chống Cross-Site Scripting (XSS):** Dữ liệu xuất ra màn hình được làm sạch thông qua hàm `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Chống Cross-Site Request Forgery (CSRF):** Mọi form thực thi đều được nhúng mã Token sinh ngẫu nhiên `bin2hex(random_bytes(32))` và xác thực bằng `hash_equals`.
- **Bảo vệ phiên làm việc (Session Security):** Cookie phiên được cấu hình bắt buộc với các cờ `HttpOnly`, `SameSite=Strict` và `Secure` khi chạy qua HTTPS.
- **Ghi log có cấu trúc chuẩn:** Khi phát sinh hành vi đăng nhập sai, ứng dụng lập tức ghi từ khóa `LOGIN_FAILED` ra luồng `STDERR` phục vụ tác tử Promtail thu thập và chuyển tiếp về Loki.

---

## 6. Nginx Reverse Proxy & Cấu Hình HTTPS/SSL

Nginx (phiên bản `1.27-alpine`) đóng vai trò cổng tiếp nhận lưu lượng duy nhất từ bên ngoài:
- **Chuyển hướng HTTPS bắt buộc:** Yêu cầu HTTP cổng 80 được chuyển hướng (HTTP 301 Redirect) sang cổng mã hóa 443.
- **Mã hóa TLS an toàn:** Giới hạn giao thức chỉ cho phép **TLSv1.2** và **TLSv1.3**, từ chối các giao thức cũ tiềm ẩn lỗ hổng (TLSv1.0, TLSv1.1).
- **Bộ Security Headers tiêu chuẩn:**
  - `X-Frame-Options: SAMEORIGIN` (Chống tấn công Clickjacking)
  - `X-Content-Type-Options: nosniff` (Ngăn chặn MIME-type Sniffing)
  - `X-XSS-Protection: 1; mode=block` (Kích hoạt bộ lọc XSS của trình duyệt)
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (Ép buộc duyệt web qua HTTPS)
  - `Content-Security-Policy (CSP)` phù hợp bảo vệ tài nguyên
- **Ẩn thông tin phiên bản:** Khai báo `server_tokens off` để ẩn phiên bản máy chủ Nginx trong Header phản hồi.
- **Bảo vệ chống tấn công Brute-force (Rate Limiting):** Cấu hình `limit_req_zone $binary_remote_addr zone=login_limit:10m rate=5r/m;` đối với endpoint `/login.php`, trả về mã **HTTP 429 Too Many Requests** khi phát hiện yêu cầu dồn dập.

---

## 7. Hạ Tầng Giám Sát Prometheus & Grafana

Hệ thống triển khai bộ công cụ giám sát hiệu năng toàn diện (Observability) bằng **Prometheus (v2.54.1)** và **Grafana (v11.2.0)**:
- **Scrape Jobs của Prometheus (15 giây/lần):**
  1. `prometheus`: Giám sát máy chủ Prometheus nội bộ.
  2. `cadvisor`: Giám sát tỷ lệ CPU %, RAM tiêu thụ và Network I/O của toàn bộ các container Docker.
  3. `nginx`: Đọc chỉ số kết nối (Active Connections, Request Rate) từ `/nginx_status`.
  4. `mysql`: Đo lường số lượng truy vấn trên giây (QPS), số kết nối đồng thời và hiệu suất bộ nhớ đệm InnoDB Buffer Pool.
  5. `webapp`: Thu thập số liệu nghiệp vụ cổng tin tức qua endpoint `/api/metrics.php`.
- **Grafana Provisioning:** Tự động nạp sẵn Data Sources và 4 bộ Dashboard chuyên nghiệp:
  - *Dashboard 1:* Giám sát tài nguyên phần cứng Container (cAdvisor).
  - *Dashboard 2:* Giám sát máy chủ web Nginx Reverse Proxy.
  - *Dashboard 3:* Giám sát hiệu năng cơ sở dữ liệu MySQL 8.0.
  - *Dashboard 4:* Giám sát nhật ký tập trung và an toàn thông tin (Loki Logs).

---

## 8. Quản Lý Nhật Ký Tập Trung Loki & Promtail

Hệ thống log tập trung được thiết lập bằng bộ đôi **Grafana Loki (v3.1.0)** và **Promtail (v3.1.0)**:
- Promtail tự động thu thập nhật ký hoạt động từ các container Docker, phân tích pipeline và gán nhãn dịch vụ: `job="nginx"`, `job="webapp"`, `job="mysql"`.
- Các câu truy vấn **LogQL** tiêu chuẩn đã được kiểm chứng thành công trên hệ thống:
  1. **Lọc log lỗi HTTP 5xx từ Nginx access log:**
     ```logql
     {job="nginx"} |~ "HTTP/[0-9.]+\" 5[0-9]{2} "
     ```
  2. **Tìm kiếm các thông điệp lỗi hoặc ngoại lệ trong ứng dụng Web:**
     ```logql
     {job="webapp"} |~ "(?i)(error|exception|failed)"
     ```
  3. **Lọc toàn bộ đăng nhập thất bại phục vụ điều tra an toàn thông tin:**
     ```logql
     {job="webapp"} |= "LOGIN_FAILED"
     ```
  4. **Đo tần suất đăng nhập sai theo phút phục vụ giám sát và kích hoạt cảnh báo:**
     ```logql
     sum(count_over_time({job="webapp"} |= "LOGIN_FAILED" [1m]))
     ```

---

## 9. Kịch Bản Thực Nghiệm An Toàn: Tấn Công Dò Mật Khẩu (Brute-Force)

Nhằm đáp ứng yêu cầu thực nghiệm an ninh vận hành, hệ thống tích hợp kịch bản kiểm thử quy trình phát hiện, cảnh báo và ngăn chặn tự động:

### Bước 1: Thực thi kịch bản tấn công mô phỏng
Chạy script mô phỏng gửi 20 yêu cầu đăng nhập liên tiếp với mật khẩu sai nhắm vào tài khoản quản trị viên `admin`:
```bash
python scripts/simulate_brute_force.py
```

### Bước 2: Kích hoạt Cảnh báo Tự động (Alert Firing)
Khi tần suất đăng nhập sai vượt quá 5 lần/phút, quy tắc cảnh báo `NewsPortalBruteForceLogin` trên Prometheus chuyển sang trạng thái **FIRING**, thông báo tới Dashboard của Grafana.

### Bước 3: Truy vết và Điều tra số (Forensics)
Quản trị viên truy cập **Grafana Explore** -> Chọn nguồn dữ liệu **Loki** và thực thi câu truy vấn:
```logql
{job="webapp"} |= "LOGIN_FAILED"
```
Kết quả hiển thị chính xác địa chỉ IP nguồn thực hiện hành vi tấn công, thời gian chi tiết từng lần thử và tài khoản bị nhắm tới.

### Bước 4: Ngăn chặn tự động bằng Rate Limiting
Nginx Reverse Proxy phát hiện các yêu cầu vượt ngưỡng tần suất định mức và lập tức từ chối các request tiếp theo bằng mã phản hồi **HTTP 429 Too Many Requests**, ngăn chặn triệt để nguy cơ làm sập máy chủ hoặc dò trúng mật khẩu.

---

## 10. Các Biện Pháp Hardening & Kiểm Tra Sau Triển Khai

Sáu biện pháp củng cố an toàn thông tin (Hardening) đã được áp dụng toàn diện:
1. **Phân tách mạng nội bộ (Network Isolation):** Chia hệ thống làm 3 vùng mạng, CSDL cô lập trong `backend-net (internal: true)`, chỉ duy nhất Nginx mở cổng 80 và 443 ra ngoài.
2. **Quản lý mật khẩu mạnh và biến môi trường:** Mật khẩu bảo mật 20 ký tự lưu trong file `.env`, tệp mẫu `.env.example` không chứa mật khẩu thật, loại trừ khỏi Git qua `.gitignore`.
3. **Nguyên tắc đặc quyền tối thiểu CSDL (Least Privilege):** Ứng dụng web dùng tài khoản `news_user` chỉ có quyền `SELECT, INSERT, UPDATE, DELETE` trên CSDL `news_portal`, không cấp quyền `DROP`, `ALTER`. Exporter dùng tài khoản riêng chỉ có quyền đọc `performance_schema`.
4. **Chạy Container với quyền tối thiểu:** Áp dụng `no-new-privileges: true` cho tất cả container, đặt hệ thống tệp chỉ đọc (`read_only: true`) cho Nginx, đồng thời giới hạn tài nguyên CPU và RAM định mức.
5. **Bảo vệ thông tin máy chủ & Cookie:** Ẩn phiên bản máy chủ Nginx (`server_tokens off`), loại bỏ `X-Powered-By`, cookie phiên có đủ cờ `HttpOnly`, `SameSite=Strict` và `Secure`.
6. **Script tự động kiểm tra hệ thống sau triển khai:**
   ```bash
   # Chạy script Python kiểm định 7 tiêu chí an toàn:
   python scripts/post_deployment_check.py

   # Hoặc kiểm tra nhanh qua curl (Linux/Bash):
   chmod +x scripts/post_deployment_check.sh
   ./scripts/post_deployment_check.sh
   ```

---

## 11. Lịch Sử Commit & Quản Lý Nhánh Trên GitHub

Dự án được quản lý chặt chẽ trên GitHub theo đúng thông tin sinh viên **Ma Thị Hiền** ([`mathihien356-web`](https://github.com/mathihien356-web)) với lịch sử commit rõ ràng theo từng giai đoạn triển khai:

- **Nhánh triển khai chính:** `main` (Production release) và `develop` (Tích hợp phát triển).
- **Lịch sử Commit theo từng bước:**
  1. `commit 1`: `feat(app-db): Khởi tạo ứng dụng web tin tức và cơ sở dữ liệu MySQL`
  2. `commit 2`: `feat(nginx-proxy): Cấu hình Nginx Reverse Proxy, SSL HTTPS và Security Headers`
  3. `commit 3`: `feat(monitoring): Tích hợp Prometheus và Grafana giám sát tài nguyên`
  4. `commit 4`: `feat(logging): Thiết lập hệ thống log tập trung với Grafana Loki và Promtail`
  5. `commit 5`: `feat(security-hardening): Áp dụng các biện pháp Hardening và kịch bản Brute-force`

---
*Báo cáo và mã nguồn được xây dựng hoàn thiện phục vụ học phần Triển khai và Quản trị Hệ thống Phần mềm - Trường Đại học Công nghệ Thông tin và Truyền thông Thái Nguyên.*
