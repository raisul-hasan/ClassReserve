function getCsrf(){ const m=document.cookie.split('; ').find(c=>c.startsWith('csrf_token=')); return m?m.split('=')[1]:''; }

async function loadApprovals(){
  const el = document.getElementById('approvals-list');
  try{
    const res = await fetch('/api/bookings.php');
    const rows = await res.json();
    const pending = rows.filter(r=>r.status==='pending');
    if (!pending.length) { el.innerHTML = '<p>No pending approvals.</p>'; return; }
    el.innerHTML = pending.map(b=>`
      <article class="card">
        <h3>${b.title||'Booking'}</h3>
        <p>${b.start_datetime} → ${b.end_datetime}</p>
        <p>${b.room_name}</p>
        <p>Requester: ${b.user_name} (${b.user_role})</p>
        <div><button data-id="${b.id}" data-action="approve" class="button button-primary">Approve</button>
        <button data-id="${b.id}" data-action="reject" class="button button-secondary">Reject</button></div>
      </article>
    `).join('');

    el.querySelectorAll('button').forEach(btn=>btn.addEventListener('click', async (e)=>{
      const id = btn.getAttribute('data-id');
      const action = btn.getAttribute('data-action');
      let reason = '';
      if (action==='reject') reason = prompt('Enter rejection reason:')||'';
      try{
        const res = await fetch('/api/bookings.php', { method:'POST', credentials:'include', headers: { 'Content-Type':'application/json', 'X-CSRF-Token': getCsrf() }, body: JSON.stringify({ id: Number(id), action, reason }) });
        const j = await res.json();
        if (!res.ok) { alert(j.error||'Action failed'); return; }
        loadApprovals();
      }catch(err){ alert('Network error.'); }
    }));
  }catch(e){ el.textContent = 'Unable to load approvals.'; }
}

document.addEventListener('DOMContentLoaded', loadApprovals);
