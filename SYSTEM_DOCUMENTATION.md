# System Documentation

## Modules

1. **Access and roles:** Fortify handles authentication; Spatie roles and permissions separate Owner administration from Coach work. Inactive accounts are rejected during credential authentication. Public registration is disabled.
2. **Coaches:** Owner-managed accounts, activation, profiles, and active coach-to-enrollment assignments.
3. **Students and categories:** Owner registration with private medical report storage and one or more category enrollments. Each enrollment preserves the agreed fee and required training hours from enrollment time.
4. **Payments:** A student has a payment plan and planned installments; only recorded `student_payments` receipts count as income. Receipt rows and plan balance updates are transactional. Financial reporting is Owner-only.
5. **Training:** Active coach assignments connect a Coach to a student's category enrollment. Coaches record dated sessions; progress is calculated from completed minutes against the enrollment's required hours.
6. **Trials:** Owners schedule trials and assign active student category enrollments. Results are recorded per enrollment; corrections append to `trial_result_histories` rather than erasing prior result changes.
7. **Financial reports:** Overview, date-filtered income summaries and transaction lists, outstanding plan balances, chart summaries, CSV export, and browser print support.
8. **System settings:** Owner-configurable school identity, logo, timezone, currency, date style, receipt prefix, and student-number prefix.
9. **Audit logs:** Read-only Owner audit page with actor snapshot, action/module, related model/key, changed fields, IP, and timestamp. Passwords, tokens, and medical report paths/content are excluded.
10. **Notifications:** In-app database notifications with deduplication for assignments, upcoming trials, incomplete training before a trial, result updates, and Owner-only payment follow-up.

## Roles and permissions

- **Owner:** Owner-only administrative routes; detailed access is additionally gated by permissions such as `students.view_all`, `payments.manage`, `training.manage`, `trials.manage`, `financial-reports.view`, `financial-reports.export`, `settings.manage`, and `audit-logs.view`.
- **Coach:** Assigned active student/category enrollments, training recording, relevant trials/results, and personal notifications. Coach queries are restricted to active assignments. Coaches are not given finance, settings, or audit permissions.
- `RolePermissionSeeder` creates/synchronizes the role permission sets. Review any local custom permission grants before running it in an existing environment because it synchronizes role permissions.

## Main data relationships

- `users` has Spatie role/permission pivots. Coach users are referenced by `training_coach_assignments`, `training_sessions`, and audit/user snapshot fields.
- `students` belongs to many `vehicle_categories` through `student_vehicle_categories`; the pivot stores agreed fee/training-hour snapshots, status, and has training assignments/sessions and trial category results.
- `students` has one `student_payment_plans` record; each plan has planned `student_payment_installments` and actual `student_payments` receipts.
- `trials` has `trial_participants`; each participant has one or more `trial_category_results`, which retain correction rows in `trial_result_histories`.
- `system_settings` stores school configuration; `audit_logs` are immutable through application UI; Laravel `notifications` belong polymorphically to a user; `notification_dispatches` enforces event deduplication per recipient.

## Workflows

### Student registration

Owner selects active vehicle categories. The app snapshots category fee and required hours into the enrollment pivot, generates a number using the configured prefix/year and the database ID, and creates a payment plan with its installment schedule. An optional advance payment creates an actual receipt and updates plan balances in one transaction. Medical reports remain on Laravel's private disk and are served by an Owner-authorized controller.

### Payments

Owners record actual receipt rows through `StudentPaymentService`. The service locks the plan, validates the amount against the current outstanding balance, records the receipt, updates paid/outstanding totals and payment status, then returns the receipt. Planned installments are not income. Financial reports sum receipt amounts only.

### Training

An Owner assigns an active Coach to an active student category enrollment. Coaches see assigned active enrollments and record training dates/durations. Training records remain in history; dashboard progress uses completed session minutes.

### Trials

Owners create a scheduled trial and assign active students' active category enrollments. Each selected enrollment has its own pending/pass/fail result. Authorized Owners or currently assigned Coaches record results on/after the trial date. A later correction appends previous/new values to result history.

### Reporting and audit

Owners filter actual receipts by date/method/student/receipt and outstanding plans by status/student. CSV exports use the same filters and require export permission. Audit history is read-only in the application and can be filtered by user, module, action, date, and text.

## Operational notes and limitations

- Run pending additive migrations before using Settings, Audit Logs, or Notifications.
- Run the permission seeder in the intended environment to attach the new audit permission to Owner; it synchronizes Owner and Coach permission sets.
- Notifications are in-app only. Configure the Laravel scheduler at one-minute cadence on hosting for the daily task to execute.
- The logo is public-facing and stored on the public disk; medical reports are private. Configure `storage:link` for logos and protect/encrypt backups.
- System timezone changes apply to HTTP requests through middleware. Configure `APP_TIMEZONE` as the initial fallback for CLI/scheduler before a school settings row exists.
