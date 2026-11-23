# UdhaarHisab SaaS — Production Deployment Guide

This guide covers deploying **UdhaarHisab** to a production Linux server (Ubuntu 22.04 / 24.04 LTS) running **PHP 8.2+**, **Nginx**, and **MySQL 8.0+**.

---

## 1. Server Prerequisites & Stack Installation

Ensure the server has the following components installed:

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and required production extensions
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath \
    php8.2-curl php8.2-zip php8.2-intl php8.2-gd php8.2-sqlite3 nginx supervisor git unzip certbot python3-certbot-nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 2. Code Deployment & Directory Permissions

Clone or upload the repository to `/var/www/udhaarhisab`:

```bash
cd /var/www
git clone <YOUR_GIT_REPO_URL> udhaarhisab
cd /var/www/udhaarhisab

# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Set appropriate ownership and permissions
sudo chown -R www-data:www-data /var/www/udhaarhisab
sudo find /var/www/udhaarhisab -type f -exec chmod 644 {} \;
sudo find /var/www/udhaarhisab -type d -exec chmod 755 {} \;

# Grant write permissions to storage and bootstrap/cache
sudo chmod -R 775 /var/www/udhaarhisab/storage
sudo chmod -R 775 /var/www/udhaarhisab/bootstrap/cache

# Create public storage symlink
php artisan storage:link
```

---

## 3. Environment Configuration

```bash
# Copy production environment template
cp .env.production.example .env

# Generate a cryptographically secure 32-character application key
php artisan key:generate

# Edit production configuration
nano .env
```

Ensure the following production flags are strictly set in `.env`:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://udhaarhisab.in`
- `FORCE_HTTPS=true`
- `DB_DATABASE=udhaarhisab_production`
- `DB_USERNAME=your_secure_user`
- `DB_PASSWORD=your_secure_password`
- `RAZORPAY_KEY_ID=rzp_live_...`
- `RAZORPAY_KEY_SECRET=...`
- `RAZORPAY_WEBHOOK_SECRET=...`

---

## 4. Database Setup & Production Migrations

Log in to MySQL and create the database:

```sql
CREATE DATABASE udhaarhisab_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'udhaarhisab_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON udhaarhisab_production.* TO 'udhaarhisab_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Run production migrations and seeders:

```bash
# Run migrations with production confirmation flag
php artisan migrate --force

# Seed initial SaaS subscription plans and Super Admin account
php artisan db:seed --class=PlanSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
```

---

## 5. Performance Optimization & Laravel Caching

Execute Laravel's production caching commands to maximize request throughput:

```bash
# Cache configuration, routes, and compiled views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **Note**: Whenever you update `.env` or deploy code changes, always re-run `php artisan optimize:clear` followed by `php artisan optimize`.

---

## 6. Nginx Web Server Configuration

Create an Nginx configuration file:

```bash
sudo nano /etc/nginx/sites-available/udhaarhisab.conf
```

Add the following virtual host definition:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name udhaarhisab.in www.udhaarhisab.in;
    root /var/www/udhaarhisab/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and obtain a free SSL certificate via Let's Encrypt:

```bash
sudo ln -s /etc/nginx/sites-available/udhaarhisab.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Install SSL certificate
sudo certbot --nginx -d udhaarhisab.in -d www.udhaarhisab.in
```

---

## 7. Supervisor Configuration (Background Queue Workers)

Create a Supervisor worker configuration for processing background tasks (notifications, exports, webhook retries):

```bash
sudo nano /etc/supervisor/conf.d/udhaarhisab-worker.conf
```

Add:

```ini
[program:udhaarhisab-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/udhaarhisab/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/udhaarhisab/storage/logs/worker.log
stopwaitsecs=3600
```

Start and verify Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```

---

## 8. Scheduled Cron Configuration

Add Laravel's scheduler to the system crontab:

```bash
sudo crontab -e -u www-data
```

Add this single line:

```cron
* * * * * cd /var/www/udhaarhisab && php artisan schedule:run >> /dev/null 2>&1
```

This automates:
- Subscription expiry scans (`udhaarhisab:check-subscriptions`) daily at midnight.
- Automated daily database backups (`udhaarhisab:backup --retention=7`) daily at 02:00 AM.
- Expired password reset token purging (`auth:clear-resets`) daily.

---

## 9. Razorpay Webhook Configuration

In your **Razorpay Live Dashboard** (`https://dashboard.razorpay.com/#/app/webhooks`):
1. **Webhook URL**: `https://udhaarhisab.in/webhook/razorpay`
2. **Secret**: Enter the exact secret string defined in `RAZORPAY_WEBHOOK_SECRET` in `.env`.
3. **Active Events**:
   - `payment.captured`
   - `order.paid`
   - `payment.failed`

---

## 10. Final Pre-Launch Verification Checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false` confirmed.
- [ ] Application opens over HTTPS with valid SSL certificate (`https://udhaarhisab.in`).
- [ ] Storage directory has permissions `775` owned by `www-data:www-data`.
- [ ] Public storage symlink (`public/storage`) is connected.
- [ ] Registered a test shop account to verify SMS/Email and database insertion.
- [ ] Created customer, added Udhaar (Credit), and verified balance computation with `bcmath`.
- [ ] Verified Customer PDF statement generation and download.
- [ ] Verified WhatsApp click-to-chat modal opens with pre-filled reminder template.
- [ ] Verified Super Admin dashboard accessible at `/admin` for authorized admin user only.
- [ ] Verified regular merchant accounts receive `403 Forbidden` on `/admin` paths.
- [ ] Tested Razorpay checkout order generation and simulated signature verification.
- [ ] Confirmed daily database backup command creates valid SQL file in `storage/app/backups/`.

