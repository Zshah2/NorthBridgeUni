## Northbridge College (CollegeWeb)

Plain PHP + modern frontend (Tailwind + small JS). Database (MySQL) and CSV import will be added in later steps.

### Local MySQL setup (for live data)
Set environment variables when running PHP, or configure your server env:
- `DB_HOST` (default `127.0.0.1`)
- `DB_PORT` (default `3306`)
- `DB_NAME` (default `collegeweb`)
- `DB_USER` (default `root`)
- `DB_PASS` (default empty)
- `APP_DEBUG` — set to `1` or `true` for detailed error pages during development; leave unset or `0` for demos (safe generic errors + stack traces in logs only).

Create the database (example):

```sql
CREATE DATABASE collegeweb CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

Then run:

```bash
php scripts/migrate.php
php scripts/seed_admin.php admin yourPassword
php scripts/import_all.php
php scripts/seed_demo_registration.php
```

The demo seed adds term **FA26**, two sections (**ENG101**, **HIS103**), enrollments for student **`123123`**, and a sample **Bursar** hold so admin schedule and holds pages are testable.

Department contact emails in `Data/department.csv` use the demo domain **`@northbridge.edu`**. If you edit that file, re-run `php scripts/import_all.php` so `departments.email` in MySQL matches (existing rows are updated on `dept_id` match).

### Grading / demo checklist

Step-by-step flows (including failure paths) are in [docs/PROFESSOR_TEST_CHECKLIST.md](docs/PROFESSOR_TEST_CHECKLIST.md).

### Smoke test (optional)

With the built-in server running:

```bash
php scripts/smoke_check.php http://127.0.0.1:8000
```

### Requirements
- PHP 8+ (7.4+ usually works, but 8+ recommended)

### Run locally (built-in PHP server)
From the project root:

```bash
php -S localhost:8000 -t public
```

Then open `http://localhost:8000`.

### PhpStorm
- Open this folder as a project.
- Set DocumentRoot to `public/` if using a local server config.

### Project log

Notable changes and why they happened: [docs/PROJECT_LOG.md](docs/PROJECT_LOG.md).

