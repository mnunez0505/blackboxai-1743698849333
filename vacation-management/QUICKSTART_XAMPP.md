# Quick Start Guide for XAMPP Users

This guide will help you quickly set up the Vacation Management System on XAMPP.

## Prerequisites

- XAMPP installed (with PHP 7.4 or higher)
- Administrator access to your computer
- Text editor (like Notepad++ or VSCode)

## 1. Installation (5 minutes)

1. **Copy Files**
   ```
   Copy the 'vacation-management' folder to:
   C:\xampp\htdocs\vacation-management
   ```

2. **Run Setup Script**
   - Right-click `setup_xampp.bat` or `setup_xampp.ps1`
   - Select "Run as Administrator"
   - Follow the on-screen instructions

## 2. Database Setup (2 minutes)

1. **Start MySQL**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Import Database**
   - Run `database/import_mysql.bat` or `import_mysql.ps1`
   OR
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database 'vacation_management'
   - Import `database/schema_mysql.sql`

## 3. Configuration (3 minutes)

1. **Copy Configuration File**
   ```
   copy config.xampp.php config.php
   ```

2. **Edit config.php**
   ```php
   // Database settings (default XAMPP)
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'vacation_management');

   // Email settings (example for Gmail)
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your_email@gmail.com');
   define('SMTP_PASS', 'your_app_password');
   ```

## 4. Test Installation (1 minute)

1. **Run Test Script**
   ```
   php database/test_mysql.php
   ```

2. **Check Web Access**
   - Open browser
   - Visit: http://localhost/vacation-management/
   - You should see the login page

## 5. First Login

- **Username:** admin
- **Password:** Admin123!

**IMPORTANT:** Change these credentials immediately!

## Common Issues

### 1. Internal Server Error
```
- Check Apache error logs: C:\xampp\apache\logs\error.log
- Verify .htaccess is enabled in Apache config
- Check file permissions
```

### 2. Database Connection Error
```
- Verify MySQL is running
- Check config.php database settings
- Default XAMPP MySQL has no password
```

### 3. Email Not Working
```
- For Gmail: Use App Password, not regular password
- Check SMTP settings in config.php
- Test email configuration in PHP
```

## Quick Commands

### Start Services
```batch
C:\xampp\xampp_start.exe
```

### Stop Services
```batch
C:\xampp\xampp_stop.exe
```

### Test Database
```batch
php database/test_mysql.php
```

### Import Database
```batch
database/import_mysql.bat
```

## Directory Structure

```
vacation-management/
├── ajax/           # Ajax handlers
├── assets/         # CSS, JS, images
├── database/       # Database files
├── config.php      # Configuration
└── index.php       # Entry point
```

## Security Tips

1. **Change Default Credentials**
   ```sql
   UPDATE users 
   SET password = 'new_hashed_password' 
   WHERE username = 'admin';
   ```

2. **Set MySQL Password**
   - Open phpMyAdmin
   - User Accounts → Change Password

3. **Update config.php**
   - Change CSRF token
   - Set secure email settings
   - Disable debug mode in production

## Next Steps

1. Add employees and supervisors
2. Configure email notifications
3. Set up vacation categories
4. Test request workflow
5. Configure automated tasks

## Support

- Check `error.log` for issues
- Review documentation in `/docs`
- Visit phpMyAdmin for database management
- Check XAMPP logs for server issues

## Updating

1. Backup your files:
   ```batch
   xcopy C:\xampp\htdocs\vacation-management C:\backup\vacation-management /E/H/C/I
   ```

2. Backup database:
   ```batch
   C:\xampp\mysql\bin\mysqldump -u root vacation_management > backup.sql
   ```

3. Update files
4. Run migrations:
   ```php
   php migrate.php
   ```

## Development Tools

- **PHP Error Log:**
  ```
  C:\xampp\php\logs\php_error_log
  ```

- **Apache Error Log:**
  ```
  C:\xampp\apache\logs\error.log
  ```

- **MySQL Log:**
  ```
  C:\xampp\mysql\data\mysql_error.log
  ```

## Troubleshooting Checklist

- [ ] Apache and MySQL are running
- [ ] Database exists and is accessible
- [ ] config.php has correct settings
- [ ] File permissions are set correctly
- [ ] PHP extensions are enabled
- [ ] Error logs are accessible
- [ ] SMTP settings are correct

Remember to check the full documentation in INSTALL_XAMPP.md for detailed information.