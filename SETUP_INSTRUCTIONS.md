# Casino App Setup Instructions

## Problem: App Only Works Locally

If your casino app only works locally and registered accounts aren't visible from other devices, you need to run it through a web server instead of opening HTML files directly.

## Solution: Use a Web Server

### Option 1: Node.js Server (Recommended - No PHP Required)
1. **Double-click `start_node_server.bat`**
2. The server will start on port 8000
3. Access your app at: `http://localhost:8000`
4. For other devices on your network: `http://[YOUR_IP_ADDRESS]:8000`

### Option 2: Manual Node.js Start
1. Open Command Prompt in this folder
2. Run: `node server.js`
3. Access your app at: `http://localhost:8000`

### Option 3: PHP Server (Requires PHP Installation)
1. Install PHP from https://windows.php.net/download
2. Double-click `start_network_server.bat`
3. Access at: `http://localhost:8000`

## Why This is Needed

- **File Protocol Limitation**: Opening HTML files directly (`file://`) stores data locally on each device
- **Server Sync**: The app only syncs data between devices when accessed via `http://` protocol
- **Cross-Device Access**: Running through a web server allows all devices to share the same data

## Finding Your IP Address

To access from other devices on your network:
1. Open Command Prompt
2. Run: `ipconfig`
3. Look for "IPv4 Address" (usually starts with 192.168.x.x or 10.x.x.x)
4. Use that IP address: `http://[YOUR_IP]:8000`

## Troubleshooting

- **Node.js Not Found**: Install Node.js from https://nodejs.org/
- **PHP Not Found**: Install PHP from https://windows.php.net/download
- **Port Already in Use**: Change the port number in server.js (line 150) or batch files
- **Firewall Issues**: Allow Node.js/PHP through Windows Firewall
- **Network Access**: Ensure devices are on the same network

## Data Storage

- **Node.js**: User accounts are stored in `data/keys/storage.json`
- **PHP**: User accounts are stored in `data/keys/` directory
- Data syncs automatically between all devices accessing the server
- Each device maintains a local cache for performance

## Quick Test

1. Start the server using one of the options above
2. Open `http://localhost:8000` in your browser
3. Create a test account
4. Open the same URL on another device
5. Try to login with the test account - it should work!
