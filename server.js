const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

// Storage directory
const DATA_DIR = path.join(__dirname, 'data', 'keys');
const STORAGE_FILE = path.join(DATA_DIR, 'storage.json');

// Ensure data directory exists
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

// Initialize storage file if it doesn't exist
if (!fs.existsSync(STORAGE_FILE)) {
    fs.writeFileSync(STORAGE_FILE, '{}');
}

// Read storage
function readStorage() {
    try {
        const data = fs.readFileSync(STORAGE_FILE, 'utf8');
        return JSON.parse(data);
    } catch (error) {
        return {};
    }
}

// Write storage
function writeStorage(data) {
    try {
        fs.writeFileSync(STORAGE_FILE, JSON.stringify(data, null, 2));
        return true;
    } catch (error) {
        console.error('Error writing storage:', error);
        return false;
    }
}

// Allowed storage keys
const ALLOWED_KEYS = [
    'demo_users',
    'demo_wallets', 
    'demo_transactions',
    'withdrawRequests',
    'depositRequests',
    'vip_requests',
    'transfer_requests',
    'notifications'
];

const ALLOWED_PREFIXES = ['balance:', 'sec_code:'];

function isAllowedKey(key) {
    if (ALLOWED_KEYS.includes(key)) return true;
    return ALLOWED_PREFIXES.some(prefix => key.startsWith(prefix));
}

// MIME types
const mimeTypes = {
    '.html': 'text/html',
    '.js': 'text/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon'
};

const server = http.createServer((req, res) => {
    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // Health check endpoint
    if (pathname === '/healthz') {
        const storage = readStorage();
        // Basic writeability check: try touching storage file
        try {
            writeStorage(storage);
            sendJSONResponse(res, { ok: true, writable: true, time: Date.now() });
        } catch (e) {
            sendJSONResponse(res, { ok: true, writable: false, error: String(e) });
        }
        return;
    }

    // Handle storage API
    if (pathname === '/storage_api.php') {
        handleStorageAPI(req, res, parsedUrl);
        return;
    }

    // Serve static files
    serveStaticFile(req, res, pathname);
});

function handleStorageAPI(req, res, parsedUrl) {
    const action = parsedUrl.query.action;
    const storage = readStorage();

    if (req.method === 'POST') {
        let body = '';
        req.on('data', chunk => {
            body += chunk.toString();
        });
        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                handleStorageAction(res, action || data.action, data, storage);
            } catch (error) {
                sendJSONResponse(res, { success: false, error: 'Invalid JSON' }, 400);
            }
        });
    } else {
        handleStorageAction(res, action, parsedUrl.query, storage);
    }
}

function handleStorageAction(res, action, data, storage) {
    switch (action) {
        case 'get':
            const key = data.key;
            if (!key || !isAllowedKey(key)) {
                sendJSONResponse(res, { success: false, error: 'Key not allowed' }, 403);
                return;
            }
            const value = storage[key] || null;
            sendJSONResponse(res, { success: true, data: value });
            break;

        case 'set':
            const setKey = data.key;
            const setValue = data.value;
            if (!setKey || !isAllowedKey(setKey)) {
                sendJSONResponse(res, { success: false, error: 'Key not allowed' }, 403);
                return;
            }
            if (setValue === undefined) {
                sendJSONResponse(res, { success: false, error: 'Missing value' }, 400);
                return;
            }
            storage[setKey] = setValue;
            writeStorage(storage);
            sendJSONResponse(res, { success: true, data: true });
            break;

        case 'get_all':
            const allData = {};
            ALLOWED_KEYS.forEach(key => {
                if (storage[key] !== undefined) {
                    allData[key] = storage[key];
                }
            });
            // Add prefix keys
            Object.keys(storage).forEach(key => {
                if (ALLOWED_PREFIXES.some(prefix => key.startsWith(prefix))) {
                    allData[key] = storage[key];
                }
            });
            sendJSONResponse(res, { success: true, data: allData });
            break;

        case 'set_batch':
            const items = data.items;
            if (!items || typeof items !== 'object') {
                sendJSONResponse(res, { success: false, error: 'items must be an object map' }, 400);
                return;
            }
            Object.keys(items).forEach(key => {
                if (isAllowedKey(key)) {
                    storage[key] = items[key];
                }
            });
            writeStorage(storage);
            sendJSONResponse(res, { success: true, data: true });
            break;

        case 'list':
            const keys = Object.keys(storage).filter(key => isAllowedKey(key));
            sendJSONResponse(res, { success: true, data: keys });
            break;

        default:
            sendJSONResponse(res, { success: false, error: 'Unknown action' }, 404);
    }
}

function sendJSONResponse(res, data, statusCode = 200) {
    res.writeHead(statusCode, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(data));
}

function serveStaticFile(req, res, pathname) {
    // Default to index.html
    if (pathname === '/') {
        pathname = '/index.html';
    }

    // Remove leading slash
    const filePath = path.join(__dirname, pathname.substring(1));
    
    // Security check - prevent directory traversal
    if (!filePath.startsWith(__dirname)) {
        res.writeHead(403);
        res.end('Forbidden');
        return;
    }

    fs.readFile(filePath, (err, data) => {
        if (err) {
            res.writeHead(404);
            res.end('File not found');
            return;
        }

        const ext = path.extname(filePath);
        const contentType = mimeTypes[ext] || 'application/octet-stream';
        
        res.writeHead(200, { 'Content-Type': contentType });
        res.end(data);
    });
}

const PORT = process.env.PORT || 8000;
const HOST = process.env.NODE_ENV === 'production' ? '0.0.0.0' : '0.0.0.0';

server.listen(PORT, HOST, () => {
    console.log(`🎲 Casino Server running at:`);
    console.log(`   Local: http://localhost:${PORT}`);
    if (process.env.NODE_ENV !== 'production') {
        console.log(`   Network: http://[YOUR_IP]:${PORT}`);
    }
    console.log(`   Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`   Press Ctrl+C to stop the server`);
});

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🛑 Shutting down server...');
    server.close(() => {
        console.log('✅ Server stopped');
        process.exit(0);
    });
});
