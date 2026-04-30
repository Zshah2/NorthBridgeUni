-- Contact fields for users (students); faculty contact remains on faculty.* from 005 as well.
ALTER TABLE users
  ADD COLUMN email VARCHAR(255) NULL AFTER zip_code,
  ADD COLUMN phone_number VARCHAR(40) NULL AFTER email;
