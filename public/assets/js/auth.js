const authForm = document.querySelector('#login-form, #register-form');

if (authForm) {
  authForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const action = formData.get('action');
    const payload = {};

    for (const [key, value] of formData.entries()) {
      if (key === 'action') continue;
      payload[key] = value;
    }

    try {
      const response = await fetch('/api/auth.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...payload }),
      });
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || 'Authentication failed.');
      }
      if (action === 'login') {
        window.location.href = 'dashboard.php';
      } else {
        window.location.href = 'login.php';
      }
    } catch (error) {
      const existingAlert = document.querySelector('.alert');
      const message = (error instanceof Error ? error.message : 'Unknown error.');
      if (existingAlert) {
        existingAlert.textContent = message;
      } else {
        const alert = document.createElement('div');
        alert.className = 'alert';
        alert.textContent = message;
        authForm.prepend(alert);
      }
    }
  });
}
