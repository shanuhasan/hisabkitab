# UdhaarHisab — Database Backup Guide

This guide covers backing up the UdhaarHisab multi-tenant MySQL database safely and reliably.

---

## 1. Automated Scheduled Backups

UdhaarHisab includes a built-in Artisan backup command:

```bash
php artisan udhaarhisab:backup {--retention=7}
```

### How It Works:
1. Connects to the active database connection.
2. Extracts table structures (`CREATE TABLE`) and all records (`INSERT INTO`).
3. Temporarily disables `FOREIGN_KEY_CHECKS` during export to guarantee clean table restoration without dependency errors.
4. Writes the timestamped `.sql` file to `storage/app/backups/backup-udhaarhisab-YYYY-MM-DD_HHmmss.sql`.
5. Automatically purges backups older than the retention threshold (default: 7 days).

### Scheduled Execution:
The scheduler runs this command automatically every morning at **02:00 AM** via `routes/console.php`:

```php
Schedule::command('udhaarhisab:backup --retention=7')->dailyAt('02:00');
```

---

## 2. Manual On-Demand Backups

Before running database migrations, major system upgrades, or package updates, always execute a manual backup:

```bash
# Using the built-in UdhaarHisab artisan command
php artisan udhaarhisab:backup

# Or using native mysqldump from terminal
mysqldump -u udhaarhisab_user -p udhaarhisab_production > /var/backups/udhaarhisab-manual-$(date +%Y%m%d_%H%M%S).sql
```

---

## 3. Offsite / S3 Cloud Backup Automation

To protect against local server hardware failures, sync local backups to an offsite AWS S3 bucket:

### Step 1: Install AWS CLI on your server
```bash
sudo apt install -y awscli
aws configure
```

### Step 2: Create a Sync Script (`/usr/local/bin/sync-udhaarhisab-backups.sh`)
```bash
#!/bin/bash
BACKUP_DIR="/var/www/udhaarhisab/storage/app/backups"
S3_BUCKET="s3://your-company-udhaarhisab-backups/database"

# Sync files to S3 bucket with server-side encryption
aws s3 sync "$BACKUP_DIR" "$S3_BUCKET" --sse AES256

echo "Backup sync to S3 completed at $(date)"
```

Make it executable and add to crontab at 02:30 AM:
```bash
chmod +x /usr/local/bin/sync-udhaarhisab-backups.sh
# Add to crontab:
# 30 2 * * * /usr/local/bin/sync-udhaarhisab-backups.sh >> /var/log/backup-s3.log 2>&1
```

---

## 4. Backup Integrity Verification

To verify that a backup file is valid and not corrupted:

```bash
# Check file size (should not be 0 bytes)
ls -lh storage/app/backups/

# Inspect the header and footer of the SQL file
head -n 15 storage/app/backups/backup-udhaarhisab-*.sql
tail -n 10 storage/app/backups/backup-udhaarhisab-*.sql

# Check for syntax errors without executing
mysql -u udhaarhisab_user -p --execute="SELECT 1;" udhaarhisab_production
```

