@echo off
echo 🚀 Cloudflare Deployment Script
echo ===============================
echo.

REM Check if Wrangler is installed
wrangler --version >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Wrangler CLI is not installed.
    echo.
    echo Installing Wrangler CLI...
    npm install -g wrangler
    if %ERRORLEVEL% NEQ 0 (
        echo Failed to install Wrangler CLI.
        echo Please install Node.js first: https://nodejs.org/
        pause
        exit /b 1
    )
)

echo.
echo Step 1: Login to Cloudflare
echo ---------------------------
echo This will open your browser to login to Cloudflare.
echo.
wrangler login
if %ERRORLEVEL% NEQ 0 (
    echo Login failed. Please try again.
    pause
    exit /b 1
)

echo.
echo Step 2: Deploy to Cloudflare
echo ----------------------------
echo Deploying your casino app...
echo.
wrangler deploy

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Deployment successful!
    echo.
    echo Your app is now live on Cloudflare!
    echo Check your Cloudflare dashboard for the URL.
    echo.
) else (
    echo.
    echo ❌ Deployment failed.
    echo Check the error messages above.
    echo.
)

pause




