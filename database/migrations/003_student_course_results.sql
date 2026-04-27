CREATE TABLE IF NOT EXISTS student_course_results (
  student_id BIGINT UNSIGNED NOT NULL,
  course_id VARCHAR(30) NOT NULL,
  term_id BIGINT UNSIGNED NOT NULL,
  letter_grade VARCHAR(5) NOT NULL,
  grade_points DECIMAL(4,2) NOT NULL,
  credits_earned INT NOT NULL DEFAULT 0,
  PRIMARY KEY (student_id, course_id, term_id),
  KEY idx_scr_student (student_id),
  CONSTRAINT fk_scr_student FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_scr_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_scr_term FOREIGN KEY (term_id) REFERENCES terms(term_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
