document.addEventListener('DOMContentLoaded', async ()=>{
  const el = document.getElementById('users-list');
  try{
    const res = await fetch('/api/users.php');
    const rows = await res.json();
    if (!Array.isArray(rows) || !rows.length) { el.innerHTML = '<p>No users found.</p>'; return; }
    el.innerHTML = `<table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr></thead><tbody>${rows.map(u=>`<tr><td>${u.name}</td><td>${u.email}</td><td>${u.role}</td><td>${u.is_active? 'Yes':'No'}</td></tr>`).join('')}</tbody></table>`;
  }catch(e){ el.textContent = 'Unable to load users.'; }
});
