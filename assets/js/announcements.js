(() => {
    const banner = document.getElementById('topAnnouncementBanner');
    if (!banner) return;
    const original = banner.innerHTML;
    const labels = { info: 'INFORMATION', success: 'BONNE NOUVELLE', warning: 'ATTENTION', danger: 'URGENT', new_year: 'BONNE ANNÉE', christmas: 'JOYEUX NOËL' };
    let currentId = null;
    const dismissed = new Set();
    let urgentDialog = null;

    function isDismissed(id) {
        try { if (localStorage.getItem(`dismissed_ann_${id}`) === 'true') return true; } catch {}
        return dismissed.has(id);
    }

    function showFallback() {
        banner.innerHTML = original;
        delete banner.dataset.theme;
        banner.hidden = false;
    }

    function dismiss(id) {
        dismissed.add(id);
        try { localStorage.setItem(`dismissed_ann_${id}`, 'true'); } catch {}
        showFallback();
        if (urgentDialog) urgentDialog.close();
    }

    function showUrgent(announcement) {
        const key = `seen_ann_${announcement.id}`;
        try { if (sessionStorage.getItem(key)) return; } catch {}
        if (urgentDialog) return;
        const dialog = document.createElement('dialog');
        dialog.className = 'announcement-dialog';
        const title = document.createElement('h2');
        title.id = 'urgent-announcement-title';
        title.textContent = 'Annonce importante';
        dialog.setAttribute('aria-labelledby', title.id);
        const message = document.createElement('p');
        message.textContent = announcement.message;
        const close = document.createElement('button');
        close.type = 'button';
        close.textContent = 'Fermer';
        close.addEventListener('click', () => dialog.close());
        dialog.append(title, message, close);
        dialog.addEventListener('close', () => {
            try { sessionStorage.setItem(key, 'true'); } catch {}
            dialog.remove();
            urgentDialog = null;
            if (currentId === String(announcement.id)) dismiss(String(announcement.id));
        });
        document.body.append(dialog);
        urgentDialog = dialog;
        if (typeof dialog.showModal === 'function') dialog.showModal();
        else { dialog.remove(); urgentDialog = null; }
    }

    function render(announcement) {
        if (!announcement) {
            showFallback();
            currentId = null;
            if (urgentDialog) urgentDialog.close();
            return;
        }
        const id = String(announcement.id);
        if (isDismissed(id)) {
            currentId = id;
            showFallback();
            if (urgentDialog) urgentDialog.close();
            return;
        }
        if (id === currentId) return;
        currentId = id;
        banner.hidden = false;
        banner.dataset.theme = labels[announcement.type] ? announcement.type : 'info';
        const inner = document.createElement('div');
        inner.className = 'announcement-inner';
        const copy = document.createElement('div');
        copy.className = 'announcement-copy';
        copy.setAttribute('role', 'status');
        const label = document.createElement('span');
        label.className = 'announcement-label';
        label.textContent = labels[announcement.type] || labels.info;
        const message = document.createElement('span');
        message.className = 'announcement-message';
        message.textContent = announcement.message;
        copy.append(label, message);
        const close = document.createElement('button');
        close.className = 'announcement-close';
        close.type = 'button';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Fermer cette annonce');
        close.addEventListener('click', () => dismiss(id));
        inner.append(copy, close);
        banner.replaceChildren(inner);
        if (urgentDialog) urgentDialog.close();
        if (announcement.type === 'danger') showUrgent(announcement);
    }

    async function refresh() {
        try {
            const response = await fetch('api/get_announcement', { cache: 'no-store' });
            if (!response.ok) return;
            const result = await response.json();
            render(result.success && result.data ? result.data : null);
        } catch { /* Keep the existing banner when the network is unavailable. */ }
    }
    refresh();
    setInterval(() => { if (!document.hidden) refresh(); }, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
