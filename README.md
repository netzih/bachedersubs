# Bay Area Cheder - Substitute Tracking System

A simple, self-hosted web application for tracking substitute teachers and their hours.

## Features

### For Administrators
- **Dashboard Overview**: See total owed, unpaid entries, and active substitutes at a glance
- **Time Entry Management**: View, filter, and manage all time entries
  - Filter by date range, teacher, and payment status
  - Mark entries as paid/unpaid
  - Delete entries
- **Substitute Management**:
  - View all substitutes with contact information
  - Set hourly rates (hidden from substitutes)
  - Track amounts owed per substitute
- **Teacher Management**:
  - Add, edit, and remove teachers
  - View total hours used per teacher
- **Reports**:
  - Generate reports by date range
  - View hours breakdown by teacher
  - See which days substitutes were used

### For Substitutes
- **Easy Time Logging**:
  - Select teacher, date, and hours worked
  - Add optional notes
- **Payment Tracking**:
  - View all submitted entries
  - See which entries are paid vs. unpaid
  - Track total hours and amounts
- **Profile Management**:
  - Update contact information
  - Manage Zelle payment details

## Technology Stack

- **Backend**: PHP 8.x with PDO for secure database access
- **Database**: MySQL with optimized schema
- **Frontend**: Vanilla JavaScript (no framework dependencies)
- **Authentication**: Secure session-based auth with bcrypt password hashing
- **Timezone**: California (America/Los_Angeles) for all dates and times

## Quick Start

See [SETUP.md](SETUP.md) for detailed installation instructions.

### Basic Steps:
1. Create MySQL database in Plesk
2. Upload files to your server
3. Copy `config.example.php` to `config.php` and configure
4. Import `setup.sql` into your database
5. Access your domain and login

### Default Admin Credentials:
- Email: `admin@bayareacheder.com`
- Password: `admin123`
- **⚠️ Change immediately after first login!**

## Screenshots

### Login Page
Clean, simple authentication for both admins and substitutes.

### Admin Dashboard
- Real-time statistics
- Comprehensive time entry management
- Teacher and substitute management
- Custom reports

### Substitute Dashboard
- Quick hour logging
- Payment status tracking
- Personal profile management

## Security Features

- Bcrypt password hashing
- Session-based authentication
- Role-based access control (admin vs. substitute)
- SQL injection protection via PDO prepared statements
- XSS protection with input sanitization
- HTTPS recommended (easy with Plesk Let's Encrypt)

## System Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- SSL certificate (recommended)

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile responsive

## Backup & Maintenance

- Regular database backups via Plesk
- Keep PHP and MySQL updated
- Monitor error logs
- Test after updates

## License

Proprietary - Bay Area Cheder

## Support

For setup assistance or issues, see [SETUP.md](SETUP.md) troubleshooting section.

---

Built with ❤️ for Bay Area Cheder
