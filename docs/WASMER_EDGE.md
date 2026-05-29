# Deploy CollegWeb on Wasmer Edge

This app runs on [Wasmer Edge](https://wasmer.io/products/edge) as PHP with managed MySQL. Secrets are **not** committed; configure them in the Wasmer dashboard or CLI.

## 1. Install tools

**Wasmer CLI** (macOS/Linux):

```bash
curl https://get.wasmer.io -sSfL | sh
```

Restart the terminal, then:

```bash
wasmer --version
wasmer login
```

**Composer** (for PHPMailer / 2FA):

```bash
cd /path/to/CollegWeb
composer install
```

`vendor/` must exist before `wasmer deploy` (it is gitignored but included in the package filesystem).

## 2. Configure the app

Edit [`app.yaml`](../app.yaml) if needed:

- `owner` — your Wasmer.io username
- `name` — app name (URL becomes `https://<name>-<owner>.wasmer.app`)

[`wasmer.toml`](../wasmer.toml) serves `public/` via PHP’s built-in server and [`public/router.php`](../public/router.php).

## 3. Deploy

```bash
cd /path/to/CollegWeb
composer install
wasmer deploy
```

Answer prompts (owner, app name, deploy now). Redeploy after code changes with the same command.

Useful commands:

```bash
wasmer app info
wasmer app list
wasmer app get <owner>/<app-name>
```

## 4. Managed MySQL

`app.yaml` enables Wasmer’s MySQL capability. After the first deploy:

```bash
wasmer app database list --with-password
```

Note host, port, database, username, and password. The app connects via **`MYSQL_*`** environment variables (set automatically on Edge when using the database capability in `app.yaml`):

- `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

Legacy `DB_*` names still work for local migration scripts if `MYSQL_*` are unset.

### Load schema and data (from your laptop)

Point your shell at the Edge database, then run migrations and seeds locally (do **not** run long imports on Edge itself):

```bash
export MYSQL_HOST=...
export MYSQL_PORT=...
export MYSQL_DATABASE=...
export MYSQL_USER=...
export MYSQL_PASSWORD=...

php scripts/migrate.php
php scripts/import_all.php
php scripts/seed_demo_registration.php
php scripts/seed_superadmin.php <username> '<password>' <email@example.com>
```

Use your own passwords; see `docs/LOGIN_CREDENTIALS.txt.example` (local copy only).

## 5. Email OTP (2FA) secrets

On Wasmer, set app **secrets** / environment variables (no `2fa_config.php` in the repo):

| Variable | Purpose |
|----------|---------|
| `SMTP_HOST` | SMTP server |
| `SMTP_PORT` | Usually `587` |
| `SMTP_ENCRYPTION` | `tls` or `ssl` |
| `SMTP_USERNAME` | SMTP user |
| `SMTP_PASSWORD` | SMTP password |
| `SMTP_FROM_EMAIL` | From address |
| `SMTP_FROM_NAME` | From display name |
| `OTP_EXPIRY_MINUTES` | Optional; default `5` |

Set each staff user’s email under **Admin → Accounts** (or via seed script).

## 6. One-time DB setup endpoint (optional)

If you prefer setting up schema from a browser (instead of running `php scripts/migrate.php` from your laptop), you can use the protected endpoint:

- `https://<name>-<owner>.wasmer.app/db_setup.php?secret=YOUR_SECRET`

It applies all `database/migrations/*.sql` in order, tracks applied filenames in `setup_migrations`, and ensures `otp_codes` exists.

Add this environment variable in Wasmer:

| Variable | Purpose |
|----------|---------|
| `SETUP_SECRET` | Secret query-string token required to run `db_setup.php` |

After the first successful run, **remove** `db_setup.php` or rotate `SETUP_SECRET`.

## 7. Smoke test

1. `https://<name>-<owner>.wasmer.app/` — public site  
2. `https://…/login.php` — password, then OTP email  
3. `https://…/verify_otp.php` — enter code  
4. `https://…/admin.php` — dashboard  

Between password and OTP, `admin.php` must **not** be accessible (pending 2FA only).

## 8. Local test with Wasmer (optional)

```bash
wasmer run .
curl http://127.0.0.1:8080/
```

For local DB, use `app/config/database.local.php` as usual.

## Troubleshooting

| Issue | Check |
|-------|--------|
| Deploy fails / huge upload | Run `composer install`; trim unused CSVs under `storage/import/` if needed |
| DB connection error | `wasmer app database list --with-password`; map values to `MYSQL_*` on the app; region must match `app.yaml` (`us-socal1`) |
| 2FA email not sent | SMTP secrets on Edge; `vendor/` present at deploy time |
| 404 on routes | `public/router.php` must be the router script in `wasmer.toml` |

References: [PHP on Wasmer Edge](https://docs.wasmer.io/edge/guides/php), [app.yaml configuration](https://docs.wasmer.io/edge/configuration/).
