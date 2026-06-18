-- One staff email per account (multiple NULLs allowed until email is set).
CREATE UNIQUE INDEX uq_auth_users_email ON auth_users (email);
