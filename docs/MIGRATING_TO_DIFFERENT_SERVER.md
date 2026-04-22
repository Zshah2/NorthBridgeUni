# Migrating CollegeWeb to a different server

Use this when you move the project to another laptop, VPS, shared hosting, or classroom machine. Goal: same code, same database **content**, working `DB_*` configuration, and `public/` as the web root.

---

## 1. What to move

| Item | Notes |
|------|--------|
| **Application code** | Whole repo folder (e.g. `CollegWeb/`). |
| **MySQL data** | Export on the old server, import on the new one (see §2). Do not rely on copying raw MySQL data directories unless you know that host’s MySQL version and paths match. |
| **Secrets** | Recreate `DB_*` (and any future `.env`) on the new server; do not commit real passwords. |
| **Optional SQL dumps** | If you use `docs/sql/srs_course_export.sql` or a custom seed, keep that file in the repo or backup so you can rebuild the DB from scratch. |

Schema choices are documented in [SQL_NOTES.md](SQL_NOTES.md) (SRS dump vs `001_init` migration).

---

## 2. Database: export on old server

Replace names/passwords with yours.

```bash
mysqldump -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction --routines --triggers \
  "$DB_NAME" > collegeweb_backup.sql
```

- Omit `-p"$DB_PASS"` and type the password at the prompt if you prefer.
- If you only need structure + a small seed, you can dump specific tables instead; for grading, a **full** dump of the database you tested is safest.

---

## 3. Database: import on new server

1. Install MySQL or MariaDB and create an empty database (charset `utf8mb4`):

   ```sql
   CREATE DATABASE collegeweb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Import the dump:

   ```bash
   mysql -h 127.0.0.1 -u your_user -p collegeweb < collegeweb_backup.sql
   ```

3. Create a **dedicated DB user** for the app (recommended on a real server):

   ```sql
   CREATE USER 'collegeweb_app'@'localhost' IDENTIFIED BY 'strong_password_here';
   GRANT SELECT, INSERT, UPDATE, DELETE ON collegeweb.* TO 'collegeweb_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

   Adjust host (`%` vs `localhost`) if PHP runs on another host.

---

## 4. Application configuration on the new server

The app reads MySQL settings from the environment (see [app/config/database.php](../app/config/database.php)):

| Variable | Typical value |
|----------|----------------|
| `DB_HOST` | `127.0.0.1` or the DB server hostname |
| `DB_PORT` | `3306` |
| `DB_NAME` | e.g. `collegeweb` |
| `DB_USER` / `DB_PASS` | App user credentials |

How you set env vars depends on the host:

- **Apache:** `SetEnv` in vhost, or `mod_php` `php_admin_value` / FPM pool `env[...]`.
- **nginx + PHP-FPM:** `environment` in the pool file.
- **Built-in PHP server (dev):** `export DB_...=...` in the shell before `php -S`.
- **PhpStorm:** Run configuration environment variables.

---

## 5. Web server document root

The site entry point is [public/index.php](../public/index.php). Point the virtual host **document root** at the `public/` directory, not the repo root.

Example (conceptual): `DocumentRoot /var/www/collegeweb/public`

---

## 6. Fresh install instead of a dump (alternative)

If you prefer rebuilding from scripts instead of restoring `mysqldump`:

1. Create empty DB (same as §3).
2. From project root, follow [README.md](../README.md):

   ```bash
   export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=collegeweb DB_USER=... DB_PASS=...
   php scripts/migrate.php
   php scripts/seed_admin.php admin yourPassword
   php scripts/import_all.php
   ```

3. If your course uses SRS SQL as authority, use [SQL_NOTES.md](SQL_NOTES.md) and `./scripts/apply_srs_database.sh` instead of or in addition to `migrate.php`, per your chosen schema—**do not mix conflicting schemas** on one database without a plan.

---

## 7. Checklist before you call it done

- [ ] `DB_*` on new server matches the database you imported or built.
- [ ] Homepage loads over HTTP/S.
- [ ] Login and at least one DB-backed flow (e.g. student search) work on the new host.
- [ ] PHP version is 8+ (see [README.md](../README.md)).
- [ ] File permissions allow the web user to read the project; only `public/` must be world-readable as needed.
- [ ] TLS/HTTPS configured if this is production or a graded deployment.

---

## 8. Optional next-semester note

Keep a **known-good SQL dump** (or scripted seed) and a short **grader checklist** with the exact `student_id` and steps you verified. That makes the next migration mostly “import dump + set env + smoke test” instead of debugging from memory.
