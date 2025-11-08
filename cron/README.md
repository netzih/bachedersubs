# Cron Job Setup for Weekly Email Reports

This directory contains the cron job script for sending automated weekly email reports.

## Setup Instructions

### 1. Configure Email Settings

Before setting up the cron job, configure your SMTP settings:

1. Log in to the admin dashboard
2. Navigate to the "Reports" tab
3. Click "📧 Email Settings"
4. Fill in your SMTP configuration:
   - **SMTP Host**: `smtp.gmail.com` (for Gmail SMTP Relay)
   - **SMTP Port**: `587` (for TLS) or `465` (for SSL)
   - **SMTP Encryption**: Select `TLS` or `SSL`
   - **SMTP Username**: Your email or leave blank for IP authentication
   - **SMTP Password**: Your app password or leave blank for IP authentication
   - **Recipient Email**: Where to send the weekly reports
   - **Report Day**: Friday (or any day you prefer)
   - **Report Time**: 15:00 (3 PM Pacific Time)

5. Click "🔍 Test Connection" to verify SMTP works
6. Click "📨 Send Test Report" to send a test email
7. Click "💾 Save Settings"

### 2. Set Up Cron Job

#### Option A: Using Plesk (Recommended for Plesk hosting)

1. Log in to Plesk
2. Go to **Websites & Domains** > **Cron Jobs**
3. Click **Add Task**
4. Configure the task:
   - **Minute**: 0
   - **Hour**: 15 (3 PM Pacific - adjust based on your server's timezone)
   - **Day of Month**: *
   - **Month**: *
   - **Day of Week**: 5 (Friday)
   - **Command**:
     ```bash
     cd /var/www/vhosts/yourdomain.com/httpdocs && /usr/bin/php cron/send-weekly-report.php >> logs/weekly-report.log 2>&1
     ```
   - Replace `/var/www/vhosts/yourdomain.com/httpdocs` with your actual path

5. Click **OK**

#### Option B: Using crontab (Linux/Unix servers)

1. SSH into your server
2. Edit the crontab:
   ```bash
   crontab -e
   ```

3. Add this line:
   ```bash
   0 15 * * 5 cd /path/to/bachedersubs && /usr/bin/php cron/send-weekly-report.php >> logs/weekly-report.log 2>&1
   ```

4. **Important**: Adjust for timezone differences!
   - The script uses `America/Los_Angeles` (Pacific Time)
   - If your server is in a different timezone, adjust the hour accordingly
   - Example: If server is in UTC and you want 3 PM Pacific:
     - Pacific Time UTC offset: -7 (PDT) or -8 (PST)
     - 3 PM Pacific = 10 PM or 11 PM UTC
     - Use: `0 22 * * 5` (for PDT) or `0 23 * * 5` (for PST)

5. Save and exit

### 3. Create Logs Directory

Create a logs directory for cron output:

```bash
mkdir -p logs
chmod 755 logs
```

## Manual Testing

To manually test the cron job:

```bash
cd /path/to/bachedersubs
php cron/send-weekly-report.php
```

This will run the script immediately, regardless of day/time checks. To skip day/time validation for testing, comment out the day/time check lines in the script.

## Cron Schedule Explanation

The cron expression `0 15 * * 5` means:
- `0` - At minute 0
- `15` - At hour 15 (3 PM)
- `*` - Every day of the month
- `*` - Every month
- `5` - On Friday (0 = Sunday, 1 = Monday, ..., 5 = Friday, 6 = Saturday)

Combined: **Every Friday at 3:00 PM**

## Troubleshooting

### Check if Cron is Running

```bash
tail -f logs/weekly-report.log
```

### Common Issues

1. **Permission Denied**
   - Ensure the script is executable: `chmod +x cron/send-weekly-report.php`
   - Ensure logs directory is writable: `chmod 755 logs`

2. **PHP Not Found**
   - Find PHP path: `which php`
   - Update cron command with correct PHP path

3. **Database Connection Failed**
   - Verify `config.php` has correct database credentials
   - Test database connection manually

4. **SMTP Authentication Failed**
   - Test SMTP settings in the admin dashboard
   - Check SMTP logs in the dashboard for detailed errors
   - For Gmail: Use an App Password, not your regular password

5. **No Emails Received**
   - Check spam/junk folder
   - Verify recipient email in settings
   - Check cron logs: `tail -50 logs/weekly-report.log`
   - Ensure "Enable weekly email reports" is checked

### Email Not Sending at Scheduled Time

Check:
1. Cron is set up correctly: `crontab -l`
2. Server timezone matches expectations: `date`
3. Script time checks match your configuration
4. Logs show any errors: `tail -50 logs/weekly-report.log`

## Report Schedule

The weekly report covers:
- **Period**: Last full week (Monday through Sunday)
- **Sent**: Every Friday at 3 PM Pacific Time (configurable)
- **Contents**:
  - Summary statistics (total hours, total amount)
  - Breakdown by substitute
  - Detailed list of all time entries for the week

## Customization

To change the report schedule:
1. Update settings in admin dashboard (📧 Email Settings)
2. Adjust cron job to match new schedule
3. Script automatically respects the configured day/time

## Gmail SMTP Relay Setup

For IP authentication (no username/password required):

1. Go to Google Admin Console
2. Navigate to **Apps** > **Google Workspace** > **Gmail** > **Routing**
3. Add your server's IP to allowed senders
4. Configure SMTP relay settings
5. In the app, leave username and password blank
6. Use `smtp-relay.gmail.com` as the SMTP host
7. Port: 587 with TLS

For authentication with credentials:

1. Enable 2-Step Verification for your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password (not your regular password) in SMTP settings
4. Host: `smtp.gmail.com`, Port: `587`, Encryption: TLS
