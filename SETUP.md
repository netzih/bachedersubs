# Bay Area Cheder - Substitute Tracking System

## Setup Instructions for Plesk Hosting

### Prerequisites
- Plesk hosting account with PHP 8.x support
- MySQL database access
- FTP/SFTP or File Manager access

### Step-by-Step Installation

#### 1. Create MySQL Database in Plesk

1. Log into your Plesk control panel
2. Go to **Databases** → **Add Database**
3. Create a new database (e.g., `bachedersubs`)
4. Create a database user with a strong password
5. Grant all privileges to the user for this database
6. **Note down** the database name, username, and password

#### 2. Upload Files

**Option A: Using Git (Recommended)**
1. In Plesk, go to **Git** (if available)
2. Clone this repository
3. Or use SSH to clone: `git clone <repository-url>`

**Option B: Using FTP/File Manager**
1. Upload all files to your domain's root directory (e.g., `httpdocs/` or `public_html/`)
2. Ensure all files maintain their directory structure

#### 3. Configure Database Connection

1. Locate the file `config.example.php` in the root directory
2. Copy it and rename to `config.php`:
   ```bash
   cp config.example.php config.php
   ```
3. Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
4. **IMPORTANT**: Change the `JWT_SECRET` to a random string (minimum 32 characters)
5. Update `SITE_URL` to your actual domain

#### 4. Run Database Setup

1. In Plesk, go to **Databases** → **phpMyAdmin**
2. Select your database
3. Click on the **SQL** tab
4. Open the `setup.sql` file from your project
5. Copy and paste the entire SQL content
6. Click **Go** to execute

**Default Admin Account Created:**
- Email: `admin@bayareacheder.com`
- Password: `admin123`
- **IMPORTANT**: Change this password immediately after first login!

#### 5. Set File Permissions (if needed)

Ensure the web server can read all files:
```bash
chmod -R 755 .
chmod 644 config.php
```

#### 6. Configure PHP Settings (Optional)

In Plesk, go to **PHP Settings** and ensure:
- PHP version: 8.0 or higher
- `session.auto_start`: Off
- `display_errors`: Off (for production)

#### 7. SSL Certificate (Recommended)

1. In Plesk, go to **SSL/TLS Certificates**
2. Install a Let's Encrypt certificate (free)
3. Enable "Redirect from HTTP to HTTPS"

### First Time Access

1. Visit your domain: `https://yourdomain.com`
2. You'll see the login page
3. Login with the default admin account
4. **Immediately change the admin password**
5. Add teachers from the admin dashboard
6. Invite substitutes to register

### File Structure

```
/
├── index.html              # Login page
├── config.php              # Database configuration (create from config.example.php)
├── setup.sql               # Database schema
├── .gitignore             # Git ignore file
├── SETUP.md               # This file
├── README.md              # Project documentation
├── api/                   # API endpoints
│   ├── auth.php          # Authentication
│   ├── teachers.php      # Teachers management
│   ├── substitutes.php   # Substitutes management
│   └── time_entries.php  # Time tracking
├── includes/              # PHP classes
│   ├── Database.php      # Database connection
│   ├── Auth.php          # Authentication handler
│   └── functions.php     # Helper functions
├── admin/                 # Admin dashboard
│   └── dashboard.php
├── substitute/            # Substitute dashboard
│   └── dashboard.php
└── assets/               # Static assets
    ├── css/
    │   └── style.css
    └── js/
        ├── auth.js
        ├── admin.js
        └── substitute.js
```

### Security Notes

1. **Never commit `config.php` to version control** - it contains sensitive credentials
2. Change the default admin password immediately
3. Use strong passwords for all accounts
4. Keep PHP and database software updated
5. Regularly backup your database
6. Use HTTPS (SSL) for all traffic

### Updating the Application

**Using Git:**
```bash
git pull origin main
```

**Manual:**
1. Download updated files
2. Upload via FTP, **excluding** `config.php`
3. Check if `setup.sql` has updates and run new migrations if needed

### Backup

**Database Backup (via Plesk):**
1. Go to **Databases** → Select your database
2. Click **Export Dump**
3. Download the SQL file
4. Store securely (schedule regular backups)

**File Backup:**
- Use Plesk's backup feature or download via FTP
- Ensure `config.php` is included in backups

### Troubleshooting

**White screen / 500 error:**
- Check PHP error logs in Plesk
- Verify database credentials in `config.php`
- Ensure PHP version is 8.0 or higher

**Database connection failed:**
- Verify credentials in `config.php`
- Ensure database exists and user has permissions
- Check if DB_HOST is correct (usually `localhost`)

**Login not working:**
- Clear browser cache and cookies
- Check if `setup.sql` was run successfully
- Verify sessions are working (check PHP settings)

**Can't access admin dashboard:**
- Ensure you're logged in as admin
- Check database for user role: `SELECT * FROM users WHERE role='admin'`

### Support

For issues or questions, please contact your system administrator or refer to the project documentation.

---

## For Developers

### Local Development

1. Install XAMPP/WAMP/MAMP
2. Create database and import `setup.sql`
3. Copy `config.example.php` to `config.php`
4. Update database credentials
5. Set `ENVIRONMENT` to `'development'` and `DEBUG_MODE` to `true`
6. Access via `http://localhost/bachedersubs/`

### API Endpoints

All API endpoints return JSON responses.

**Authentication:**
- `POST /api/auth.php?action=login` - Login
- `POST /api/auth.php?action=register` - Register new substitute
- `GET /api/auth.php?action=logout` - Logout
- `GET /api/auth.php?action=check` - Check auth status

**Teachers:**
- `GET /api/teachers.php?action=list` - List all teachers
- `POST /api/teachers.php?action=create` - Create teacher (admin)
- `POST /api/teachers.php?action=update` - Update teacher (admin)
- `POST /api/teachers.php?action=delete` - Delete teacher (admin)

**Substitutes:**
- `GET /api/substitutes.php?action=list` - List substitutes (admin)
- `POST /api/substitutes.php?action=update_rate` - Update rate (admin)
- `GET /api/substitutes.php?action=profile` - Get profile
- `POST /api/substitutes.php?action=update_profile` - Update profile

**Time Entries:**
- `POST /api/time_entries.php?action=create` - Log hours
- `GET /api/time_entries.php?action=list` - List entries (with filters)
- `POST /api/time_entries.php?action=mark_paid` - Mark as paid (admin)
- `POST /api/time_entries.php?action=delete` - Delete entry
- `GET /api/time_entries.php?action=stats` - Get statistics
