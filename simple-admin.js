(function(){
  const STORAGE_KEYS = {
    users: 'sa_users',
    balances: 'sa_balances'
  };

  function read(key, fallback){
    try { const raw = localStorage.getItem(key); return raw ? JSON.parse(raw) : (fallback ?? null); } catch { return (fallback ?? null); }
  }
  function write(key, value){ localStorage.setItem(key, JSON.stringify(value)); }

  function ensureSeed(){
    const users = read(STORAGE_KEYS.users, []);
    if (users.length === 0) {
      users.push({ username: 'demo_user', email: 'demo@example.com', created_at: new Date().toISOString() });
      write(STORAGE_KEYS.users, users);
    }
    const balances = read(STORAGE_KEYS.balances, {});
    if (balances['demo_user'] === undefined) {
      balances['demo_user'] = 0;
      write(STORAGE_KEYS.balances, balances);
    }
  }

  function renderUsers(){
    const container = document.getElementById('saUsers');
    if (!container) return;
    const users = read(STORAGE_KEYS.users, []);
    const balances = read(STORAGE_KEYS.balances, {});
    if (users.length === 0) {
      container.innerHTML = '<div style="color:#94a3b8">No users. Click "Seed demo user" to create one.</div>';
      return;
    }
    container.innerHTML = users.map(u => {
      const bal = balances[u.username] ?? 0;
      return `<div style="display:flex;justify-content:space-between;border-bottom:1px solid #334155;padding:8px 0;">
        <div>
          <div style="color:#e2e8f0;font-weight:600">${u.username}</div>
          <div style="color:#94a3b8;font-size:12px">${u.email || ''}</div>
        </div>
        <div style="color:#fbbf24;font-weight:700">$${Number(bal).toFixed(2)}</div>
      </div>`;
    }).join('');
  }

  function showMessage(msg, isError){
    const el = document.getElementById('saMessage');
    if (!el) return;
    el.style.color = isError ? '#fca5a5' : '#93c5fd';
    el.textContent = msg;
    setTimeout(() => { el.textContent=''; }, 3000);
  }

  window.saSeedDemo = function(){
    const users = read(STORAGE_KEYS.users, []);
    if (!users.find(u => u.username === 'demo_user')) {
      users.push({ username: 'demo_user', email: 'demo@example.com', created_at: new Date().toISOString() });
      write(STORAGE_KEYS.users, users);
    }
    const balances = read(STORAGE_KEYS.balances, {});
    if (balances['demo_user'] === undefined) {
      balances['demo_user'] = 0;
      write(STORAGE_KEYS.balances, balances);
    }
    renderUsers();
    showMessage('Demo user seeded', false);
  }

  window.saAddBalance = function(){
    const username = (document.getElementById('saUsername')?.value || '').trim();
    const amountStr = (document.getElementById('saAmount')?.value || '').trim();
    const reason = (document.getElementById('saReason')?.value || '').trim();
    const amount = Number(amountStr);
    if (!username) return showMessage('Username required', true);
    if (!Number.isFinite(amount) || amount <= 0) return showMessage('Amount must be > 0', true);

    const users = read(STORAGE_KEYS.users, []);
    if (!users.find(u => u.username === username)) {
      users.push({ username, email: '', created_at: new Date().toISOString() });
      write(STORAGE_KEYS.users, users);
    }
    const balances = read(STORAGE_KEYS.balances, {});
    const before = Number(balances[username] ?? 0);
    balances[username] = before + amount;
    write(STORAGE_KEYS.balances, balances);

    // Also reflect in the global UI balance for the logged user if names match
    const currentUser = localStorage.getItem('username');
    if (currentUser && currentUser === username) {
      localStorage.setItem('balance', String(balances[username]));
      window.dispatchEvent(new Event('visibilitychange'));
    }

    renderUsers();
    showMessage(`Added $${amount.toFixed(2)} to ${username}${reason ? ' ('+reason+')' : ''}`, false);
    const amountEl = document.getElementById('saAmount'); if (amountEl) amountEl.value = '';
    const reasonEl = document.getElementById('saReason'); if (reasonEl) reasonEl.value = '';
  }

  document.addEventListener('DOMContentLoaded', function(){
    ensureSeed();
    renderUsers();
  });
})();







