@echo off
echo Casino Website - Node.js Server
echo ===============================
echo.

REM Check if Node.js is installed
node --version >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Node.js is not installed or not in your PATH.
    echo.
    echo Please install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo Starting Node.js server...
echo.
echo Your app will be available at:
echo - Local: http://localhost:8000
echo - Network: http://[YOUR_IP_ADDRESS]:8000
echo.
echo To find your IP address, run: ipconfig
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start Node.js server
node server.js

pause






