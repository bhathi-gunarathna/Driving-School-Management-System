# Driving School Management System

Laravel application for managing driving school students, coaches, vehicle categories, payments, training, trials, financial reports, system settings, audit history, and in-app notifications.

## Technology

- PHP 8.2+
- Laravel 12, Fortify, Livewire 4
- Blade and Tailwind CSS 4 (Vite)
- MySQL for local/production use; the automated suite uses the configured testing database
- Spatie Laravel Permission

## Local setup (Windows / XAMPP)

1. Install PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `xml`, `ctype`, `tokenizer`, `bcmath`, and `zip`; install Composer, Node.js/npm, and MySQL.
2. Create a MySQL database and a least-privilege database user. Do not use production credentials locally.
3. Copy `.env.example` to `.env`. Set `APP_NAME`, `APP_URL`, `APP_TIMEZONE`, and `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local database. The committed example intentionally contains no credentials.
4. Install dependencies and generate a local key:

   ```powershell
   composer install
   Copy-Item .env.example .env -ErrorAction SilentlyContinue
   php artisan key:generate
   npm install
   ```

   If `.env` already exists, keep it and update it manually rather than replacing it.
5. Apply new migrations using `php artisan migrate` (this preserves existing records). Set up permissions and the optional initial Owner using the instructions below.
6. Link public logo storage and build assets:

   ```powershell
   php artisan storage:link
   npm run build
   ```

7. Start the local app using XAMPP Apache/MySQL or `php artisan serve`. During development use `npm run dev` in a second terminal.

## Permissions and initial access

Run `php artisan db:seed --class=RolePermissionSeeder` to create the Owner and Coach roles and synchronize the permission lists. This seeder does not create or reset user passwords. `OwnerUserSeeder` requires `OWNER_NAME`, `OWNER_EMAIL`, and `OWNER_PASSWORD` to be set in the local environment; it uses `firstOrCreate` and must be run only with values you control. Do not put real credentials in source control or documentation.

There are no committed default login credentials. Create the initial Owner through a controlled local provisioning process and secure the account with a unique password and configured MFA/passkey.

## Main modules

- Coach and student registration, profile, status, and assignment management.
- Vehicle categories with enrollment-time price/hour snapshots.
- Payment plans, installment schedules, receipt history, and financial analytics.
- Training assignment and session recording with progress calculations.
- Trial scheduling, participant assignment, result corrections, and retained history.
- School settings, immutable-in-app audit history, and role-relevant in-app notifications.

## Development commands

```powershell
php artisan serve
npm run dev
php artisan test
php artisan route:list
php artisan migrate:status
npm run build
```

The application scheduler sends due in-app notifications. For local testing, use `php artisan schedule:work`; notification delivery is database-only and does not send email or SMS.

## Testing

Run the full suite with `php artisan test`. Feature tests use `RefreshDatabase` and should use a separate test database (SQLite in-memory is configured by the test suite where available). Never point `RefreshDatabase` or test runs at a database containing user data.

## More documentation

- [Deployment guide](DEPLOYMENT.md)
- [System documentation](SYSTEM_DOCUMENTATION.md)
