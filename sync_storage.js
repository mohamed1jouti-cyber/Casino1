// Client sync layer: mirror selected localStorage keys to server when hosted
(function(){
    const API = 'storage_api.php';
    const KEYS = [
        'demo_users',
        'demo_wallets',
        'demo_transactions',
        'withdrawRequests',
        'depositRequests',
        'vip_requests',
        'transfer_requests',
        'notifications'
    ];

    function isHttp() {
        return location.protocol === 'http:' || location.protocol === 'https:';
    }

    async function apiSet(key, value){
        try {
            const res = await fetch(`${API}?action=set`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ key, value })
            });
            if (!res.ok) {
                console.error('storage_api set HTTP error', { status: res.status, key });
            }
            const json = await res.json();
            if (!json || json.success !== true) {
                console.error('storage_api set failed', { key, error: json && json.error });
            }
            return json;
        } catch(e){
            console.error('storage_api set exception', { key, error: e && e.message });
            return { success: false, error: e.message };
        }
    }

    async function apiGet(key){
        try {
            const res = await fetch(`${API}?action=get&key=${encodeURIComponent(key)}`);
            if (!res.ok) {
                console.warn('storage_api get HTTP error', { status: res.status, key });
            }
            const json = await res.json();
            if (!json || json.success !== true) {
                console.warn('storage_api get failed', { key, error: json && json.error });
            }
            return json;
        } catch(e){
            console.warn('storage_api get exception', { key, error: e && e.message });
            return { success: false, error: e.message };
        }
    }

    function readLocal(key){
        const raw = localStorage.getItem(key);
        if (raw == null) return null;
        try { return JSON.parse(raw); } catch { return raw; }
    }

    function writeLocal(key, value){
        try {
            const raw = typeof value === 'string' ? value : JSON.stringify(value);
            localStorage.setItem(key, raw);
        } catch(_){}
    }

    async function pullInitial(){
        for (const k of KEYS){
            const r = await apiGet(k);
            if (r && r.success && r.data !== null && r.data !== undefined) {
                writeLocal(k, r.data);
            }
        }
    }

    function schedulePush(key){
        if (!isHttp()) return;
        if (!KEYS.includes(key) && !key.startsWith('balance:') && !key.startsWith('sec_code:')) return;
        let value = readLocal(key);
        apiSet(key, value);
    }

    function init(){
        if (!isHttp()) return; // do nothing for file:// usage
        // Pull server state first
        pullInitial();
        // Push on changes
        window.addEventListener('storage', (e) => {
            if (!e || !e.key) return;
            schedulePush(e.key);
        });
        // Also intercept same-tab writes
        try {
            const _setItem = localStorage.setItem.bind(localStorage);
            localStorage.setItem = function(k, v){
                _setItem(k, v);
                try { schedulePush(k); } catch(_) {}
            };
        } catch(_) {}
        // Initial push for known keys
        setTimeout(() => {
            for (const k of KEYS){ schedulePush(k); }
            // push per-user balance keys in this session
            for (let i = 0; i < localStorage.length; i++){
                const k = localStorage.key(i);
                if (!k) continue;
                if (k.startsWith('balance:') || k.startsWith('sec_code:')) schedulePush(k);
            }
        }, 500);
        // Periodic reconciliation to ensure durability
        setInterval(() => {
            for (const k of KEYS){ schedulePush(k); }
            for (let i = 0; i < localStorage.length; i++){
                const k = localStorage.key(i);
                if (!k) continue;
                if (k.startsWith('balance:') || k.startsWith('sec_code:')) schedulePush(k);
            }
        }, 5000);
    }

    init();
})();


