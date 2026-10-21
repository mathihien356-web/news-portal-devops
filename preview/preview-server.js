// ==============================================================================
// MÁY CHỦ XEM TRƯỚC CỤC BỘ DÀNH CHO WINDOWS / NODE.JS (PREVIEW RUNNER)
// Giúp lập trình viên kiểm thử tức thì giao diện, xác thực và các chức năng
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948 - Lớp: CNTTK23C)
// ==============================================================================

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 3000;
const WEBAPP_DIR = path.join(__dirname, '..', 'webapp');

// Mock Data trong bộ nhớ tương ứng với init.sql
const state = {
    users: [
        { id: 1, username: 'admin', fullName: 'Quản trị viên Hệ thống', role: 'admin', password: 'Admin@123456', avatar: '/assets/images/avatar-admin.png' },
        { id: 2, username: 'mathihien', fullName: 'Ma Thị Hiền', role: 'author', password: 'Hien@123456', avatar: '/assets/images/avatar-hien.png' },
        { id: 3, username: 'nguyenvanan', fullName: 'Nguyễn Văn An', role: 'author', password: 'An@123456', avatar: '/assets/images/avatar-an.png' },
        { id: 4, username: 'reader1', fullName: 'Trần Minh Đức', role: 'reader', password: 'Reader@123456', avatar: '/assets/images/avatar-reader.png' }
    ],
    categories: [
        { id: 1, name: 'Thời sự', slug: 'thoi-su', description: 'Tin tức chính trị, xã hội nổi bật' },
        { id: 2, name: 'Công nghệ', slug: 'cong-nghe', description: 'Đổi mới sáng tạo, AI, DevOps và Cloud' },
        { id: 3, name: 'Kinh tế', slug: 'kinh-te', description: 'Kinh tế số, tài chính và khởi nghiệp' },
        { id: 4, name: 'Đời sống', slug: 'doi-song', description: 'Văn hóa, giáo dục và phong cách sống' },
        { id: 5, name: 'Thể thao', slug: 'the-thao', description: 'Bóng đá và thể thao học đường' },
        { id: 6, name: 'Giải trí', slug: 'giai-tri', description: 'Nghệ thuật, điện ảnh và âm nhạc' }
    ],
    articles: [
        {
            id: 1,
            title: 'Kỷ nguyên AI và Chuyển đổi số: Xu hướng công nghệ đột phá định hình tương lai',
            slug: 'ky-nguyen-ai-va-chuyen-doi-so-2026',
            categoryId: 2,
            categoryName: 'Công nghệ',
            authorName: 'Ma Thị Hiền',
            summary: 'Trí tuệ nhân tạo (AI) đang phát triển với tốc độ chưa từng có, mở ra cơ hội to lớn cho các doanh nghiệp tự động hóa và nâng cao năng suất.',
            content: '<p>Trí tuệ nhân tạo (AI) không còn là khái niệm xa vời mà đã trở thành công cụ thiết yếu. Tại Việt Nam, chiến lược quốc gia về AI đang mở ra vận hội mới cho toàn ngành công nghệ thông tin.</p><h3>An toàn thông tin và Observability</h3><p>Song hành cùng phát triển là bài toán giám sát hệ thống tập trung với Prometheus, Grafana và Grafana Loki giúp phát hiện sự cố từ sớm.</p>',
            thumbnail: '/assets/images/news-ai-tech.jpg',
            views: 1520,
            isFeatured: 1,
            publishedAt: '2026-10-21 14:30'
        },
        {
            id: 2,
            title: 'Thái Nguyên đẩy mạnh phát triển hạ tầng số và đô thị thông minh kiểu mẫu',
            slug: 'thai-nguyen-day-manh-ha-tang-so',
            categoryId: 1,
            categoryName: 'Thời sự',
            authorName: 'Nguyễn Văn An',
            summary: 'Tỉnh Thái Nguyên tiếp tục khẳng định vị thế dẫn đầu trong tiến trình chuyển đổi số với hàng loạt dự án ứng dụng công nghệ phục vụ người dân.',
            content: '<p>Với mục tiêu lấy người dân và doanh nghiệp làm trung tâm, hơn 95% thủ tục hành chính đã được số hóa toàn trình.</p>',
            thumbnail: '/assets/images/news-smart-city.jpg',
            views: 840,
            isFeatured: 1,
            publishedAt: '2026-10-21 11:15'
        },
        {
            id: 3,
            title: 'Kinh tế số Việt Nam bứt phá mạnh mẽ, ghi nhận mức tăng trưởng kỷ lục',
            slug: 'kinh-te-so-viet-nam-but-pha-manh-me',
            categoryId: 3,
            categoryName: 'Kinh tế',
            authorName: 'Nguyễn Văn An',
            summary: 'Thương mại điện tử và thanh toán số đóng góp tỷ trọng ngày càng cao vào cơ cấu GDP toàn quốc.',
            content: '<p>Báo cáo kinh tế số ghi nhận tốc độ phục hồi và bứt phá ấn tượng trong mọi lĩnh vực phân phối bán lẻ.</p>',
            thumbnail: '/assets/images/news-economy.jpg',
            views: 610,
            isFeatured: 0,
            publishedAt: '2026-10-20 09:00'
        },
        {
            id: 4,
            title: 'Xu hướng Containerization và DevOps: Tối ưu quy trình triển khai phần mềm hiện đại',
            slug: 'xu-huong-containerization-va-devops',
            categoryId: 2,
            categoryName: 'Công nghệ',
            authorName: 'Ma Thị Hiền',
            summary: 'Sự kết hợp giữa Docker, Prometheus, Grafana và Loki mang lại khả năng quản trị và quan sát toàn diện.',
            content: '<p>Đóng gói ứng dụng thành các microservices độc lập giúp loại bỏ hoàn toàn sự sai lệch giữa môi trường dev và prod.</p>',
            thumbnail: '/assets/images/news-devops.jpg',
            views: 1290,
            isFeatured: 1,
            publishedAt: '2026-10-19 16:20'
        }
    ],
    comments: [
        { id: 1, articleId: 1, userName: 'Trần Minh Đức', content: 'Bài viết phân tích rất sâu sắc về xu hướng AI và DevOps!', createdAt: '2026-10-21 15:00' }
    ],
    auditLogs: [
        { id: 1, time: '2026-10-21 12:00:00', action: 'SYSTEM_INIT', user: 'system', ip: '127.0.0.1', details: 'Hệ thống tin tức khởi tạo thành công.' }
    ]
};

// Simple Cookie Parser
function parseCookies(req) {
    const list = {};
    const rc = req.headers.cookie;
    if (rc) {
        rc.split(';').forEach(cookie => {
            const parts = cookie.split('=');
            list[parts.shift().trim()] = decodeURI(parts.join('='));
        });
    }
    return list;
}

function renderHeader(currentUser, title = 'NewsPortal') {
    const catLinks = state.categories.slice(0, 5).map(c => 
        `<li><a href="/category.php?slug=${c.slug}" class="nav-link">${c.name}</a></li>`
    ).join('');

    const authSection = currentUser ? `
        <div class="user-menu-btn">
            <div class="user-avatar-mini" style="display:flex;align-items:center;justify-content:center;background:#0284c7;color:#fff;font-weight:700;">
                ${currentUser.fullName.charAt(0)}
            </div>
            <span style="font-size:0.88rem; font-weight:600; color:var(--text-primary);">${currentUser.fullName}</span>
            <span class="user-role-badge role-${currentUser.role}">${currentUser.role.toUpperCase()}</span>
            <a href="/admin/index.php" style="margin-left:8px;font-size:0.8rem;color:var(--primary);">Quản trị</a>
            <a href="/logout.php" style="margin-left:8px;font-size:0.8rem;color:#f43f5e;">Thoát</a>
        </div>
    ` : `
        <div style="display:flex; gap:8px;">
            <a href="/login.php" class="btn btn-outline" style="padding:7px 14px; font-size:0.85rem;">Đăng nhập</a>
            <a href="/register.php" class="btn btn-primary" style="padding:7px 14px; font-size:0.85rem;">Đăng ký</a>
        </div>
    `;

    return `
<!DOCTYPE html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${title} | NewsPortal DevOps</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="top-bar">
    <div class="container top-bar-content">
        <div class="breaking-news">
            <span class="breaking-badge">NÓNG</span>
            <span>Hệ thống Website Tin tức & Cổng thông tin đã tích hợp đầy đủ Docker, SSL, Nginx, Prometheus, Grafana và Loki</span>
        </div>
        <div>Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)</div>
    </div>
</div>
<header class="site-header">
    <div class="container navbar">
        <a href="/" class="logo-brand">
            <div class="logo-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path></svg>
            </div>
            <div>
                <div class="logo-text">NEWS<span>PORTAL</span></div>
                <div class="logo-subtitle">Cổng Thông Tin Số</div>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="/" class="nav-link active">Trang chủ</a></li>
            ${catLinks}
        </ul>
        <div class="nav-actions">
            <form action="/search.php" method="GET" class="search-box-mini">
                <input type="text" name="q" placeholder="Tìm kiếm tin tức...">
            </form>
            <button id="themeToggleBtn" class="theme-toggle-btn" title="Chuyển chế độ sáng/tối">🌓</button>
            ${authSection}
        </div>
    </div>
</header>
<main>
    `;
}

function renderFooter() {
    return `
</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="logo-brand" style="margin-bottom:14px;"><div class="logo-text">NEWS<span>PORTAL</span></div></div>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.6;">
                    Đồ án môn Triển khai và Quản trị Hệ thống Phần mềm - Khoa CNTT - Đại học CNTT & Truyền thông (ICTU).
                </p>
                <div style="font-size:0.82rem; color:var(--text-secondary); margin-top:8px;">
                    <strong>Sinh viên thực hiện:</strong> Ma Thị Hiền<br>
                    <strong>MSSV:</strong> DTC245200948 - Lớp: CNTTK23C
                </div>
            </div>
            <div class="footer-col">
                <h4>Chuyên mục</h4>
                <ul class="footer-links">
                    <li><a href="/category.php?slug=cong-nghe">Công nghệ số & AI</a></li>
                    <li><a href="/category.php?slug=thoi-su">Thời sự & Xã hội</a></li>
                    <li><a href="/category.php?slug=kinh-te">Kinh tế số</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Hạ tầng DevOps</h4>
                <ul class="footer-links">
                    <li><a href="/api/metrics.php" target="_blank">Prometheus Metrics Endpoint</a></li>
                    <li><a href="https://github.com/mathihien356-web/news-portal-devops" target="_blank">GitHub Repository</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Bảo Mật & Vận Hành</h4>
                <p style="font-size:0.85rem; color:var(--text-muted);">
                    Hệ thống chạy trên Docker Compose gồm 11 dịch vụ, cô lập 3 mạng Docker, Reverse Proxy Nginx mã hóa HTTPS, Promtail + Loki và Prometheus + Grafana.
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 NewsPortal. Bản quyền thuộc về Ma Thị Hiền - ICTU.</p>
        </div>
    </div>
</footer>
<script src="/assets/js/main.js"></script>
</body>
</html>
    `;
}

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    const cookies = parseCookies(req);
    const loggedUser = state.users.find(u => u.username === cookies.session_user);

    // 1. Phục vụ tài nguyên tĩnh (assets)
    if (pathname.startsWith('/assets/')) {
        const filePath = path.join(WEBAPP_DIR, pathname);
        if (fs.existsSync(filePath)) {
            const ext = path.extname(filePath).toLowerCase();
            const mimeTypes = {
                '.css': 'text/css',
                '.js': 'application/javascript',
                '.png': 'image/png',
                '.jpg': 'image/jpeg',
                '.jpeg': 'image/jpeg',
                '.svg': 'image/svg+xml'
            };
            res.writeHead(200, { 'Content-Type': mimeTypes[ext] || 'application/octet-stream' });
            return fs.createReadStream(filePath).pipe(res);
        }
    }

    // 2. Metrics Endpoint cho Prometheus
    if (pathname === '/api/metrics.php') {
        res.writeHead(200, { 'Content-Type': 'text/plain; version=0.0.4; charset=utf-8' });
        let metrics = `# HELP news_portal_articles_total Tong so bai viet theo trang thai\n# TYPE news_portal_articles_total gauge\n`;
        metrics += `news_portal_articles_total{status="published"} ${state.articles.length}\n`;
        metrics += `\n# HELP news_portal_views_total Tong so luot xem tat ca bai viet\n# TYPE news_portal_views_total counter\n`;
        const totalViews = state.articles.reduce((acc, a) => acc + a.views, 0);
        metrics += `news_portal_views_total ${totalViews}\n`;
        metrics += `\n# HELP news_portal_users_total Tong so nguoi dung\n# TYPE news_portal_users_total gauge\n`;
        metrics += `news_portal_users_total{role="admin"} 1\nnews_portal_users_total{role="author"} 2\nnews_portal_users_total{role="reader"} 1\n`;
        metrics += `\n# HELP news_portal_app_info Thong tin phien ban\n# TYPE news_portal_app_info gauge\n`;
        metrics += `news_portal_app_info{version="1.0.0",author="MaThiHien",student_id="DTC245200948"} 1\n`;
        return res.end(metrics);
    }

    // 3. API Bình luận AJAX
    if (pathname === '/api/comments.php' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                const artId = parseInt(data.article_id);
                const content = (data.content || '').trim();
                const uName = loggedUser ? loggedUser.fullName : 'Độc giả khách';
                const newComment = { id: state.comments.length + 1, articleId: artId, userName: uName, content, createdAt: 'Vừa xong' };
                state.comments.unshift(newComment);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, comment: { user_name: uName, content } }));
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, message: 'Bad JSON' }));
            }
        });
        return;
    }

    // 4. Đăng nhập (POST /login.php)
    if (pathname === '/login.php' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            const params = new URLSearchParams(body);
            const username = (params.get('username') || '').trim();
            const password = params.get('password') || '';
            const user = state.users.find(u => (u.username === username || u.email === username) && u.password === password);

            if (user) {
                console.log(`[${new Date().toISOString()}] [SECURITY] [INFO] LOGIN_SUCCESS user=${user.username} role=${user.role}`);
                state.auditLogs.unshift({
                    id: state.auditLogs.length + 1,
                    time: new Date().toISOString().replace('T', ' ').slice(0, 19),
                    action: 'LOGIN_SUCCESS',
                    user: user.username,
                    ip: req.socket.remoteAddress || '127.0.0.1',
                    details: `Đăng nhập thành công với vai trò ${user.role}`
                });
                res.writeHead(302, {
                    'Set-Cookie': `session_user=${user.username}; Path=/; HttpOnly; SameSite=Strict`,
                    'Location': user.role === 'admin' || user.role === 'author' ? '/admin/index.php' : '/'
                });
                return res.end();
            } else {
                // GHI NHẬN LOGIN_FAILED RA CONSOLE / STDERR CHO PROMTAIL LOKI
                console.error(`[${new Date().toISOString()}] [SECURITY] [WARN] LOGIN_FAILED user=${username} ip=${req.socket.remoteAddress}`);
                state.auditLogs.unshift({
                    id: state.auditLogs.length + 1,
                    time: new Date().toISOString().replace('T', ' ').slice(0, 19),
                    action: 'LOGIN_FAILED',
                    user: username || 'anonymous',
                    ip: req.socket.remoteAddress || '127.0.0.1',
                    details: `Đăng nhập sai mật khẩu cho tài khoản: ${username}`
                });
                res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
                return res.end(renderHeader(null, 'Đăng Nhập') + `
                    <div class="auth-page-container">
                        <div class="auth-card">
                            <div class="alert-message alert-error">Tên đăng nhập hoặc mật khẩu không chính xác! Đã ghi log an toàn.</div>
                            <a href="/login.php" class="btn btn-primary" style="width:100%; text-align:center;">Thử lại</a>
                        </div>
                    </div>
                ` + renderFooter());
            }
        });
        return;
    }

    // 5. Đăng xuất (/logout.php)
    if (pathname === '/logout.php') {
        res.writeHead(302, {
            'Set-Cookie': 'session_user=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT',
            'Location': '/'
        });
        return res.end();
    }

    // 6. Trang Đăng nhập (GET /login.php)
    if (pathname === '/login.php') {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(renderHeader(loggedUser, 'Đăng Nhập') + `
            <div class="auth-page-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1 class="auth-title">Đăng Nhập Cổng Tin Tức</h1>
                        <p style="color:var(--text-secondary); font-size:0.9rem;">Hệ thống xác thực và bảo vệ phiên</p>
                    </div>
                    <form method="POST" action="/login.php">
                        <div class="form-group">
                            <label class="form-label">Tài khoản (admin, mathihien, reader1)</label>
                            <input type="text" name="username" class="form-input" required value="admin">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-input" required value="Admin@123456">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; margin-top:10px;">Đăng Nhập</button>
                    </form>
                    <div style="margin-top:20px; padding:12px; background:var(--bg-surface-elevated); border-radius:8px; font-size:0.8rem; border:1px dashed var(--border-color);">
                        <strong>Tài khoản thử nghiệm:</strong><br>
                        - Admin: <code>admin</code> / <code>Admin@123456</code><br>
                        - Author: <code>mathihien</code> / <code>Hien@123456</code><br>
                        - Reader: <code>reader1</code> / <code>Reader@123456</code>
                    </div>
                </div>
            </div>
        ` + renderFooter());
    }

    // 7. Bàn Quản trị Admin (/admin/index.php)
    if (pathname.startsWith('/admin/')) {
        if (!loggedUser || (loggedUser.role !== 'admin' && loggedUser.role !== 'author')) {
            res.writeHead(302, { 'Location': '/login.php' });
            return res.end();
        }

        const logRows = state.auditLogs.slice(0, 5).map(l => `
            <tr>
                <td style="font-size:0.82rem; color:var(--text-muted);">${l.time}</td>
                <td><code style="color:${l.action.includes('FAILED') ? '#f43f5e' : '#38bdf8'}">${l.action}</code></td>
                <td><code>${l.ip}</code></td>
                <td style="font-size:0.85rem; color:var(--text-secondary);">${l.details}</td>
            </tr>
        `).join('');

        const artRows = state.articles.map(a => `
            <tr>
                <td><strong>${a.title}</strong></td>
                <td><span style="color:var(--primary);">${a.categoryName}</span></td>
                <td>${a.authorName}</td>
                <td>${a.views}</td>
                <td><span class="status-badge badge-published">PUBLISHED</span></td>
            </tr>
        `).join('');

        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(renderHeader(loggedUser, 'Bàn Quản Trị') + `
            <div class="admin-layout">
                <aside class="admin-sidebar">
                    <div class="admin-sidebar-title">Quản Lý</div>
                    <a href="/admin/index.php" class="admin-nav-item active">Tổng Quan</a>
                    <a href="/admin/articles.php" class="admin-nav-item">Bài Viết (${state.articles.length})</a>
                    <a href="/admin/audit_logs.php" class="admin-nav-item">Nhật Ký An Toàn (${state.auditLogs.length})</a>
                </aside>
                <main class="admin-main">
                    <div class="admin-header-row">
                        <div>
                            <h1 class="admin-page-title">Bàn Quản Trị Hệ Thống Tin Tức</h1>
                            <p style="color:var(--text-secondary); margin-top:4px;">Chào mừng <strong>${loggedUser.fullName}</strong> (${loggedUser.role.toUpperCase()})</p>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon icon-blue">📰</div>
                            <div class="stat-info"><div class="stat-value">${state.articles.length}</div><div class="stat-label">Tổng bài viết</div></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-green">👁️</div>
                            <div class="stat-info"><div class="stat-value">${state.articles.reduce((acc, a) => acc + a.views, 0)}</div><div class="stat-label">Lượt xem tin</div></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-purple">👥</div>
                            <div class="stat-info"><div class="stat-value">${state.users.length}</div><div class="stat-label">Người dùng</div></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-amber">🛡️</div>
                            <div class="stat-info"><div class="stat-value">${state.auditLogs.length}</div><div class="stat-label">Audit Logs</div></div>
                        </div>
                    </div>
                    <div class="table-card">
                        <div class="table-card-header"><h3 style="font-size:1.1rem; font-weight:700;">Danh Sách Bài Viết Đang Hoạt Động</h3></div>
                        <table class="admin-table">
                            <thead><tr><th>Tiêu đề</th><th>Chuyên mục</th><th>Tác giả</th><th>Lượt xem</th><th>Trạng thái</th></tr></thead>
                            <tbody>${artRows}</tbody>
                        </table>
                    </div>
                    <div class="table-card">
                        <div class="table-card-header"><h3 style="font-size:1.1rem; font-weight:700; color:#f43f5e;">🛡️ Nhật Ký An Toàn Gần Nhất (Audit Trail)</h3></div>
                        <table class="admin-table">
                            <thead><tr><th>Thời gian</th><th>Action</th><th>Địa chỉ IP</th><th>Chi tiết</th></tr></thead>
                            <tbody>${logRows}</tbody>
                        </table>
                    </div>
                </main>
            </div>
        ` + renderFooter());
    }

    // 8. Chi tiết bài viết (/article.php?slug=...)
    if (pathname === '/article.php') {
        const slug = parsedUrl.query.slug;
        const art = state.articles.find(a => a.slug === slug) || state.articles[0];
        art.views += 1;

        const artComments = state.comments.filter(c => c.articleId === art.id);
        const commentListHtml = artComments.map(c => `
            <div class="comment-card">
                <div class="comment-header">
                    <strong style="color:var(--primary); font-size:0.92rem;">${c.userName}</strong>
                    <span style="color:var(--text-muted); font-size:0.8rem;">${c.createdAt}</span>
                </div>
                <p style="color:var(--text-primary); font-size:0.95rem;">${c.content}</p>
            </div>
        `).join('');

        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(renderHeader(loggedUser, art.title) + `
            <div class="container" style="padding:30px 0;">
                <div class="main-layout">
                    <div class="content-primary">
                        <article class="article-detail-wrap">
                            <header class="article-header">
                                <div class="article-breadcrumb"><a href="/">Trang chủ</a> &rsaquo; <span style="color:var(--primary);">${art.categoryName}</span></div>
                                <h1 class="article-main-title">${art.title}</h1>
                                <div class="author-meta-box">
                                    <div class="author-info">
                                        <div class="user-avatar-mini" style="width:44px; height:44px; background:#0284c7; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700;">${art.authorName.charAt(0)}</div>
                                        <div>
                                            <div class="author-name">${art.authorName}</div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);">Xuất bản: ${art.publishedAt}</div>
                                        </div>
                                    </div>
                                    <div style="font-size:0.85rem; color:var(--text-muted);">👁️ ${art.views} lượt xem</div>
                                </div>
                            </header>
                            <div class="article-summary-lead">${art.summary}</div>
                            <div style="margin-bottom:28px; border-radius:12px; overflow:hidden;">
                                <img src="${art.thumbnail}" alt="${art.title}" style="width:100%; max-height:450px; object-fit:cover;">
                            </div>
                            <div class="article-content">${art.content}</div>
                        </article>
                        <section class="comments-section">
                            <h3 style="font-size:1.3rem; font-weight:700; margin-bottom:20px;">Ý Kiến Độc Giả (${artComments.length})</h3>
                            <div id="commentAlert" style="display:none;"></div>
                            <form id="commentForm" style="margin-bottom:24px;">
                                <input type="hidden" name="article_id" value="${art.id}">
                                <input type="hidden" name="csrf_token" value="preview_token">
                                <textarea id="commentContent" class="form-textarea" rows="3" placeholder="Viết bình luận văn minh..."></textarea>
                                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Gửi bình luận</button>
                            </form>
                            <div id="commentsContainer">${commentListHtml}</div>
                        </section>
                    </div>
                </div>
            </div>
        ` + renderFooter());
    }

    // 9. Trang chủ mặc định (/)
    const hero = state.articles[0];
    const recents = state.articles.slice(1);
    const recentCards = recents.map(a => `
        <article class="article-card">
            <a href="/article.php?slug=${a.slug}" class="card-thumb-wrap">
                <img src="${a.thumbnail}" alt="${a.title}" class="card-thumb">
                <span class="card-category-badge">${a.categoryName}</span>
            </a>
            <div class="card-body">
                <h3 class="card-title"><a href="/article.php?slug=${a.slug}">${a.title}</a></h3>
                <p class="card-summary">${a.summary}</p>
                <div class="card-footer">
                    <span>✍️ ${a.authorName}</span>
                    <span>👁️ ${a.views} xem</span>
                </div>
            </div>
        </article>
    `).join('');

    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    return res.end(renderHeader(loggedUser, 'Trang Chủ') + `
        <div class="container">
            <section class="hero-section">
                <div class="hero-grid">
                    <a href="/article.php?slug=${hero.slug}" class="hero-main-card">
                        <img src="${hero.thumbnail}" alt="${hero.title}" class="hero-main-bg">
                        <div class="hero-overlay"></div>
                        <div class="hero-content">
                            <span class="category-tag">${hero.categoryName}</span>
                            <h1 class="hero-title">${hero.title}</h1>
                            <p class="hero-summary">${hero.summary}</p>
                            <div class="meta-info">
                                <span>✍️ ${hero.authorName}</span>
                                <span>👁️ ${hero.views} lượt xem</span>
                            </div>
                        </div>
                    </a>
                    <div class="hero-side-column">
                        <h3 style="font-size:1.1rem; font-weight:700;">🔥 Tin Đọc Nhiều</h3>
                        ${recents.map(t => `
                            <a href="/article.php?slug=${t.slug}" class="side-card">
                                <img src="${t.thumbnail}" class="side-thumb">
                                <div class="side-card-content">
                                    <span style="font-size:0.72rem; color:var(--primary); font-weight:700;">${t.categoryName}</span>
                                    <h4 class="side-title">${t.title}</h4>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">👁️ ${t.views} xem</span>
                                </div>
                            </a>
                        `).join('')}
                    </div>
                </div>
            </section>
            <div class="main-layout">
                <div class="content-primary">
                    <div class="section-header"><h2 class="section-title">Tin Mới Cập Nhật</h2></div>
                    <div class="article-cards-grid">${recentCards}</div>
                </div>
                <aside class="sidebar">
                    <div class="widget">
                        <h3 class="widget-title">Chuyên Mục</h3>
                        <ul class="categories-list">
                            ${state.categories.map(c => `
                                <li><a href="/category.php?slug=${c.slug}" class="category-item-link"><span>${c.name}</span></a></li>
                            `).join('')}
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    ` + renderFooter());
});

server.listen(PORT, () => {
    console.log(`======================================================================`);
    console.log(`🚀 NewsPortal Preview Server is running on: http://localhost:${PORT}`);
    console.log(`👤 Default admin account: admin / Admin@123456`);
    console.log(`======================================================================`);
});
