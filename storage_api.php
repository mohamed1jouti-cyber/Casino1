<?php
// Simple file-backed key/value JSON storage API
// Stores per-key JSON blobs under data/ directory for easy hosting

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$keysDir = $baseDir . DIRECTORY_SEPARATOR . 'keys';

function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

ensureDir($baseDir);
ensureDir($keysDir);

// Whitelist of allowed storage keys; supports simple prefix rules
$allowedExact = [
    'demo_users',
    'demo_wallets',
    'demo_transactions',
    'withdrawRequests',
    'depositRequests',
    'vip_requests',
    'transfer_requests',
    'notifications',
    'adminNotifications'
];
$allowedPrefixes = [
    'balance:',
    'sec_code:'
];

function isAllowedKey(string $key) {
    global $allowedExact, $allowedPrefixes;
    if (in_array($key, $allowedExact, true)) return true;
    foreach ($allowedPrefixes as $p) {
        if (strpos($key, $p) === 0) return true;
    }
    return false;
}

function keyToFilename(string $key) {
    // Safe filename: replace non-alnum with underscore
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $key);
    return $safe . '.json';
}

function readKey(string $key) {
    global $keysDir;
    $file = $keysDir . DIRECTORY_SEPARATOR . keyToFilename($key);
    if (!file_exists($file)) return null;
    $data = @file_get_contents($file);
    if ($data === false) return null;
    return $data;
}

function writeKey(string $key, string $rawJson) {
    global $keysDir;
    $file = $keysDir . DIRECTORY_SEPARATOR . keyToFilename($key);
    $temp = $file . '.tmp';
    $fp = @fopen($temp, 'wb');
    if (!$fp) return false;
    fwrite($fp, $rawJson);
    fclose($fp);
    return @rename($temp, $file);
}

function listAllKeys() {
    global $keysDir;
    $items = @scandir($keysDir);
    if (!$items) return [];
    $keys = [];
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        if (substr($it, -5) === '.json') {
            $key = substr($it, 0, -5);
            $keys[] = $key; // note: sanitized name, not original key
        }
    }
    return $keys;
}

function ok($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

$input = file_get_contents('php://input');
$json = null;
if (!empty($input)) {
    $json = json_decode($input, true);
}

$action = $_GET['action'] ?? ($json['action'] ?? '');

switch ($action) {
    case 'get': {
        $key = $_GET['key'] ?? ($json['key'] ?? '');
        if (!$key || !isAllowedKey($key)) fail('Key not allowed', 403);
        $raw = readKey($key);
        if ($raw === null) ok(null);
        // Validate stored JSON
        $decoded = json_decode($raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            // Return raw string to avoid data loss
            ok(['raw' => $raw]);
        } else {
            ok($decoded);
        }
        break;
    }
    case 'set': {
        $key = $_GET['key'] ?? ($json['key'] ?? '');
        $value = $json['value'] ?? null;
        if (!$key || !isAllowedKey($key)) fail('Key not allowed', 403);
        if ($value === null) fail('Missing value');
        $raw = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($raw === false) fail('Value must be JSON-serializable');
        if (!writeKey($key, $raw)) fail('Failed to write', 500);
        ok(true);
        break;
    }
    case 'get_all': {
        // Return map of allowed keys that currently exist
        $out = [];
        // exact keys first
        global $allowedExact;
        foreach ($allowedExact as $k) {
            $raw = readKey($k);
            if ($raw !== null) {
                $val = json_decode($raw, true);
                $out[$k] = ($val === null && json_last_error() !== JSON_ERROR_NONE) ? $raw : $val;
            }
        }
        // prefix keys: scan directory
        $files = @scandir($keysDir) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || substr($f, -5) !== '.json') continue;
            $sanitized = substr($f, 0, -5);
            // We cannot recover original key safely, so we only include if it matches known sanitized pattern for prefixes
            // Accept balances and sec_code files based on sanitized prefix
            if (strpos($sanitized, 'balance_') === 0 || strpos($sanitized, 'sec_code_') === 0) {
                $raw = @file_get_contents($keysDir . DIRECTORY_SEPARATOR . $f);
                $val = json_decode($raw, true);
                $out[$sanitized] = ($val === null && json_last_error() !== JSON_ERROR_NONE) ? $raw : $val;
            }
        }
        ok($out);
        break;
    }
    case 'set_batch': {
        $items = $json['items'] ?? null;
        if (!is_array($items)) fail('items must be an object map');
        foreach ($items as $key => $value) {
            if (!isAllowedKey($key)) continue; // skip disallowed silently
            $raw = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($raw === false) continue;
            writeKey($key, $raw);
        }
        ok(true);
        break;
    }
    case 'list': {
        ok(listAllKeys());
        break;
    }
    default:
        fail('Unknown action', 404);
}



