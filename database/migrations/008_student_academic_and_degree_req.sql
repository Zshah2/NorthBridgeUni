-- Undergrad class year & enrollment intensity; major/minor on declarations; catalog for degree gaps

ALTER TABLE undergrad_students
  ADD COLUMN academic_year_level VARCHAR(32) NULL COMMENT 'e.g. Freshman, Sophomore' AFTER student_type,
  ADD COLUMN enrollment_intensity VARCHAR(32) NULL COMMENT 'Full-time, Part-time, Other' AFTER academic_year_level;

ALTER TABLE student_departments
  ADD COLUMN declaration_role ENUM('major', 'minor') NOT NULL DEFAULT 'major' AFTER dept_id;

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
