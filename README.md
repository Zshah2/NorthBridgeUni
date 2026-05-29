## Northbridge College (CollegeWeb)

PHP + MySQL (PDO) + Tailwind. Public site uses the front controller (`public/index.php`); **staff** use **`public/login.php`** and the unified **`public/admin.php`** dashboard.

### Demo logins (after seed scripts)

| Role | Username | Password |
|------|-----------|----------|
| Full admin | `mainadmin` | `Main@1234` |
| Limited admin | `limitedadmin` | `Limited@1234` |
| Viewer | `staff` | `Staff@1234` |

### Database credentials

The app reads **`app/config/database.php`**, then merges **`app/config/database.local.php`** if it exists (copy from **`app/config/database.local.php.example`**). Environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` override the file when set.

If you see **“Cannot connect to MySQL”** on the login page, MySQL may be stopped **or** the username/password does not match (default is `root` with an empty password).

### Setup

```bash
# Create DB (example)
mysql -e "CREATE DATABASE collegeweb CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"

# Option A: env vars
export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=collegeweb DB_USER=… DB_PASS=…

# Option B: copy app/config/database.local.php.example → app/config/database.local.php and edit

php scripts/migrate.php
php scripts/import_all.php
# After import, automatically fills empty course descriptions and infers prerequisite links (same-prefix numbering).
# To skip that step: SKIP_CATALOG_ENRICH=1 php scripts/import_all.php

php scripts/seed_demo_registration.php
# Fills terms FA26/SP27/FA27, demo catalog rows (BI0101 chain), sections, and sample enrollments.
# SQL-only equivalent (after import): mysql … < database/seeds/prefill_course_demo.sql

php scripts/enrich_all_courses.php
# Optional manual re-run (same as post-import auto-enrich). Use --force to overwrite every description.

php scripts/seed_full_catalog.php
# One-shot: 5+ realistic courses per dept (BIO, CHE, COM, ECO, ENG, ENGL, HIS, PHI) with descriptions + prereq chains; then fills gaps on other imported courses.

php scripts/fix_duplicate_course_enrollments.php
# If two sections of the same course_id (e.g. BIO110) are enrolled/waitlisted for one student in one term, drops extras (keeps enrolled over waitlist, then earliest enrollment_id). Use --dry-run first.

php scripts/seed_superadmin.php mainadmin Main@1234
php scripts/seed_limited_admin.php limitedadmin Limited@1234
php scripts/seed_staff.php

composer install
cp app/config/2fa_config.php.example app/config/2fa_config.php
# Edit 2fa_config.php (SMTP). Set staff emails in Admin → Accounts for email OTP 2FA on login.
```

Optional: `APP_DEBUG=1` for verbose errors during development.

Staff sign-in uses **email OTP 2FA** after password (see [docs/LOGIN_CREDENTIALS.txt](docs/LOGIN_CREDENTIALS.txt)).

### URLs

- **Marketing / home:** `/` (via `public/index.php` + router)
- **Staff login:** `public/login.php` (direct file — works with PhpStorm built-in server)
- **Admin dashboard:** `public/admin.php` — People lookup, master schedule, directory, registration (add/drop with holds / conflicts / credits / prereqs / waitlist)

### Built-in server

```bash
php -S localhost:8000 -t public public/router.php
```

Then open `http://localhost:8000/` and `http://localhost:8000/login.php`.

### Requirements

- PHP 8+
- MySQL 8+ (or compatible)

### Project notes

- Department emails in `storage/import/department.csv` use `@northbridge.edu`; re-run `import_all.php` after edits.
- UI polish backlog: [docs/UI_FINE_TUNE_CHECKLIST.txt](docs/UI_FINE_TUNE_CHECKLIST.txt)
- Grader checklist: [docs/PROFESSOR_TEST_CHECKLIST.md](docs/PROFESSOR_TEST_CHECKLIST.md)
