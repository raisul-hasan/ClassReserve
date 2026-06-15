const maintenanceList = document.getElementById('maintenance-list');

fetch('/api/maintenance.php')
  .then((response) => response.json())
  .then((data) => {
    if (!Array.isArray(data)) {
      maintenanceList.textContent = 'Unable to load maintenance blocks.';
      return;
    }

    maintenanceList.innerHTML = data.length
      ? data.map((block) => `
        <article class="card maintenance-card">
          <h3>${block.room_name}</h3>
          <p><strong>Building:</strong> ${block.building || 'Campus'}</p>
          <p><strong>Start:</strong> ${block.start_datetime}</p>
          <p><strong>End:</strong> ${block.end_datetime}</p>
          <p><strong>Reason:</strong> ${block.reason || 'Maintenance'}</p>
        </article>
      `).join('')
      : '<p>No maintenance blocks scheduled.</p>';
  })
  .catch(() => {
    maintenanceList.textContent = 'Error loading maintenance blocks. Please try again later.';
  });
