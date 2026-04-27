CREATE TABLE IF NOT EXISTS course_prereqs (
  course_id VARCHAR(30) NOT NULL,
  prereq_course_id VARCHAR(30) NOT NULL,
  PRIMARY KEY (course_id, prereq_course_id),
  CONSTRAINT fk_cp_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cp_prereq FOREIGN KEY (prereq_course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
