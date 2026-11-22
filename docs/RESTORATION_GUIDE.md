# HisabKitab — Database Restoration Guide

This guide covers restoring the HisabKitab database from a backup file during disaster recovery, data corruption incidents, or migration to a new server.

---

## 1. Safety Rules Before Restoring

> [!CAUTION]
> Restoring a database completely overwrites all existing tables and data! Always follow these safety rules:
> 1. Put the application in maintenance mode: `php artisan down --secret="rescue-mode-pass"`
> 2. Create an immediate snapshot of the current state before restoring: `php artisan hisabkitab:backup`
> 3. Verify that all background queue workers are stopped: `sudo supervisorctl stop all`

---

## 2. Restoring Using the HisabKitab Artisan Command

HisabKitab includes an interactive restoration command:

```bash
# 1. Restore the most recent backup automatically
php artisan hisabkitab:restore

# 2. Or restore a specific backup file
php artisan hisabkitab:restore backup-hisabkitab-2026-11-22_035839.sql

# 3. For non-interactive automated scripts, pass --force
php artisan hisabkitab:restore backup-hisabkitab-2026-11-22_035839.sql --force
```

---

## 3. Restoring Using Native MySQL Terminal

If the application is unreachable or you are provisioning a fresh server:

```bash
# Decompress if backup is gzipped
# gunzip -c /var/backups/backup.sql.gz > /tmp/backup.sql

# Import database into MySQL
mysql -u hisabkitab_user -p hisabkitab_production < /var/www/hisabkitab/storage/app/backups/backup-hisabkitab-YYYY-MM-DD_HHmmss.sql
```

---

## 4. Post-Restoration Checklist

After database restoration completes, execute the following steps to return the platform to full production readiness:

```bash
# 1. Run any pending migrations (if restoring to a newer codebase)
php artisan migrate --force

# 2. Clear stale cache data
php artisan cache:clear
php artisan optimize:clear

# 3. Re-generate production caches
php artisan optimize

# 4. Restart background queue workers
sudo supervisorctl start all

# 5. Bring the application back online
php artisan up
```

### Verification Checks:
1. Log in to a merchant account and verify customer lists and ledger balances.
2. Log in to the Super Admin portal (`/admin`) and verify MRR and registered shops.
3. Check the application logs (`storage/logs/laravel.log`) for any foreign key or schema mismatch warnings.

