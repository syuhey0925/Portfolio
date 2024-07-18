const escapeHTML = (unsafe) => {
    return String(unsafe).replace(/[&<>"'`=\/]/g, (match) => {
        return escapeHTML.characters[match];
    });
};

escapeHTML.characters = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '=': '&#61;',
    '/': '&#47;'
};

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const table = document.querySelector('table');
    let isPopupOpen = false;
    let popup;

    const closePopup = () => {
        const closeButton = document.getElementById('close-popup');
        const submitButton = document.getElementById('submit-comment');

        if (closeButton) {
            closeButton.removeEventListener('click', closePopup);
        }

        if (submitButton) {
            submitButton.removeEventListener('click', submitCommentHandler);
        }

        window.removeEventListener('mousedown', outsideClickHandler);
        document.body.removeChild(popup);
        isPopupOpen = false;
        table.classList.remove('table-disabled');
        body.classList.remove('popup-open');
    };

    const outsideClickHandler = (event) => {
        if (isPopupOpen && popup && !popup.contains(event.target)) {
            closePopup();
        }
    };

    const submitCommentHandler = () => {
        const commentTextarea = document.getElementById('comment-textarea');
        const newComment = commentTextarea.value.trim();
        console.log('新しいコメント:', newComment);
        const form = document.querySelector('form');
        form.submit();
        closePopup();
    };

    const createPopupContent = (classInfo, userEmail, nowTotal, toExam) => {
        const commentForm = `
            <form action="" method="post">
                <input type="hidden" name="date" value="${classInfo.date}">
                <input type="hidden" name="period" value="${classInfo.period}">
                <input type="hidden" name="class" value="${classInfo.class}">
                <input type="hidden" name="teacher_email" value="${classInfo.teacher_email}">
                <input type="hidden" name="subject" value="${classInfo.subject}">
                <textarea id="comment-textarea" name="new_comment" rows="4" cols="50">${classInfo.comment}</textarea><br>
                <input id="submit-comment" type="submit" value="更新">
            </form>`;

        return `
            <div class="popup">
                <button id="close-popup">×</button>
                <p>${nowTotal}回目 (全${classInfo.total}回)</p>
                ${toExam === 'no_exam' ? `<p>テストなし</p>` : `<p>テストまで${toExam}回 (今回を含む)</p>`}
                ${userEmail === classInfo.teacher_email ? commentForm : (classInfo.comment === '' ? `<p>コメントなし</p>` : `<p>${classInfo.comment}</p>`)}
            </div>`;
    };

    table.addEventListener('click', (e) => {
        if (isPopupOpen || e.target.tagName !== 'TD') return;

        const cell = e.target;
        const userEmail = escapeHTML(cell.getAttribute('data-user-email'));
        const classInfo = JSON.parse(cell.getAttribute('data-class-info'));
        const nowTotal = escapeHTML(cell.getAttribute('data-now-total'));
        const toExam = escapeHTML(cell.getAttribute('data-to-exam'));

        if (cell.textContent.trim() !== '') {
            isPopupOpen = true;
            table.classList.add('table-disabled');
            const content = createPopupContent(classInfo, userEmail, nowTotal, toExam);
            body.classList.add('popup-open');
            popup = document.createElement('div');
            popup.innerHTML = content;
            body.appendChild(popup);

            const submitButton = document.getElementById('submit-comment');
            if (submitButton) {
                submitButton.addEventListener('click', submitCommentHandler);
            }

            document.getElementById('close-popup').addEventListener('click', closePopup);
            window.addEventListener('mousedown', outsideClickHandler);
        }
    });
});