# Installation Guide for XAMPP (Windows)

## Prerequisites

1. XAMPP installed with:
   - PHP 7.4 or higher
   - Apache
   - Oracle extensions for PHP

2. Git (optional, for cloning repository)

## Installation Steps

### 1. Copy Files

1. Download or clone the project files
2. Copy the `vacation-management` folder to:
   ```
   C:\xampp\htdocs\vacation-management
   ```

### 2. Configure Apache

1. Open `C:\xampp\apache\conf\httpd.conf`
2. Ensure these modules are uncommented (remove # if present):
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   LoadModule ssl_module modules/mod_ssl.so
   ```

### 3. Configure PHP

1. Open `C:\xampp\php\php.ini`
2. Enable required extensions (remove ; if present):
   ```ini
   extension=pdo
   extension=pdo_oci
   extension=oci8
   extension=openssl
   extension=mbstring
   extension=curl
   extension=gd
   ```
3. Set these values:
   ```ini
   max_execution_time = 300
   memory_limit = 256M
   post_max_size = 20M
   upload_max_filesize = 20M
   ```

### 4. Database Setup

1. Create an Oracle database and user
2. Import the schema:
   ```sql
   -- Using SQL*Plus
   sqlplus username/password@database @C:\xampp\htdocs\vacation-management\database\schema.sql
   ```

### 5. Configure Application

1. Copy configuration template:
   ```cmd
   cd C:\xampp\htdocs\vacation-management
   copy config.example.php config.php
   ```

2. Edit `config.php`:
   ```php
   // Database settings
   define('DB_DSN', 'oci:dbname=//localhost:1521/YOUR_SID');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');

   // Email settings
   define('SMTP_HOST', 'smtp.gmail.com'); // or your SMTP server
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your_email@gmail.com');
   define('SMTP_PASS', 'your_email_password');
   ```

### 6. Set Directory Permissions

1. Open Command Prompt as Administrator
2. Set permissions:
   ```cmd
   cd C:\xampp\htdocs\vacation-management
   icacls uploads /grant Everyone:(OI)(CI)F
   icacls logs /grant Everyone:(OI)(CI)F
   icacls cache /grant Everyone:(OI)(CI)F
   ```

### 7. Create Required Directories

```cmd
cd C:\xampp\htdocs\vacation-management
mkdir uploads
mkdir logs
mkdir cache
```

### 8. Configure Task Scheduler (Instead of Cron)

1. Open Windows Task Scheduler
2. Create Basic Task:
   - Name: "Vacation Management - Auto Vacation"
   - Trigger: Daily at midnight
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\vacation-management\cron\auto_vacation.php`

### 9. Test Installation

1. Start XAMPP Control Panel
2. Start Apache service
3. Open browser and navigate to:
   ```
   http://localhost/vacation-management/
   ```

4. Run system test:
   ```cmd
   cd C:\xampp\htdocs\vacation-management
   C:\xampp\php\php.exe tests\test_system.php
   ```

### Default Login

- Username: admin
- Password: Admin123!

Change these credentials immediately after first login.

## Troubleshooting

### 1. Internal Server Error
- Check Apache error logs: `C:\xampp\apache\logs\error.log`
- Ensure mod_rewrite is enabled
- Verify .htaccess file is present and readable

### 2. Database Connection Error
- Verify Oracle credentials in config.php
- Check if Oracle service is running
- Verify PHP Oracle extensions are installed

### 3. Permission Issues
- Ensure directories (uploads, logs, cache) are writable
- Check PHP has permission to write files
- Verify Apache has access to project directory

### 4. Email Not Working
- Check SMTP settings in config.php
- Verify email server is accessible
- Check PHP mail logs

## Security Notes

1. In production:
   - Set `display_errors = Off` in php.ini
   - Enable HTTPS
   - Use strong passwords
   - Keep XAMPP updated

2. File permissions:
   - Restrict access to configuration files
   - Protect sensitive directories
   - Use appropriate file ownership

## Support

For issues and support:
- Check Apache error logs
- Review PHP error logs
- Contact support team
- Check documentation

## Updating

1. Backup your files and database
2. Download new version
3. Replace files (keep your config.php)
4. Run database migrations:
   ```cmd
   C:\xampp\php\php.exe migrate.php
   ```

## Additional Resources

- XAMPP Documentation: https://www.apachefriends.org/docs/
- PHP Documentation: https://www.php.net/docs.php
- Oracle Documentation: https://docs.oracle.com/en/database/