# 85 - Exam Seat Plans and Admit Cards

This guide explains how a school owner prepares exam rooms, allocates students to seats,
prints seat-plan reports, generates admit cards, and verifies an admit card by QR code.

> **Who can use it:** The management screens require the **school-owner** role and the
> corresponding permissions. Students can view only their own active admit cards.
> Public QR verification does not require login.

---

## 1. Recommended workflow

Follow this order for each examination:

1. Confirm the academic year, exam, classes, sections, and active student enrollments.
2. Create and enable the required **Exam Rooms**.
3. Generate an **Exam Seat Plan** and review its allocations.
4. Make any manual seat changes, then lock the plan.
5. Print room lists, door notices, visual plans, or student seat slips.
6. Generate **Exam Admit Cards** using the same school, session, and exam.
7. Review, print, or download the admit cards.
8. Test at least one QR verification link before distribution.

Seat plans should normally be completed before admit cards. Admit cards can display room and
seat information from a saved seat plan, and generation can be restricted to students who have
an allocation.

---

## 2. Prerequisites

Before starting, make sure the following records exist and are active:

- School and academic session
- Examination linked to that session
- Classes and, where applicable, sections
- Active students with active enrollments in the selected session
- Shifts and departments, if the school uses them
- Enabled exam rooms with valid row, column, and capacity values

An exam room's capacity cannot exceed its row-by-column layout. For example, a room with 5 rows
and 6 columns can contain no more than 30 usable seats.

---

## 3. Exam Rooms

**Menu:** Examination -> **Exam Rooms**  
**URL:** `/examination/exam-rooms`

Create the physical rooms that can be selected during seat-plan generation. Each enabled room
should have a recognizable name/number, row count, column count, and capacity.

Room details are copied into a seat plan when it is generated. This snapshot keeps an existing
plan stable if the room record is renamed or changed later.

---

## 4. Exam Seat Plans

**Menu:** Examination -> **Seat Plan**  
**URL:** `/examination/seat-plans`

The list page supports text, school, and status filters. It shows the exam, allocation method,
candidate/room counts, current status, and available actions.

### 4.1 Generate a seat plan

1. Click `New Seat Plan`.
2. Select the school, session, and examination.
3. Enter a title and optional examination date.
4. Optionally restrict students by shift or department.
5. Select one or more classes and, if required, sections.
6. Click `Load Students`, then include or exclude individual students.
7. Select one or more enabled exam rooms.
8. Confirm that available seats are equal to or greater than selected students.
9. Choose an allocation method and seat-number format.
10. Click `Preview Allocation` to review the result.
11. Click `Generate & Save`.

Saving is transactional: if the plan, room snapshots, or allocations cannot all be saved, the
operation is rolled back instead of retaining a partial plan.

### 4.2 Allocation methods

| Method | Behavior |
|--------|----------|
| Sequential | Orders students by class, section, natural roll order, name, and student ID. |
| Mixed Class | Alternates class/section groups where possible so adjacent allocations are less likely to come from the same group. |
| Random | Produces a seeded, reproducible randomized order for the generated plan. |

Seats are filled room by room in row-major order. Numeric seat numbers appear as `1`, `2`, `3`,
and so on. Alpha-numeric numbers appear as `A01`, `A02`, `A03`, and so on.

### 4.3 Review and manually adjust a plan

Open `View` for a plan to see allocations grouped by room. While the plan is unlocked and the
user has edit permission, the following actions are available:

- **Move Student:** move one allocation to an empty room/seat position.
- **Swap Two Students:** exchange the positions of two allocated students.
- **Remove:** remove a student from the plan.
- **Add Unallocated Student:** add an eligible, currently unallocated student to an empty seat.
- **Regenerate Plan:** replace all current allocations using the selected allocation method.

Regeneration replaces manual seat changes. It is blocked if the plan is empty, locked, or one
of its allocated students is no longer active/available.

### 4.4 Lock and unlock

Lock the plan after the allocations have been checked. A locked plan cannot be moved, swapped,
regenerated, added to, or removed from. This protects the room and seat data used for printing
and admit cards.

Unlock only when an authorized correction is required. After making corrections, review and
lock the plan again.

### 4.5 Print and PDF reports

The plan view provides these reports:

- **Visual Seat Plan**
- **Room Student List**
- **Class-wise Seat List**
- **Room Door Notice**
- **Student Seat Slips**

Each report can be previewed/printed or downloaded as PDF. Seat slips can be filtered by room
and by selected students; leaving every student unchecked includes all matching allocations.

---

## 5. Exam Admit Cards

**Menu:** Examination -> **Admit Card**  
**Management URL:** `/examination/admit-cards`

The list page shows each school/exam/session configuration and its active card count. From the
Actions column, an authorized user can view cards, edit settings, print, or download PDF.

### 5.1 Generate admit cards

1. Click `Generate Admit Cards`.
2. Select the school, session, exam, classes, and optional sections.
3. Enter the card title and instructions.
4. Choose which fields should appear on the document.
5. Optionally enable `Generate only for students with a saved seat allocation`.
6. Click `Load Students` and review the eligible active students.
7. Select the required students and click `Generate Admit Cards`.

The available display options are:

- Student photo
- Student ID
- Registration number
- Exam date and time
- Room
- Seat number
- Verification QR code

There is one settings record for each school/exam/session combination. Generating again for the
same combination updates that configuration and refreshes the selected students' admit cards.

### 5.2 Room and seat information

If room or seat display is enabled, the admit-card system looks for the student's saved
allocation for the same school and exam. A locked plan is preferred; otherwise, the most
recently updated matching plan is used.

Enable the seat-allocation requirement when admit cards must not be generated until every
selected student has a saved seat.

### 5.3 Admit-card settings

Open `Settings` from a batch's Actions column:

`/examination/admit-cards/settings/{batch-token}`

The school, exam, and session are read-only. The title, instructions, display options, QR option,
and seat-plan requirement can be changed. Saved changes immediately affect subsequent view,
print, and PDF output for that batch.

### 5.4 View, print, and PDF

- `View` opens the batch inside the normal application layout.
- `Print` opens a clean A4 document in a new tab and starts the browser print workflow.
- The PDF action downloads an A4 PDF containing the active cards in the batch.

Use the preview before distributing cards, especially after changing settings or seat
allocations.

---

## 6. Student access

Authenticated students can access their own active cards at:

`/examination/student/admit-cards`

A student can view a card or download its PDF. The controller verifies the logged-in student's
identity, school, card token, and active status before returning the document.

---

## 7. Public QR verification

When QR display is enabled, every admit card contains a public verification URL:

`/admit-card/verify/{card-token}`

Scanning the QR code opens a page that confirms whether the token belongs to an active admit
card. A valid response displays the school, student, exam, class/section, roll, room, and seat.
Malformed, unknown, inactive, or inconsistent tokens return an invalid-card page with HTTP 404.

Because this page is public, the token should be treated as a private verification link. It does
not provide access to other students or management functions.

---

## 8. Permissions

### Seat-plan permissions

| Permission slug | Capability |
|-----------------|------------|
| `exam_seat_plan_view` | List and view authorized seat plans. |
| `exam_seat_plan_generate` | Preview, generate, and regenerate plans. |
| `exam_seat_plan_edit` | Move, swap, add, and remove allocations. |
| `exam_seat_plan_lock` | Lock a reviewed plan. |
| `exam_seat_plan_unlock` | Unlock a plan for corrections. |
| `exam_seat_plan_print` | Preview, print, and download reports/PDFs. |

### Admit-card permissions

| Permission slug | Capability |
|-----------------|------------|
| `exam_admit_card_view` | List and view authorized admit-card batches. |
| `exam_admit_card_generate` | Generate cards and update batch settings. |
| `exam_admit_card_print` | Print and download admit-card PDFs. |

School authorization is checked in addition to permissions, so a user cannot open a token that
belongs to a school outside their authorized school list.

---

## 9. Troubleshooting

| Symptom | Likely cause / fix |
|---------|--------------------|
| No students are loaded | Confirm active student and enrollment status, session, class, section, shift, and department filters. |
| No rooms are available | Create/enable exam rooms and verify their dimensions and capacity. |
| Generate button is disabled | Select at least one student and enough room capacity. |
| Insufficient room capacity | Add rooms, increase valid capacity, or select fewer students. |
| Manual adjustment controls are missing | The plan is locked or the user lacks `exam_seat_plan_edit`. |
| Regeneration is blocked | Unlock the plan and confirm all allocated students are still active. |
| Admit card has no room/seat | Generate a matching seat plan, allocate the student, and enable room/seat display. |
| Admit-card generation reports missing seats | Allocate every selected student or disable the seat-plan requirement. |
| QR page says Invalid Admit Card | Check that the full URL was scanned and the card remains active. |
| Print/PDF action is unavailable | Assign the corresponding print permission. |

---

## 10. Operational checklist

Before issuing documents:

- Confirm the correct school, session, and exam.
- Compare candidate count with selected students.
- Confirm room capacity and the absence of seat shortages.
- Review manual moves and swaps.
- Lock the final seat plan.
- Preview every required report.
- Generate admit cards with the intended display settings.
- Check a sample student's room and seat against the locked plan.
- Scan a sample QR code and confirm the verification details.
- Download an archive PDF before distribution.
