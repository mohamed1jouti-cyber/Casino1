@echo off
echo Casino Website - Local Development Server
echo =========================================
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

echo Starting PHP development server on http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start PHP's built-in server
php -S localhost:8000

pause