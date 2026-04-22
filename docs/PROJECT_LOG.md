# Project log (reference)

Short, dated notes on **what changed** and **why**, so you (or a grader) can trace decisions without digging through chat history.

Add new entries at the **top** under the latest date.

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
