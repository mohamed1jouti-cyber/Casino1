@echo off
echo ========================================
echo    Casino App - Netlify + Supabase
echo ========================================
echo.

echo Step 1: Installing dependencies...
npm install
if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)
echo ✅ Dependencies installed successfully
echo.

echo Step 2: Checking local accounts...
node migrate-to-database.js
echo.

echo Step 3: Preparing for Netlify + Supabase deployment...
echo.
echo 🚀 Ready to deploy to Netlify + Supabase!
echo.
echo This will solve your local accounts problem by:
echo ✅ Moving accounts to a real database
echo ✅ Making them accessible from anywhere
echo ✅ Syncing across all devices
echo ✅ Backing up automatically
echo.
echo Next steps:
echo.
echo PART 1 - SUPABASE DATABASE:
echo 1. Go to https://supabase.com/
echo 2. Sign up and create new project
echo 3. Get database connection details from Settings ^> Database
echo 4. Note down: Host, Database, User, Password
echo.
echo PART 2 - NETLIFY DEPLOYMENT:
echo 1. Go to https://netlify.com/
echo 2. Sign up and create new site
echo 3. Connect your GitHub repository
echo 4. Add environment variables in Site Settings ^> Environment Variables:
echo    DB_HOST=your-supabase-host
echo    DB_PORT=5432
echo    DB_NAME=postgres
echo    DB_USER=postgres
echo    DB_PASSWORD=your-supabase-password
echo.
echo Your app will be live at: https://your-site-name.netlify.app
echo.
echo 📖 For detailed instructions, see: NETLIFY_DEPLOYMENT_GUIDE.md
echo.
pause


