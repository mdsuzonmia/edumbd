# Examination Module Helpers

This document describes the helper functions available in `app/Helpers/examination_helper.php` for the examination module.

## Overview

The examination helper provides reusable functions for common operations across examination controllers, reducing code duplication and improving maintainability.

## Helper Categories

### 1. Authentication & User Helpers

#### `get_exam_user_id(): int`
Get the current logged-in user ID.

```php
$user_id = get_exam_user_id();
```

#### `get_exam_user_schools(): array`
Get all schools accessible by the current user.

```php
$schools = get_exam_user_schools();
// Returns: [['id' => 1, 'name' => 'School Name', 'params' => '...'], ...]
```

#### `get_exam_school_dropdown(): array`
Get school list formatted for dropdowns `[id => name]`.

```php
$schoolList = get_exam_school_dropdown();
// Returns: [1 => 'School Name', 2 => 'Another School', ...]
```

#### `is_exam_school_setting_enabled(int $school_id, string $key): bool`
Check if a specific school setting is enabled.

```php
$sectionEnabled = is_exam_school_setting_enabled($school_id, 'academic_section_enabled');
```

---

### 2. Academic Data Helpers

#### `get_exam_active_options(int $school_id, string $model_name, $controller): array`
Get active options from a model for dropdowns.

```php
$yearList = get_exam_active_options($school_id, 'YearModel', $this);
$classList = get_exam_active_options($school_id, 'ClassModel', $this);
// Returns: [1 => '2024', 2 => '2025', ...]
```

#### `get_exam_academic_data_by_school(int $school_id, $controller, bool $include_exams = true): array`
Get all academic data (years, classes, sections, exams) for a school.

```php
$data = get_exam_academic_data_by_school($school_id, $this);
// Returns: [
//     'status' => true,
//     'year_list' => [...],
//     'class_list' => [...],
//     'section_list' => [...],
//     'exam_list' => [...],
//     'academic_section_enabled' => true/false
// ]
```

---

### 3. Enrollment & Student Helpers

#### `get_exam_enrollments_by_filters($enrollmentModel, int $school_id, int $class_id, ?int $section_id, ?int $year_id): array`
Get student enrollments with filters.

```php
$enrollments = get_exam_enrollments_by_filters(
    $this->EnrollmentModel,
    $school_id,
    $class_id,
    $section_id,
    $year_id
);
```

#### `get_exam_student_subjects($studentSubjectModel, int $school_id, int $enrollment_id): array`
Get main and optional subjects for a student.

```php
$subjects = get_exam_student_subjects($this->StudentSubjectModel, $school_id, $enrollment_id);
// Returns: [
//     'main' => [...],
//     'optional' => [...],
//     'all_ids' => [1, 2, 3, ...]
// ]
```

#### `filter_exam_subjects_for_student(array $allSubjects, array $studentSubjectIds): array`
Filter subjects array for a specific student.

```php
$studentSubjects = filter_exam_subjects_for_student($allSubjects, $subjectIds);
```

---

### 4. Grade Calculation Helpers

#### `calculate_exam_grade_for_percentage(float $percentage, ?int $grade_system_id, $gradeRuleModel): array`
Calculate grade and grade point for a percentage.

```php
$result = calculate_exam_grade_for_percentage(85.5, $grade_system_id, $this->GradeRulesModel);
// Returns: ['grade' => 'A+', 'grade_point' => 5.0, 'letter_grade' => 'A+']
```

#### `calculate_exam_gpa(array $gradePoints, int $totalSubjects): float`
Calculate GPA from grade points.

```php
$gpa = calculate_exam_gpa([5.0, 4.5, 4.0], 3);
// Returns: 4.5
```

#### `calculate_exam_percentage(float $obtained, float $full): float`
Calculate percentage from obtained and full marks.

```php
$percentage = calculate_exam_percentage(85, 100);
// Returns: 85.0
```

---

### 5. Mark Helpers

#### `get_exam_student_raw_marks($markModel, int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): array`
Get aggregated raw marks for a student.

```php
$marks = get_exam_student_raw_marks($this->MarkModel, $school_id, $exam_id, $class_id, $subject_id, $student_id);
// Returns: [
//     'full_mark' => 100,
//     'obtained_mark' => 85,
//     'is_absent' => 0,
//     'remarks' => ''
// ]
```

#### `get_exam_highest_mark_for_subject($subjectResultModel, $markModel, int $school_id, int $exam_id, int $class_id, int $subject_id, float $currentStudentMark): float`
Get highest mark for a subject.

```php
$highest = get_exam_highest_mark_for_subject(
    $this->SubjectResultModel,
    $this->MarkModel,
    $school_id,
    $exam_id,
    $class_id,
    $subject_id,
    $currentMark
);
```

---

### 6. Subject Result Helpers

#### `build_exam_subject_result_data(array $config): array`
Build subject result data array for insert/update.

```php
$data = build_exam_subject_result_data([
    'school_id' => $school_id,
    'user_id' => $user_id,
    'exam_id' => $exam_id,
    'year_id' => $year_id,
    'class_id' => $class_id,
    'section_id' => $section_id,
    'enrollment' => $enrollment,
    'subject_id' => $subject_id,
    'full_mark' => 100,
    'obtained_mark' => 85,
    'percentage' => 85.0,
    'grade_point' => 5.0,
    'grade' => 'A+',
    'letter_grade' => 'A+',
    'highest_mark' => 95,
    'is_fail' => 0
]);
```

#### `upsert_exam_subject_result($subjectResultModel, array $data, int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): int`
Insert or update subject result.

```php
$resultId = upsert_exam_subject_result(
    $this->SubjectResultModel,
    $data,
    $school_id,
    $exam_id,
    $class_id,
    $subject_id,
    $student_id
);
```

---

### 7. Exam Result Helpers

#### `build_exam_result_data(array $config): array`
Build exam result data array for insert/update.

```php
$data = build_exam_result_data([
    'school_id' => $school_id,
    'user_id' => $user_id,
    'exam_id' => $exam_id,
    'year_id' => $year_id,
    'class_id' => $class_id,
    'section_id' => $section_id,
    'student_id' => $student_id,
    'enrollment_id' => $enrollment_id,
    'total_subjects' => 5,
    'passed_subjects' => 5,
    'failed_subjects' => 0,
    'total_marks' => 500,
    'obtained_marks' => 450,
    'percentage' => 90.0,
    'gpa' => 5.0,
    'total_grade_point' => 25.0,
    'grade' => 'A+',
    'letter_grade' => 'A+',
    'result_status' => 'PASS'
]);
```

#### `upsert_exam_result($examResultModel, array $data, int $school_id, int $exam_id, int $class_id, int $student_id, callable $tokenGenerator): void`
Insert or update exam result.

```php
upsert_exam_result(
    $this->ExamResultModel,
    $data,
    $school_id,
    $exam_id,
    $class_id,
    $student_id,
    fn() => generate_exam_token()
);
```

---

### 8. Position/Rank Helpers

#### `assign_exam_ranks_with_ties(array $results, string $marks_key = 'obtained_marks', string $rank_key = 'rank'): array`
Assign ranks to results array with tie handling.

```php
$results = assign_exam_ranks_with_ties($results, 'obtained_marks', 'class_rank');
```

#### `update_exam_result_ranks_batch($examResultModel, array $results, array $rankFields): void`
Update ranks for exam results in batch.

```php
update_exam_result_ranks_batch(
    $this->ExamResultModel,
    $results,
    ['class_rank', 'section_rank', 'exam_rank']
);
```

---

### 9. Aggregate Result Helpers

#### `calculate_exam_weighted_aggregate(array $examResults, array $exams): array`
Calculate weighted aggregate for a subject across multiple exams.

```php
$aggregate = calculate_exam_weighted_aggregate($examResults, $exams);
// Returns: ['weighted_obtained' => 85.5, 'weighted_full' => 100.0]
```

#### `build_exam_final_result_data(array $config): array`
Build final result data array for insert/update.

```php
$data = build_exam_final_result_data([
    'school_id' => $school_id,
    'user_id' => $user_id,
    'student_id' => $student_id,
    'enrollment_id' => $enrollment_id,
    'year_id' => $year_id,
    'class_id' => $class_id,
    'section_id' => $section_id,
    'total_marks' => 450,
    'total_full_marks' => 500,
    'percentage' => 90.0,
    'gpa' => 5.0,
    'letter_grade' => 'A+',
    'grade' => 'A+',
    'class_position' => 1,
    'section_position' => 1,
    'result_status' => 'PASS'
]);
```

#### `upsert_exam_final_result($finalResultModel, array $data, int $student_id, int $year_id, int $class_id, callable $tokenGenerator): void`
Insert or update final result.

```php
upsert_exam_final_result(
    $this->FinalResultModel,
    $data,
    $student_id,
    $year_id,
    $class_id,
    fn() => generate_exam_token()
);
```

---

### 10. Publish Helpers

#### `upsert_exam_publish_record($resultPublishModel, array $config): void`
Insert or update result publish record.

```php
upsert_exam_publish_record($this->ResultPublishModel, [
    'school_id' => $school_id,
    'user_id' => $user_id,
    'exam_id' => $exam_id,
    'class_id' => $class_id,
    'section_id' => $section_id,
    'is_published' => 0,
    'published_by' => 0,
    'published_at' => null
]);
```

---

### 11. Delete Helpers

#### `delete_exam_related_results($subjectResultModel, $examResultModel, $finalResultModel, $resultPublishModel, int $school_id, int $exam_id, int $class_id, ?int $section_id, ?int $year_id): void`
Delete examination results for recalculation.

```php
delete_exam_related_results(
    $this->SubjectResultModel,
    $this->ExamResultModel,
    $this->FinalResultModel,
    $this->ResultPublishModel,
    $school_id,
    $exam_id,
    $class_id,
    $section_id,
    $year_id
);
```

---

### 12. Lock/Unlock Helpers

#### `get_exam_lock_error_message($markLockModel, $examModel, $yearModel, $classModel, int $school_id, int $exam_id, int $class_id, int $session_id, ?int $subject_id = null): ?string`
Get formatted lock error message.

```php
$errorMessage = get_exam_lock_error_message(
    $this->MarkLockModel,
    $this->ExamModel,
    $this->YearModel,
    $this->ClassModel,
    $school_id,
    $exam_id,
    $class_id,
    $session_id,
    $subject_id
);

if ($errorMessage) {
    return redirect()->back()->with('error', $errorMessage);
}
```

---

### 13. Token Generator

#### `generate_exam_token(): string`
Generate secure random token.

```php
$token = generate_exam_token();
// Returns: 'a1b2c3d4e5f6...' (32 character hex string)
```

---

### 14. Highest Mark Recalculation

#### `recalculate_exam_highest_marks($subjectResultModel, int $school_id, int $exam_id, int $class_id, ?int $section_id, array $subjects): void`
Recalculate highest marks for all subjects.

```php
recalculate_exam_highest_marks(
    $this->SubjectResultModel,
    $school_id,
    $exam_id,
    $class_id,
    $section_id,
    $subjects
);
```

---

### 15. Merge/Exclude Subject Helpers

#### `apply_exam_merge_subject(float $currentFullMark, float $currentObtainedMark, bool $currentIsAbsent, ?object $mergeSubject, array $studentRawMarks): array`
Apply merge_others_subject logic to marks.

```php
$merged = apply_exam_merge_subject(
    $fullMark,
    $obtainedMark,
    $isAbsent,
    $mergeSubject,
    $studentRawMarks
);
// Returns: ['full_mark' => ..., 'obtained_mark' => ..., 'is_absent' => ...]
```

#### `apply_exam_exclude_mark(float $obtainedMark, object $subject): array`
Apply enabled_exclude_mark logic.

```php
$exclude = apply_exam_exclude_mark($obtainedMark, $subject);
// Returns: [
//     'include_mark' => ...,
//     'exclude_mark' => ...,
//     'exclude_grade_point' => ...,
//     'exclude_percentage' => ...
// ]
```

---

### 16. Validation Helpers

#### `validate_exam_marks_exist($markModel, array $subjects, array $enrollments, int $school_id, int $exam_id, int $class_id): array`
Validate that marks exist for all subject-student combinations.

```php
$validation = validate_exam_marks_exist(
    $this->MarkModel,
    $subjects,
    $enrollments,
    $school_id,
    $exam_id,
    $class_id
);

if (!$validation['status']) {
    return exam_json_response($validation);
}
```

#### `validate_exam_marks_locked($markLockModel, $subjectModel, array $subjects, int $school_id, int $exam_id, int $class_id, int $year_id): array`
Validate that all marks are locked.

```php
$validation = validate_exam_marks_locked(
    $this->MarkLockModel,
    $this->SubjectModel,
    $subjects,
    $school_id,
    $exam_id,
    $class_id,
    $year_id
);
```

---

### 17. JSON Response Helper

#### `exam_json_response(array $response)`
Create JSON response with CSRF token header.

```php
return exam_json_response([
    'status' => true,
    'message' => 'Success'
]);
```

---

### 18. Logging Helper

#### `exam_log_message(string $type, string $message, string $prefix = '[Examination]'): void`
Log examination message with prefix.

```php
exam_log_message('info', 'Results generated successfully');
exam_log_message('error', 'Generation failed: ' . $e->getMessage(), '[ResultGenerator]');
```

---

## Usage in Controllers

All helpers are automatically loaded via `app/Config/Autoload.php`. Simply call them directly in your controllers:

```php
<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;

class ResultGeneratorController extends BaseController
{
    public function generate()
    {
        $user_id = get_exam_user_id();
        $schools = get_exam_user_schools();
        
        // Use helpers instead of protected methods
        $enrollments = get_exam_enrollments_by_filters(
            $this->EnrollmentModel,
            $school_id,
            $class_id,
            $section_id,
            $year_id
        );
        
        // ... rest of the logic
    }
}
```

## Benefits

1. **Code Reusability**: Common logic is written once and used across all examination controllers
2. **Maintainability**: Changes to business logic only need to be made in one place
3. **Testability**: Helper functions can be unit tested independently
4. **Consistency**: Ensures consistent behavior across all examination operations
5. **Reduced Controller Bloat**: Controllers become cleaner and more focused on orchestration

## Migration Guide

To migrate existing controller code to use helpers:

1. Replace protected methods with helper function calls
2. Remove duplicate model instantiations (helpers create their own when needed)
3. Use helper functions for common operations like:
   - Getting user schools
   - Fetching enrollments
   - Calculating grades
   - Building result data arrays
   - Upserting records

## Example: Before and After

### Before (Controller Method)
```php
protected function getUserId(): int
{
    return (int) session('user_id');
}

protected function getUserSchools(): array
{
    $user_id = $this->getUserId();
    if (!$user_id) {
        return [];
    }

    return $this->SchoolModel
        ->select('schools.id, schools.name, schools.params')
        ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
        ->where('school_user_relation.user_id', $user_id)
        ->where('schools.status', 1)
        ->orderBy('schools.name', 'ASC')
        ->findAll();
}
```

### After (Using Helpers)
```php
// Just call the helper directly
$user_id = get_exam_user_id();
$schools = get_exam_user_schools();
```

## Notes

- All helper functions are wrapped in `if (!function_exists())` checks to prevent conflicts
- Helpers follow the naming convention `exam_*` or `*_exam_*` to avoid namespace collisions
- Models are instantiated within helpers when needed, reducing controller dependencies
- All helpers are documented with PHPDoc blocks for IDE autocomplete