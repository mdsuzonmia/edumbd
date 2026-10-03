# 80 — Examinations & Results

This is the deepest workflow in Edum. The **examination module** lets a school define its
grading system, set up exams, enter marks, generate and publish results, and produce reports,
transcripts and promotions.

> **Who can use it:** Most examination screens require the **school-owner** role. Teachers enter
> marks through their own area. Students and parents only view
> **published** results.

---

## 0. The workflow at a glance

```
1. Define grade systems  →  2. Set up exams  →  3. Define subjects
4. Configure mark distributions  →  5. Enter marks (manual/bulk) & lock
6. Generate results  →  7. Publish results  →  8. Remarks
9. Reports & transcripts  →  10. Promotions  →  11. Settings & templates
```

You can run some steps in parallel (e.g., subjects and exams), but results cannot be generated
until marks exist, and students/school/class data must already be in place.

---

## 1. Prerequisites

Before using the examination module, make sure the following already exist in your school:

- **Academic year, classes, sections** — see [30 — School Owner](30-school-owner.md).
- **Enrolled students** in the relevant classes.
- **School modules enabled** — the examination module must be enabled in School Settings.

---

## 2. Grade systems & grade rules

**Menu:** Examination → **Grade Systems** (`/examination/grade-systems`)

A *grade system* defines how a set of grades and grade points map to percentages, e.g.
`A+ = 80–100% (5.00)`, `A = 70–79% (4.00)`, … `F = 0–39% (0.00)`.

### Create a grade system
1. Open **Grade Systems** and click **Create**.
2. Give the system a name (e.g., "Standard 5-point").
3. **Save** it.

### Add grade rules to a grade system
1. From the grade system list, open **Rules** for the system (column action, or
   `/examination/grade-systems/rules/{system}`).
2. Click **Create** and enter, for each rule:
   - the **minimum/maximum percentage** (or similar range boundaries)
   - the **grade** letter (A+, A, B…)
   - the **grade point**.
3. **Store** the rule; repeat until every percentage range is covered.

You can **edit/trash/restore** grade rules, and the AJAX helper
`get-grade-systems-by-school` is used elsewhere to pick the right system. Empty trash when you
are sure a system is no longer needed.

> **Sanity check:** your rules should be **contiguous** (no gaps between ranges) so every student
> percentage maps to exactly one grade.

---

## 3. Exam setup

**Menu:** Examination → **Exam Setup** (`/examination/exam-setup`)

Exams represent graded assessments, e.g., "First Term Examination 2025" or "Mid-Term".

1. Open **Exam Setup** and click **Create**.
2. Enter the exam name and link it to a **school** and **academic year**.
   (The dropdowns are populated via the `get-years-by-school` /
   `get-exams-by-school-and-year` helpers so you only see relevant options.)
3. **Store** the exam. You can later **Edit**, **Update**, **Trash** or **Restore** it.

---

## 4. Subjects

**Menu:** Examination → **Subjects** (`/examination/subjects`)

Subjects are the units marks are recorded against. Create subjects for each class as needed
(main subjects and optional subjects are supported).

1. Open **Subjects** and click **Create**.
2. Enter the subject name, code and the class/year it belongs to.
3. **Store** the subject; **edit/update/trash/restore** as needed.

### Assign students to subjects
Not every student always takes every subject. Use **Assign Students**
(`/examination/subjects/assign-students`) to:

1. Pick the school, year, class and subject.
2. Select which students take the subject.
3. Save the assignment.

This list is used during marks entry and result generation to know which students appear for a
subject.
---

## 5. Mark distributions

**Menu:** Examination → **Mark Distributions** (`/examination/mark-distributions`)

A *mark distribution* defines how a subject's total marks are broken into components. For
example "English: Written = 70, Oral = 20, Project = 10 (total 100)". Grades/percentages are
computed from these components.

1. Open **Mark Distributions** and click **Create**.
2. Choose the **school**, **year**, **class**, **subject** and **exam**.
3. Add each **component** with its name and maximum marks.
4. **Store** the distribution; **edit/update/trash/restore** as needed.

Each subject + class + exam combination should have exactly **one** distribution before marks
are entered, or marks entry won't know which components to expect.

---

## 6. Entering marks

Teachers normally enter marks through their own screens, but the school owner can enter and manage them centrally under
**Marks** (`/examination/marks`).

### 6.1 Manual entry
1. Open **Marks** and click **Create** (or open an existing one to edit).
2. Select school → year → class → exam → subject. The student list is filtered to the relevant
   enrollments via the `getStudentsByFilter` helper.
3. Enter each component's marks for every student.
4. **Store**. Records can later be **updated**, **trashed** or **restored**.

### 6.2 Bulk import (CSV / JSON)
For large classes, use **Marks → Bulk Import** (`/examination/marks/bulk-import`):

1. Open **Bulk Import**.
2. Choose the academic data (school/year/class/subject/exam).
3. **Download the real-data template** — the system can generate a file pre-filled with the
   current data (`download-real-data-csv` / `download-real-data-json`), or a **blank sample**
   (`download-sample-data-csv` / `download-sample-data-json`) if you prefer to start fresh.
4. Fill in the marks in the spreadsheet and upload the file.
5. **Preview** the parsed data and verify it looks correct.
6. Click **Import** to write the marks.

### 6.3 Locking marks
Once marks are verified, you can **lock** them so nobody can quietly change them:

- **Lock** (`/examination/marks/lock`) — freeze marks for a subject/exam filter.
- **Unlock** (`/examination/marks/unlock`) — reopen marks for editing if a correction is needed.
- **Check lock status** — see whether a given subject/exam is locked.
- **Locked marks list** (`/examination/marks/locked-marks`) — review everything currently locked.

> **Recommended:** lock marks after verification and **before** generating results so the
> generation and any reports are based on a stable dataset.

---

## 7. Generate results

**Menu:** Examination → **Results → Generate** (`/examination/results/generate`)

Result generation computes each student's percentage, grade and grade point per subject (using
the mark distribution and the grade system's rules), and stores the result records.

1. Open **Results → Generate**.
2. Select school → year → class (→ section) → exam → subject as filters.
3. Click **Generate**. The screen confirms how many results were created.
4. If marks were corrected afterwards, click **Recalculate** to regenerate the affected results.

> Results should be regenerated/recalculated **after any mark change** so reports stay accurate.

---

## 8. Publish results

**Menu:** Examination → **Result Publish** (`/examination/result-publish`)

Publishing makes results visible to students and parents, and to public result links.

- **Publish** — reveal results for the selected year/class/exam/section filter.
- **Unpublish** — hide results again (e.g., if you must correct a batch of marks).
- **Check status** — verify whether a given filter is currently published.

> Nothing appears in the student/parent portals or public result links until you publish.

---

## 9. Aggregate results & remarks

**Menu:** Examination → **Aggregate** (`/examination/aggregate`)

Aggregation combines results across exams (e.g., term 1 + term 2 + final) into an overall or
yearly figure, including a calculated **total** and **GPA**.

1. Open **Aggregate** and click **Generate** after selecting the relevant filters.
2. Review the aggregated totals.

**Remarks** add written comments to results — either automatically derived or manually entered.

- **Aggregate remarks** (`/examination/remarks/aggregate`) — load the aggregated results
  (`aggregate/results`), set remarks, and **save** them (`aggregate/save`).
- **Exam remarks** (`/examination/remarks/exam`) — per-exam remarks: load results
  (`exam/results`) and **save** (`exam/save`).

---

## 10. Reports & transcripts

**Menu:** Examination → **Reports** (`/examination/reports`)

All reports are filter-driven (year/class/section/exam) and are generated against **published**
data, so publish results first. Many reports can be **downloaded as PDF**.

### Individual Result
- **Individual Result** (`/examination/reports/individual-result`) — pick a student; view
  **details** (per-subject marks, grades, GPA) and **download PDF**.
- Optionally **send the result by email** to the student.

### Aggregate Result
- **Aggregate Result** — a combined/overall view of results.

### Transcript
- **Transcript** (`/examination/reports/transcript`) — a student's academic record across terms/years.
- Open the student, view **details**, and **download PDF**.

### Tabulation Sheet
- **Tabulation Sheet** (`/examination/reports/tabulation-sheet`) — a class-wide table of every
  student's marks and grades across subjects.
- Pick filters and **generate the tabulation**, then **download PDF**.

### Merit List
- **Merit List** — ranks students by performance (e.g., top of class / GPA order).

### Class Statistics
- **Class Statistics** — summary statistics (averages, counts) for a class/exam.

### Subject Analysis
- **Subject Analysis** — per-subject performance breakdown (used to spot weak subjects).

### GPA Analysis
- **GPA Analysis** — distribution/analysis of grade points; exportable as **PDF**.

### Pass/Fail Report
- **Pass/Fail Report** (`/examination/reports/pass-fail-report`) — how many students passed or
  failed, selectable by year/exam/class/section; **generate** then **download PDF**.

---

## 11. Promotion

**Menu:** Examination → **Promotion** (`/examination/promotion`)

Promotion moves students from one class/year to the next, typically based on the current
year's results (this is the examination-module equivalent of the owner/teacher promotion
screens).

1. Open **Promotion** and select year/class/section/exam.
2. **Preview** the eligible students.
3. **Process** the promotion to move students up.
4. Review **promotion history** and open a batch's **details**.
5. If a batch was applied in error, **roll it back** (`promotion/rollback/{id}`).
---

## 12. Result settings & templates

### Result settings
**Menu:** Examination → **Settings** (`/examination/settings`)

Configure how results are computed and displayed for the whole school, for example:

- whether sections are used in the academic flow,
- pass/fail thresholds or aggregate weights,
- defaults shown on result sheets.

Make changes only when you understand their effect — settings apply to every subsequent
generation/report.

### Result templates
**Menu:** Examination → **Templates** (`/examination/templates`)

Templates control how result sheets and report cards look when printed/downloaded.

1. Open **Templates** to see existing templates.
2. Use **Builder** to create or modify a template layout (which sections/columns appear).
3. **Save** the template; **Delete** templates that are no longer used.

> If a PDF looks wrong, check the active template before troubleshooting data.

---

## 13. Student & public access to results

### Students (authenticated)
After results are **published**, students can view them under:

- `/examination/student/result` — list of available results; **View** opens one.
- `/examination/student/transcript` — list of transcripts; **View** and **Download PDF**.

Students can also see results from their normal student area (`/student/results`).

### Public links (no login)
Published results/transcripts can be shared with anyone via token links that need no login:

- **Public result** — `examination/result/{token}`
- **Public transcript** — `examination/transcript/{token}`

These are useful for sharing a report card with a parent who does not have a portal account.
Treat tokens like private links — anyone with the link can see that result while it is
published.

---

## 14. Tips, troubleshooting & best practices

**Order matters.** You cannot generate results before marks exist, and you cannot publish before
generating. Run the steps in order at least once on a small filter (one class) to validate your
setup before doing a full year.

**Lock before generating.** Lock marks → generate → publish. Unlock only when a verified
correction is necessary, then **recalculate** and re-publish.

**Keep grade rules contiguous.** Gaps in percentage ranges produce students with no grade.

**One distribution per subject/class/exam.** Missing or duplicate distributions are the most
common cause of "no marks possible" or wrong totals in marks entry and reports.

**Published = visible.** Changes after publishing do not reach students/parents until you
publish again (and generally until you recalculate).

**Emails.** Individual results can optionally be emailed from the report screen — confirm the
school's email settings are working first.

| Symptom | Likely cause / fix |
|---------|--------------------|
| A student has no marks | Subject not assigned to them, or no mark distribution for that subject/class/exam |
| A student has no grade | Percentage falls in a gap between grade rules — check contiguity |
| Result not visible to students | Not generated, or not published yet |
| Marks can't be changed | Marks are locked — unlock the subject/exam first |
| PDF looks wrong | Wrong/absent result template or unpublished data |
| Wrong totals | Mark distribution components don't add up, or data changed after generation — recalculate |