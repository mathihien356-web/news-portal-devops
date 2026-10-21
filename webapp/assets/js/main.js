// ==============================================================================
// MAIN JAVASCRIPT LOGIC - CỔNG THÔNG TIN TIN TỨC
// Sinh viên: Ma Thị Hiền (MSSV: DTC245200948)
// ==============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // 1. Quản lý Theme (Dark / Light Mode)
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const savedTheme = localStorage.getItem('news_portal_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('news_portal_theme', newTheme);
        });
    }

    // 2. Tự động sinh Slug từ Tiêu đề bài viết trong form Quản trị
    const titleInput = document.getElementById('articleTitleInput');
    const slugInput = document.getElementById('articleSlugInput');
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', () => {
            if (!slugInput.dataset.manualEdit) {
                slugInput.value = generateSlug(titleInput.value);
            }
        });
        slugInput.addEventListener('input', () => {
            slugInput.dataset.manualEdit = 'true';
        });
    }

    function generateSlug(str) {
        return str
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[đĐ]/g, 'd')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    }

    // 3. Xử lý gửi bình luận AJAX mượt mà
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = commentForm.querySelector('button[type="submit"]');
            const contentArea = document.getElementById('commentContent');
            const articleId = commentForm.querySelector('input[name="article_id"]').value;
            const csrfToken = commentForm.querySelector('input[name="csrf_token"]').value;
            const commentsContainer = document.getElementById('commentsContainer');
            const commentAlert = document.getElementById('commentAlert');

            if (!contentArea.value.trim()) return;

            submitBtn.disabled = true;
            submitBtn.innerText = 'Đang gửi...';

            try {
                const response = await fetch('/api/comments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        article_id: articleId,
                        csrf_token: csrfToken,
                        content: contentArea.value.trim()
                    })
                });
                const resData = await response.json();

                if (resData.success) {
                    const newCommentEl = document.createElement('div');
                    newCommentEl.className = 'comment-card';
                    newCommentEl.innerHTML = `
                        <div class="comment-header">
                            <strong style="color: var(--primary);">${escapeHtml(resData.comment.user_name)}</strong>
                            <span style="color: var(--text-muted);">Vừa xong</span>
                        </div>
                        <p style="color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(resData.comment.content)}</p>
                    `;
                    commentsContainer.prepend(newCommentEl);
                    contentArea.value = '';
                    if (commentAlert) {
                        commentAlert.className = 'alert-message alert-success';
                        commentAlert.innerText = 'Bình luận của bạn đã được đăng tải thành công!';
                        commentAlert.style.display = 'block';
                    }
                } else {
                    if (commentAlert) {
                        commentAlert.className = 'alert-message alert-error';
                        commentAlert.innerText = resData.message || 'Có lỗi xảy ra.';
                        commentAlert.style.display = 'block';
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Gửi bình luận';
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
