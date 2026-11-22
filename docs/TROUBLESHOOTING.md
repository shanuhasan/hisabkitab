# HisabKitab — Production Troubleshooting Guide

This guide helps resolve common operational and runtime issues in production.

---

## 1. HTTP 500 Internal Server Error

### Diagnosis:
Check the latest Laravel error logs:
```bash
tail -n 100 /var/www/hisabkitab/storage/logs/laravel.log
```
Check Nginx error logs:
```bash
sudo tail -n 50 /var/log/nginx/error.log
```

### Common Causes & Fixes:
1. **Missing APP_KEY**:
   - Error: `No application encryption key has been specified.`
   - Fix: `php artisan key:generate` followed by `php artisan config:cache`.
2. **Missing PHP Extensions**:
   - Error: `Call to undefined function bcadd()` or `NumberFormatter::format()`.
   - Fix: Install missing extensions: `sudo apt install php8.2-bcmath php8.2-intl` and reload PHP-FPM: `sudo systemctl reload php8.2-fpm`.

---

## 2. Storage & Cache Permission Errors

### Diagnosis:
- Error: `The stream or file "/var/www/hisabkitab/storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied`

### Fix:
Re-apply ownership and permissions to `storage` and `bootstrap/cache`:
```bash
sudo chown -R www-data:www-data /var/www/hisabkitab/storage /var/www/hisabkitab/bootstrap/cache
sudo chmod -R 775 /var/www/hisabkitab/storage /var/www/hisabkitab/bootstrap/cache
```

---

## 3. Database Connection Errors

### Diagnosis:
- Error: `SQLSTATE[HY000] [2002] Connection refused` or `Access denied for user 'hisabkitab_user'@'localhost'`

### Fix:
1. Verify MySQL service is active:
   ```bash
   sudo systemctl status mysql
   ```
2. Test connection with the credentials specified in `.env`:
   ```bash
   mysql -u your_user -p your_database
   ```
3. If using `localhost` vs `127.0.0.1`, ensure `DB_HOST=127.0.0.1` in `.env` to avoid Unix domain socket path issues.
4. After updating `.env`, clear the configuration cache:
   ```bash
   php artisan config:cache
   ```

---

## 4. Razorpay Webhook Signature Mismatch

### Diagnosis:
- Error: Webhook response returns HTTP 400 with message `Invalid webhook signature`.

### Fix:
1. Ensure the secret configured in Razorpay Dashboard (`https://dashboard.razorpay.com/#/app/webhooks`) exactly matches `RAZORPAY_WEBHOOK_SECRET` in `.env`.
2. Ensure you have run `php artisan config:cache` after updating `.env`.
3. Check `SubscriptionPayment` table for `idempotency_key` to verify if the event was already processed previously.

---

## 5. PDF Generation & Memory Issues

### Diagnosis:
- PDF statement or receipt generation times out or returns white screen / memory error.

### Fix:
1. Increase PHP memory limit in `/etc/php/8.2/fpm/php.ini`:
   ```ini
   memory_limit = 256M
   max_execution_time = 60
   ```
2. Ensure temporary directory write permissions for DomPDF:
   ```bash
   sudo chmod -R 775 /var/www/hisabkitab/storage/framework/cache
   ```
3. Reload PHP-FPM:
   ```bash
   sudo systemctl reload php8.2-fpm
   ```

---

## 6. Rate Limiting Blocks (HTTP 429)

### Diagnosis:
- Merchant receives: `Too many password reset attempts` or `Too many reminder requests sent`.

### Fix:
1. Rate limiters automatically reset after 60 seconds.
2. In emergency testing environments, clear rate limiters via Tinker:
   ```bash
   php artisan cache:clear
   ```
3. Ensure Nginx passes the real client IP address (`X-Forwarded-For` / `X-Real-IP`) if using Cloudflare or a load balancer.

---

## 7. Background Queue Worker Issues

### Diagnosis:
- Jobs are not processing or status remains `pending`.

### Fix:
1. Check failed jobs table:
   ```bash
   php artisan queue:failed
   ```
2. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```
3. Check Supervisor status:
   ```bash
   sudo supervisorctl status
   sudo supervisorctl restart all
   ```
4. Check worker log:
   ```bash
   tail -n 50 /var/www/hisabkitab/storage/logs/worker.log
   ```

---

## 8. Configuration Changes Not Taking Effect

### Diagnosis:
- Modifying `.env` does not alter application behavior.

### Fix:
Laravel caches configuration in `bootstrap/cache/config.php`. Always run:
```bash
php artisan optimize:clear
php artisan optimize
```

