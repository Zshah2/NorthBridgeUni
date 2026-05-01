-- Ensure catalog tables for degree gaps (admin UI) and prerequisite checks (registration).
-- Idempotent: safe if 004_course_prereqs.sql / 008_student_academic_and_degree_req.sql already ran.

CREATE TABLE IF NOT EXISTS course_prereqs (
  course_id VARCHAR(30) NOT NULL,
  prereq_course_id VARCHAR(30) NOT NULL,
  PRIMARY KEY (course_id, prereq_course_id),
  CONSTRAINT fk_cp_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cp_prereq FOREIGN KEY (prereq_course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS degree_requirement_courses (
  dept_id VARCHAR(10) NOT NULL,
  course_id VARCHAR(30) NOT NULL,
  requirement_kind ENUM('major', 'minor', 'both') NOT NULL DEFAULT 'both',
  PRIMARY KEY (dept_id, course_id, requirement_kind),
  CONSTRAINT fk_dreq_dept FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dreq_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
