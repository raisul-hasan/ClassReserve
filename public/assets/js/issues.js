function getCsrf(){ const m=document.cookie.split('; ').find(c=>c.startsWith('csrf_token=')); return m?m.split('=')[1]:''; }

document.addEventListener('DOMContentLoaded', ()=>{
  const form = document.getElementById('issue-form');
  const list = document.getElementById('issues-list');
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    try{
      const res = await fetch('/api/issues.php', { method:'POST', credentials:'include', headers: { 'X-CSRF-Token': getCsrf() }, body: fd });
      const j = await res.json();
      if (!res.ok) { alert(j.error||'Failed to submit'); return; }
      alert('Issue submitted'); loadIssues();
    }catch(err){ alert('Network error'); }
  });
  async function loadIssues(){
    try{
      const res = await fetch('/api/issues.php');
      const rows = await res.json();
      if (!Array.isArray(rows) || !rows.length) { list.innerHTML = '<p>No issues found.</p>'; return; }
      list.innerHTML = rows.map(i=>`<article class="card"><h3>${i.title}</h3><p>${i.room_name||i.roomName||''}</p><p>${i.created_at}</p><p>Status: ${i.status}</p></article>`).join('');
    }catch(e){ list.textContent = 'Unable to load issues.'; }
  }
  loadIssues();
});
