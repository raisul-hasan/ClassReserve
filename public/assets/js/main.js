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
