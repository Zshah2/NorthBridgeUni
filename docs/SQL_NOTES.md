# CollegeWeb — SQL notes (project database)

Student **use cases** live in **`docs/STUDENT_USE_CASES.md`** and **`docs/USE_CASES_GOOGLE_DOC.txt`** (generated from the vertical CSV).

---

## Recommended: SRS / course SQL as your real database (build the website on top)

Your class **CREATE TABLE + INSERT** dump should be the database you use so you can focus on the site, not reinventing schema.

### Steps

1. **Create an empty database** (name it what you use in `.env` / env vars), e.g.:

   ```sql
   CREATE DATABASE collegeweb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Paste your full SRS MySQL script** into  
   **[docs/database/sql/srs_course_export.sql](database/sql/srs_course_export.sql)**  
   (everything: tables + sample data your professor gave you).

3. **Apply it** (from project root, with the same `DB_*` values as [app/config/database.php](../app/config/database.php)):

   ```bash
   export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=collegeweb DB_USER=root DB_PASS='yourpassword'
   ./scripts/apply_srs_database.sh
   ```

   Requires the **`mysql` CLI** on your PATH (comes with MySQL / MariaDB installs).  
   If you prefer a GUI, run the same file in TablePlus / MySQL Workbench / PhpStorm database tool.

4. **Website code** — the PHP app was written against **`database/migrations/001_init.sql`** (table names like `users`, `auth_users`).  
   After your SRS is loaded, we **align** PHP/SQL to your SRS table/column names (e.g. `user` vs `users`) in a follow-up step. Until then, admin pages may not match SRS tables until that mapping is done.

---

## Alternative: CollegeWeb-only schema (no SRS file yet)

- **Files:** [database/migrations/001_init.sql](../database/migrations/001_init.sql) (core) and [002_holds_audit.sql](../database/migrations/002_holds_audit.sql) (`student_holds`, `admin_audit_log`)
- **Apply:** `php scripts/migrate.php` (runs every `*.sql` in that folder in name order)
- Good for a quick prototype using our CSV import scripts; **different** from full SRS naming.

Do **not** mix both on the same database if tables would conflict — use **either** SRS dump **or** `001_init` for a fresh DB, unless you know how to merge them.

---

## CSV data (separate from SRS SQL)

- **Folder:** [storage/import/](../storage/import/)
- **Import:** `php scripts/import_all.php` — only applies to the **CollegeWeb** schema from `001_init`, not automatically to arbitrary SRS tables.

---

## Files in `docs/database/sql/`

| File | Role |
|------|------|
| [srs_course_export.sql](database/sql/srs_course_export.sql) | **Your** SRS dump — primary DB when filled |
| [README.txt](database/sql/README.txt) | Short index |
| `srs_schema.sql` / `srs_sample_data.sql` | Optional split of the same content |

---

## Aligning SRS with the PHP app (next dev step)

Once `srs_course_export.sql` is applied, list differences vs `001_init.sql` (table names, PKs) and update queries / thin “repository” layer so login, student lookup, etc. read/write your SRS tables.
