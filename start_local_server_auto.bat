@echo off
echo Casino Website - Auto Local Server
echo ==================================
echo.

REM First, try to find PHP
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 goto :start_server

echo PHP not found. Cannot start without PHP.
echo Please install PHP (e.g., XAMPP/WAMP or standalone) and add it to PATH.
echo Download: https://windows.php.net/download
pause
exit /b 1

:start_server
echo Starting PHP development server on http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8000
pause


