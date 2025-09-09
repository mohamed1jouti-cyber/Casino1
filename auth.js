async function __pushServerItems(items){
  try {
    const res = await fetch('storage_api.php?action=set_batch', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items })
    });
    return await res.json();
  } catch(_) { return { success:false }; }
}

document.getElementById("signupForm")?.addEventListener("submit", async function(e){
  e.preventDefault();
  let user = document.getElementById("signupUsername").value;
  let email = document.getElementById("signupEmail").value;
  let pass = document.getElementById("signupPassword").value;
  
  // Basic email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("❌ Please enter a valid email address");
    return;
  }

  // Uniqueness checks (username and email)
  try {
    const usersKey = 'demo_users';
    const users = JSON.parse(localStorage.getItem(usersKey) || '[]');
    const usernameTaken = users.some(u => String(u.username).toLowerCase() === String(user).toLowerCase());
    if (usernameTaken) {
      alert("❌ Username already taken. Please choose another.");
      return;
    }
    const emailTaken = users.some(u => String(u.email || '').toLowerCase() === String(email).toLowerCase());
    if (emailTaken) {
      alert("❌ Email already in use. Please use a different email.");
      return;
    }
  } catch(_) { /* ignore and proceed */ }
  
  localStorage.setItem("username", user);
  localStorage.setItem("email", email);
  localStorage.setItem("password", pass);
  // Also create local admin user record so it appears in admin lists
  try {
    const usersKey = 'demo_users';
    const walletsKey = 'demo_wallets';
    const users = JSON.parse(localStorage.getItem(usersKey) || '[]');
    const now = new Date().toISOString();
    const exists = users.find(u => String(u.username).toLowerCase() === String(user).toLowerCase());
    let id;
    if (!exists) {
      id = users.length > 0 ? Math.max(...users.map(u => Number(u.id)||0)) + 1 : 1;
      users.push({ id, username: user, email: email, password: pass, first_name: '', last_name: '', is_verified: false, is_active: true, created_at: now, last_login: now });
      localStorage.setItem(usersKey, JSON.stringify(users));
      const wallets = JSON.parse(localStorage.getItem(walletsKey) || '{}');
      if (!wallets[id]) { wallets[id] = { balance: 0, bonus_balance: 0, locked_balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: now }; }
      localStorage.setItem(walletsKey, JSON.stringify(wallets));
      // Explicit server push to ensure internet persistence
      const payload = {};
      payload[usersKey] = users;
      payload[walletsKey] = wallets;
      payload[`balance:${user}`] = 0;
      await __pushServerItems(payload);
    }
  } catch(_) { /* ignore */ }
  alert("✅ Account created! Please login.");
  window.location.href = "index.html";
});

document.getElementById("loginForm")?.addEventListener("submit", function(e){
  e.preventDefault();
  let userInput = document.getElementById("loginUsername").value;
  let pass = document.getElementById("loginPassword").value;
  
  // Demo mode removed
  const isDemoMode = localStorage.getItem("demo_mode") === "true";
  
  // Get stored credentials (local demo user store)
  let storedUsername = localStorage.getItem("username");
  let storedEmail = localStorage.getItem("email");
  let storedPassword = localStorage.getItem("password");
  // Check against demo_users as source of truth for multiple accounts
  try {
    const users = JSON.parse(localStorage.getItem('demo_users') || '[]');
    const candidate = users.find(u => String(u.username).toLowerCase() === String(userInput).toLowerCase() || String(u.email||'').toLowerCase() === String(userInput).toLowerCase());
    if (candidate) {
      storedUsername = candidate.username;
      storedEmail = candidate.email;
      storedPassword = candidate.password || storedPassword;
      localStorage.setItem('username', storedUsername);
      localStorage.setItem('email', storedEmail);
      if (candidate.password) localStorage.setItem('password', candidate.password);
    }
  } catch(_) { /* ignore */ }
  
  // Check if input matches username or email
  let isValidLogin = false;
  if (userInput === storedUsername || userInput === storedEmail) {
    if (pass === storedPassword) {
      isValidLogin = true;
    }
  }
  
  // Always enforce normal auth
  if (isDemoMode && (!storedUsername || !storedPassword)) {
    if (userInput && pass) {
      // Store the credentials for future use
      localStorage.setItem("username", userInput);
      localStorage.setItem("email", userInput.includes("@") ? userInput : userInput + "@example.com");
      localStorage.setItem("password", pass);
      isValidLogin = true;
    }
  }
  
  if (isValidLogin) {
    // Block access if user is banned (is_active=false in demo_users)
    try {
      const usersKey = 'demo_users';
      const users = JSON.parse(localStorage.getItem(usersKey) || '[]');
      const username = localStorage.getItem("username") || userInput;
      const email = localStorage.getItem("email") || (userInput.includes("@") ? userInput : `${userInput}@example.com`);
      let userRec = users.find(u => String(u.username).toLowerCase() === String(username).toLowerCase() || (email && String(u.email).toLowerCase() === String(email).toLowerCase()));
      if (userRec && userRec.is_active === false) {
        alert("❌ Your account is banned. Please contact support.");
        return;
      }
    } catch(_) { /* ignore */ }
    // Ensure user exists in local admin store so admin search/lists show them
    try {
      const usersKey = 'demo_users';
      const walletsKey = 'demo_wallets';
      const users = JSON.parse(localStorage.getItem(usersKey) || '[]');
      const now = new Date().toISOString();
      const username = localStorage.getItem("username") || userInput;
      const email = localStorage.getItem("email") || (userInput.includes("@") ? userInput : `${userInput}@example.com`);
      let user = users.find(u => String(u.username).toLowerCase() === String(username).toLowerCase());
      if (!user) {
        const id = users.length > 0 ? Math.max(...users.map(u => Number(u.id)||0)) + 1 : 1;
        user = { id, username, email, first_name: '', last_name: '', is_verified: false, is_active: true, created_at: now, last_login: now };
        users.push(user);
        localStorage.setItem(usersKey, JSON.stringify(users));
        const wallets = JSON.parse(localStorage.getItem(walletsKey) || '{}');
        if (!wallets[id]) { wallets[id] = { balance: 0, bonus_balance: 0, locked_balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: now }; }
        localStorage.setItem(walletsKey, JSON.stringify(wallets));
      } else {
        user.last_login = now;
        localStorage.setItem(usersKey, JSON.stringify(users));
      }
    } catch(_) { /* ignore */ }

    localStorage.setItem("loggedIn", "true");
    window.location.href = "welcome.html";
  } else {
    alert("❌ Wrong credentials. Please check your username/email and password.");
  }
});

// Forgot Password Function
function showForgotPassword() {
  let userInput = prompt("Enter your username or email address:");
  
  if (userInput) {
    let storedUsername = localStorage.getItem("username");
    let storedEmail = localStorage.getItem("email");
    let storedPassword = localStorage.getItem("password");
    
    if (userInput === storedUsername || userInput === storedEmail) {
      alert(`Your password is: ${storedPassword}\n\nPlease remember to keep your password secure!`);
    } else {
      alert("❌ No account found with that username or email address.");
    }
  }
}