document.addEventListener('DOMContentLoaded', async ()=>{
  const el = document.getElementById('calendar-events');
  try{
    const [bookingsRes, maintenanceRes] = await Promise.all([fetch('/api/bookings.php'), fetch('/api/maintenance.php')]);
    const bookings = await bookingsRes.json();
    const maintenance = await maintenanceRes.json();
    const events = (bookings||[]).map(b=>({title:b.title||'Booking', start:b.start_datetime, end:b.end_datetime, room:b.room_name, type:'booking', status:b.status}))
      .concat((maintenance||[]).map(m=>({title:m.reason||'Maintenance', start:m.start_datetime, end:m.end_datetime, room:m.room_name, type:'maintenance'})));
    if (!events.length) { el.innerHTML = '<p>No upcoming events.</p>'; return; }
    // Group by date
    const byDate = {};
    events.forEach(ev=>{ const day = ev.start ? ev.start.slice(0,10):'unknown'; (byDate[day]||(byDate[day]=[])).push(ev); });
    el.innerHTML = Object.keys(byDate).sort().map(d=>`<section><h3>${d}</h3>${byDate[d].map(e=>`<article class="card"><h4>${e.title}</h4><p>${e.start} → ${e.end}</p><p>${e.room} — ${e.type} ${e.status? ' — '+e.status : ''}</p></article>`).join('')}</section>`).join('');
  }catch(e){ el.textContent = 'Unable to load calendar.'; }
});
