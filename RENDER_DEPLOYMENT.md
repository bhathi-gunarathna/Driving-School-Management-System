# Render deployment (Docker)

This repository is prepared for a Render **Docker Web Service** connected to a Render PostgreSQL database. The container listens on Render's assigned `PORT` (defaulting to `10000` for local Docker use), serves `public/` through Apache, builds Vite assets with Node, and installs Composer production dependencies.

## Render setup

1. Push this project to a Git provider and create a Render PostgreSQL database.
2. Create a **Web Service**, select the repository, choose **Docker**, and set the Dockerfile path to `./Dockerfile`. No separate build or start command is needed; the Docker image supplies both.
3. Add the environment variables below to the Web Service. Use the database's **internal** connection details when available. Do not copy credentials into this repository or a `.env` file committed to Git.
4. Deploy. The container entrypoint applies pending Laravel migrations with `php artisan migrate --force`, creates the public storage link, caches Laravel config/views, and starts Apache. It does not use `migrate:fresh` or reset database data. Review new migrations before deploying them because they run automatically at container startup.
5. Create the Owner account through the configured seeder/administrative process after the schema is ready. The `OwnerUserSeeder` requires `OWNER_NAME`, `OWNER_EMAIL`, and `OWNER_PASSWORD`; do not commit those values. The free web service has no SSH shell or one-off command facility, so plan a secure seeding method before first use (for example, a temporary protected deployment task or a locally run Artisan command with the production `DB_URL`; remove credentials from the local environment immediately afterward).

If you configure a Render health check path, use `/login` (the root path redirects there). To seed from a trusted machine that can reach the database's external URL, set `DB_CONNECTION=pgsql`, `DB_URL`, `APP_ENV=production`, and `APP_KEY` in that machine's environment after the first deployment has applied migrations, then run:

```sh
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=OwnerUserSeeder --force
```

Supply `OWNER_NAME`, `OWNER_EMAIL`, and `OWNER_PASSWORD` only to the second command's environment. The first seeder provisions roles/permissions; the second creates or updates the configured Owner role assignment. Prefer the internal database connection from a Render service if running this from another Render environment; a local machine generally needs the external connection URL.

## Required environment variables

| Variable | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | A generated Laravel key, such as the output of `php artisan key:generate --show`; store it as a Render secret. |
| `APP_URL` | The deployed `https://<service>.onrender.com` URL (or your domain). |
| `APP_TIMEZONE` | `Asia/Colombo` (or the school's configured timezone). |
| `LOG_CHANNEL` | `stderr` so application logs appear in Render logs. |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Render PostgreSQL **internal database URL**. Keep it secret. Alternatively provide `DB_HOST`, `DB_PORT` (`5432`), `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. |
| `DB_SSLMODE` | `prefer` for internal Render connections; use the mode required by the selected connection endpoint. |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` (existing application paths explicitly use `local` or `public`). |

Set `OWNER_NAME`, `OWNER_EMAIL`, and `OWNER_PASSWORD` only if using `OwnerUserSeeder`. Do not put these or `APP_KEY`/database credentials in source control. Render provides `PORT` to the container; do not hardcode it in the dashboard.

## Local Docker commands

Build:

```sh
docker build -t driving-school-management .
```

Run (supply a real PostgreSQL URL and a generated key locally):

```sh
docker run --rm -p 10000:10000 \
  -e APP_ENV=production -e APP_DEBUG=false -e APP_KEY='base64:...' \
  -e APP_URL=http://localhost:10000 -e DB_CONNECTION=pgsql \
  -e DB_URL='postgresql://user:password@host:5432/database' \
  -e LOG_CHANNEL=stderr \
  driving-school-management
```

For direct manual schema setup outside Render, the migration command is `php artisan migrate --force`. The container runs that command at startup against the configured database.

## PostgreSQL audit notes

- Laravel's migrations use standard IDs, foreign keys, indexes, unique constraints, dates, decimals, JSON, and text fields. PostgreSQL supports these definitions; Laravel's unsigned integer schema declarations become PostgreSQL integer types (PostgreSQL does not have unsigned integers), so validation remains the application-level range guard.
- The app's monthly income queries used MySQL `DATE_FORMAT()` for every non-SQLite connection. They now use PostgreSQL `TO_CHAR(..., 'YYYY-MM')` for PostgreSQL and retain SQLite/MySQL expressions. Daily grouping uses `DATE(payment_date)`, supported by PostgreSQL.
- Notification deduplication uses Laravel's `insertOrIgnore`, which compiles to PostgreSQL conflict-ignore behavior against the unique dispatch key.
- Money is stored as `DECIMAL` and calculations use integer cents in PHP. Receipt and student numbers use generated row IDs and unique columns, avoiding MySQL-only sequences/functions.
- Spatie permission migrations use standard pivot tables, bigint keys, indexes, and unique constraints compatible with PostgreSQL. No MySQL-specific migration SQL was found.
- PostgreSQL `LIKE` comparisons can be case-sensitive where local MySQL collation may have made searches case-insensitive. Verify the desired search behavior after migration if case-insensitive matching is required.

## Uploaded files and scheduled notifications

- Student medical report images use Laravel's private `local` disk (`storage/app/private`); authorized access is streamed through the app controller.
- School logo uploads use the `public` local disk (`storage/app/public`) and the `public/storage` symlink.
- Render Free Web Services have an ephemeral filesystem. Both file types can disappear on restart, spin-down, or redeploy. `FILESYSTEM_DISK=local` does not make them persistent. Use object storage (for example S3-compatible storage) before storing real student documents or logos in production.
- The app schedules `school:send-notifications` every minute and only sends at 08:00 school time. The container starts `schedule:work` alongside Apache. Render Free Web Services spin down after 15 idle minutes, so scheduled work pauses while the service is asleep and notification timing is not guaranteed. Render Cron Jobs are a separate billed service (minimum $1/month per current Render documentation); for reliable scheduling, use a cron job that runs `php artisan school:send-notifications` at the required cadence, or a suitable paid always-on scheduler.
- Render Free Postgres is limited to 1 GB and expires 30 days after creation under Render's current free-tier terms. It is suitable for evaluation, not durable production records; arrange a paid database and backups before entering real student/payment data.

## Free-tier behavior

Render's free web instance sleeps after 15 minutes without inbound traffic and may take about a minute to wake. It also has an ephemeral filesystem. This can cause cold starts and temporary loss of uploaded files/logs. The free PostgreSQL database's 30-day expiration and lack of durable file storage are material limits for a school management system.
