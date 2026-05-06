-- Enforce at most one major + one minor per student across student_departments.
-- Uses generated columns + unique indexes (unique allows multiple NULLs).

ALTER TABLE student_departments
  ADD COLUMN major_student_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN declaration_role = 'major' THEN student_id ELSE NULL END) VIRTUAL,
  ADD COLUMN minor_student_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN declaration_role = 'minor' THEN student_id ELSE NULL END) VIRTUAL,
  ADD UNIQUE KEY uq_student_one_major (major_student_id),
  ADD UNIQUE KEY uq_student_one_minor (minor_student_id);

