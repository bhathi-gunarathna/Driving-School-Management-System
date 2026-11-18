# Production Deployment Guide

This is a preparation guide only. No production deployment commands have been run from this development workspace. Rehearse the procedure on staging and take verified backups before production changes.

## Hosting requirements

- PHP 8.2 or newer within the application's Composer constraint (`^8.2`) and required extensions: PDO MySQL, mbstring, OpenSSL, fileinfo, cURL, XML, Ctype, Tokenizer, BCMath, and ZIP. Enable GD only if future image processing requires it; current upload validation/storage does not resize images.
- MySQL 8+ or a compatible supported MySQL release, InnoDB, `utf8mb4`.
- Apache with `mod_rewrite` or Nginx configured to route requests to Laravel's `public/` directory. Never expose the project root, `.env`, `storage`, or `vendor` directly.
- Writable `storage/` and `bootstrap/cache/` for the PHP-FPM/web-server user. Uploaded medical reports use the private local disk; the school logo uses the public disk.
- Node.js/npm only on the build system for the Vite asset build; production can serve the generated `public/build` assets.

## Environment configuration

Set values in the host's secret environment store or an untracked `.env` file:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Colombo
APP_KEY=base64:<generated-key>

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<database-name>
DB_USERNAME=<least-privilege-user>
DB_PASSWORD=<secret>

MAIL_MAILER=<approved-mail-driver-or-log>
MAIL_HOST=<mail-host-if-configured>
MAIL_PORT=<mail-port-if-configured>
MAIL_USERNAME=<mail-user-if-configured>
MAIL_PASSWORD=<secret-if-configured>
MAIL_FROM_ADDRESS=<verified-sender>
MAIL_FROM_NAME="${APP_NAME}"
```

Keep `APP_DEBUG=false`, restrict database grants to the application database, use HTTPS, and do not commit `.env`, keys, or uploaded documents. The app's owner-configurable timezone, currency, date style, receipt prefix, and student prefix are stored in System Settings after the first migration; `APP_TIMEZONE` is the pre-settings fallback.

## Release sequence

Use the deployment platform's release directory and rollback strategy. Run these commands from the new release, after its environment has been installed:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate   # only for a genuinely new application; never rotate a live key casually
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `migrate:fresh`, truncate tables, or reseed the Owner account on production. Provision the first Owner through an approved secure process. Verify the release on staging, confirm current migrations and backups, put the app in maintenance mode only if the chosen release procedure requires it, then switch traffic and restart long-running workers. Re-run cache commands after environment/configuration changes.

## Scheduler and queue

The application schedules `school:send-notifications` once daily at 08:00 in the configured application timezone. Configure the hosting cron to invoke Laravel's scheduler every minute:

```cron
* * * * * cd /srv/driving-school/current && php artisan schedule:run >> /dev/null 2>&1
```

Notifications use Laravel's database channel synchronously. No email/SMS integration or queue worker is required for this feature. If queued jobs are introduced later, configure and supervise a queue worker and restart it after releases.

## Authentication session revocation

Immediate per-user session revocation is supported when `SESSION_DRIVER=database` and the configured session table exists. Password resets, administrative Coach password changes, and Coach deactivation remove that user's database session rows while leaving other accounts' sessions untouched. Coach routes also check the active flag and log out deactivated Coaches on their next request.

Laravel's `file`, `cookie`, `array`, `memcached`, `redis`, and `dynamodb` session drivers do not expose a portable per-user delete operation through this application. With those drivers, password changes cannot immediately revoke all existing sessions; deactivated Coaches are denied access at their next Coach route request. Use database sessions in production when immediate revocation is required. Confirm the session table has been migrated before enabling this setting.

## Backups and recovery

1. Back up MySQL with a transaction-consistent dump and encrypt it at rest. Example (use a protected MySQL option file or secret manager rather than putting passwords in shell history):

   ```sh
   mysqldump --single-transaction --routines --triggers --databases <database-name> > <backup-file>.sql
   ```
2. Back up `storage/app/private/` (medical reports) and `storage/app/public/school/logo/` (school logos) as separate protected artifacts. Medical reports contain sensitive personal information: restrict access, encrypt backups, set retention, and test restores.
3. Store backups off-host; define and regularly test recovery point and recovery time targets.
4. To recover, restore uploaded files with correct ownership/permissions, restore the SQL dump into the intended database, install the matching release and `.env`, verify `APP_KEY` matches the backup's encrypted data, run non-destructive pending migrations only if required by that release, clear/rebuild caches, and validate health, login, file access, audit history, and payment/trial records before reopening traffic.

## Pre-release checks

- Confirm `APP_ENV`, `APP_DEBUG`, URL, timezone, database, mail placeholders, APP_KEY, HTTPS, and permissions.
- Review `php artisan migrate:status`, full tests, `php artisan route:list`, asset build, and a staging smoke test.
- Confirm scheduled task execution, notification behavior, private medical report access, receipt output, backups, and rollback procedure.
- No live deployment or production migration has been performed by this development task.
