ALTER TABLE auth_users
  ADD COLUMN display_name VARCHAR(100) NULL AFTER username;

UPDATE auth_users
SET display_name = 'Mohammad Shah'
WHERE username = 'mainadmin' AND (display_name IS NULL OR TRIM(display_name) = '');
