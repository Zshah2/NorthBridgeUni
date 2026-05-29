ALTER TABLE auth_users
  ADD COLUMN email VARCHAR(255) NULL AFTER username;
