ALTER TABLE auth_users
  MODIFY COLUMN role ENUM('admin','limited','viewer') NOT NULL DEFAULT 'admin';
