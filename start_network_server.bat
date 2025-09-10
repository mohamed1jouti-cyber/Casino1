@echo off
echo Casino Website - Network Server
echo ===============================
echo.

REM Check if PHP is installed and in PATH
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo PHP is not installed or not in your PATH.
    echo.
    echo Please install PHP (e.g., via XAMPP/WAMP or standalone) and ensure php.exe is in PATH.
    echo Download: https://windows.php.net/download
    echo.
    pause
    exit /b 1
)

echo Starting PHP server on all network interfaces...
echo.
echo Your app will be available at:
echo - Local: http://localhost:8000
echo - Network: http://[YOUR_IP_ADDRESS]:8000
echo.
echo To find your IP address, run: ipconfig
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start PHP's built-in server on all interfaces
php -S 0.0.0.0:8000

pause






