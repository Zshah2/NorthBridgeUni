#!/usr/bin/env bash
# Apply docs/sql/srs_course_export.sql to MySQL (your SRS / course schema + INSERT data).
# Use this when the SRS dump is your source of truth for the database.
#
# Usage (from project root):
#   export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=collegeweb DB_USER=root DB_PASS='yourpass'
#   ./scripts/apply_srs_database.sh
#
# Or one line:
#   DB_PASS=secret ./scripts/apply_srs_database.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SQL_FILE="${ROOT}/docs/sql/srs_course_export.sql"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-collegeweb}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "Missing: $SQL_FILE" >&2
  exit 1
fi

# Skip if file is only comments / empty (no CREATE or INSERT)
if ! grep -qE '^\s*(CREATE|INSERT|DROP|ALTER|SET)' "$SQL_FILE" 2>/dev/null; then
  echo "Nothing to apply: $SQL_FILE has no SQL statements yet." >&2
  echo "Paste your full SRS MySQL dump (CREATE TABLE, INSERT, …) into that file, then run again." >&2
  exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
  echo "mysql client not found. Install MySQL client or use TablePlus / MySQL Workbench to run $SQL_FILE manually." >&2
  exit 1
fi

echo "Applying SRS SQL to database ${DB_NAME} on ${DB_HOST}:${DB_PORT} ..."
export MYSQL_PWD="${DB_PASS}"
mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" --default-character-set=utf8mb4 "${DB_NAME}" < "${SQL_FILE}"
unset MYSQL_PWD
echo "Done."
