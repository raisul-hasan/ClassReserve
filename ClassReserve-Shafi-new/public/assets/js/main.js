const CLASSRESERVE_THEME_KEY = 'classreserve.theme';

function readClassReserveTheme() {
  try {
    const storedTheme = localStorage.getItem(CLASSRESERVE_THEME_KEY);
    return storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';
  } catch {
    return 'dark';
  }
}

function applyClassReserveTheme(theme) {
  const nextTheme = theme === 'light' ? 'light' : 'dark';
  document.documentElement.dataset.theme = nextTheme;
  document.documentElement.style.colorScheme = nextTheme;
  try {
    localStorage.setItem(CLASSRESERVE_THEME_KEY, nextTheme);
  } catch {
    // Keep the visible page switch working even if storage is unavailable.
  }

  document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
    toggle.textContent = nextTheme === 'dark' ? 'Light' : 'Dark';
    toggle.setAttribute('aria-label', `Switch to ${nextTheme === 'dark' ? 'light' : 'dark'} mode`);
  });
}

applyClassReserveTheme(readClassReserveTheme());

document.addEventListener('DOMContentLoaded', function () {
  if (!document.querySelector('[data-theme-toggle]')) {
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'theme-toggle theme-toggle-floating';
    toggle.dataset.themeToggle = '';
    document.body.appendChild(toggle);
  }

  applyClassReserveTheme(readClassReserveTheme());

  document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
    toggle.addEventListener('click', function () {
      const current = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
      applyClassReserveTheme(current === 'light' ? 'dark' : 'light');
    });
  });
});

// Minimal frontend JS placeholder for AJAX calls
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (form.id === 'login-form') {
    e.preventDefault();
    const data = { action: 'login', email: form.email.value, password: form.password.value };
    fetch('/api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
      .then(r => r.json()).then(console.log).catch(console.error);
  }
});
