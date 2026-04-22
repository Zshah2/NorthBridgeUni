-- Student holds (registration blocks) and admin audit trail

CREATE TABLE IF NOT EXISTS student_holds (
  hold_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  hold_type VARCHAR(80) NOT NULL,
  note VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cleared_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (hold_id),
  KEY idx_student_holds_student (student_id),
  KEY idx_student_holds_active (student_id, is_active),
  CONSTRAINT fk_student_holds_student FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_auth_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(64) NOT NULL,
  details VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_audit_created (created_at),
  CONSTRAINT fk_admin_audit_auth FOREIGN KEY (admin_auth_id) REFERENCES auth_users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
