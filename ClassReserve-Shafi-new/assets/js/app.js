document.addEventListener('DOMContentLoaded', () => {
    initThemeSwitch();

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // Confirm destructive actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Set min datetime on booking forms to now
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    if (startInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const minVal = now.toISOString().slice(0, 16);
        startInput.min = minVal;
        if (endInput) endInput.min = minVal;

        startInput.addEventListener('change', () => {
            if (endInput) endInput.min = startInput.value;
        });
    }

    // Calendar month navigation
    const calPrev = document.getElementById('cal-prev');
    const calNext = document.getElementById('cal-next');
    if (calPrev && calNext) {
        const params = new URLSearchParams(window.location.search);
        let year = parseInt(params.get('year') || new Date().getFullYear());
        let month = parseInt(params.get('month') || (new Date().getMonth() + 1));

        calPrev.addEventListener('click', () => {
            month--;
            if (month < 1) { month = 12; year--; }
            window.location.search = `?year=${year}&month=${month}`;
        });
        calNext.addEventListener('click', () => {
            month++;
            if (month > 12) { month = 1; year++; }
            window.location.search = `?year=${year}&month=${month}`;
        });
    }

    // AJAX room search (optional enhancement)
    const searchForm = document.getElementById('room-search-form');
    if (searchForm && searchForm.dataset.ajax === 'true') {
        searchForm.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(searchForm);
            const results = document.getElementById('search-results');
            results.innerHTML = '<p>Searching...</p>';

            try {
                const res = await fetch('/api/search_rooms.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) {
                    results.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                    return;
                }
                if (data.rooms.length === 0) {
                    results.innerHTML = '<div class="empty-state"><p>No rooms available for the selected time and capacity.</p></div>';
                    return;
                }
                results.innerHTML = data.rooms.map(room => `
                    <div class="room-card">
                        <h3>${escapeHtml(room.name)}</h3>
                        <div class="room-meta">${escapeHtml(room.building)} &middot; Floor ${room.floor} &middot; Capacity: ${room.capacity}</div>
                        <p style="font-size:.85rem;margin-bottom:.75rem">${escapeHtml(room.equipment || '')}</p>
                        <a href="/student/book.php?room_id=${room.id}&start=${encodeURIComponent(formData.get('start_time'))}&end=${encodeURIComponent(formData.get('end_time'))}&attendees=${formData.get('attendees')}" class="btn btn-primary btn-sm">Request Booking</a>
                    </div>
                `).join('');
                results.classList.add('grid-2');
            } catch {
                results.innerHTML = '<div class="alert alert-error">Search failed. Please try again.</div>';
            }
        });
    }
});

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function initThemeSwitch() {
    const storageKey = 'classreserve.theme';

    const readTheme = () => {
        try {
            const storedTheme = localStorage.getItem(storageKey);
            return storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';
        } catch {
            return 'dark';
        }
    };

    const applyTheme = (theme) => {
        const nextTheme = theme === 'light' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        document.documentElement.style.colorScheme = nextTheme;
        try {
            localStorage.setItem(storageKey, nextTheme);
        } catch {
            // Storage can fail in private contexts; keep the in-page theme working.
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            const showLight = nextTheme === 'dark';
            toggle.setAttribute('aria-label', `Switch to ${showLight ? 'light' : 'dark'} mode`);
            toggle.setAttribute('title', `Switch to ${showLight ? 'light' : 'dark'} mode`);
            toggle.querySelector('.theme-switch-label')?.replaceChildren(document.createTextNode(showLight ? 'Light' : 'Dark'));
        });
    };

    applyTheme(readTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const current = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
            applyTheme(current === 'light' ? 'dark' : 'light');
        });
    });
}
