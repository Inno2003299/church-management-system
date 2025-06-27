# Church Inventory Management System

A comprehensive web-based system for managing church operations including member registration, attendance tracking, offering collection, and instrumentalist payments with fingerprint authentication support.

## 🎯 Features

### Core Modules
- **Member Management**: Register members with biometric data support
- **Attendance Tracking**: Manual and fingerprint-based check-in system
- **Offering Collection**: Record and track different types of offerings
- **Instrumentalist Payments**: Manage musicians and their payment records
- **Fingerprint Authentication**: WebAuthn-based biometric authentication

### Key Capabilities
- ✅ Responsive Bootstrap-based UI
- ✅ WebAuthn fingerprint authentication
- ✅ Real-time attendance tracking
- ✅ Multiple offering types support
- ✅ Instrumentalist payment management
- ✅ Dashboard with statistics
- ✅ RESTful API architecture

## 🛠 Tech Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: WebAuthn API
- **Server**: Apache (XAMPP/MAMP/Laragon)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- Modern browser with WebAuthn support (Chrome/Edge recommended)
- Fingerprint sensor (for biometric features)

## 🚀 Installation

### 1. Clone/Download the Project
```bash
git clone [repository-url]
# OR download and extract the ZIP file
```

### 2. Setup Web Server
- Place the project folder in your web server directory:
  - **XAMPP**: `C:\xampp\htdocs\Church-inventory`
  - **MAMP**: `C:\MAMP\htdocs\Church-inventory`
  - **Laragon**: `C:\laragon\www\Church-inventory`

### 3. Database Configuration
1. Start your MySQL server
2. Edit `config/db.php` with your database credentials:
```php
$host = 'localhost';
$user = 'root';        // Your MySQL username
$pass = '';            // Your MySQL password
$db   = 'church_db';   // Database name
```

### 4. Database Setup
1. Open your browser and navigate to: `http://localhost/Church-inventory/setup.php`
2. The setup script will:
   - Create the database and tables
   - Insert default data
   - Add sample members and instrumentalists
   - Configure system settings

### 5. Verify Installation
1. Visit: `http://localhost/Church-inventory/test.html`
2. Check that all system components are working
3. Verify WebAuthn support status

### 6. Access the Application
- Main Application: `http://localhost/Church-inventory/index.html`
- Default Admin: `admin` / `admin123`

## 📱 Usage Guide

### Member Management
1. Navigate to **Members** section
2. Click **Add New Member** to register members
3. Use **Register Fingerprint** to enable biometric authentication
4. Members can then use fingerprint check-in

### Fingerprint Check-in
1. Go to **Check-in** section
2. Select service type
3. Click the fingerprint scanner
4. Follow browser prompts to authenticate
5. System automatically records attendance

### Attendance Tracking
1. **Manual Method**: Admin marks attendance using checkboxes
2. **Fingerprint Method**: Members self-check-in using biometric
3. View attendance reports by date/service

### Offering Management
1. Navigate to **Offerings** section
2. Select service date and type
3. Choose offering category (Tithe, Thanksgiving, etc.)
4. Enter amount and notes
5. System tracks totals by service/date

### Instrumentalist Payments
1. Go to **Instrumentalists** section
2. Add musicians with their instruments and rates
3. Record payments per service
4. Track payment status and history

## 🔧 Configuration

### System Settings
Edit settings in the database `system_settings` table:
- `church_name`: Your church name
- `church_address`: Physical address
- `default_currency`: Currency for offerings
- `enable_fingerprint`: Enable/disable biometric features

### WebAuthn Configuration
- Ensure HTTPS in production (required for WebAuthn)
- Configure `rp.id` in `webauthn.js` for your domain
- Test with supported browsers and devices

## 🔒 Security Features

- **WebAuthn Authentication**: Industry-standard biometric authentication
- **SQL Injection Protection**: Prepared statements throughout
- **Input Sanitization**: All user inputs are sanitized
- **HTTPS Ready**: Designed for secure deployment

## 📊 API Endpoints

### Members
- `GET api/get_members.php` - List all members
- `POST api/save_members.php` - Add new member

### Attendance
- `GET api/attendance.php?date=YYYY-MM-DD` - Get attendance by date
- `POST api/save_attendance.php` - Record attendance

### Offerings
- `GET api/offerings.php` - Get offering types
- `POST api/offerings.php` - Record offering

### WebAuthn
- `POST api/webauthn_register.php` - Register fingerprint
- `POST api/webauthn_authenticate.php` - Authenticate fingerprint
- `POST api/fingerprint_checkin.php` - Check-in with fingerprint

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check MySQL server is running
- Verify credentials in `config/db.php`
- Ensure database exists

**WebAuthn Not Working**
- Use Chrome or Edge browser
- Ensure fingerprint sensor is available
- Check browser console for errors
- HTTPS required in production

**API Errors**
- Check PHP error logs
- Verify file permissions
- Ensure all required files exist

### Browser Compatibility
- **Recommended**: Chrome 67+, Edge 18+
- **WebAuthn Support**: Required for fingerprint features
- **Mobile**: iOS Safari 14+, Android Chrome 70+

## 📈 Future Enhancements

- [ ] Mobile app development
- [ ] Advanced reporting and analytics
- [ ] Email/SMS notifications
- [ ] Multi-church support
- [ ] Advanced user roles and permissions
- [ ] Integration with church management software

## 🤝 Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review browser console for errors
3. Verify server requirements are met

## 📄 License

This project is developed for church management purposes. Please ensure compliance with local data protection regulations when handling member information.

---

**Built with ❤️ for church communities**
