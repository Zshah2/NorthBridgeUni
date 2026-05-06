-- Find students who currently violate "max 1 major + max 1 minor".
-- Run this before applying migration 016_student_departments_single_major_minor.sql.

SELECT
  sd.student_id,
  SUM(sd.declaration_role = 'major') AS major_cnt,
  SUM(sd.declaration_role = 'minor') AS minor_cnt
FROM student_departments sd
GROUP BY sd.student_id
HAVING major_cnt > 1 OR minor_cnt > 1
ORDER BY major_cnt DESC, minor_cnt DESC, sd.student_id;

-- Optional: list the exact declarations for those students
SELECT
  sd.student_id,
  sd.dept_id,
  d.dept_name,
  sd.declaration_role,
  sd.date_of_declaration
FROM student_departments sd
LEFT JOIN departments d ON d.dept_id = sd.dept_id
WHERE sd.student_id IN (
  SELECT x.student_id
  FROM (
    SELECT
      sd2.student_id,
      SUM(sd2.declaration_role = 'major') AS major_cnt,
      SUM(sd2.declaration_role = 'minor') AS minor_cnt
    FROM student_departments sd2
    GROUP BY sd2.student_id
    HAVING major_cnt > 1 OR minor_cnt > 1
  ) x
)
ORDER BY sd.student_id, sd.declaration_role, sd.date_of_declaration, sd.dept_id;

