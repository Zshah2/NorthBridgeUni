-- Track number of major changes per student (for change-limit enforcement).

CREATE TABLE IF NOT EXISTS student_major_change_stats (
  student_id BIGINT UNSIGNED NOT NULL,
  major_change_count INT NOT NULL DEFAULT 0,
  last_changed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (student_id),
  CONSTRAINT fk_major_change_stats_student FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

