# XAMPP Setup Guide

## Quick Setup Steps

1. **Copy Files**
   ```
   Copy vacation-management folder to: C:\xampp\htdocs\
   ```

2. **Configure Apache**
   - Run as Administrator:
   ```
   configure_xampp.bat
   ```
   OR
   ```
   Right-click configure_xampp.ps1 → Run with PowerShell
   ```

3. **Import Database**
   - Run as Administrator:
   ```
   database\import_mysql.bat
   ```
   OR
   ```
   Right-click database\import_mysql.ps1 → Run with PowerShell
   ```

4. **Configure Application**
   ```
   copy config.xampp.php config.php
   ```
   Edit config.php with your settings.

## Troubleshooting Common Issues

### 1. .htaccess Error
If you see ".htaccess not allowed" error:

1. Open XAMPP Control Panel
2. Click "Config" next to Apache
3. Select "httpd.conf"
4. Find this section:
   ```apache
   <Directory "C:/xampp/htdocs">
       AllowOverride None
       Require all granted
   </Directory>
   ```
5. Change to:
   ```apache
   <Directory "C:/xampp/htdocs">
       AllowOverride All
       Require all granted
   </Directory>
   ```
6. Save and restart Apache

### 2. Database Connection Error

1. Verify MySQL is running
2. Check config.php has correct settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'vacation_management');
   ```

### 3. Permission Issues

Run as Administrator:
```cmd
icacls C:\xampp\htdocs\vacation-management\uploads /grant Everyone:(OI)(CI)F
icacls C:\xampp\htdocs\vacation-management\logs /grant Everyone:(OI)(CI)F
```

### 4. 500 Internal Server Error

1. Check Apache error log:
   ```
   C:\xampp\apache\logs\error.log
   ```

2. Enable error display in config.php:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. Verify mod_rewrite is enabled:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

## Testing Installation

1. **Run System Test**
   ```
   php database\test_mysql.php
   ```

2. **Access Application**
   ```
   http://localhost/vacation-management/
   ```

3. **Default Login**
   - Username: admin
   - Password: Admin123!

## Directory Structure

```
C:\xampp\htdocs\vacation-management\
├── config.php           # Your configuration
├── .htaccess           # Apache configuration
├── uploads/            # File uploads
├── logs/              # System logs
└── database/          # Database files
```

## Important Files

- **configure_xampp.bat/ps1**: XAMPP configuration script
- **import_mysql.bat/ps1**: Database import script
- **test_mysql.php**: Database connection test
- **.htaccess.xampp**: Apache configuration template

## Security Notes

1. **Change Default Password**
   - Login as admin
   - Go to profile settings
   - Update password immediately

2. **Set MySQL Password**
   - Open phpMyAdmin
   - User accounts → Change password
   - Update config.php with new password

3. **Secure Directories**
   - Ensure uploads/ and logs/ are not publicly accessible
   - Keep backup of config.php
   - Remove installation scripts after setup

## Maintenance

1. **Backup Database**
   ```cmd
   C:\xampp\mysql\bin\mysqldump -u root vacation_management > backup.sql
   ```

2. **Check Logs**
   - Apache: C:\xampp\apache\logs\error.log
   - MySQL: C:\xampp\mysql\data\mysql_error.log
   - Application: C:\xampp\htdocs\vacation-management\logs\

3. **Update Application**
   - Backup files and database
   - Replace files
   - Run database migrations
   - Clear cache

## Support

If you encounter issues:

1. Check error logs
2. Verify XAMPP services are running
3. Test database connection
4. Verify file permissions
5. Check Apache configuration

## Additional Resources

- XAMPP Documentation: https://www.apachefriends.org/docs/
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/

Remember to always backup your data before making any configuration changes!