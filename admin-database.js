// Admin Database Management JavaScript
// This file handles all database operations for the admin dashboard

class AdminDatabase {
    constructor() {
        this.apiBase = (typeof window !== 'undefined' && window.ADMIN_API_BASE) ? window.ADMIN_API_BASE : 'admin_api.php';
        this.currentPage = 1;
        this.itemsPerPage = 20;
    }

    // Generic API call method
    async apiCall(action, params = {}, method = 'GET') {
        try {
            let url = `${this.apiBase}?action=${action}`;
            
            if (method === 'GET' && Object.keys(params).length > 0) {
                const queryParams = new URLSearchParams(params);
                url += '&' + queryParams.toString();
            }

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            if (method === 'POST' && Object.keys(params).length > 0) {
                options.body = JSON.stringify(params);
            }

            const response = await fetch(url, options);
            const contentType = response.headers.get('content-type') || '';
            let data;
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                const hint = (typeof location !== 'undefined' && location.protocol === 'file:')
                    ? 'PHP is not running. Open this site via a PHP server or use Simple Admin on welcome.html.'
                    : 'The server did not return JSON. Ensure PHP executes admin_api.php and returns JSON.';
                const preview = text.slice(0, 200).replace(/\s+/g, ' ');
                throw new Error(`API did not return JSON (${response.status}). ${hint} Response preview: ${preview}`);
            }

            if (!response.ok) {
                throw new Error(data.error || 'API call failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Dashboard Statistics
    async getDashboardStats() {
        try {
            const data = await this.apiCall('get_stats');
            return data.data;
        } catch (error) {
            // Graceful local fallback when server does not return JSON or is unavailable
            const message = String(error && error.message || '');
            const nonJson = message.includes('did not return JSON') || message.includes('Failed to fetch') || message.includes('NetworkError');
            if (!nonJson) {
            console.error('Failed to get dashboard stats:', error);
            throw error;
            }

            // Return mock dashboard stats to allow UI to function
            console.warn('API unavailable; using local fallback dashboard stats');
            return {
                total_users: 1,
                vip_users: 0,
                total_deposits: 0,
                total_deposits_amount: 0,
                total_withdrawals: 0,
                total_withdrawals_amount: 0,
                pending_requests: 0,
                today_new_users: 0
            };
        }
    }

    // User Management
    async getUsers(page = 1, limit = 20) {
        try {
            const data = await this.apiCall('get_users', { page, limit });
            return data;
        } catch (error) {
            // Graceful local fallback when server does not return JSON or is unavailable
            const message = String(error && error.message || '');
            const nonJson = message.includes('did not return JSON') || message.includes('Failed to fetch') || message.includes('NetworkError');
            if (!nonJson) {
            console.error('Failed to get users:', error);
            throw error;
            }

            // Return mock user data to allow UI to function
            console.warn('API unavailable; using local fallback user data');
            return {
                success: true,
                data: [
                    {
                        id: 1,
                        username: 'demo_user',
                        email: 'demo@example.com',
                        first_name: 'Demo',
                        last_name: 'User',
                        is_verified: true,
                        is_active: true,
                        created_at: new Date().toISOString(),
                        last_login: new Date().toISOString(),
                        balance: 0,
                        bonus_balance: 0,
                        total_deposited: 0,
                        total_withdrawn: 0,
                        total_points: 0,
                        vip_level: 'Bronze'
                    }
                ],
                pagination: { page: 1, limit: 20, total: 1, pages: 1 }
            };
        }
    }

    async getUserDetails(userId) {
        try {
            const data = await this.apiCall('get_user_details', { user_id: userId });
            return data.data;
        } catch (error) {
            console.error('Failed to get user details:', error);
            throw error;
        }
    }

    async searchUsers(query, limit = 20) {
        try {
            const data = await this.apiCall('search_users', { q: query, limit });
            return data.data;
        } catch (error) {
            console.error('Failed to search users:', error);
            throw error;
        }
    }

    async updateUserStatus(userId, status) {
        try {
            const data = await this.apiCall('update_user_status', { user_id: userId, status }, 'POST');
            return data;
        } catch (error) {
            console.error('Failed to update user status:', error);
            throw error;
        }
    }

    async addUserBalance(userId, amount, reason = '') {
        try {
            const data = await this.apiCall('add_user_balance', { user_id: userId, amount, reason }, 'POST');
            return data;
        } catch (error) {
            // Graceful local fallback when server does not return JSON or is unavailable
            const message = String(error && error.message || '');
            const nonJson = message.includes('did not return JSON') || message.includes('Failed to fetch') || message.includes('NetworkError');
            if (!nonJson) {
            console.error('Failed to add user balance:', error);
            throw error;
            }

            // Minimal local fallback: store balances per user ID to allow UI progress
            const BAL_KEY = 'fallback_balances';
            const raw = localStorage.getItem(BAL_KEY);
            let balances = {};
            try { balances = raw ? JSON.parse(raw) : {}; } catch { balances = {}; }

            const uid = String(userId);
            const before = Number(balances[uid] || 0);
            const inc = Number(amount || 0);
            if (!Number.isFinite(inc) || inc <= 0) {
                throw new Error('Amount must be greater than 0');
            }
            balances[uid] = before + inc;
            localStorage.setItem(BAL_KEY, JSON.stringify(balances));

            console.warn('API unavailable; applied local fallback balance update for user', uid);
            return { success: true, message: 'Balance added locally (API unavailable)' };
        }
    }

    // VIP Management
    async getVIPStats() {
        try {
            const data = await this.apiCall('get_vip_stats');
            return data.data;
        } catch (error) {
            console.error('Failed to get VIP stats:', error);
            throw error;
        }
    }

    // Transaction Management
    async getTransactions(page = 1, limit = 20, type = '', status = '') {
        try {
            const params = { page, limit };
            if (type) params.type = type;
            if (status) params.status = status;
            
            const data = await this.apiCall('get_transactions', params);
            return data;
        } catch (error) {
            console.error('Failed to get transactions:', error);
            throw error;
        }
    }

    // Game Statistics
    async getGameStats() {
        try {
            const data = await this.apiCall('get_game_stats');
            return data.data;
        } catch (error) {
            console.error('Failed to get game stats:', error);
            throw error;
        }
    }

    // User Activity
    async getUserActivity(userId, limit = 50) {
        try {
            const data = await this.apiCall('get_user_activity', { user_id: userId, limit });
            return data.data;
        } catch (error) {
            console.error('Failed to get user activity:', error);
            throw error;
        }
    }
}

// --- Local (no-server) database implementation ---
class LocalAdminDatabase {
    constructor() {
        this.storageKeys = {
            users: 'demo_users',
            wallets: 'demo_wallets',
            transactions: 'demo_transactions'
        };
        this.itemsPerPage = 20;
        this.ensureSeedData();
    }

    ensureSeedData() {
        const usersRaw = localStorage.getItem(this.storageKeys.users);
        if (!usersRaw) {
            const now = new Date().toISOString();
            const users = [
                { id: 1, username: 'demo_user', email: 'demo@example.com', first_name: 'Demo', last_name: 'User', is_verified: true, is_active: true, created_at: now, last_login: now }
            ];
            const wallets = { 1: { balance: 0, bonus_balance: 0, locked_balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: now } };
            const transactions = [];
            localStorage.setItem(this.storageKeys.users, JSON.stringify(users));
            localStorage.setItem(this.storageKeys.wallets, JSON.stringify(wallets));
            localStorage.setItem(this.storageKeys.transactions, JSON.stringify(transactions));
        }
    }

    read(key) {
        try { const raw = localStorage.getItem(key); return raw ? JSON.parse(raw) : null; } catch { return null; }
    }
    write(key, value) { localStorage.setItem(key, JSON.stringify(value)); }

    async getDashboardStats() {
        const users = this.read(this.storageKeys.users) || [];
        const txs = this.read(this.storageKeys.transactions) || [];
        const deposits = txs.filter(t => t.transaction_type === 'deposit' && t.status === 'completed');
        const withdrawals = txs.filter(t => t.transaction_type === 'withdrawal' && t.status === 'completed');
        return {
            total_users: users.length,
            vip_users: 0,
            total_deposits: deposits.length,
            total_deposits_amount: deposits.reduce((s, t) => s + Number(t.amount || 0), 0),
            total_withdrawals: withdrawals.length,
            total_withdrawals_amount: withdrawals.reduce((s, t) => s + Number(t.amount || 0), 0),
            pending_requests: txs.filter(t => t.status === 'pending').length,
            today_new_users: 0
        };
    }

    async getUsers(page = 1, limit = 20) {
        const users = this.read(this.storageKeys.users) || [];
        const wallets = this.read(this.storageKeys.wallets) || {};
        const start = (page - 1) * limit;
        const slice = users.slice(start, start + limit).map(u => ({
            ...u,
            balance: Number((wallets[u.id] && wallets[u.id].balance) || 0),
            bonus_balance: Number((wallets[u.id] && wallets[u.id].bonus_balance) || 0),
            total_deposited: Number((wallets[u.id] && wallets[u.id].total_deposited) || 0),
            total_withdrawn: Number((wallets[u.id] && wallets[u.id].total_withdrawn) || 0),
            total_points: 0,
            vip_level: 'Bronze'
        }));
        return {
            success: true,
            data: slice,
            pagination: { page, limit, total: users.length, pages: Math.max(1, Math.ceil(users.length / limit)) }
        };
    }

    async searchUsers(query, limit = 20) {
        const q = String(query || '').toLowerCase();
        const users = this.read(this.storageKeys.users) || [];
        const wallets = this.read(this.storageKeys.wallets) || {};
        return users.filter(u => (
            u.username.toLowerCase().includes(q) ||
            String(u.email || '').toLowerCase().includes(q) ||
            String(u.first_name || '').toLowerCase().includes(q) ||
            String(u.last_name || '').toLowerCase().includes(q)
        )).slice(0, limit).map(u => ({ ...u, balance: Number((wallets[u.id] && wallets[u.id].balance) || 0) }));
    }

    async ensureUserByUsername(username) {
        const uname = String(username || '').trim();
        if (!uname) throw new Error('Username required');
        const users = this.read(this.storageKeys.users) || [];
        let user = users.find(u => u.username.toLowerCase() === uname.toLowerCase());
        if (user) return user;
        const now = new Date().toISOString();
        const newId = users.length > 0 ? Math.max(...users.map(u => Number(u.id) || 0)) + 1 : 1;
        user = { id: newId, username: uname, email: '', first_name: '', last_name: '', is_verified: false, is_active: true, created_at: now, last_login: now };
        users.push(user);
        this.write(this.storageKeys.users, users);
        const wallets = this.read(this.storageKeys.wallets) || {};
        if (!wallets[newId]) {
            wallets[newId] = { balance: 0, bonus_balance: 0, locked_balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: now };
            this.write(this.storageKeys.wallets, wallets);
        }
        return user;
    }

    async getUserDetails(userId) {
        const users = this.read(this.storageKeys.users) || [];
        const wallets = this.read(this.storageKeys.wallets) || {};
        const user = users.find(u => u.id === Number(userId));
        if (!user) throw new Error('User not found');
        const txs = (this.read(this.storageKeys.transactions) || []).filter(t => t.user_id === Number(userId)).slice(-10).reverse();
        return {
            ...user,
            balance: Number((wallets[user.id] && wallets[user.id].balance) || 0),
            bonus_balance: Number((wallets[user.id] && wallets[user.id].bonus_balance) || 0),
            locked_balance: Number((wallets[user.id] && wallets[user.id].locked_balance) || 0),
            total_deposited: Number((wallets[user.id] && wallets[user.id].total_deposited) || 0),
            total_withdrawn: Number((wallets[user.id] && wallets[user.id].total_withdrawn) || 0),
            recent_transactions: txs,
            game_stats: []
        };
    }

    async updateUserStatus(userId, status) {
        const users = this.read(this.storageKeys.users) || [];
        const idx = users.findIndex(u => u.id === Number(userId));
        if (idx === -1) throw new Error('User not found');
        users[idx].is_active = Boolean(status);
        this.write(this.storageKeys.users, users);
        return { success: true, message: 'User status updated successfully' };
    }

    async addUserBalance(userId, amount, reason = '') {
        const uid = Number(userId);
        const amt = Number(amount);
        if (!uid || !Number.isFinite(amt) || amt <= 0) throw new Error('Amount must be greater than 0');
        const wallets = this.read(this.storageKeys.wallets) || {};
        const now = new Date().toISOString();
        const w = wallets[uid] || { balance: 0, bonus_balance: 0, locked_balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: now };
        const before = Number(w.balance || 0);
        w.balance = before + amt;
        w.total_deposited = Number(w.total_deposited || 0) + amt;
        w.updated_at = now;
        wallets[uid] = w;
        this.write(this.storageKeys.wallets, wallets);

        // Also sync per-user balance key used by user pages: balance:<username>
        const users = this.read(this.storageKeys.users) || [];
        const u = users.find(x => Number(x.id) === uid);
        if (u && u.username) {
            const balanceKey = `balance:${String(u.username)}`;
            localStorage.setItem(balanceKey, String(w.balance));
            // Broadcast sync event for open tabs
            try {
                localStorage.setItem('balance_sync', JSON.stringify({ username: String(u.username), balance: Number(w.balance), ts: Date.now() }));
            } catch(_) { /* ignore */ }
        }

        const txs = this.read(this.storageKeys.transactions) || [];
        txs.push({
            id: txs.length + 1,
            user_id: uid,
            transaction_type: 'bonus',
            amount: amt,
            balance_before: before,
            balance_after: w.balance,
            status: 'completed',
            description: reason || 'Admin balance addition',
            created_at: now
        });
        this.write(this.storageKeys.transactions, txs);

        return { success: true, message: 'Balance added successfully' };
    }

    async getVIPStats() { return []; }
    async getTransactions(page = 1, limit = 20) {
        const txs = this.read(this.storageKeys.transactions) || [];
        const start = (page - 1) * limit;
        return { data: txs.slice(start, start + limit), pagination: { page, limit, total: txs.length, pages: Math.max(1, Math.ceil(txs.length / limit)) } };
    }
    async getUserActivity() { return []; }
}

// Global admin database instance (force local mode)
const adminDB = new LocalAdminDatabase();

// Dashboard Functions
async function loadDashboardStats() {
    try {
        const stats = await adminDB.getDashboardStats();
        
        // Update dashboard stats
        document.getElementById('totalDeposits').textContent = stats.total_deposits;
        document.getElementById('totalWithdrawals').textContent = stats.total_withdrawals;
        document.getElementById('pendingRequests').textContent = stats.pending_requests;
        document.getElementById('totalAmount').textContent = `$${(stats.total_deposits_amount + stats.total_withdrawals_amount).toLocaleString()}`;
        
        // Add new stats if they exist
        const statsGrid = document.querySelector('.stats-grid');
        
        // Check if new stats already exist
        if (!document.getElementById('totalUsers')) {
            const totalUsersCard = document.createElement('div');
            totalUsersCard.className = 'stat-card';
            totalUsersCard.innerHTML = `
                <div class="stat-number" id="totalUsers">${stats.total_users}</div>
                <div class="stat-label">Total Users</div>
            `;
            statsGrid.appendChild(totalUsersCard);
        } else {
            document.getElementById('totalUsers').textContent = stats.total_users;
        }
        
        if (!document.getElementById('vipUsers')) {
            const vipUsersCard = document.createElement('div');
            vipUsersCard.className = 'stat-card';
            vipUsersCard.innerHTML = `
                <div class="stat-number" id="vipUsers">${stats.vip_users}</div>
                <div class="stat-label">VIP Users</div>
            `;
            statsGrid.appendChild(vipUsersCard);
        } else {
            document.getElementById('vipUsers').textContent = stats.vip_users;
        }
        
        if (!document.getElementById('todayUsers')) {
            const todayUsersCard = document.createElement('div');
            todayUsersCard.className = 'stat-card';
            todayUsersCard.innerHTML = `
                <div class="stat-number" id="todayUsers">${stats.today_new_users}</div>
                <div class="stat-label">New Users Today</div>
            `;
            statsGrid.appendChild(todayUsersCard);
        } else {
            document.getElementById('todayUsers').textContent = stats.today_new_users;
        }
        
    } catch (error) {
        showMessage('Failed to load dashboard stats: ' + error.message, 'error');
    }
}

// User Management Functions
async function loadUsersFromDatabase(page = 1) {
    try {
        const usersData = await adminDB.getUsers(page, this.itemsPerPage);
        displayUsersFromDatabase(usersData.data);
        displayPagination(usersData.pagination);
    } catch (error) {
        showMessage('Failed to load users: ' + error.message, 'error');
    }
}

function displayUsersFromDatabase(users) {
    const userBalancesList = document.getElementById('userBalancesList');
    
    if (users.length === 0) {
        userBalancesList.innerHTML = '<div class="no-requests">No users found in database.</div>';
        return;
    }
    
    userBalancesList.innerHTML = users.map(user => `
        <div class="user-balance-card">
            <div class="user-balance-header">
                <div class="user-name">${user.username}</div>
                <div class="user-balance">$${parseFloat(user.balance || 0).toFixed(2)}</div>
            </div>
            <div class="user-email">📧 ${user.email}</div>
            <div class="user-details">
                <div class="detail-item">
                    <div class="detail-label">VIP Level</div>
                    <div class="detail-value">${user.vip_level || 'Bronze'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Points</div>
                    <div class="detail-value">${user.total_points || 0}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value ${user.is_active ? 'status-active' : 'status-inactive'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </div>
                </div>
            </div>
            <div class="user-actions">
                <button class="action-btn" onclick="viewUserDetails(${user.id})">👁️ View Details</button>
                <button class="action-btn ${user.is_active ? 'reject-btn' : 'approve-btn'}" 
                        onclick="toggleUserStatus(${user.id}, ${user.is_active})">
                    ${user.is_active ? 'Deactivate' : 'Activate'}
                </button>
            </div>
        </div>
    `).join('');
}

function displayPagination(pagination) {
    const userBalancesList = document.getElementById('userBalancesList');
    
    if (pagination.pages <= 1) return;
    
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'pagination';
    paginationDiv.innerHTML = `
        <div class="pagination-info">
            Page ${pagination.page} of ${pagination.pages} (${pagination.total} total users)
        </div>
        <div class="pagination-controls">
            ${pagination.page > 1 ? `<button onclick="loadUsersFromDatabase(${pagination.page - 1})">← Previous</button>` : ''}
            ${pagination.page < pagination.pages ? `<button onclick="loadUsersFromDatabase(${pagination.page + 1})">Next →</button>` : ''}
        </div>
    `;
    
    userBalancesList.appendChild(paginationDiv);
}

async function viewUserDetails(userId) {
    try {
        const user = await adminDB.getUserDetails(userId);
        showUserDetailsModal(user);
    } catch (error) {
        showMessage('Failed to load user details: ' + error.message, 'error');
    }
}

async function toggleUserStatus(userId, currentStatus) {
    try {
        const newStatus = !currentStatus;
        await adminDB.updateUserStatus(userId, newStatus);
        showMessage(`User ${newStatus ? 'activated' : 'deactivated'} successfully!`, 'success');
        loadUsersFromDatabase(); // Reload users
    } catch (error) {
        showMessage('Failed to update user status: ' + error.message, 'error');
    }
}

// Enhanced Add Balance Function
async function addBalanceToUserFromDatabase(username, amount, reason) {
    try {
        // First search for the user
        const users = await adminDB.searchUsers(username, 1);
        if (users.length === 0) {
            showMessage(`User "${username}" not found.`, 'error');
            return;
        }
        const user = users[0];
        
        // Add balance to the user
        await adminDB.addUserBalance(user.id, parseFloat(amount), reason);
        
        showMessage(`Successfully added $${amount} to ${username}'s balance!`, 'success');
        loadUsersFromDatabase(); // Reload users
        
        // Reset form
        document.getElementById('addBalanceForm').reset();
        
    } catch (error) {
        showMessage('Failed to add balance: ' + error.message, 'error');
    }
}

// User Details Modal
function showUserDetailsModal(user) {
    // Create modal HTML
    const modalHTML = `
        <div id="userDetailsModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>User Details: ${user.username}</h2>
                
                <div class="user-info-grid">
                    <div class="info-section">
                        <h3>Basic Information</h3>
                        <p><strong>Email:</strong> ${user.email}</p>
                        <p><strong>Name:</strong> ${user.first_name || 'N/A'} ${user.last_name || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="${user.is_active ? 'status-active' : 'status-inactive'}">${user.is_active ? 'Active' : 'Inactive'}</span></p>
                        <p><strong>Verified:</strong> ${user.is_verified ? 'Yes' : 'No'}</p>
                        <p><strong>Member Since:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                        <p><strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</p>
                    </div>
                    
                    <div class="info-section">
                        <h3>VIP Information</h3>
                        <p><strong>VIP Level:</strong> ${user.vip_level || 'Bronze'}</p>
                        <p><strong>Total Points:</strong> ${user.total_points || 0}</p>
                        <p><strong>Cashback:</strong> ${user.cashback_percentage || 0}%</p>
                        <p><strong>Bonus Multiplier:</strong> ${user.bonus_multiplier || 1}x</p>
                    </div>
                    
                    <div class="info-section">
                        <h3>Financial Information</h3>
                        <p><strong>Main Balance:</strong> $${parseFloat(user.balance || 0).toFixed(2)}</p>
                        <p><strong>Bonus Balance:</strong> $${parseFloat(user.bonus_balance || 0).toFixed(2)}</p>
                        <p><strong>Locked Balance:</strong> $${parseFloat(user.locked_balance || 0).toFixed(2)}</p>
                        <p><strong>Total Deposited:</strong> $${parseFloat(user.total_deposited || 0).toFixed(2)}</p>
                        <p><strong>Total Withdrawn:</strong> $${parseFloat(user.total_withdrawn || 0).toFixed(2)}</p>
                    </div>
                    
                    <div class="info-section">
                        <h3>Gaming Statistics</h3>
                        <p><strong>Lifetime Wagered:</strong> $${parseFloat(user.lifetime_wagered || 0).toFixed(2)}</p>
                        <p><strong>Lifetime Won:</strong> $${parseFloat(user.lifetime_won || 0).toFixed(2)}</p>
                        <p><strong>Lifetime Lost:</strong> $${parseFloat(user.lifetime_lost || 0).toFixed(2)}</p>
                    </div>
                </div>
                
                <div class="recent-activity">
                    <h3>Recent Transactions</h3>
                    <div class="transactions-list">
                        ${user.recent_transactions && user.recent_transactions.length > 0 ? 
                            user.recent_transactions.map(t => `
                                <div class="transaction-item">
                                    <span class="transaction-type">${t.transaction_type}</span>
                                    <span class="transaction-amount">$${parseFloat(t.amount).toFixed(2)}</span>
                                    <span class="transaction-date">${new Date(t.created_at).toLocaleDateString()}</span>
                                    <span class="transaction-status status-${t.status}">${t.status}</span>
                                </div>
                            `).join('') : '<p>No recent transactions</p>'
                        }
                    </div>
                </div>
                
                <div class="game-stats">
                    <h3>Game Statistics</h3>
                    <div class="game-stats-grid">
                        ${user.game_stats && user.game_stats.length > 0 ? 
                            user.game_stats.map(g => `
                                <div class="game-stat-item">
                                    <h4>${g.game_type}</h4>
                                    <p>Games: ${g.games_played}</p>
                                    <p>Wagered: $${parseFloat(g.total_wagered || 0).toFixed(2)}</p>
                                    <p>Won: $${parseFloat(g.total_won || 0).toFixed(2)}</p>
                                    <p>Lost: $${parseFloat(g.total_lost || 0).toFixed(2)}</p>
                                </div>
                            `).join('') : '<p>No game statistics available</p>'
                        }
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = document.getElementById('userDetailsModal');
    modal.style.display = 'block';
    
    // Close modal functionality
    const closeBtn = modal.querySelector('.close');
    closeBtn.onclick = function() {
        modal.remove();
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.remove();
        }
    }
}

// Search Users Function
async function searchUsersInDatabase(query) {
    if (!query.trim()) {
        loadUsersFromDatabase();
        return;
    }
    
    try {
        const users = await adminDB.searchUsers(query, 50);
        displayUsersFromDatabase(users);
    } catch (error) {
        showMessage('Failed to search users: ' + error.message, 'error');
    }
}

// Initialize database functionality
function initializeDatabaseFeatures() {
    // Load dashboard stats
    loadDashboardStats();
    
    // Add search functionality to user management
    const userManagementTab = document.getElementById('users');
    if (userManagementTab) {
        const searchDiv = document.createElement('div');
        searchDiv.className = 'search-section';
        searchDiv.innerHTML = `
            <div class="search-container">
                <input type="text" id="userSearchInput" placeholder="Search users by username, email, or name..." 
                       class="search-input" onkeyup="searchUsersInDatabase(this.value)">
                <button onclick="searchUsersInDatabase(document.getElementById('userSearchInput').value)" 
                        class="action-btn">🔍 Search</button>
            </div>
        `;
        
        userManagementTab.insertBefore(searchDiv, userManagementTab.firstChild);
    }
    
    // Override the existing loadUserBalances function
    if (typeof loadUserBalances === 'function') {
        // Store the original function
        window.originalLoadUserBalances = loadUserBalances;
        
        // Replace with database version
        window.loadUserBalances = function() {
            loadUsersFromDatabase();
        };
    }
    
    // Override the existing addBalanceToUser function
    if (typeof addBalanceToUser === 'function') {
        // Store the original function
        window.originalAddBalanceToUser = addBalanceToUser;
        
        // Replace with database version
        window.addBalanceToUser = function(username, amount, reason) {
            addBalanceToUserFromDatabase(username, amount, reason);
        };
    }
}

// Add CSS for new elements
function addDatabaseStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .search-section {
            margin-bottom: 20px;
            padding: 20px;
            background: #1a202c;
            border-radius: 10px;
            border: 2px solid #4a5568;
        }
        
        .search-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .detail-item {
            background: #2d3748;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .detail-label {
            color: #a0aec0;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: white;
            font-weight: bold;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .status-active {
            color: #48bb78;
        }
        
        .status-inactive {
            color: #e53e3e;
        }
        
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            background: #2d3748;
            border-radius: 10px;
        }
        
        .pagination-controls button {
            padding: 8px 16px;
            background: #4a5568;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 0 5px;
        }
        
        .pagination-controls button:hover {
            background: #fbbf24;
            color: #1a202c;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            background-color: #1a202c;
            margin: 5% auto;
            padding: 30px;
            border: 2px solid #4a5568;
            border-radius: 15px;
            width: 90%;
            max-width: 1200px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #a0aec0;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #fbbf24;
        }
        
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-section {
            background: #2d3748;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #4a5568;
        }
        
        .info-section h3 {
            color: #fbbf24;
            margin-bottom: 15px;
            border-bottom: 2px solid #4a5568;
            padding-bottom: 10px;
        }
        
        .info-section p {
            margin: 10px 0;
            color: #e2e8f0;
        }
        
        .recent-activity, .game-stats {
            margin: 20px 0;
        }
        
        .recent-activity h3, .game-stats h3 {
            color: #fbbf24;
            margin-bottom: 15px;
        }
        
        .transactions-list {
            background: #2d3748;
            border-radius: 10px;
            padding: 15px;
        }
        
        .transaction-item {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #4a5568;
            align-items: center;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .game-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .game-stat-item {
            background: #2d3748;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #4a5568;
            text-align: center;
        }
        
        .game-stat-item h4 {
            color: #fbbf24;
            margin-bottom: 10px;
        }
        
        .game-stat-item p {
            margin: 5px 0;
            color: #e2e8f0;
        }
    `;
    
    document.head.appendChild(style);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addDatabaseStyles();
    initializeDatabaseFeatures();
});

// Export for use in other scripts
window.AdminDatabase = AdminDatabase;
window.adminDB = adminDB;



                    <div class="info-section">

                        <h3>VIP Information</h3>

                        <p><strong>VIP Level:</strong> ${user.vip_level || 'Bronze'}</p>

                        <p><strong>Total Points:</strong> ${user.total_points || 0}</p>

                        <p><strong>Cashback:</strong> ${user.cashback_percentage || 0}%</p>

                        <p><strong>Bonus Multiplier:</strong> ${user.bonus_multiplier || 1}x</p>

                    </div>

                    

                    <div class="info-section">

                        <h3>Financial Information</h3>

                        <p><strong>Main Balance:</strong> $${parseFloat(user.balance || 0).toFixed(2)}</p>

                        <p><strong>Bonus Balance:</strong> $${parseFloat(user.bonus_balance || 0).toFixed(2)}</p>

                        <p><strong>Locked Balance:</strong> $${parseFloat(user.locked_balance || 0).toFixed(2)}</p>

                        <p><strong>Total Deposited:</strong> $${parseFloat(user.total_deposited || 0).toFixed(2)}</p>

                        <p><strong>Total Withdrawn:</strong> $${parseFloat(user.total_withdrawn || 0).toFixed(2)}</p>

                    </div>

                    

                    <div class="info-section">

                        <h3>Gaming Statistics</h3>

                        <p><strong>Lifetime Wagered:</strong> $${parseFloat(user.lifetime_wagered || 0).toFixed(2)}</p>

                        <p><strong>Lifetime Won:</strong> $${parseFloat(user.lifetime_won || 0).toFixed(2)}</p>

                        <p><strong>Lifetime Lost:</strong> $${parseFloat(user.lifetime_lost || 0).toFixed(2)}</p>

                    </div>

                </div>

                

                <div class="recent-activity">

                    <h3>Recent Transactions</h3>

                    <div class="transactions-list">

                        ${user.recent_transactions && user.recent_transactions.length > 0 ? 

                            user.recent_transactions.map(t => `

                                <div class="transaction-item">

                                    <span class="transaction-type">${t.transaction_type}</span>

                                    <span class="transaction-amount">$${parseFloat(t.amount).toFixed(2)}</span>

                                    <span class="transaction-date">${new Date(t.created_at).toLocaleDateString()}</span>

                                    <span class="transaction-status status-${t.status}">${t.status}</span>

                                </div>

                            `).join('') : '<p>No recent transactions</p>'

                        }

                    </div>

                </div>

                

                <div class="game-stats">

                    <h3>Game Statistics</h3>

                    <div class="game-stats-grid">

                        ${user.game_stats && user.game_stats.length > 0 ? 

                            user.game_stats.map(g => `

                                <div class="game-stat-item">

                                    <h4>${g.game_type}</h4>

                                    <p>Games: ${g.games_played}</p>

                                    <p>Wagered: $${parseFloat(g.total_wagered || 0).toFixed(2)}</p>

                                    <p>Won: $${parseFloat(g.total_won || 0).toFixed(2)}</p>

                                    <p>Lost: $${parseFloat(g.total_lost || 0).toFixed(2)}</p>

                                </div>

                            `).join('') : '<p>No game statistics available</p>'

                        }

                    </div>

                </div>

            </div>

        </div>

    `;

    

    // Add modal to page

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    

    // Show modal

    const modal = document.getElementById('userDetailsModal');

    modal.style.display = 'block';

    

    // Close modal functionality

    const closeBtn = modal.querySelector('.close');

    closeBtn.onclick = function() {

        modal.remove();

    }

    

    // Close modal when clicking outside

    window.onclick = function(event) {

        if (event.target === modal) {

            modal.remove();

        }

    }

}



// Search Users Function

async function searchUsersInDatabase(query) {

    if (!query.trim()) {

        loadUsersFromDatabase();

        return;

    }

    

    try {

        const users = await adminDB.searchUsers(query, 50);

        displayUsersFromDatabase(users);

    } catch (error) {

        showMessage('Failed to search users: ' + error.message, 'error');

    }

}



// Initialize database functionality

function initializeDatabaseFeatures() {

    // Load dashboard stats

    loadDashboardStats();

    

    // Add search functionality to user management

    const userManagementTab = document.getElementById('users');

    if (userManagementTab) {

        const searchDiv = document.createElement('div');

        searchDiv.className = 'search-section';

        searchDiv.innerHTML = `

            <div class="search-container">

                <input type="text" id="userSearchInput" placeholder="Search users by username, email, or name..." 

                       class="search-input" onkeyup="searchUsersInDatabase(this.value)">

                <button onclick="searchUsersInDatabase(document.getElementById('userSearchInput').value)" 

                        class="action-btn">🔍 Search</button>

            </div>

        `;

        

        userManagementTab.insertBefore(searchDiv, userManagementTab.firstChild);

    }

    

    // Override the existing loadUserBalances function

    if (typeof loadUserBalances === 'function') {

        // Store the original function

        window.originalLoadUserBalances = loadUserBalances;

        

        // Replace with database version

        window.loadUserBalances = function() {

            loadUsersFromDatabase();

        };

    }

    

    // Override the existing addBalanceToUser function

    if (typeof addBalanceToUser === 'function') {

        // Store the original function

        window.originalAddBalanceToUser = addBalanceToUser;

        

        // Replace with database version

        window.addBalanceToUser = function(username, amount, reason) {

            addBalanceToUserFromDatabase(username, amount, reason);

        };

    }

}



// Add CSS for new elements

function addDatabaseStyles() {

    const style = document.createElement('style');

    style.textContent = `

        .search-section {

            margin-bottom: 20px;

            padding: 20px;

            background: #1a202c;

            border-radius: 10px;

            border: 2px solid #4a5568;

        }

        

        .search-container {

            display: flex;

            gap: 15px;

            align-items: center;

        }

        

        .user-details {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 15px;

            margin: 15px 0;

        }

        

        .detail-item {

            background: #2d3748;

            padding: 10px;

            border-radius: 8px;

            text-align: center;

        }

        

        .detail-label {

            color: #a0aec0;

            font-size: 12px;

            margin-bottom: 5px;

        }

        

        .detail-value {

            color: white;

            font-weight: bold;

        }

        

        .user-actions {

            display: flex;

            gap: 10px;

            justify-content: center;

            margin-top: 15px;

        }

        

        .status-active {

            color: #48bb78;

        }

        

        .status-inactive {

            color: #e53e3e;

        }

        

        .pagination {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 20px;

            padding: 20px;

            background: #2d3748;

            border-radius: 10px;

        }

        

        .pagination-controls button {

            padding: 8px 16px;

            background: #4a5568;

            color: white;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            margin: 0 5px;

        }

        

        .pagination-controls button:hover {

            background: #fbbf24;

            color: #1a202c;

        }

        

        .modal {

            display: none;

            position: fixed;

            z-index: 1000;

            left: 0;

            top: 0;

            width: 100%;

            height: 100%;

            background-color: rgba(0,0,0,0.8);

        }

        

        .modal-content {

            background-color: #1a202c;

            margin: 5% auto;

            padding: 30px;

            border: 2px solid #4a5568;

            border-radius: 15px;

            width: 90%;

            max-width: 1200px;

            max-height: 80vh;

            overflow-y: auto;

        }

        

        .close {

            color: #a0aec0;

            float: right;

            font-size: 28px;

            font-weight: bold;

            cursor: pointer;

        }

        

        .close:hover {

            color: #fbbf24;

        }

        

        .user-info-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));

            gap: 20px;

            margin: 20px 0;

        }

        

        .info-section {

            background: #2d3748;

            padding: 20px;

            border-radius: 10px;

            border: 2px solid #4a5568;

        }

        

        .info-section h3 {

            color: #fbbf24;

            margin-bottom: 15px;

            border-bottom: 2px solid #4a5568;

            padding-bottom: 10px;

        }

        

        .info-section p {

            margin: 10px 0;

            color: #e2e8f0;

        }

        

        .recent-activity, .game-stats {

            margin: 20px 0;

        }

        

        .recent-activity h3, .game-stats h3 {

            color: #fbbf24;

            margin-bottom: 15px;

        }

        

        .transactions-list {

            background: #2d3748;

            border-radius: 10px;

            padding: 15px;

        }

        

        .transaction-item {

            display: grid;

            grid-template-columns: 1fr 1fr 1fr 1fr;

            gap: 15px;

            padding: 10px;

            border-bottom: 1px solid #4a5568;

            align-items: center;

        }

        

        .transaction-item:last-child {

            border-bottom: none;

        }

        

        .game-stats-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 15px;

        }

        

        .game-stat-item {

            background: #2d3748;

            padding: 15px;

            border-radius: 8px;

            border: 2px solid #4a5568;

            text-align: center;

        }

        

        .game-stat-item h4 {

            color: #fbbf24;

            margin-bottom: 10px;

        }

        

        .game-stat-item p {

            margin: 5px 0;

            color: #e2e8f0;

        }

    `;

    

    document.head.appendChild(style);

}



// Initialize when DOM is loaded

document.addEventListener('DOMContentLoaded', function() {

    addDatabaseStyles();

    initializeDatabaseFeatures();

});



// Export for use in other scripts

window.AdminDatabase = AdminDatabase;

window.adminDB = adminDB;





                    

                    <div class="info-section">

                        <h3>VIP Information</h3>

                        <p><strong>VIP Level:</strong> ${user.vip_level || 'Bronze'}</p>

                        <p><strong>Total Points:</strong> ${user.total_points || 0}</p>

                        <p><strong>Cashback:</strong> ${user.cashback_percentage || 0}%</p>

                        <p><strong>Bonus Multiplier:</strong> ${user.bonus_multiplier || 1}x</p>

                    </div>

                    

                    <div class="info-section">

                        <h3>Financial Information</h3>

                        <p><strong>Main Balance:</strong> $${parseFloat(user.balance || 0).toFixed(2)}</p>

                        <p><strong>Bonus Balance:</strong> $${parseFloat(user.bonus_balance || 0).toFixed(2)}</p>

                        <p><strong>Locked Balance:</strong> $${parseFloat(user.locked_balance || 0).toFixed(2)}</p>

                        <p><strong>Total Deposited:</strong> $${parseFloat(user.total_deposited || 0).toFixed(2)}</p>

                        <p><strong>Total Withdrawn:</strong> $${parseFloat(user.total_withdrawn || 0).toFixed(2)}</p>

                    </div>

                    

                    <div class="info-section">

                        <h3>Gaming Statistics</h3>

                        <p><strong>Lifetime Wagered:</strong> $${parseFloat(user.lifetime_wagered || 0).toFixed(2)}</p>

                        <p><strong>Lifetime Won:</strong> $${parseFloat(user.lifetime_won || 0).toFixed(2)}</p>

                        <p><strong>Lifetime Lost:</strong> $${parseFloat(user.lifetime_lost || 0).toFixed(2)}</p>

                    </div>

                </div>

                

                <div class="recent-activity">

                    <h3>Recent Transactions</h3>

                    <div class="transactions-list">

                        ${user.recent_transactions && user.recent_transactions.length > 0 ? 

                            user.recent_transactions.map(t => `

                                <div class="transaction-item">

                                    <span class="transaction-type">${t.transaction_type}</span>

                                    <span class="transaction-amount">$${parseFloat(t.amount).toFixed(2)}</span>

                                    <span class="transaction-date">${new Date(t.created_at).toLocaleDateString()}</span>

                                    <span class="transaction-status status-${t.status}">${t.status}</span>

                                </div>

                            `).join('') : '<p>No recent transactions</p>'

                        }

                    </div>

                </div>

                

                <div class="game-stats">

                    <h3>Game Statistics</h3>

                    <div class="game-stats-grid">

                        ${user.game_stats && user.game_stats.length > 0 ? 

                            user.game_stats.map(g => `

                                <div class="game-stat-item">

                                    <h4>${g.game_type}</h4>

                                    <p>Games: ${g.games_played}</p>

                                    <p>Wagered: $${parseFloat(g.total_wagered || 0).toFixed(2)}</p>

                                    <p>Won: $${parseFloat(g.total_won || 0).toFixed(2)}</p>

                                    <p>Lost: $${parseFloat(g.total_lost || 0).toFixed(2)}</p>

                                </div>

                            `).join('') : '<p>No game statistics available</p>'

                        }

                    </div>

                </div>

            </div>

        </div>

    `;

    

    // Add modal to page

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    

    // Show modal

    const modal = document.getElementById('userDetailsModal');

    modal.style.display = 'block';

    

    // Close modal functionality

    const closeBtn = modal.querySelector('.close');

    closeBtn.onclick = function() {

        modal.remove();

    }

    

    // Close modal when clicking outside

    window.onclick = function(event) {

        if (event.target === modal) {

            modal.remove();

        }

    }

}



// Search Users Function

async function searchUsersInDatabase(query) {

    if (!query.trim()) {

        loadUsersFromDatabase();

        return;

    }

    

    try {

        const users = await adminDB.searchUsers(query, 50);

        displayUsersFromDatabase(users);

    } catch (error) {

        showMessage('Failed to search users: ' + error.message, 'error');

    }

}



// Initialize database functionality

function initializeDatabaseFeatures() {

    // Load dashboard stats

    loadDashboardStats();

    

    // Add search functionality to user management

    const userManagementTab = document.getElementById('users');

    if (userManagementTab) {

        const searchDiv = document.createElement('div');

        searchDiv.className = 'search-section';

        searchDiv.innerHTML = `

            <div class="search-container">

                <input type="text" id="userSearchInput" placeholder="Search users by username, email, or name..." 

                       class="search-input" onkeyup="searchUsersInDatabase(this.value)">

                <button onclick="searchUsersInDatabase(document.getElementById('userSearchInput').value)" 

                        class="action-btn">🔍 Search</button>

            </div>

        `;

        

        userManagementTab.insertBefore(searchDiv, userManagementTab.firstChild);

    }

    

    // Override the existing loadUserBalances function

    if (typeof loadUserBalances === 'function') {

        // Store the original function

        window.originalLoadUserBalances = loadUserBalances;

        

        // Replace with database version

        window.loadUserBalances = function() {

            loadUsersFromDatabase();

        };

    }

    

    // Override the existing addBalanceToUser function

    if (typeof addBalanceToUser === 'function') {

        // Store the original function

        window.originalAddBalanceToUser = addBalanceToUser;

        

        // Replace with database version

        window.addBalanceToUser = function(username, amount, reason) {

            addBalanceToUserFromDatabase(username, amount, reason);

        };

    }

}



// Add CSS for new elements

function addDatabaseStyles() {

    const style = document.createElement('style');

    style.textContent = `

        .search-section {

            margin-bottom: 20px;

            padding: 20px;

            background: #1a202c;

            border-radius: 10px;

            border: 2px solid #4a5568;

        }

        

        .search-container {

            display: flex;

            gap: 15px;

            align-items: center;

        }

        

        .user-details {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 15px;

            margin: 15px 0;

        }

        

        .detail-item {

            background: #2d3748;

            padding: 10px;

            border-radius: 8px;

            text-align: center;

        }

        

        .detail-label {

            color: #a0aec0;

            font-size: 12px;

            margin-bottom: 5px;

        }

        

        .detail-value {

            color: white;

            font-weight: bold;

        }

        

        .user-actions {

            display: flex;

            gap: 10px;

            justify-content: center;

            margin-top: 15px;

        }

        

        .status-active {

            color: #48bb78;

        }

        

        .status-inactive {

            color: #e53e3e;

        }

        

        .pagination {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 20px;

            padding: 20px;

            background: #2d3748;

            border-radius: 10px;

        }

        

        .pagination-controls button {

            padding: 8px 16px;

            background: #4a5568;

            color: white;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            margin: 0 5px;

        }

        

        .pagination-controls button:hover {

            background: #fbbf24;

            color: #1a202c;

        }

        

        .modal {

            display: none;

            position: fixed;

            z-index: 1000;

            left: 0;

            top: 0;

            width: 100%;

            height: 100%;

            background-color: rgba(0,0,0,0.8);

        }

        

        .modal-content {

            background-color: #1a202c;

            margin: 5% auto;

            padding: 30px;

            border: 2px solid #4a5568;

            border-radius: 15px;

            width: 90%;

            max-width: 1200px;

            max-height: 80vh;

            overflow-y: auto;

        }

        

        .close {

            color: #a0aec0;

            float: right;

            font-size: 28px;

            font-weight: bold;

            cursor: pointer;

        }

        

        .close:hover {

            color: #fbbf24;

        }

        

        .user-info-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));

            gap: 20px;

            margin: 20px 0;

        }

        

        .info-section {

            background: #2d3748;

            padding: 20px;

            border-radius: 10px;

            border: 2px solid #4a5568;

        }

        

        .info-section h3 {

            color: #fbbf24;

            margin-bottom: 15px;

            border-bottom: 2px solid #4a5568;

            padding-bottom: 10px;

        }

        

        .info-section p {

            margin: 10px 0;

            color: #e2e8f0;

        }

        

        .recent-activity, .game-stats {

            margin: 20px 0;

        }

        

        .recent-activity h3, .game-stats h3 {

            color: #fbbf24;

            margin-bottom: 15px;

        }

        

        .transactions-list {

            background: #2d3748;

            border-radius: 10px;

            padding: 15px;

        }

        

        .transaction-item {

            display: grid;

            grid-template-columns: 1fr 1fr 1fr 1fr;

            gap: 15px;

            padding: 10px;

            border-bottom: 1px solid #4a5568;

            align-items: center;

        }

        

        .transaction-item:last-child {

            border-bottom: none;

        }

        

        .game-stats-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 15px;

        }

        

        .game-stat-item {

            background: #2d3748;

            padding: 15px;

            border-radius: 8px;

            border: 2px solid #4a5568;

            text-align: center;

        }

        

        .game-stat-item h4 {

            color: #fbbf24;

            margin-bottom: 10px;

        }

        

        .game-stat-item p {

            margin: 5px 0;

            color: #e2e8f0;

        }

    `;

    

    document.head.appendChild(style);

}



// Initialize when DOM is loaded

document.addEventListener('DOMContentLoaded', function() {

    addDatabaseStyles();

    initializeDatabaseFeatures();

});



// Export for use in other scripts

window.AdminDatabase = AdminDatabase;

window.adminDB = adminDB;



