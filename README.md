# LicenseCore

A comprehensive, professional license authentication and management system built with PHP and MySQL. LicenseCore provides a secure API for software licensing, user management, and application control.

## 🚀 Features

- **Secure API**: RESTful API with HMAC signature verification
- **User Management**: Register, login, and manage end-users
- **License Keys**: Generate, manage, and validate license keys
- **Application Control**: Create and manage multiple applications
- **Web Dashboard**: User-friendly web interface for management
- **Advanced Admin Panel**: Complete system administration and monitoring
- **Multi-platform**: Supports various programming languages via API
- **Real-time Validation**: Session-based authentication with expiry
- **Comprehensive Logging**: Audit trails and error logging
- **Blacklist System**: HWID, IP, and username blacklisting
- **Webhook Support**: Real-time notifications and integrations
- **File Distribution**: Secure file download system
- **System Analytics**: Real-time statistics and activity monitoring
- **User Administration**: Ban/unban users with detailed audit trails
- **License Oversight**: System-wide license key management
- **Application Lifecycle**: Complete app management across all users

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)
- XAMPP/WAMP (recommended for development)

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/LicenseCore.git
cd LicenseCore
```

### 2. Database Setup
1. Create a MySQL database named `LicenseCore`
2. Run the database setup script:
   ```bash
   php db_enhanced.php
   ```
   Or visit `http://localhost/LicenseCore/db_enhanced.php` in your browser

### 3. Configuration
1. Update database credentials in `includes/config.php` if needed
2. Set your site URL in the configuration file
3. Ensure proper file permissions for logs and uploads directories

### 4. Web Server Configuration
- Point your web server document root to the project directory
- Ensure `mod_rewrite` is enabled for clean URLs
- Set up SSL for production use

### 5. Initial Setup
1. Visit `http://localhost/LicenseCore/setup.php` to create your admin account
2. Access the web dashboard at `http://localhost/LicenseCore/app/`
3. Use the admin panel at `http://localhost/LicenseCore/admin/`

## 📖 Usage

### Web Interface
- **Dashboard**: Overview of applications, licenses, and users
- **Applications**: Create and manage software applications
- **Licenses**: Generate and manage license keys
- **Users**: View and manage registered users
- **Settings**: Configure application settings

### Admin Panel
The comprehensive admin dashboard provides system-wide management capabilities:

#### Dashboard Overview
- **System Statistics**: Real-time metrics for users, applications, licenses, and activity
- **Recent Activity**: Latest applications, users, and license keys
- **Quick Actions**: Direct access to management functions

#### User Management
- **User Overview**: Complete list of all registered users with detailed information
- **User Statistics**: Applications owned, licenses created, end users managed
- **Administrative Controls**: Ban/unban users with reason logging
- **Status Monitoring**: Track active and banned user accounts

#### Application Management
- **Application Control**: View all applications across the system
- **Create Applications**: Admin can create apps for any user
- **Delete Applications**: Complete removal with all associated data cleanup
- **Statistics Tracking**: Monitor active keys, users, and activity per application

#### License Management
- **License Oversight**: System-wide view of all license keys
- **Key Statistics**: Active, inactive, expired, and valid license counts
- **Administrative Actions**: Delete license keys when necessary
- **Usage Tracking**: Monitor which users have utilized which keys

#### System Logs & Analytics
- **Activity Monitoring**: Comprehensive audit trails of all system actions
- **Analytics Dashboard**: Most active users and applications
- **Log Pagination**: Efficient browsing through large datasets
- **Security Tracking**: IP address and access pattern monitoring

**Access**: Navigate to `/admin/` after logging in to access the admin panel.

### API Integration

#### Authentication
```php
// Initialize API connection
$api = new api(
    name: "YourApp",
    ownerid: "YOUR_OWNER_ID",
    secret: "YOUR_APP_SECRET",
    version: "1.0"
);

$api->init();
```

#### License Validation
```php
// Validate a license key
$api->license("LICENSE-KEY-HERE");
```

#### User Management
```php
// Register a user
$api->register("username", "password", "license_key");

// Login user
$api->login("username", "password");
```

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/1.2/` | POST | Main API endpoint |
| `/api/validate.php` | POST | License validation |
| `/app/` | GET | Web dashboard |
| `/admin/` | GET | Admin panel |

### Supported Languages

- C# (.NET)
- Python
- JavaScript/Node.js
- Java
- C++
- And more via REST API

## 🔧 Configuration

### Environment Variables
```php
// In includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'LicenseCore');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'LicenseCore');
```

### API Settings
- **Signature Verification**: HMAC-SHA256
- **Session Timeout**: 1 hour
- **Rate Limiting**: Built-in protection
- **CORS Support**: Configurable origins

## 📚 Documentation

- [API Documentation](DOCUMENTATION.md)
- [Quick Start Guide](QUICK_START.md)
- [Language Examples](EXAMPLES.md)
- [Setup Guide](SETUP_GUIDE.md)

## 🔒 Security Features

- HMAC signature verification
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting
- Secure password hashing
- Session management
- Input sanitization

## 🐛 Troubleshooting

### Common Issues

1. **Signature Check Fail**
   - Ensure correct app secret
   - Check system time synchronization
   - Verify API endpoint URL

2. **Database Connection Error**
   - Check database credentials
   - Ensure MySQL server is running
   - Verify database exists

3. **Permission Errors**
   - Set proper file permissions
   - Check web server user permissions

### Debug Tools
- Run `php debug_api.php` for API testing
- Check `logs/api_errors.log` for errors
- Use browser developer tools for client-side issues

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with modern PHP practices
- Inspired by KeyAuth architecture
- Community contributions welcome

## 📞 Support

- [Documentation](DOCUMENTATION.md)
- [GitHub Issues](https://github.com/sharmaofficiall/LicenseCore/issues)
- [Discord Community](https://discord.gg/phfhMqsBQF)

---

**LicenseCore** - Professional License Management Made Simple
