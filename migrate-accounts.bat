@echo off
echo ========================================
echo    Casino App - Account Migration
echo ========================================
echo.

echo Checking your local accounts...
node migrate-to-database.js
echo.

echo 🔧 Your Options to Fix Local Accounts:
echo.
echo 1. NETLIFY + SUPABASE (Current choice)
echo    - Keep using Netlify
echo    - Add Supabase database
echo    - More complex setup
echo.
echo 2. RAILWAY (Recommended - Easier)
echo    - Switch to Railway
echo    - Built-in database
echo    - Much easier setup
echo.
echo 3. RENDER (Good Alternative)
echo    - Switch to Render
echo    - Built-in database
echo    - Good free tier
echo.

echo 🚀 Quick Recommendations:
echo.
echo For EASIEST setup: Double-click deploy-railway.bat
echo For BEST performance: Double-click deploy-vercel-supabase.bat
echo For NETLIFY + Database: Double-click deploy-netlify-supabase.bat
echo.

echo 💡 Why your accounts are local only:
echo - Netlify only hosts static files
echo - No server-side processing
echo - No database support
echo - Need to add database separately
echo.

echo 🎯 Solution: Add a database to store accounts
echo - Accounts will be accessible from anywhere
echo - Works on all devices
echo - Data persists permanently
echo - Automatic backups
echo.

pause


