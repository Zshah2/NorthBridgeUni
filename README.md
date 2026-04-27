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
php scripts/seed_demo_registration.php

php scripts/seed_superadmin.php mainadmin Main@1234
php scripts/seed_limited_admin.php limitedadmin Limited@1234
php scripts/seed_staff.php
```

Optional: `APP_DEBUG=1` for verbose errors during development.

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
- Older checklists: [docs/PROFESSOR_TEST_CHECKLIST.md](docs/PROFESSOR_TEST_CHECKLIST.md) if present.
