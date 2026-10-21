</main>

<!-- Footer Cổng thông tin -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Cột giới thiệu hệ thống -->
            <div class="footer-col">
                <div class="logo-brand" style="margin-bottom: 14px;">
                    <div class="logo-text">NEWS<span>PORTAL</span></div>
                </div>
                <p style="font-size: 0.88rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 16px;">
                    Cổng thông tin điện tử và Báo chí số tích hợp hệ thống microservices container hóa hiện đại trên Docker, Nginx Reverse Proxy mã hóa SSL/TLS, hệ thống giám sát Prometheus + Grafana và quản lý nhật ký tập trung Grafana Loki.
                </p>
                <div style="font-size: 0.82rem; color: var(--text-secondary);">
                    <strong>Sinh viên thực hiện:</strong> Ma Thị Hiền<br>
                    <strong>Mã số sinh viên (MSSV):</strong> DTC245200948 - Lớp: CNTTK23C<br>
                    <strong>Khoa:</strong> Công nghệ Thông tin - Đại học CNTT & Truyền thông (ICTU)
                </div>
            </div>

            <!-- Cột chuyên mục nhanh -->
            <div class="footer-col">
                <h4>Chuyên mục chính</h4>
                <ul class="footer-links">
                    <li><a href="/category.php?slug=thoi-su">Thời sự & Xã hội</a></li>
                    <li><a href="/category.php?slug=cong-nghe">Công nghệ số & AI</a></li>
                    <li><a href="/category.php?slug=kinh-te">Kinh tế & Đầu tư</a></li>
                    <li><a href="/category.php?slug=doi-song">Đời sống & Văn hóa</a></li>
                    <li><a href="/category.php?slug=the-thao">Thể thao 24/7</a></li>
                </ul>
            </div>

            <!-- Cột liên kết hạ tầng DevOps -->
            <div class="footer-col">
                <h4>Hạ tầng & Giám sát</h4>
                <ul class="footer-links">
                    <li><a href="/api/metrics.php" target="_blank">Endpoint Metrics (Prometheus)</a></li>
                    <li><a href="/grafana/" target="_blank">Grafana Dashboards</a></li>
                    <li><a href="/phpmyadmin/" target="_blank">Quản trị Cơ sở dữ liệu</a></li>
                    <li><a href="https://github.com/mathihien356-web/news-portal-devops" target="_blank">Mã nguồn GitHub (mathihien356-web)</a></li>
                </ul>
            </div>

            <!-- Cột thông tin liên hệ & bản quyền -->
            <div class="footer-col">
                <h4>Liên hệ Tòa soạn</h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px;">
                    Tòa soạn Cổng thông tin điện tử ICTU<br>
                    Địa chỉ: Đường Z115, Quyết Thắng, TP. Thái Nguyên<br>
                    Hotline: 0280.3846.254 - Email: dtc245200948@ictu.edu.vn
                </p>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <span style="display: inline-block; padding: 4px 10px; background: var(--bg-surface); border-radius: var(--radius-sm); font-size: 0.75rem; border: 1px solid var(--border-color); color: #38bdf8;">
                        🔒 SSL 2048-bit Encrypted
                    </span>
                    <span style="display: inline-block; padding: 4px 10px; background: var(--bg-surface); border-radius: var(--radius-sm); font-size: 0.75rem; border: 1px solid var(--border-color); color: #34d399;">
                        🐳 Docker Containerized
                    </span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Cổng Thông Tin Điện Tử NewsPortal. Đồ án Triển khai và Quản trị Hệ thống Phần mềm - Đại học CNTT & Truyền thông.</p>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
</body>
</html>
