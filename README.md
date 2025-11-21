# iOrganize - Secure Personal Organizer for IT Students

iOrganize is a web-based personal organizer designed for IT students to manage their daily academic and personal activities securely. It integrates essential productivity tools—a calendar, sticky notes, and a digital diary—under a single user account system.

## Features

### User Account System
- Secure user registration and login using password hashing (bcrypt)
- Account lockout after multiple failed login attempts
- Session management

### Calendar Module
- Add, view, edit, and delete events or reminders
- Monthly and weekly calendar views
- Color-coding for categories (academic, personal, work, health, social, etc.)
- Event time tracking

### Sticky Notes
- Quick notes displayed on the dashboard
- Editable and draggable interface
- Autosave functionality
- Timestamped notes
- Color customization

### Diary Module
- Personal journal entries with date and mood tracking
- Rich text editor for formatting
- Encrypted diary storage for privacy
- Mood tracking with emojis
- Entry filtering by date range

### Security Features
- **Confidentiality**: Password encryption (bcrypt), access control, secure session management
- **Integrity**: Input validation, SQL injection prevention, XSS prevention, data verification
- **Availability**: Backup and restore features, error handling, stable server configuration
- **Incident Response**: Logs of failed login attempts and suspicious actions, breach notification mechanism, recovery system through database backups

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB)
- Apache web server (XAMPP recommended)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation

### Step 1: Clone or Download
Place the project in your web server directory (e.g., `C:\xampp\htdocs\L` for XAMPP).

### Step 2: Database Setup
1. Make sure MySQL is running in XAMPP
2. Run the installation script:
   ```
   http://localhost/L/install.php
   ```
   Or manually import `config/install.sql` into MySQL.

### Step 3: Configuration
1. Edit `config/config.php` and update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'iorganize');
   ```

2. **IMPORTANT**: Change the encryption key for production:
   ```php
   define('ENCRYPTION_KEY', 'your-secret-key-change-this-in-production');
   ```

### Step 4: Permissions
Ensure the following directories are writable:
- `logs/` - For error and security logs
- `backups/` - For database backups

### Step 5: Access
Open your browser and navigate to:
```
http://localhost/L
```

## Usage

### Registration
1. Click "Register" on the login page
2. Enter a username, email, and password
3. Password must be at least 8 characters with uppercase, lowercase, and number
4. Click "Register" to create your account

### Login
1. Enter your username or email and password
2. Click "Login"
3. Account will be locked after 5 failed login attempts for 15 minutes

### Calendar
1. Navigate to "Calendar" from the main menu
2. Switch between month and week views
3. Click on a date to add an event
4. Click on an event to edit or delete it
5. Choose a category and color for each event

### Sticky Notes
1. Go to the Dashboard
2. Click "+ Add Note" to create a new note
3. Drag notes to reposition them
4. Click on a note to edit its content
5. Change note color using the "Change Color" button
6. Delete notes using the "×" button

### Diary
1. Navigate to "Diary" from the main menu
2. Click "+ New Entry" to create a new entry
3. Select a date and mood
4. Write your entry using the rich text editor
5. Entries are automatically encrypted
6. Filter entries by date range
7. Click on an entry in the sidebar to view/edit it

### Settings
1. Navigate to "Settings" from the main menu
2. View security logs
3. Create backups of your data
4. View account information

## Security Features

### Authentication & Authorization
- Password hashing using bcrypt with cost factor 12
- Account lockout after 5 failed login attempts
- Session management with secure settings
- CSRF token protection

### Input Validation & Sanitization
- Server-side validation for all inputs
- Client-side validation for better UX
- SQL injection prevention using prepared statements
- XSS prevention through output escaping
- Input sanitization functions

### Encryption & Data Protection
- Diary entries encrypted using AES-256-CBC
- Password hashing with bcrypt
- Secure session cookies
- Encryption key stored in configuration

### Network/Server Security
- Security headers (X-Content-Type-Options, X-Frame-Options, etc.)
- Error logging to files
- Security event logging
- Session timeout handling

### Incident Response
- Security event logging
- Failed login attempt tracking
- Account lockout mechanism
- Backup and restore functionality
- Error reporting and logging

## File Structure

```
L/
├── api/                    # API endpoints
│   ├── calendar.php       # Calendar API
│   ├── diary.php          # Diary API
│   ├── notes.php          # Sticky notes API
│   └── stats.php          # Statistics API
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   ├── database.php       # Database connection
│   └── install.sql        # Database schema
├── includes/               # PHP includes
│   ├── functions.php      # Helper functions
│   └── security.php       # Security functions
├── logs/                   # Log files (created automatically)
├── backups/                # Backup files (created automatically)
├── dashboard.php           # Dashboard page
├── calendar.php            # Calendar page
├── diary.php               # Diary page
├── settings.php            # Settings page
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── index.php               # Entry point
├── install.php             # Installation script
└── README.md               # This file
```

## Database Schema

The database includes the following tables:
- `users` - User accounts
- `sessions` - Active sessions
- `calendar_events` - Calendar events
- `sticky_notes` - Sticky notes
- `diary_entries` - Diary entries
- `security_logs` - Security event logs
- `backups` - Backup records

## Troubleshooting

### Database Connection Error
- Check MySQL is running in XAMPP
- Verify database credentials in `config/config.php`
- Ensure database `iorganize` exists

### Permission Errors
- Check directory permissions for `logs/` and `backups/`
- Ensure PHP has write permissions

### Encryption Errors
- Verify encryption key is set in `config/config.php`
- Check OpenSSL extension is enabled in PHP

### Session Issues
- Check session directory permissions
- Verify session settings in `config/config.php`

## Security Best Practices

1. **Change Default Settings**: Update encryption key and database credentials
2. **Use HTTPS**: Enable HTTPS in production
3. **Regular Backups**: Create regular backups of your data
4. **Monitor Logs**: Regularly check security logs for suspicious activity
5. **Strong Passwords**: Use strong passwords for user accounts
6. **Update Regularly**: Keep PHP and MySQL updated

## License

This project is for educational purposes as a cybersecurity learning project.

## Support

For issues or questions, please check the security logs in `logs/security.log` and error logs in `logs/error.log`.

## Credits

iOrganize is designed as a cybersecurity learning project demonstrating secure web application development practices.

