Primary file for your real database (SRS / course dump):

  srs_course_export.sql   — paste full CREATE TABLE + INSERT here, then run:
                            ./scripts/apply_srs_database.sh
                            (see docs/SQL_NOTES.md for DB_* env vars)

Optional split:
  srs_schema.sql          — DDL only
  srs_sample_data.sql     — INSERT only

Project SQL overview: docs/SQL_NOTES.md

Student use cases: docs/STUDENT_USE_CASES.md
Google-Doc paste: docs/USE_CASES_GOOGLE_DOC.txt

Regenerate student use cases:
  python3 scripts/build_use_cases_from_vertical_csv.py
