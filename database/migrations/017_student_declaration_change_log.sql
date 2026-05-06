-- Audit log of major/minor declaration changes (admin/direct + future student requests).

CREATE TABLE IF NOT EXISTS student_declaration_change_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  change_kind ENUM('major','minor') NOT NULL,
  old_dept_id VARCHAR(10) NULL,
  new_dept_id VARCHAR(10) NULL,
  actor_kind ENUM('admin','student','system') NOT NULL DEFAULT 'admin',
  actor_auth_id BIGINT UNSIGNED NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_decl_change_student_created (student_id, created_at),
  KEY idx_decl_change_actor_auth (actor_auth_id),
  CONSTRAINT fk_decl_change_student FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_decl_change_actor_auth FOREIGN KEY (actor_auth_id) REFERENCES auth_users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

