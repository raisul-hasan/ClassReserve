const roomList = document.getElementById('room-list');

fetch('/api/rooms.php')
  .then((response) => response.json())
  .then((data) => {
    if (!Array.isArray(data)) {
      roomList.textContent = 'Unable to load rooms.';
      return;
    }

    const html = data.map((room) => {
      const status = room.status === 'available' ? 'Available' : room.status.charAt(0).toUpperCase() + room.status.slice(1);
      return `
        <article class="card room-card">
          <h3>${room.name}</h3>
          <p><strong>Building:</strong> ${room.building || 'Campus'}</p>
          <p><strong>Capacity:</strong> ${room.capacity || 'N/A'}</p>
          <p><strong>Type:</strong> ${room.type || 'Lecture'}</p>
          <p><strong>Equipment:</strong> ${room.equipment || 'None'}</p>
          <p><strong>Status:</strong> ${status}</p>
          <p>${room.notes || ''}</p>
        </article>
      `;
    }).join('');

    roomList.innerHTML = html || '<p>No rooms found.</p>';
  })
  .catch(() => {
    roomList.textContent = 'Error loading rooms. Please try again later.';
  });
