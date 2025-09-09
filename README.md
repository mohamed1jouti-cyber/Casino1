# Casino Website Setup Guide

## Prerequisites

This website requires:

- PHP 7.0 or higher with MySQL extension
- MySQL server
- Web server (Apache or similar)

The easiest way to get all these components is to install one of these packages:

- [XAMPP](https://www.apachefriends.org/download.html) (Windows, macOS, Linux)
- [WAMP](https://www.wampserver.com/en/download-wampserver-64bits/) (Windows)
- [MAMP](https://www.mamp.info/en/downloads/) (macOS)

## Installation Steps

1. **Install XAMPP, WAMP, or MAMP**
   - Download and install one of the packages mentioned above
   - Start Apache and MySQL services

2. **Place Website Files**
   - Copy all website files to your web server's document root:
     - XAMPP: `C:\xampp\htdocs\casino`
     - WAMP: `C:\wamp64\www\casino`
     - MAMP: `/Applications/MAMP/htdocs/casino`

3. **Set Up Database**
   - Open your web browser and navigate to: `http://localhost/casino/check_environment.php`
   - This will check if your environment is properly configured
   - If everything is OK, click on the "Setup Database" link
   - The setup script will create the necessary database and tables

4. **Access the Website**
   - Open your web browser and navigate to: `http://localhost/casino/`
   - You should see the login page
   - Create a new account by clicking on "Sign up"

## Troubleshooting

### Database Connection Error

If you see "Connection error: Failed to fetch" or similar database errors:

1. Make sure MySQL service is running
2. Check if the database credentials in `config.php` are correct
3. Run the environment check script: `http://localhost/casino/check_environment.php`
4. Make sure the database has been set up by visiting: `http://localhost/casino/setup_database.php`

### PHP or MySQL Not Found

If you see errors about PHP or MySQL not being installed:

1. Make sure you've installed XAMPP, WAMP, or MAMP correctly
2. Ensure that Apache and MySQL services are running
3. Check if PHP is configured with MySQL support

### File Permissions

If you encounter permission errors:

1. Make sure the web server has read and write permissions to the website directory
2. On Linux/macOS, you might need to run: `chmod -R 755 /path/to/casino`

## Support

If you continue to experience issues, please contact support or refer to the documentation for your web server package (XAMPP, WAMP, or MAMP).