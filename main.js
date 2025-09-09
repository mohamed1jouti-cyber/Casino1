document.addEventListener("DOMContentLoaded", function(){
  // Demo mode removed: always use real flow
  const demoMode = localStorage.getItem("demo_mode") === "true";
  
  // Check for database error flag (set by PHP scripts)
  if (sessionStorage.getItem("database_error") && !demoMode) {
    window.location.href = "database_error.html";
    return;
  }
  
  let loggedIn = localStorage.getItem("loggedIn");
  if(!loggedIn) window.location.href = "index.html";

  const currentUsername = (localStorage.getItem("username") || "Player").toString();
  document.getElementById("username").textContent = currentUsername;
  document.getElementById("useremail").textContent = localStorage.getItem("email") || "No email";
  
  // Per-user balance: store under balance:<username>
  const balanceKey = `balance:${currentUsername}`;
  function refreshBalanceDisplay() {
    // Reconcile with admin wallets if available
    try {
      const usersRaw = localStorage.getItem('demo_users');
      const walletsRaw = localStorage.getItem('demo_wallets');
      if (usersRaw && walletsRaw) {
        const users = JSON.parse(usersRaw);
        const wallets = JSON.parse(walletsRaw);
        const user = Array.isArray(users) ? users.find(u => String(u.username).toLowerCase() === currentUsername.toLowerCase()) : null;
        if (user && wallets && wallets[user.id] && typeof wallets[user.id].balance !== 'undefined') {
          const walletBalance = parseInt(wallets[user.id].balance);
          const existing = parseInt(localStorage.getItem(balanceKey));
          if (!isNaN(walletBalance) && walletBalance !== existing) {
            localStorage.setItem(balanceKey, String(walletBalance));
          }
        }
      }
    } catch(_) { /* ignore parse errors */ }

    const raw = localStorage.getItem(balanceKey);
    let num = parseInt(raw);
    if (isNaN(num) || raw === null || raw === undefined || raw === '') {
      num = 0;
      localStorage.setItem(balanceKey, num.toString());
    }
    document.getElementById("balance").textContent = num;
  }

  refreshBalanceDisplay();

  // Update when coming back from a game or when another tab updates storage
  document.addEventListener("visibilitychange", function(){
    if (!document.hidden) refreshBalanceDisplay();
  });
  window.addEventListener("storage", function(e){
    if (e.key === balanceKey) refreshBalanceDisplay();
    if (e.key === 'balance_sync') {
      try {
        const payload = JSON.parse(e.newValue || '{}');
        if (payload && String(payload.username).toLowerCase() === currentUsername.toLowerCase()) {
          localStorage.setItem(balanceKey, String(parseInt(payload.balance)||0));
          refreshBalanceDisplay();
        }
      } catch(_) { /* ignore */ }
    }
  });

  // Fallback: periodic refresh to catch same-tab navigation cases
  setInterval(refreshBalanceDisplay, 1500);
});

function deposit(){
  let amt = parseInt(document.getElementById("amount").value);
  if(!isNaN(amt) && amt > 0){
    const currentUsername = (localStorage.getItem("username") || "Player").toString();
    const balanceKey = `balance:${currentUsername}`;
    let bal = parseInt(localStorage.getItem(balanceKey) || 0);
    if (isNaN(bal)) bal = 0;
    bal += amt;
    localStorage.setItem(balanceKey, bal.toString());
    document.getElementById("balance").textContent = bal;
  }
}

function withdraw(){
  let amt = parseInt(document.getElementById("amount").value);
  const currentUsername = (localStorage.getItem("username") || "Player").toString();
  const balanceKey = `balance:${currentUsername}`;
  let bal = parseInt(localStorage.getItem(balanceKey) || 0);
  if (isNaN(bal)) bal = 0;
  if(!isNaN(amt) && amt > 0 && amt <= bal){
    bal -= amt;
    localStorage.setItem(balanceKey, bal.toString());
    document.getElementById("balance").textContent = bal;
  } else {
    alert("❌ Not enough balance");
  }
}

function logout(){
  localStorage.removeItem("loggedIn");
  window.location.href = "index.html";
}