# ==============================================================================
# HỆ THỐNG CỔNG THÔNG TIN ĐIỆN TỬ - DOCKERFILE CHO DỊCH VỤ WEB (PHP 8.2 APACHE)
# Tác giả: Ma Thị Hiền (MSSV: DTC245200948 - Lớp: CNTTK23C)
# ==============================================================================

FROM php:8.2-apache-bullseye

# Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Cấu hình và cài đặt tiện ích mở rộng PHP: pdo, pdo_mysql, gd, opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        opcache

# Kích hoạt các module Apache cần thiết (rewrite, headers)
RUN a2enmod rewrite headers

# Cấu hình bảo mật Apache: Ẩn thông tin phiên bản máy chủ
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "TraceEnable Off" >> /etc/apache2/apache2.conf

# Cấu hình PHP tối ưu và bảo mật
RUN { \
    echo "expose_php = Off"; \
    echo "memory_limit = 256M"; \
    echo "upload_max_filesize = 16M"; \
    echo "post_max_size = 20M"; \
    echo "max_execution_time = 60"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.cookie_samesite = Strict"; \
    echo "session.use_strict_mode = 1"; \
    echo "date.timezone = Asia/Ho_Chi_Minh"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.interned_strings_buffer = 8"; \
    echo "opcache.max_accelerated_files = 4000"; \
    echo "opcache.validate_timestamps = 0"; \
} > /usr/local/etc/php/conf.d/security.ini

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Sao chép toàn bộ mã nguồn ứng dụng web vào container
COPY . /var/www/html/

# Phân quyền tệp tin an toàn (chạy dưới quyền www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Chạy Apache trên cổng 80 nội bộ container
EXPOSE 80

CMD ["apache2-foreground"]
