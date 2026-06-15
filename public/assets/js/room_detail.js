async function fetchRoom(){
  const info = document.getElementById('room-info');
  try{
    const res = await fetch('/api/rooms.php?id=' + ROOM_ID);
    const room = await res.json();
    if (!room) { info.textContent = 'Room not found.'; return; }
    document.getElementById('room-name').textContent = room.name;
    info.innerHTML = `<p><strong>Building:</strong> ${room.building||'Campus'}</p>
      <p><strong>Capacity:</strong> ${room.capacity}</p>
      <p><strong>Type:</strong> ${room.type}</p>
      <p><strong>Equipment:</strong> ${room.equipment || 'None'}</p>
      <p>${room.notes || ''}</p>`;
    document.getElementById('book-now').href = 'new_booking.php?room_id=' + ROOM_ID;
  }catch(e){ info.textContent = 'Error loading room.'; }
}

async function fetchBookings(){
  const el = document.getElementById('room-bookings');
  try{
    const res = await fetch('/api/bookings.php?room_id=' + ROOM_ID);
    const rows = await res.json();
    if (!Array.isArray(rows) || !rows.length){ el.innerHTML = '<p>No bookings for this room.</p>'; return; }
    el.innerHTML = rows.map(b=>`<article class="card"><h3>${b.title||'Booking'}</h3><p>${b.start_datetime} → ${b.end_datetime}</p><p>Status: ${b.status}</p></article>`).join('');
  }catch(e){ el.textContent = 'Unable to load bookings.'; }
}

document.addEventListener('DOMContentLoaded', ()=>{ if (ROOM_ID) { fetchRoom(); fetchBookings(); } });
