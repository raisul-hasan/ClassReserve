const bookingsContainer = document.getElementById('bookings-list');

if (bookingsContainer) {
  fetch('/api/bookings.php')
    .then((response) => response.json())
    .then((data) => {
      if (!Array.isArray(data)) {
        bookingsContainer.textContent = 'Unable to load bookings.';
        return;
      }

      if (!data.length) {
        bookingsContainer.innerHTML = '<p>No bookings found.</p>';
        return;
      }

      const html = `
        <table class="table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Room</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${data.map((booking) => `
              <tr>
                <td>${booking.title || 'Untitled'}</td>
                <td>${booking.room_name || 'Unknown'}</td>
                <td>${booking.start_datetime || 'N/A'}</td>
                <td>${booking.end_datetime || 'N/A'}</td>
                <td>${booking.status || 'unknown'}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
      bookingsContainer.innerHTML = html;
    })
    .catch(() => {
      bookingsContainer.textContent = 'Error loading bookings. Please try again later.';
    });
}
