# Project log (reference)

Short, dated notes on **what changed** and **why**, so you (or a grader) can trace decisions without digging through chat history.

Add new entries at the **top** under the latest date.

---

## 2026-04-22 — PhpStorm :63342 and `/public/login`

**Problem:** JetBrains’ built-in server (`http://localhost:63342/...`) does not rewrite `/CollegWeb/public/login` to `index.php`, so PHP routing never runs.

**Change:** [app/lib/url.php](app/lib/url.php) — when `HTTP_HOST` contains `:63342` (or `APP_USE_INDEX_PHP_LINKS=1`), internal links use `/…/public/index.php/login` style via `app_front_controller()` + `app_use_index_php_in_links()`. Override off with `APP_USE_INDEX_PHP_LINKS=0`.

---

## 2026-04-22 — Login link / subpath routing

**Problem:** Navbar **Login** used `href="/login"`, which goes to the **server root**, not the app folder when the site runs under a subpath (e.g. `.../public/`). Same for CSS/JS `/assets/...` and form `action` attributes.

**Changes**

| Area | Detail |
|------|--------|
| [app/lib/url.php](app/lib/url.php) | New `app_base_path()`, `url()`, `nav_url()` (optional `APP_BASE_PATH` override). |
| [public/index.php](public/index.php) | Strip `dirname(SCRIPT_NAME)` from the request path so `/public/login` matches the `/login` route. |
| Views / layout | All internal `href` / `action` and asset URLs go through `url()` or `nav_url()`. |
| [app/lib/auth.php](app/lib/auth.php), [app/controllers.php](app/controllers.php), [app/lib/csrf.php](app/lib/csrf.php) | Redirects / CSRF recovery link use `url()`. |

---

## 2026-04-22 — Styled 404 (unknown routes)

**Goal:** Unknown URLs were always `404` with plain text `Not found.` — correct behavior, but not obvious what went wrong.

**Changes:** [public/index.php](public/index.php) now renders [app/views/pages/404.php](app/views/pages/404.php) (same layout as the site) with the path and links to `/` and `/login`. [docs/PROFESSOR_TEST_CHECKLIST.md](docs/PROFESSOR_TEST_CHECKLIST.md) updated accordingly.

---

## 2026-04-22 — Login / signup UX

**Goal:** Make staff vs student expectations obvious; improve browser tab titles; avoid a raw-text CSRF failure.

**Changes**

| Area | Detail |
|------|--------|
| [app/views/pages/admin/login.php](app/views/pages/admin/login.php) | Staff + Admin labels, clearer copy (signup vs `seed_admin.php`, staff tools not student portal), link back to `/`. |
| [app/views/pages/admin/signup.php](app/views/pages/admin/signup.php) | Same labeling; explains admin account + password rule; link home. |
| [app/views/partials/seo.php](app/views/partials/seo.php) | Optional `pageTitle` → document title `Page — Northbridge College`. |
| [app/controllers.php](app/controllers.php) | Pass `pageTitle` for login and signup renders. |
| [app/lib/csrf.php](app/lib/csrf.php) | CSRF failure returns a small styled HTML page with link to `/login`. |

---

## 2026-04-22 — Git: initial push to GitHub

**Goal:** Version the project on GitHub (`NorthBridge`, private remote).

**Changes**

| Area | Detail |
|------|--------|
| Repository | Initialized git in project root (replaced incomplete `.git` from an earlier partial init), first commit on `main`, remote `origin` → `https://github.com/Zshah2/NorthBridge.git`, pushed `main`. |

**Note:** Set `git config user.name` / `user.email` if you want commits attributed to your GitHub identity instead of the machine default.

---

## 2026-04-22 — Department emails: Northbridge branding

**Goal:** Remove the synthetic `@boolean.edu` domain from demo data so nothing in the repo reads “boolean” for school contact email.

**Changes**

| Area | Detail |
|------|--------|
| [Data/department.csv](../Data/department.csv) | Replaced `@boolean.edu` with `@northbridge.edu` on all department `email` values (local-part unchanged, e.g. `physics@northbridge.edu`). |
| [README.md](../README.md) | Noted that department emails use `@northbridge.edu` and that `php scripts/import_all.php` should be re-run after editing the CSV so `departments.email` in MySQL stays in sync. |

**Verification**

- Repo search for `boolean` / `boolean.edu` under project root: **no matches** (after CSV update).

**What you should do locally**

- If MySQL was populated **before** this change: run `php scripts/import_all.php` (with your `DB_*` env) so `departments` rows pick up the new emails.

**Related**

- Import path: `import_departments()` in [scripts/import_all.php](../scripts/import_all.php).
- Demo / grading flows: [PROFESSOR_TEST_CHECKLIST.md](PROFESSOR_TEST_CHECKLIST.md).

---

## How to use this file

- After any meaningful change (schema, import data, admin behavior), add **one short dated block**: goal, files touched, how to verify, optional “run this command”.
- Keep it factual; link to other docs instead of pasting long SQL here.
