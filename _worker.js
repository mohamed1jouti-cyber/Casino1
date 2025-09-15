// Cloudflare Workers version of the casino server
export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const pathname = url.pathname;

    // CORS headers
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
    };

    // Handle preflight requests
    if (request.method === 'OPTIONS') {
      return new Response(null, {
        status: 200,
        headers: corsHeaders,
      });
    }

    // Handle storage API
    if (pathname === '/storage_api.php') {
      return handleStorageAPI(request, env);
    }

    // Serve static files
    return serveStaticFile(request, pathname, corsHeaders);
  },
};

// Storage handling using Cloudflare KV (if available) or fallback to memory
let memoryStorage = {};

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

async function handleStorageAPI(request, env) {
  const url = new URL(request.url);
  const action = url.searchParams.get('action');
  
  let data = {};
  if (request.method === 'POST') {
    try {
      data = await request.json();
    } catch (error) {
      return sendJSONResponse({ success: false, error: 'Invalid JSON' }, 400);
    }
  } else {
    // Convert URLSearchParams to object
    for (const [key, value] of url.searchParams.entries()) {
      data[key] = value;
    }
  }

  return handleStorageAction(action || data.action, data);
}

function handleStorageAction(action, data) {
  switch (action) {
    case 'get':
      const key = data.key;
      if (!key || !isAllowedKey(key)) {
        return sendJSONResponse({ success: false, error: 'Key not allowed' }, 403);
      }
      const value = memoryStorage[key] || null;
      return sendJSONResponse({ success: true, data: value });

    case 'set':
      const setKey = data.key;
      const setValue = data.value;
      if (!setKey || !isAllowedKey(setKey)) {
        return sendJSONResponse({ success: false, error: 'Key not allowed' }, 403);
      }
      if (setValue === undefined) {
        return sendJSONResponse({ success: false, error: 'Missing value' }, 400);
      }
      memoryStorage[setKey] = setValue;
      return sendJSONResponse({ success: true, data: true });

    case 'get_all':
      const allData = {};
      ALLOWED_KEYS.forEach(key => {
        if (memoryStorage[key] !== undefined) {
          allData[key] = memoryStorage[key];
        }
      });
      // Add prefix keys
      Object.keys(memoryStorage).forEach(key => {
        if (ALLOWED_PREFIXES.some(prefix => key.startsWith(prefix))) {
          allData[key] = memoryStorage[key];
        }
      });
      return sendJSONResponse({ success: true, data: allData });

    case 'set_batch':
      const items = data.items;
      if (!items || typeof items !== 'object') {
        return sendJSONResponse({ success: false, error: 'items must be an object map' }, 400);
      }
      Object.keys(items).forEach(key => {
        if (isAllowedKey(key)) {
          memoryStorage[key] = items[key];
        }
      });
      return sendJSONResponse({ success: true, data: true });

    case 'list':
      const keys = Object.keys(memoryStorage).filter(key => isAllowedKey(key));
      return sendJSONResponse({ success: true, data: keys });

    default:
      return sendJSONResponse({ success: false, error: 'Unknown action' }, 404);
  }
}

function sendJSONResponse(data, statusCode = 200) {
  return new Response(JSON.stringify(data), {
    status: statusCode,
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
    },
  });
}

async function serveStaticFile(request, pathname, corsHeaders) {
  // Default to index.html
  if (pathname === '/') {
    pathname = '/index.html';
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

  try {
    // Try to fetch the file from the assets
    const ext = pathname.substring(pathname.lastIndexOf('.'));
    const contentType = mimeTypes[ext] || 'application/octet-stream';
    
    // For Cloudflare Pages, we need to handle static assets differently
    // This is a simplified version - in practice, you'd use Cloudflare Pages
    // static asset handling
    
    return new Response('File not found', {
      status: 404,
      headers: {
        'Content-Type': 'text/plain',
        ...corsHeaders,
      },
    });
  } catch (error) {
    return new Response('File not found', {
      status: 404,
      headers: {
        'Content-Type': 'text/plain',
        ...corsHeaders,
      },
    });
  }
}






