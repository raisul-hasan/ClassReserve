document.addEventListener('DOMContentLoaded', async ()=>{
  const el = document.getElementById('notifications-list');
  try{
    const res = await fetch('/api/notifications.php');
    const rows = await res.json();
    if (!Array.isArray(rows) || !rows.length) { el.innerHTML = '<p>No notifications.</p>'; return; }
    el.innerHTML = rows.map(n=>`<article class="card"><h3>${n.title}</h3><p>${n.message}</p><p>${n.created_at}</p></article>`).join('');
  }catch(e){ el.textContent = 'Unable to load notifications.'; }
});
