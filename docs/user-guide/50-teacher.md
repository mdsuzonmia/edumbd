# 50 — Teacher

This guide is for **Teachers**. Routes are under `/teacher` and require the `teacher` role.

## Dashboard

**Dashboard** (`/teacher/dashboard`) shows your overview, classes, and quick actions for the
subjects you teach.

## Profile

- **Profile** (`/teacher/profile`) — view and update your own details.

## Entering marks

- **Manual input** (`/teacher/marks/input`) — pick the year, class, section, subject and exam,
  enter each student's marks per the mark distribution, and **Store**.
- **CSV input** (`/teacher/marks/csv_input`) — download/use a spreadsheet, fill in marks, upload,
  and **Store** the results.

> Marks are typically entered per the school's defined exam setup and mark distribution — see
> [80 — Examinations & Results](80-examinations.md) for the full workflow.

## Attendance

- **Input attendance** (`/teacher/attendance/input`) — select year/class/section/date and mark
  each student present/absent, then **Store**.
- **View attendance** (`/teacher/attendance`) — review previously entered attendance.

## Results

- **View results** (`/teacher/results`) — see results for your subject(s).
- **Add a remark** — as class/section teacher you can add a remark to students' results
  (`/teacher/results/remark`).

## Reference views

- **Routines** (`/teacher/routines`) — your class timetable.
- **Syllabus** (`/teacher/syllabus`) — the syllabus you need to cover.
- **Students** (`/teacher/students`) — the students in your classes.
- **Subjects** (`/teacher/subjects`) — the subjects assigned to you.

## Analytics

- **Performance analytics** (`/teacher/analytics/performance`) — visual analysis of student
  performance for your subjects.
- **Attendance analytics** (`/teacher/analytics/attendance`) — attendance trends for your classes.

## Promotion

- **Promote students** (`/teacher/students/promote`) — eligible students can be promoted to the
  next class (school policy governs who may promote).

## Common questions

**Why can't I see a subject?** You only see subjects/classes assigned to you by the school
owner/admin.

**Can I edit marks after saving?** Yes, unless the school has locked marks for that exam/
subject — see [80 — Examinations & Results](80-examinations.md).