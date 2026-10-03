# Examination Module Helpers

This document describes how to use the examination module helpers, which follow the same pattern as module routes.

## Overview

The examination module now has its own helper system, just like routes:

```
app/Modules/examination/
├── Helpers/
│   └── examination_helper.php    # Module-specific helper functions
├── Controllers/
│   ├── BaseController.php        # Base controller that auto-loads helpers
│   └── ...
└── Config/
    └── Routes.php                 # Module routes
```

## How It Works

### 1. **Module Helper File**
Location: `app/Modules/examination/Helpers/examination_helper.php`

This file contains all helper functions specific to the examination module. Functions are prefixed with `exam_` or `*_exam_*` to avoid conflicts.

### 2. **Module BaseController**
Location: `app/Modules/examination/Controllers/BaseController.php`

This base controller:
- Automatically loads the examination helper in its constructor
- Provides wrapper methods for all helper functions
- Should be extended by all examination controllers

### 3. **Usage in Controllers**

Simply extend the module's BaseController:

```php
<?php

namespace App\Modules\examination\Controllers;

use App\Modules\examination\Controllers\BaseController;

class ResultGeneratorController extends BaseController
{
    public function generate()
    {
        // All helper functions are available as protected methods
        $user_id = $this->getUserId();
        $schools = $this->getUserSchools();
        $enrollments = $this->getEnrollmentsByFilters($school_id, $class_id, $section_id, $year_id);
        
        // Or use helper functions directly
        $percentage = calculate_exam_percentage(85, 100);
        
        // ... rest of your code
    }
}
```

## Available Helper Functions

### Authentication & User Helpers

```php
// Get current user ID
$user_id = $this->getUserId();

// Get user's schools
$schools = $this->getUserSchools();

// Get school dropdown
$schoolList = $this->getSchoolDropdown();

// Check school setting
$enabled = $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled');
```

### Academic Data Helpers

```php
// Get active options for dropdown
$yearList = $this->getActiveOptions($school_id, 'YearModel');
$classList = $this->getActiveOptions($school_id, 'ClassModel');

// Get all academic data
$data = $this->getAcademicDataBySchool($school_id);
// Returns: ['year_list' => [], 'class_list' => [], 'section_list' => [], 'exam_list' => [], ...]
```

### Enrollment & Student Helpers

```php
// Get enrollments with filters
$enrollments = $this->getEnrollmentsByFilters($school_id, $class_id, $section_id, $year_id);

// Get student subjects
$subjects = $this->getStudentSubjects($school_id, $enrollment_id);
// Returns: ['main' => [], 'optional' => [], 'all_ids' => []]

// Filter subjects for student
$studentSubjects = $this->filterSubjectsForStudent($allSubjects, $subjectIds);
```

### Grade Calculation Helpers

```php
// Calculate grade for percentage
$result = $this->calculateGradeForPercentage(85.5, $grade_system_id);
// Returns: ['grade' => 'A+', 'grade_point' => 5.0, 'letter_grade' => 'A+']

// Calculate GPA
$gpa = $this->calculateGPA([5.0, 4.5, 4.0], 3);
// Returns: 4.5

// Calculate percentage
$percentage = $this->calculatePercentage(85, 100);
// Returns: 85.0
```

### Mark Helpers

```php
// Get student raw marks
$marks = $this->getStudentRawMarks($school_id, $exam_id, $class_id, $subject_id, $student_id);
// Returns: ['full_mark' => 100, 'obtained_mark' => 85, 'is_absent' => 0, 'remarks' => '']

// Get highest mark for subject
$highest = $this->getHighestMarkForSubject($school_id, $exam_id, $class_id, $subject_id, $currentMark);
```

### Subject Result Helpers

```php
// Build subject result data
$data = $this->buildSubjectResultData([
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

// Upsert subject result
$resultId = $this->upsertSubjectResult($data, $school_id, $exam_id, $class_id, $subject_id, $student_id);
```

### Exam Result Helpers

```php
// Build exam result data
$data = $this->buildExamResultData([
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

// Upsert exam result
$this->upsertExamResult($data, $school_id, $exam_id, $class_id, $student_id);
```

### Position/Rank Helpers

```php
// Assign ranks with ties
$results = $this->assignRanksWithTies($results, 'obtained_marks', 'class_rank');

// Update result ranks batch
$this->updateResultRanksBatch($results, ['class_rank', 'section_rank', 'exam_rank']);
```

### Aggregate Result Helpers

```php
// Calculate weighted aggregate
$aggregate = $this->calculateWeightedAggregate($examResults, $exams);
// Returns: ['weighted_obtained' => 85.5, 'weighted_full' => 100.0]

// Build final result data
$data = $this->buildFinalResultData([
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

// Upsert final result
$this->upsertFinalResult($data, $student_id, $year_id, $class_id);
```

### Publish Helpers

```php
// Upsert publish record
$this->upsertPublishRecord([
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

### Delete Helpers

```php
// Delete related results for recalculation
$this->deleteRelatedResults($school_id, $exam_id, $class_id, $section_id, $year_id);
```

### Lock/Unlock Helpers

```php
// Get lock error message
$errorMessage = $this->getLockErrorMessage($school_id, $exam_id, $class_id, $session_id, $subject_id);

if ($errorMessage) {
    return redirect()->back()->with('error', $errorMessage);
}
```

### Token Generator

```php
// Generate secure token
$token = $this->generateToken();
// Returns: 'a1b2c3d4e5f6...' (32 character hex string)
```

### Highest Mark Recalculation

```php
// Recalculate highest marks
$this->recalculateHighestMarks($school_id, $exam_id, $class_id, $section_id, $subjects);
```

### Merge/Exclude Subject Helpers

```php
// Apply merge subject
$merged = $this->applyMergeSubject($fullMark, $obtainedMark, $isAbsent, $mergeSubject, $studentRawMarks);
// Returns: ['full_mark' => ..., 'obtained_mark' => ..., 'is_absent' => ...]

// Apply exclude mark
$exclude = $this->applyExcludeMark($obtainedMark, $subject);
// Returns: ['include_mark' => ..., 'exclude_mark' => ..., 'exclude_grade_point' => ..., 'exclude_percentage' => ...]
```

### Validation Helpers

```php
// Validate marks exist
$validation = $this->validateMarksExist($subjects, $enrollments, $school_id, $exam_id, $class_id);

if (!$validation['status']) {
    return $this->jsonResponse($validation);
}

// Validate marks locked
$validation = $this->validateMarksLocked($subjects, $school_id, $exam_id, $class_id, $year_id);
```

### JSON Response Helper

```php
// JSON response with CSRF token
return $this->jsonResponse([
    'status' => true,
    'message' => 'Success'
]);
```

### Logging Helper

```php
// Log message
$this->logMessage('info', 'Results generated successfully');
$this->logMessage('error', 'Generation failed: ' . $e->getMessage(), '[ResultGenerator]');
```

## Migration Guide

### Step 1: Update Controller to Extend Module BaseController

**Before:**
```php
<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;

class ResultGeneratorController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    // ... many more models
    
    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
        $this->YearModel = new AcademicsYearModel();
        // ... many more instantiations
    }
    
    protected function getUserId(): int
    {
        return (int) session('user_id');
    }
    
    protected function getUserSchools(): array
    {
        // 20+ lines of code
    }
    
    // ... more duplicate methods
}
```

**After:**
```php
<?php

namespace App\Modules\examination\Controllers;

use App\Modules\examination\Controllers\BaseController;

class ResultGeneratorController extends BaseController
{
    // Only include models you actually use directly
    protected SubjectModel $SubjectModel;
    protected ExamModel $ExamModel;
    
    public function __construct()
    {
        parent::__construct();
        // Only instantiate models you need
        $this->SubjectModel = new SubjectModel();
        $this->ExamModel = new ExamModel();
    }
    
    // All common methods are inherited from BaseController
    // Focus on your business logic only!
}
```

### Step 2: Replace Controller Methods with Helper Calls

**Before:**
```php
protected function getUserId(): int
{
    return (int) session('user_id');
}

$user_id = $this->getUserId();
$schools = $this->getUserSchools();
$enrollments = $this->getEnrollmentsByFilters($school_id, $class_id, $section_id, $year_id);
```

**After:**
```php
// Methods are inherited from BaseController
$user_id = $this->getUserId();
$schools = $this->getUserSchools();
$enrollments = $this->getEnrollmentsByFilters($school_id, $class_id, $section_id, $year_id);
```

### Step 3: Use Helper Functions Directly (Optional)

You can also use helper functions directly without the wrapper methods:

```php
// Direct helper function calls
$user_id = get_exam_user_id();
$schools = get_exam_user_schools();
$percentage = calculate_exam_percentage(85, 100);
$grade = calculate_exam_grade_for_percentage(85.5, $grade_system_id, $this->GradeRulesModel);
```

## Benefits

1. **Code Reusability**: Common logic is written once and used across all examination controllers
2. **Maintainability**: Changes to business logic only need to be made in one place
3. **Cleaner Controllers**: Controllers focus on business logic, not boilerplate
4. **Consistency**: Ensures consistent behavior across all examination operations
5. **Testability**: Helper functions can be unit tested independently
6. **Module Isolation**: Helpers are specific to the examination module, avoiding global namespace pollution

## Example: Complete Controller Refactoring

### Before (500+ lines with lots of duplication)
```php
class ResultGeneratorController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected MarkModel $MarkModel;
    protected SubjectResultModel $SubjectResultModel;
    protected ExamResultModel $ExamResultModel;
    protected FinalResultModel $FinalResultModel;
    protected ResultPublishModel $ResultPublishModel;
    protected SubjectModel $SubjectModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected MarkLockModel $MarkLockModel;
    protected ExamModel $ExamModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected $db;

    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
        $this->YearModel = new AcademicsYearModel();
        $this->ClassModel = new AcademicsClassesModel();
        $this->SectionModel = new AcademicsSectionModel();
        $this->EnrollmentModel = new StudentEnrollmentModel();
        $this->StudentModel = new StudentModel();
        $this->MarkModel = new MarkModel();
        $this->SubjectResultModel = new SubjectResultModel();
        $this->ExamResultModel = new ExamResultModel();
        $this->FinalResultModel = new FinalResultModel();
        $this->ResultPublishModel = new ResultPublishModel();
        $this->SubjectModel = new SubjectModel();
        $this->GradeRulesModel = new GradeRuleModel();
        $this->GradeSystemModel = new GradeSystemModel();
        $this->MarkLockModel = new MarkLockModel();
        $this->ExamModel = new ExamModel();
        $this->StudentSubjectModel = new StudentSubjectModel();
        $this->db = \Config\Database::connect();
    }

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

    protected function getSchoolDropdown(): array
    {
        $schools = $this->getUserSchools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }

    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }

    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $model = $this->{$modelProperty};
        
        $orderColumn = ($modelProperty === 'MarkDistributionModel') ? 'name' : 'title';
        $valueColumn = ($modelProperty === 'MarkDistributionModel') ? 'name' : 'title';
        
        $records = $model
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy($orderColumn, 'ASC')
            ->findAll();

        foreach ($records as $r) {
            $list[$r->id] = $r->$valueColumn;
        }
        return $list;
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function logMessage(string $type, string $message): void
    {
        log_message($type, '[ResultGenerator] ' . $message);
    }

    // ... 1000+ more lines
}
```

### After (Clean and focused!)
```php
class ResultGeneratorController extends BaseController
{
    protected SubjectModel $SubjectModel;
    protected ExamModel $ExamModel;
    protected StudentSubjectModel $StudentSubjectModel;

    public function __construct()
    {
        parent::__construct();
        $this->SubjectModel = new SubjectModel();
        $this->ExamModel = new ExamModel();
        $this->StudentSubjectModel = new StudentSubjectModel();
    }

    public function generate()
    {
        $user_id = $this->getUserId();
        // All helper methods are available!
        // Focus on business logic only
    }
    
    // ... only your specific business logic methods
}
```

## Notes

- All helper functions are wrapped in `if (!function_exists())` checks to prevent conflicts
- Helpers follow the naming convention `exam_*` or `*_exam_*` to avoid namespace collisions
- The module BaseController automatically loads the helper file
- You can still use global helpers alongside module helpers
- Models are instantiated within helpers when needed, reducing controller dependencies

## Files Structure

```
app/Modules/examination/
├── Helpers/
│   ├── examination_helper.php    # All helper functions
│   └── README.md                 # This documentation
├── Controllers/
│   ├── BaseController.php        # Base controller with helper wrappers
│   ├── ResultGeneratorController.php
│   ├── AggregateController.php
│   └── ...
├── Models/
│   ├── SubjectModel.php
│   ├── ExamModel.php
│   └── ...
├── Views/
│   └── ...
└── Config/
    └── Routes.php
```

## Support

For issues or questions about the examination module helpers, refer to:
- `app/Helpers/EXAMINATION_HELPERS_README.md` - Detailed function documentation
- `app/Modules/examination/Helpers/examination_helper.php` - Source code with PHPDoc