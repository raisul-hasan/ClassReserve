function getCsrfToken(){
  const m = document.cookie.split('; ').find(c=>c.startsWith('csrf_token='));
  return m ? m.split('=')[1] : '';
}

async function loadRooms(){
  const sel = document.getElementById('room-select');
  try{
    const res = await fetch('/api/rooms.php');
    const data = await res.json();
    sel.innerHTML = (data.map(r=>`<option value="${r.id}" ${r.id==ROOM_ID? 'selected':''}>${r.name} (${r.building||'Campus'})</option>`)).join('');
  }catch(e){ sel.innerHTML = '<option value="">Unable to load rooms</option>'; }
}

document.addEventListener('DOMContentLoaded', ()=>{
  loadRooms();
  const form = document.getElementById('booking-form');
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    const date = fd.get('date');
    const start = fd.get('start_time');
    const end = fd.get('end_time');
    fd.append('start_datetime', `${date} ${start}:00`);
    fd.append('end_datetime', `${date} ${end}:00`);

    try{
      const res = await fetch('/api/bookings.php',{
        method:'POST',
        credentials:'include',
        headers: { 'X-CSRF-Token': getCsrfToken() },
        body: fd
      });
      const json = await res.json();
      const result = document.getElementById('booking-result');
      if (!res.ok) { result.innerHTML = '<div class="alert">' + (json.error||'Request failed') + '</div>'; return; }
      result.innerHTML = '<div class="alert">Booking request submitted.</div>';
    }catch(err){ document.getElementById('booking-result').innerHTML = '<div class="alert">Network error.</div>'; }
  });
});
