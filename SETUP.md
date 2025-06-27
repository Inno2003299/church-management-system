# Setup Instructions

## Quick Setup Guide

### 1. Database Configuration
1. Copy `config/db.example.php` to `config/db.php`
2. Update the database credentials in `config/db.php`:
   ```php
   $host = 'localhost';
   $port = 3306;
   $user = 'your_username';
   $pass = 'your_password';
   $db   = 'church_db';
   ```

### 2. Database Creation
- Create a MySQL database named `church_db`
- The system will automatically create all required tables

### 3. Default Admin Login
- **Email:** bamenorhu8@gmail.com
- **Password:** 123
- ⚠️ Change these credentials after first login!

### 4. File Permissions
Ensure the following directories are writable:
- `config/` (for configuration files)
- Any upload directories (if implemented)

### 5. Web Server Requirements
- PHP 7.2 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- HTTPS recommended for WebAuthn features

## Features Ready to Use

✅ **Member Management** - Add, edit, view members
✅ **Attendance Tracking** - Record and view attendance by service
✅ **Offering Management** - Record offerings with dashboard totals
✅ **Instrumentalist Management** - Manage instrumentalists and payments
✅ **Dashboard Statistics** - Real-time counts and totals
✅ **WebAuthn Support** - Fingerprint authentication ready

## Troubleshooting

### Database Connection Issues
1. Verify MySQL service is running
2. Check database credentials in `config/db.php`
3. Ensure database `church_db` exists

### Permission Errors
1. Check file permissions on config directory
2. Ensure web server can read/write necessary files

### WebAuthn Issues
1. Requires HTTPS in production
2. Browser must support WebAuthn API
3. Check browser console for errors

## Security Notes

- The `config/db.php` file is excluded from Git for security
- Always use HTTPS in production
- Change default admin credentials immediately
- Regular database backups recommended
