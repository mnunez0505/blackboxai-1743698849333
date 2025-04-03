# Installation Guide

This guide will help you install and configure the Employee Vacation Management System.

## Prerequisites

- PHP 7.4 or higher
- Oracle Database
- Apache/Nginx web server
- Composer (optional, for future package management)
- Git (for version control)
- SMTP server for email notifications
- WhatsApp Business API credentials (optional)

## Server Requirements

- Memory: Minimum 256MB (512MB recommended)
- Disk Space: Minimum 100MB
- PHP Extensions:
  - pdo
  - pdo_oci
  - curl
  - json
  - mbstring
  - openssl

## Step-by-Step Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/vacation-management.git
cd vacation-management
```

### 2. Configure the Web Server

#### Apache Configuration
Make sure mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

#### Nginx Configuration
Add to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3. Set Up the Database

1. Create an Oracle database and user:
```sql
CREATE USER vacation_mgmt IDENTIFIED BY your_password;
GRANT CONNECT, RESOURCE TO vacation_mgmt;
```

2. Import the database schema:
```bash
sqlplus vacation_mgmt/your_password @database/schema.sql
```

### 4. Configure the Application

1. Copy the example configuration file:
```bash
cp config.example.php config.php
```

2. Edit config.php and update:
- Database credentials
- Email settings
- Application URL
- WhatsApp API credentials (if using)
- Other configuration options as needed

### 5. Set Directory Permissions

```bash
# Make directories writable
chmod -R 755 .
chmod -R 777 uploads
chmod -R 777 logs
chmod -R 777 cache
chmod -R 777 cron
```

### 6. Set Up the Cron Job

Add to crontab:
```bash
# Run daily at midnight
0 0 * * * /usr/bin/php /path/to/vacation-management/cron/auto_vacation.php
```

### 7. Run the Setup Script

```bash
chmod +x setup.sh
sudo ./setup.sh
```

### 8. Run System Tests

```bash
php tests/test_system.php
```

## Post-Installation Steps

1. Change the default admin password:
   - Username: admin
   - Default Password: Admin123!

2. Configure email settings in config.php:
```php
define('SMTP_HOST', 'your.smtp.server');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_username');
define('SMTP_PASS', 'your_password');
```

3. Set up SSL/HTTPS (recommended for production):
   - Obtain SSL certificate
   - Update .htaccess to force HTTPS
   - Update config.php APP_URL to use https://

## Directory Structure

```
vacation-management/
├── ajax/                 # Ajax handlers
├── assets/              # Static assets
├── cron/                # Cron jobs
├── database/            # Database schema
├── logs/                # Application logs
├── uploads/             # File uploads
├── config.php           # Configuration
└── index.php           # Entry point
```

## Common Issues

### 1. Database Connection Failed
- Verify Oracle credentials in config.php
- Check if Oracle service is running
- Verify PHP Oracle extensions are installed

### 2. Emails Not Sending
- Check SMTP settings in config.php
- Verify email server is accessible
- Check PHP mail logs

### 3. Permission Issues
- Verify directory permissions
- Check Apache/PHP user permissions
- Ensure SELinux settings (if applicable)

### 4. Cron Job Not Running
- Check cron log files
- Verify PHP CLI path
- Check script permissions

## Security Recommendations

1. Use strong passwords
2. Enable HTTPS
3. Keep PHP and dependencies updated
4. Regular security audits
5. Monitor error logs
6. Configure firewall rules
7. Implement rate limiting
8. Regular backups

## Backup Procedure

1. Database backup:
```bash
exp vacation_mgmt/password@db file=backup.dmp
```

2. Files backup:
```bash
tar -czf backup.tar.gz /path/to/vacation-management
```

## Upgrading

1. Backup database and files
2. Pull latest changes:
```bash
git pull origin main
```
3. Run database migrations (if any)
4. Clear cache:
```bash
rm -rf cache/*
```
5. Update configuration if needed
6. Test system functionality

## Support

For issues and support:
- Create GitHub issue
- Email: support@yourdomain.com
- Documentation: /docs
- Wiki: [Project Wiki](https://github.com/yourusername/vacation-management/wiki)

## Contributing

See CONTRIBUTING.md for guidelines on how to contribute to this project.

## License

This project is licensed under the MIT License - see the LICENSE file for details.