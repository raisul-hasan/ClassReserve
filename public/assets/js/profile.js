const profileForm = document.querySelector('.profile-form:first-of-type');
const passwordForm = document.querySelector('.profile-form:last-of-type');

function handleSubmit(form, data) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    const payload = {};
    for (const [key, value] of formData.entries()) {
      payload[key] = value;
    }

    try {
      const response = await fetch('/api/profile.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await response.json();
      if (!response.ok) throw new Error(json.error || 'Unable to update profile.');
      const alert = document.createElement('div');
      alert.className = 'alert';
      alert.textContent = json.message || 'Saved successfully.';
      form.prepend(alert);
    } catch (error) {
      const message = (error instanceof Error ? error.message : 'Unknown error.');
      const alert = document.createElement('div');
      alert.className = 'alert';
      alert.textContent = message;
      form.prepend(alert);
    }
  });
}

if (profileForm) handleSubmit(profileForm);
if (passwordForm) handleSubmit(passwordForm);
