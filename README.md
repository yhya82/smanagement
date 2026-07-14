# School Management System

A Laravel 12 + Livewire 4 application covering admissions, enrollment, attendance, grading, rankings, promotions, teacher/timetable management, and a school calendar - built around a custom role/permission system rather than a fixed set of hardcoded roles.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+), Livewire 4 for interactive UI (no separate JS framework/API layer)
- **Frontend:** Tailwind CSS v4, Vite, Alpine.js (bundled with Livewire)
- **Database:** MySQL in this project's own dev environment; SQLite works too (see below) and is what the test suite always uses
- **Queue/Cache/Sessions:** database driver by default (see [Queue worker](#queue-worker) below - this matters, not just config trivia)

## Setup

```bash
composer setup
```

This single command (defined in `composer.json`) does everything: installs PHP/Node dependencies, copies `.env.example` to `.env`, generates an app key, runs migrations, **seeds the database**, and builds frontend assets. The seed step is not optional — without it there are no roles, no permissions, and no way to log in at all (see [First login](#first-login)).

If you'd rather run the steps yourself:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install && npm run build
```

### Database

`.env.example` defaults to SQLite (`DB_CONNECTION=sqlite`) for the fastest possible local start — create an empty file at the path in `DB_DATABASE` and migrations will use it directly. To use MySQL instead, set `DB_CONNECTION=mysql` and the usual `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`, then create the database before running `composer setup`. Both are exercised regularly in this codebase (MySQL in local dev, SQLite `:memory:` for the entire automated test suite — see `phpunit.xml`), so migrations are written to behave the same on both.

### First login

`AdminUserSeeder` creates the only account that can create every other account:

```
email: admin@example.com
password: ChangeMe123!
```

You'll be forced to change this password on first login (`must_change_password` is set on every seeded/onboarded account). Every other Administrator, Registrar, Teacher, and Student account is created *through the app itself* from there — there's no other seeded user.

### Running the dev servers

```bash
composer dev
```

Runs the Laravel dev server, Vite, a queue worker, and `php artisan pail` (log tailing) together. See [Queue worker](#queue-worker) for why the queue worker specifically matters here.

## Running tests

```bash
php artisan test
```

The whole suite runs against an in-memory SQLite database (`phpunit.xml`) with `array` cache/session drivers and `QUEUE_CONNECTION=sync` (queued jobs run inline during the test itself, not asynchronously), so it never touches real infrastructure. `tests/Feature` covers HTTP/Livewire/policy behavior end-to-end; `tests/Unit` covers business logic in isolation (ranking math, promotion rule matching, notification failure handling, cache invalidation) without going through a controller or component. CI (`.github/workflows/tests.yml`) runs the full suite on every push/PR to `master`.

## Queue worker

Four jobs depend on an actual queue worker process running continuously, not just the app being deployed. They're split across two queues so a slow bulk job can't delay the two time-sensitive daily ones behind it on a single worker:

- **`default` queue** — **`LockAttendanceRecordsJob`** (daily, scheduled) locks attendance records past their 7-day direct-edit window; **`PurgeRejectedApplicationDocumentsJob`** (daily, scheduled) purges a rejected applicant's documents/guardian PII 90 days after the decision. If these never run, attendance edits stay open past the window the SRS requires, and the retention policy silently never takes effect.
- **`bulk` queue** — **`ImportStudentsJob`** (dispatched whenever an admin submits a bulk student CSV import) and **`ComputeRankingsJob`** (dispatched whenever an admin clicks "Compute Rankings") — both can run long enough on a large school's data that, on a single worker, they'd otherwise delay whichever daily job was due to run next.

Run a worker with `php artisan queue:work --queue=default,bulk` (as `composer dev` already does locally) so `default` jobs are always drained first; a busy school should run a second, dedicated `--queue=bulk` worker so a long import can't delay attendance locking or document purging at all. If no worker is running, `ImportStudentsJob`/`ComputeRankingsJob` just sit queued forever with no feedback beyond the "queued" message the admin already saw.

All four jobs use `tries = 1` (no automatic retry — a retry of `ImportStudentsJob` would re-create students whose rows already succeeded the first time, and `ComputeRankingsJob`'s per-class isolation already means a retry offers no benefit). If one of the two on-demand jobs fails outright rather than partially, the admin who triggered it gets an in-app failure notification and must re-trigger it manually (re-upload the CSV, re-click "Compute Rankings") — there is no silent retry to wait for. All four log a completion summary and `failed()` calls, so if a worker *is* running you'll see evidence of it in the logs — but if no worker is running at all, nothing will tell you that on its own. In production, run `php artisan queue:work` (or Horizon) as a supervised, always-on process — a one-off deploy script or `composer setup` alone will never start one.

## Backups

`spatie/laravel-backup` (`config/backup.php`) dumps the database and zips the persistent uploaded content (`storage/app/private` — admission documents — and `storage/app/public` — avatars, school logo) into `storage/app/backups`, a disk deliberately kept *outside* both source directories so a backup never zips up previous backups along with itself. Scheduled nightly in `routes/console.php`: `backup:clean` at 01:00, `backup:run` at 01:30, `backup:monitor` at 02:00.

**Restoring:**
```bash
# Extract the dump from the zip (the exact filename varies by timestamp)
unzip storage/app/backups/Laravel/<timestamp>.zip -d /tmp/restore

# Restore the database
mysql -u <user> -p <database> < /tmp/restore/db-dumps_mysql-<database>.sql

# Restore uploaded files by copying the extracted app/private and app/public
# folders back into storage/app/
```
This exact sequence was tested against a real backup of this project's dev database, restored into a throwaway database, with row counts on `users`/`students`/`audit_logs` compared against the original and confirmed identical.

In production, point `MYSQLDUMP_BINARY_PATH` at wherever `mysqldump` actually lives if it isn't already on `PATH` (needed locally here because of the XAMPP install — see `.env`), set `BACKUP_NOTIFICATION_EMAIL` and real `MAIL_*` credentials so a failed backup is an actual alert and not just a line in the log (`MAIL_MAILER=log` by default — see [Notifications](#notifications)), and add the `backups` disk's destination (or a cloud disk like `s3`) to your actual off-site storage — a backup that lives on the same server as the database it's backing up doesn't survive that server failing.

## Monitoring

`/up` (Laravel's default health route) is wired to more than "did the app boot" via a `DiagnosingHealth` listener in `AppServiceProvider`: it also fails (HTTP 500) if the database is unreachable, if the `jobs` table backlog exceeds 500 rows (the cheapest available signal that no queue worker is actually draining it — see [Queue worker](#queue-worker)), or if `failed_jobs` exceeds 100. Point an uptime monitor at `/up` in production; nothing currently does this automatically.

## Security & audit logging

Two separate, both append-only, both admin-visible trails — kept separate because they answer different questions and have different shapes:

- **Audit Log** (`/admin/audit-log`) — "what changed on this business record and who changed it." Populated by the `Auditable` trait (`created`/`updated`/`deleted` on `AttendanceEditRequest`, `Promotion`, `StudentApplication`) and a few explicit `AuditLog::create()` calls (password changes, profile picture changes, document replacement). Searchable by action, user, and date range.
- **Security Events** (`/admin/security-events`) — "did someone try something they shouldn't." Failed logins, account lockouts (5 failed attempts in 60 seconds — detected independently of Fortify's own internal lockout mechanism, which this app's `throttle:login` route middleware intercepts ahead of; see the comment in `AppServiceProvider::registerSecurityEventLogging()`), and permission denials (both `$this->authorize()` and `abort_unless(..., 403)` — Laravel converts both into an `HttpException` before any custom exception renderer sees them, so the hook in `bootstrap/app.php` catches the common parent class and checks the status code rather than the original exception type). Account lockouts also notify every Administrator in-app immediately.

Both policies hardcode `update`/`delete` to `false` unconditionally — neither trail is editable by anyone, including Administrator (the same `update`/`delete` carve-out from `Gate::before` that protects role names and other invariants applies here too).

## Performance visibility & rate limiting

`AppServiceProvider::registerSlowQueryLogging()` logs any query over 200ms; `LogSlowRequests` middleware (in the `web` group) logs any full request over 1000ms — both via `Log::warning`, so a search through the logs answers "what's actually slow" instead of a guess.

Login is the only place Fortify rate-limits by default (5/minute per email+IP). The two heaviest on-demand actions — bulk CSV import (`Classes\Import::import()`) and ranking computation (`Rankings\Index::compute()`) — are separately rate-limited per-user (5/minute each) directly in the Livewire action, since each trigger queues a real `bulk`-queue job and a handful of rapid repeated clicks had no other guard against stacking several of them on top of each other.

## Roles & permissions

Not Spatie — a small custom system: `roles`, `permissions`, `role_permissions`, `user_roles` tables. `User::hasPermission('some.key')` / `hasRole('Administrator')` are the two checks everything else builds on. Administrator gets an automatic bypass for every ability *except* `update`/`delete` (see the comment in `app/Providers/AppServiceProvider.php`) — several policies hardcode invariants (the 7-day attendance edit lock, hard-delete protection, homeroom-teacher-only remarks) that must hold even for Administrator, so those two abilities always fall through to the real policy method instead of being waved through.

Four built-in roles ship via `RoleSeeder`: **Administrator**, **Registrar**, **Teacher**, **Student**. Additional custom roles (e.g. a Librarian with a narrower permission set) can be created through the admin UI — `Role::scopeAssignableViaUserManagement()` is what keeps Teacher/Student out of that flow, since those two are provisioned through their own dedicated onboarding processes instead.

## Domain overview

- **Admissions** (`AdmissionService`) — application intake by a Registrar, document upload, Administrator approval/rejection, which provisions the student's user account and class placement in one transaction.
- **Attendance** (`AttendanceService`) — teacher-marked per class/day, a 7-day direct-edit window enforced by `LockAttendanceRecordsJob`, then an edit-request/approval flow after that. Blocked entirely on days marked as a public holiday in the school calendar.
- **Grading & ranking** (`ResultService`, `RankingService`) — midterm and final are tracked and approved independently per subject (they happen at different points in the term); `RankingService` combines both into one term average per subject using the school's configured midterm/final weighting, or uses whichever one is approved so far as an interim score.
- **Promotions** (`PromotionService`) — evaluates a student's term ranking against `PromotionRule`s for their grade level, always creating a *pending* promotion regardless of how it was triggered — an Administrator approves every one.
- **Timetable** (`TimetableService`) — auto-generates a class's weekly schedule by round-robining its assigned, teacher-staffed subjects across empty slots (never overwriting an existing slot, so it's always safe to re-run), or an admin can set/clear individual slots by hand. Rejects any placement that would double-book a teacher into two classes at once.
- **School calendar** (`CalendarEvent`) — per-term holidays and events, managed from Settings. A holiday is enforced at the `AttendanceService` level, not just the UI.

## Notifications

All in-app (a custom `DatabaseChannel`, not Laravel's default polymorphic notifications table). There is no self-service password reset — every reset is admin-triggered (`Admin/Users/Show::resetPassword()`), which is why `MAIL_MAILER=log` in `.env.example` is a non-issue rather than an oversight: no code path in this app currently sends real email.
