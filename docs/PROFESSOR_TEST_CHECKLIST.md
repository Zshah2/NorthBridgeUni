# Professor / grader test checklist (CollegeWeb)

Complete setup **once** (same machine you will demo from):

```bash
export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=collegeweb DB_USER=root DB_PASS='yourpassword'
php scripts/migrate.php
php scripts/seed_admin.php admin YourSecurePass123
php scripts/import_all.php
php scripts/seed_demo_registration.php
php -S 127.0.0.1:8000 -t public public/router.php
```

Optional pre-flight:

```bash
php scripts/smoke_check.php http://127.0.0.1:8000
```

Set `APP_DEBUG=0` (or unset) when demonstrating so errors stay user-safe. Use `APP_DEBUG=1` only while developing.

---

## Expected data after seed

| Check | Expected |
|--------|-----------|
| Demo student | `student_id = 123123` (Alice Compton) exists |
| Demo term | `FA26` — Fall 2026 |
| Enrollments | At least **two** enrolled rows for `123123` (ENG101, HIS103) |
| Demo hold | One **active** `Bursar` hold on `123123` until you clear it |

---

## Flows to verify

### 1) Public site

1. Open `/` — homepage loads, no PHP warnings.
2. Open `/health` — JSON `{"ok":true,"database":true}` when MySQL is up; `503` and `"database":false` when MySQL is down (no white screen).

### 2) Authentication

1. Open `/login` — form loads.
2. Wrong password — stays on login with “Invalid email or password” (no 500).
3. Sign in as `admin` / `YourSecurePass123` — redirects to `/admin` (simple dashboard).
4. Logout — returns to `/login`; `/admin` while logged out redirects to `/login`.

### 3) Student lookup (database)

1. `/admin/students/search` — enter `123123` — profile, departments, **enrollments** (2 courses), **holds** (Bursar active).
2. Enter `999999999` — “No user found…” empty state (not an error page).

### 4) Master schedule

1. `/admin/schedule` — term `FA26` selected by default; table lists ENG101 and HIS103 sections (or empty state if seed not run).

### 5) Holds (database writes)

1. `/admin/holds` — open student `123123`.
2. **Clear** the demo Bursar hold — row shows **Cleared** with timestamp.
3. **Add** a new hold (e.g. Academic) — appears as **Active**.
4. Confirm on `/admin/students/show?student_id=123123` that holds match.

### 6) CSRF (security)

1. Submitting login or logout with a removed or wrong `csrf_token` should show a **403** message, not a silent failure.

---

## Failure paths (should stay controlled)

| Situation | Expected |
|-----------|-----------|
| MySQL stopped | `/health` reports failure; first DB page may show generic error page (not raw stack trace) when `APP_DEBUG=0`. |
| Wrong CSRF | 403 text: session/token message. |
| Not found route | App 404 view (“This page isn’t here”), current path, links to Home and Staff login. |

If anything **white-screens**, turns on a PHP **notice/warning**, or shows a **stack trace** during these steps with `APP_DEBUG=0`, treat that as a release blocker.
