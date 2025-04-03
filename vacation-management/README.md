# Employee Vacation Management System

A comprehensive web-based system for managing employee vacation requests, built with PHP, Ajax, Oracle, and Bootstrap.

## Features

- User Authentication (Login, Registration, Password Recovery)
- Role-based Access Control (Admin, Supervisor, Employee)
- Vacation Request Management
- Automatic Vacation Days Assignment
- Email and WhatsApp Notifications
- Responsive Design
- Modern UI with Bootstrap 5

## Requirements

- PHP 7.4 or higher
- Oracle Database
- Web Server (Apache/Nginx)
- Composer (for future package management)
- SMTP Server for email notifications
- WhatsApp Business API credentials (optional)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/vacation-management.git
cd vacation-management
```

2. Configure the database:
   - Create an Oracle database and user
   - Import the schema from `database/schema.sql`
   - Update database credentials in `config.php`

3. Configure email settings in `config.php`:
```php
define('EMAIL_FROM', 'your-email@domain.com');
define('EMAIL_FROM_NAME', 'Vacation Management System');
```

4. (Optional) Configure WhatsApp API settings in `config.php`:
```php
define('WHATSAPP_API_URL', 'your-api-url');
define('WHATSAPP_API_KEY', 'your-api-key');
```

5. Set up the cron job for automatic vacation days assignment:
```bash
# Add to crontab
0 0 * * * /usr/bin/php /path/to/vacation-management/cron/auto_vacation.php
```

6. Set proper permissions:
```bash
chmod 755 -R vacation-management
chmod 777 -R vacation-management/cron
```

## Default Admin Account

- Username: admin
- Password: Admin123!

Change these credentials immediately after first login.

## Directory Structure

```
vacation-management/
├── ajax/                 # Ajax handlers
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   ├── request_vacation.php
│   ├── approve_request.php
│   └── get_request_details.php
├── assets/              # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── cron/                # Cron jobs
│   └── auto_vacation.php
├── database/            # Database schema
│   └── schema.sql
├── config.php          # Configuration file
├── functions.php       # Common functions
├── index.php          # Login page
├── register.php       # Registration page
├── forgot_password.php # Password recovery
├── reset_password.php  # Password reset
├── dashboard.php      # Main dashboard
├── request_vacation.php # Vacation request form
├── approve_request.php # Request approval page
└── README.md          # This file
```

## Security Considerations

1. Update the default admin password immediately after installation
2. Configure secure SMTP settings for email notifications
3. Use HTTPS in production
4. Keep the Oracle database credentials secure
5. Regularly update dependencies
6. Monitor system logs
7. Implement rate limiting for login attempts
8. Use prepared statements for all database queries (already implemented)
9. Validate all user inputs
10. Implement CSRF protection (already implemented)

## User Roles

### Employee
- Submit vacation requests
- View their vacation history
- Check available vacation days
- Receive notifications

### Supervisor
- View and manage vacation requests from their team
- Approve or reject requests
- View team vacation calendar
- Receive notifications for new requests

### Admin
- Manage all users
- Assign supervisors
- Configure system settings
- View system logs
- Manage roles and permissions

## Automatic Features

- Automatic vacation days assignment after one year of employment
- Email notifications for:
  - New vacation requests
  - Request approvals/rejections
  - Password reset
  - Account creation
- WhatsApp notifications (if configured)
- System logs for important actions

## Customization

1. Styling:
   - Modify `assets/css/style.css` for custom styles
   - Update Bootstrap variables for theme customization

2. Email Templates:
   - Modify email templates in respective PHP files
   - Customize email formatting and content

3. Business Rules:
   - Adjust vacation day calculations in `functions.php`
   - Modify approval workflow in `approve_request.php`

## Troubleshooting

1. Check the following log files:
   - PHP error log
   - Apache/Nginx error log
   - Application logs in `cron/auto_vacation.log`

2. Common issues:
   - Database connection errors: Verify Oracle credentials
   - Email not sending: Check SMTP configuration
   - Cron job not running: Verify cron setup and permissions
   - Session issues: Check PHP session configuration

## Support

For issues and feature requests, please create an issue in the repository or contact support at support@yourdomain.com.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Authors

- Your Name - Initial work - [YourGitHub](https://github.com/yourusername)

## Acknowledgments

- Bootstrap team for the excellent UI framework
- Font Awesome for icons
- Contributors and testers