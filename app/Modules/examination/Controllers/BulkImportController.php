<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectResultHistoryModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\SubjectDistributionModel;
use App\Modules\examination\Models\MarkLockModel;
use App\Modules\examination\Models\StudentSubjectModel;
use DateTime;

class BulkImportController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected ExamModel $ExamModel;
    protected SubjectModel $SubjectModel;
    protected MarkModel $MarkModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected SubjectResultModel $SubjectResultModel;
    protected SubjectResultHistoryModel $SubjectResultHistoryModel;
    protected ExamResultModel $ExamResultModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected SubjectDistributionModel $SubjectDistributionModel;
    protected MarkLockModel $MarkLockModel;
    protected StudentSubjectModel $StudentSubjectModel;

    public function __construct()
    {
        $this->SchoolModel              = new SchoolModel();
        $this->YearModel                = new AcademicsYearModel();
        $this->ClassModel               = new AcademicsClassesModel();
        $this->EnrollmentModel          = new StudentEnrollmentModel();
        $this->StudentModel             = new StudentModel();
        $this->ExamModel                = new ExamModel();
        $this->SubjectModel             = new SubjectModel();
        $this->MarkModel                = new MarkModel();
        $this->MarkDistributionModel    = new MarkDistributionModel();
        $this->SubjectResultModel       = new SubjectResultModel();
        $this->SubjectResultHistoryModel = new SubjectResultHistoryModel();
        $this->ExamResultModel          = new ExamResultModel();
        $this->GradeRulesModel          = new GradeRuleModel();
        $this->GradeSystemModel         = new GradeSystemModel();
        $this->SubjectDistributionModel = new SubjectDistributionModel();
        $this->MarkLockModel            = new MarkLockModel();
        $this->StudentSubjectModel      = new StudentSubjectModel();
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

    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Bulk Import Marks',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\bulk_import\index', $data)
            . view('footer', $footer_data);
    }

    public function getStudentsByClass()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $year_id    = (int) $this->request->getPost('year_id');

        if (!$school_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'students' => []]);
        }

        $this->EnrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code, students.photo')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($year_id) {
            $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
        }

        $enrollments = $this->EnrollmentModel
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->findAll();

        $students = [];
        foreach ($enrollments as $enr) {
            $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            $students[] = [
                'enrollment_id' => $enr->id,
                'student_id'    => $enr->student_id,
                'student_code'  => $enr->student_code,
                'student_name'  => $studentName,
                'roll_no'       => $enr->roll_no,
                'photo'         => $enr->photo,
            ];
        }

        return $this->jsonResponse([
            'status'   => true,
            'students' => $students,
        ]);
    }

    public function getDistributionsBySubject()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        $subject_id = (int) $this->request->getPost('subject_id');

        if (!$subject_id) {
            return $this->jsonResponse(['status' => false, 'distributions' => []]);
        }

        $distributions = $this->MarkDistributionModel
            ->select('examination_mark_distributions.*, examination_subject_distributions.full_mark, examination_subject_distributions.pass_mark, examination_subject_distributions.weight_percent, examination_subject_distributions.sort_order')
            ->join('examination_subject_distributions', 'examination_subject_distributions.distribution_id = examination_mark_distributions.id', 'left')
            ->where('examination_subject_distributions.subject_id', $subject_id)
            ->where('examination_mark_distributions.status', 1)
            ->orderBy('examination_subject_distributions.sort_order', 'ASC')
            ->orderBy('examination_mark_distributions.name', 'ASC')
            ->findAll();

        $list = [];
        foreach ($distributions as $d) {
            $list[] = [
                'id'       => $d->id,
                'name'     => $d->name,
                'code'     => $d->code,
                'full_mark' => (float) ($d->full_mark ?? 0),
                'pass_mark' => (float) ($d->pass_mark ?? 0),
                'weight'   => (float) ($d->weight_percent ?? 0),
                'sort'     => (int) ($d->sort_order ?? 0),
            ];
        }

        return $this->jsonResponse([
            'status'        => true,
            'distributions' => $list,
        ]);
    }

    public function preview()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        $file = $this->request->getFile('import_file');
        if (!$file || !$file->isValid()) {
            return $this->jsonResponse(['status' => false, 'message' => 'No valid file uploaded.']);
        }

        $school_id   = (int) $this->request->getPost('school_id');
        $exam_id     = (int) $this->request->getPost('exam_id');
        $class_id    = (int) $this->request->getPost('class_id');
        $subject_id  = (int) $this->request->getPost('subject_id');
        $year_id     = (int) $this->request->getPost('year_id');

        if (!$school_id || !$exam_id || !$class_id || !$subject_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required filters.']);
        }

        $extension = $file->getClientExtension();
        $rows = [];

        try {
            if (in_array($extension, ['csv', 'txt'])) {
                $rows = $this->parseCsvFile($file);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $rows = $this->parseExcelFile($file);
            } elseif ($extension === 'json') {
                $rows = $this->parseJsonFile($file);
            } else {
                return $this->jsonResponse(['status' => false, 'message' => 'Unsupported file format. Supported formats: CSV, XLSX, JSON.']);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => false, 'message' => 'Error parsing file: ' . $e->getMessage()]);
        }

        if (empty($rows)) {
            return $this->jsonResponse(['status' => false, 'message' => 'No data found in the file.']);
        }

        // Validate rows
        $errors = [];
        $validRows = [];
        
        // Get roll numbers from the file
        $rollNumbers = [];
        foreach ($rows as $row) {
            $roll = $row['Roll'] ?? $row['roll_no'] ?? '';
            if ($roll) {
                $rollNumbers[] = $roll;
            }
        }
        $rollNumbers = array_unique($rollNumbers);
        
        // Look up students by roll number from student_enrollments table
        $enrollmentMap = [];
        if (!empty($rollNumbers)) {
            $enrollments = $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('student_enrollments.session_id', $year_id)
                ->whereIn('student_enrollments.roll_no', $rollNumbers)
                ->where('students.status', 1)
                ->findAll();
            
            foreach ($enrollments as $enr) {
                // Map by roll number for quick lookup
                $enrollmentMap[$enr->roll_no] = $enr;
            }
        }

        $distributions = $this->getDistributionsBySubjectData($subject_id);
        $distMap = [];
        foreach ($distributions as $d) {
            $distMap[$d['code']] = $d;
        }

        // Validate filename contains subject name
        $originalFilename = $file->getClientName();
        $filenameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        $subject = $this->SubjectModel->find($subject_id);
        $subjectTitle = $subject ? $subject->title : '';
        
        if (!empty($subjectTitle)) {
            // Normalize both filename and subject title for comparison
            // Replace underscores with spaces and lowercase for case-insensitive matching
            $normalizedFilename = strtolower(str_replace('_', ' ', $filenameWithoutExt));
            $normalizedSubject = strtolower($subjectTitle);
            
            // Check if subject name appears in filename (case-insensitive, underscore/space tolerant)
            if (stripos($normalizedFilename, $normalizedSubject) === false) {
                $errors[] = "Warning: The uploaded file name '{$originalFilename}' does not contain the subject name '{$subjectTitle}'. Please verify this is the correct file.";
            }
        }

        // Validate that file contains distribution columns matching the selected subject
        if (!empty($rows)) {
            $firstRow = $rows[0];
            $fileDistributionCodes = [];
            
            // Get distribution codes from file (exclude standard columns)
            foreach ($firstRow as $key => $value) {
                if (!in_array($key, ['student_code', 'Roll', 'student_name', 'Student Name', 'roll_no', 'is_absent', 'Absent', 'remarks'])) {
                    $fileDistributionCodes[] = $key;
                }
            }
            
            if (!empty($distributions)) {
                // Subject has distributions configured
                
                // If subject has distributions but file has none, that's an error
                if (empty($fileDistributionCodes)) {
                    return $this->jsonResponse([
                        'status' => false,
                        'message' => 'File does not contain any distribution columns. The selected subject expects: ' . implode(', ', array_column($distributions, 'code'))
                    ]);
                }
                
                // Check if file has at least one matching distribution column
                $hasMatchingDistribution = false;
                $matchingDists = [];
                $missingDists = [];
                
                foreach ($distributions as $dist) {
                    if (in_array($dist['code'], $fileDistributionCodes)) {
                        $hasMatchingDistribution = true;
                        $matchingDists[] = $dist['code'];
                    } else {
                        $missingDists[] = $dist['code'] . ' (' . $dist['name'] . ')';
                    }
                }
                
                // If no matching distributions found, this is likely the wrong file
                if (!$hasMatchingDistribution) {
                    return $this->jsonResponse([
                        'status' => false,
                        'message' => 'File does not appear to be for the selected subject. The file contains columns: ' . implode(', ', $fileDistributionCodes) . ' but the subject expects: ' . implode(', ', array_column($distributions, 'code'))
                    ]);
                }
                
                // Warning if some distributions are missing
                if (!empty($missingDists)) {
                    $errors[] = "Warning: File is missing some expected distribution columns: " . implode(', ', $missingDists) . ". Only found: " . implode(', ', $matchingDists);
                }
            } else {
                // Subject has NO distributions configured, but file has distribution columns - that's suspicious
                if (!empty($fileDistributionCodes)) {
                    return $this->jsonResponse([
                        'status' => false,
                        'message' => 'The selected subject has no mark distributions configured, but the file contains distribution columns: ' . implode(', ', $fileDistributionCodes) . '. Please configure distributions for this subject first.'
                    ]);
                }
            }
        }

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2; // +2 for header row and 0-index

            // Get roll number from file
            $rollNo = $row['Roll'] ?? $row['roll_no'] ?? '';
            
            if (empty($rollNo)) {
                $errors[] = "Row {$rowNum}: Roll number is missing.";
                continue;
            }

            if (!isset($enrollmentMap[$rollNo])) {
                $errors[] = "Row {$rowNum}: Student with roll number '{$rollNo}' not found.";
                continue;
            }

            $enrollment = $enrollmentMap[$rollNo];
            $studentCode = $enrollment->student_code;

            // Get student name from file or database
            $studentName = $row['Student Name'] ?? $row['student_name'] ?? trim($enrollment->first_name . ' ' . ($enrollment->middle_name ?? '') . ' ' . $enrollment->last_name);
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            // Get absent status - support Yes/No, 1/0, true/false
            $absentRaw = $row['is_absent'] ?? $row['Absent'] ?? 'No';
            if (is_string($absentRaw)) {
                $absentRaw = strtolower(trim($absentRaw));
                $isAbsent = ($absentRaw === 'yes' || $absentRaw === '1' || $absentRaw === 'true') ? 1 : 0;
            } else {
                $isAbsent = !empty($absentRaw) ? 1 : 0;
            }

            $distributionMarks = [];
            foreach ($row as $key => $value) {
                // Skip standard columns
                if (in_array($key, ['student_code', 'Roll', 'student_name', 'Student Name', 'roll_no', 'Roll', 'is_absent', 'Absent', 'remarks'])) {
                    continue;
                }
                
                // Check if this key matches a distribution code
                if (isset($distMap[$key])) {
                    $markValue = is_numeric($value) ? (float) $value : 0;
                    $distributionMarks[] = [
                        'code'       => $key,
                        'label'      => $distMap[$key]['name'],
                        'max'        => $distMap[$key]['full_mark'],
                        'obtained'   => $markValue,
                    ];
                }
            }
            
            // If no distribution marks found but we have distributions configured, log for debugging
            if (empty($distributionMarks) && !empty($distributions)) {
                // File might not have distribution columns, that's okay
            }

            // Get subject name
            $subject = $this->SubjectModel->find($subject_id);
            $subjectName = $subject ? $subject->title : 'Unknown Subject';

            $validRows[] = [
                'row_number'         => $rowNum,
                'student_code'       => $studentCode,
                'student_name'       => $studentName,
                'student_id'         => $enrollment->student_id,
                'enrollment_id'      => $enrollment->id,
                'roll_no'            => $rollNo,
                'is_absent'          => $isAbsent,
                'subject_name'       => $subjectName,
                'remarks'            => $row['remarks'] ?? '',
                'distribution_marks' => $distributionMarks,
            ];
        }

        return $this->jsonResponse([
            'status'        => true,
            'valid_rows'    => $validRows,
            'errors'        => $errors,
            'total_rows'    => count($rows),
            'valid_count'   => count($validRows),
            'error_count'   => count($errors),
            'distributions' => $distributions,
        ]);
    }

    public function import()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['html']]);
        }

        $school_id   = (int) $this->request->getPost('school_id');
        $exam_id     = (int) $this->request->getPost('exam_id');
        $class_id    = (int) $this->request->getPost('class_id');
        $subject_id  = (int) $this->request->getPost('subject_id');
        $year_id     = (int) $this->request->getPost('year_id');
        
        // Get students data from POST and decode JSON
        $students_data_json = $this->request->getPost('students');
        $students_data = json_decode($students_data_json, true);

        if (!$school_id || !$exam_id || !$class_id || !$subject_id || empty($students_data)) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required data.']);
        }

        // Check if marks are locked
        $isLocked = $this->MarkLockModel->isLocked($school_id, $exam_id, $class_id, $year_id, $subject_id);
        if ($isLocked) {
            $lockInfo = $this->MarkLockModel->getLockStatus($school_id, $exam_id, $class_id, $year_id, $subject_id);
            $exam = $this->ExamModel->find($exam_id);
            $examTitle = $exam ? $exam->title : 'this exam';
            $session = $this->YearModel->find($lockInfo->session_id ?? $year_id);
            $sessionName = $session ? $session->title : 'this session';
            $class = $this->ClassModel->find($class_id);
            $className = $class ? $class->title : 'this class';
            
            return $this->jsonResponse([
                'status' => false, 
                'message' => "Cannot import marks. Subject marks are locked for the exam '{$examTitle}', session '{$sessionName}', class '{$className}'. Please unlock the marks first."
            ]);
        }

        $user_id = $this->getUserId();
        $saved = 0;
        $errors = [];

        foreach ($students_data as $studentData) {
            $student_id    = (int) ($studentData['student_id'] ?? 0);
            $enrollment_id = (int) ($studentData['enrollment_id'] ?? 0);
            $is_absent     = !empty($studentData['is_absent']) ? 1 : 0;
            $remarks       = $studentData['remarks'] ?? '';
            $distributions = $studentData['distribution_marks'] ?? [];

            if (!$student_id) {
                continue;
            }

            // If no distributions, skip this student
            if (empty($distributions)) {
                continue;
            }

            foreach ($distributions as $dist) {
                $distribution_code = $dist['code'] ?? '';
                $obtained_mark     = (float) ($dist['obtained'] ?? 0);

                if (empty($distribution_code)) {
                    continue;
                }

                // Find distribution by code
                $distribution = $this->MarkDistributionModel
                    ->where('code', $distribution_code)
                    ->where('school_id', $school_id)
                    ->where('status', 1)
                    ->first();

                if (!$distribution) {
                    $errors[] = "Distribution code '{$distribution_code}' not found.";
                    continue;
                }

                // Get full mark from subject distribution
                $subjectDist = $this->SubjectDistributionModel
                    ->where('subject_id', $subject_id)
                    ->where('distribution_id', $distribution->id)
                    ->first();

                $fullMark = $subjectDist ? (float) $subjectDist->full_mark : 0;

                $existing = $this->MarkModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject_id)
                    ->where('student_id', $student_id)
                    ->where('distribution_id', $distribution->id)
                    ->first();

                $saveData = [
                    'school_id'        => $school_id,
                    'school_owner_uid' => $user_id,
                    'exam_id'          => $exam_id,
                    'session_id'       => $year_id,
                    'class_id'         => $class_id,
                    'subject_id'       => $subject_id,
                    'student_id'       => $student_id,
                    'enrollment_id'    => $enrollment_id,
                    'distribution_id'  => $distribution->id,
                    'obtained_mark'    => $obtained_mark,
                    'full_mark'        => $fullMark,
                    'is_absent'        => $is_absent,
                    'remarks'          => $remarks,
                    'created_by'       => $user_id,
                    'updated_by'       => $user_id,
                ];

                if ($existing) {
                    $this->MarkModel->update($existing->id, $saveData);
                } else {
                    $saveData['token'] = $this->generateToken();
                    $this->MarkModel->insert($saveData);
                }
                $saved++;
            }
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => "{$saved} mark(s) imported successfully.",
            'saved'   => $saved,
            'errors'  => $errors,
        ]);
    }

    protected function getDistributionsBySubjectData(int $subject_id): array
    {
        $distributions = $this->MarkDistributionModel
            ->select('examination_mark_distributions.*, examination_subject_distributions.full_mark, examination_subject_distributions.pass_mark, examination_subject_distributions.weight_percent, examination_subject_distributions.sort_order')
            ->join('examination_subject_distributions', 'examination_subject_distributions.distribution_id = examination_mark_distributions.id', 'left')
            ->where('examination_subject_distributions.subject_id', $subject_id)
            ->where('examination_mark_distributions.status', 1)
            ->orderBy('examination_subject_distributions.sort_order', 'ASC')
            ->orderBy('examination_mark_distributions.name', 'ASC')
            ->findAll();

        $list = [];
        foreach ($distributions as $d) {
            $list[] = [
                'id'        => $d->id,
                'name'      => $d->name,
                'code'      => $d->code,
                'full_mark' => (float) ($d->full_mark ?? 0),
                'pass_mark' => (float) ($d->pass_mark ?? 0),
                'weight'    => (float) ($d->weight_percent ?? 0),
                'sort'      => (int) ($d->sort_order ?? 0),
            ];
        }
        return $list;
    }

    /**
     * Get enrollment IDs of students assigned to an optional subject
     */
    protected function getOptionalSubjectEnrollmentIds(int $school_id, int $subject_id): array
    {
        $assignments = $this->StudentSubjectModel
            ->select('enrollment_id')
            ->where('school_id', $school_id)
            ->where('optional_subject_id', $subject_id)
            ->findAll();

        $enrollmentIds = [];
        foreach ($assignments as $assignment) {
            $enrollmentIds[] = (int) $assignment->enrollment_id;
        }
        return $enrollmentIds;
    }

    protected function parseCsvFile($file): array
    {
        $rows = [];
        if (($handle = fopen($file->getTempName(), 'r')) !== false) {
            $headers = fgetcsv($handle);
            if ($headers) {
                $headers = array_map('trim', $headers);
                while (($data = fgetcsv($handle)) !== false) {
                    $row = [];
                    foreach ($headers as $idx => $header) {
                        $row[$header] = isset($data[$idx]) ? trim($data[$idx]) : '';
                    }
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    protected function parseExcelFile($file): array
    {
        // Use PhpSpreadsheet if available
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            if (empty($data)) {
                return [];
            }

            $headers = array_map('trim', $data[0]);
            $rows = [];
            for ($i = 1; $i < count($data); $i++) {
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = isset($data[$i][$idx]) ? trim((string) $data[$i][$idx]) : '';
                }
                $rows[] = $row;
            }
            return $rows;
        }

        // Fallback: try to read as CSV
        return $this->parseCsvFile($file);
    }

    protected function parseJsonFile($file): array
    {
        $content = file_get_contents($file->getTempName());
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        // If the JSON has a 'data' key (from our download format), extract it
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return $data;
    }

    public function download_real_data_csv()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $subject_id = (int) $this->request->getGet('subject_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');

        // Get exam and subject names for filename
        $examName = 'exam';
        $subjectName = 'subject';
        
        if ($exam_id) {
            $exam = $this->ExamModel->find($exam_id);
            if ($exam) {
                $examName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->title);
            }
        }
        
        if ($subject_id) {
            $subject = $this->SubjectModel->find($subject_id);
            if ($subject) {
                $subjectName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subject->title);
            }
        }

        // Include DateTime in filename for uniqueness
        $now = new DateTime();
        $filename = "marks_data_for_{$examName}_{$subjectName}_{$now->format('Y-m-d_H-i-s')}.csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Standard columns: Roll, Student Name, Absent
        $columns = ['Roll', 'Student Name', 'Absent'];
        $distributions = [];

        if ($subject_id) {
            $distributions = $this->getDistributionsBySubjectData($subject_id);
            foreach ($distributions as $dist) {
                $columns[] = $dist['code'];
            }
        }

        $output = fopen('php://output', 'w');
        fputcsv($output, $columns);

        // Get actual students with their marks if filters are provided
        if ($school_id && $class_id && $subject_id) {
            $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1);

            if ($year_id) {
                $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
            }

            // Check if the selected subject is an optional subject
            $subject = $this->SubjectModel->find($subject_id);
            $isOptionalSubject = $subject && !empty($subject->optional);

            // If optional subject, filter to only students assigned to this optional subject
            if ($isOptionalSubject) {
                $optionalEnrollmentIds = $this->getOptionalSubjectEnrollmentIds($school_id, $subject_id);
                if (!empty($optionalEnrollmentIds)) {
                    $this->EnrollmentModel->whereIn('student_enrollments.id', $optionalEnrollmentIds);
                } else {
                    // No students assigned, output just headers
                    fclose($output);
                    exit;
                }
            }

            $enrollments = $this->EnrollmentModel
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->orderBy('students.first_name', 'ASC')
                ->findAll();

            foreach ($enrollments as $enr) {
                $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
                $studentName = preg_replace('/\s+/', ' ', $studentName);

                // Get existing marks for this student
                $existingMarks = $this->MarkModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject_id)
                    ->where('student_id', $enr->student_id)
                    ->findAll();

                $markMap = [];
                $isAbsent = 'No';
                foreach ($existingMarks as $mark) {
                    $dist = $this->MarkDistributionModel->find($mark->distribution_id);
                    if ($dist) {
                        $markMap[$dist->code] = $mark->obtained_mark;
                    }
                    if ($mark->is_absent) {
                        $isAbsent = 'Yes';
                    }
                }

                $row = [
                    $enr->roll_no,
                    $studentName,
                    $isAbsent,
                ];

                // Add existing marks
                if (!empty($distributions)) {
                    foreach ($distributions as $dist) {
                        $row[] = $markMap[$dist['code']] ?? '';
                    }
                }

                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    public function download_real_data_json()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $subject_id = (int) $this->request->getGet('subject_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');

        // Get exam and subject names for filename
        $examName = 'exam';
        $subjectName = 'subject';
        
        if ($exam_id) {
            $exam = $this->ExamModel->find($exam_id);
            if ($exam) {
                $examName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->title);
            }
        }
        
        if ($subject_id) {
            $subject = $this->SubjectModel->find($subject_id);
            if ($subject) {
                $subjectName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subject->title);
            }
        }

        // Include DateTime in filename for uniqueness
        $now = new DateTime();
        $filename = "marks_data_for_{$examName}_{$subjectName}_{$now->format('Y-m-d_H-i-s')}.json";
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Standard columns: Roll, Student Name, Absent
        $columns = ['Roll', 'Student Name', 'Absent'];
        $distributions = [];

        if ($subject_id) {
            $distributions = $this->getDistributionsBySubjectData($subject_id);
            foreach ($distributions as $dist) {
                $columns[] = $dist['code'];
            }
        }

        $sampleData = [
            'school_id' => $school_id,
            'class_id' => $class_id,
            'subject_id' => $subject_id,
            'exam_id' => $exam_id,
            'columns' => $columns,
            'data' => []
        ];

        // Get actual students with their marks if filters are provided
        if ($school_id && $class_id && $subject_id) {
            $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1);

            if ($year_id) {
                $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
            }

            // Check if the selected subject is an optional subject
            $subject = $this->SubjectModel->find($subject_id);
            $isOptionalSubject = $subject && !empty($subject->optional);

            // If optional subject, filter to only students assigned to this optional subject
            if ($isOptionalSubject) {
                $optionalEnrollmentIds = $this->getOptionalSubjectEnrollmentIds($school_id, $subject_id);
                if (!empty($optionalEnrollmentIds)) {
                    $this->EnrollmentModel->whereIn('student_enrollments.id', $optionalEnrollmentIds);
                } else {
                    // No students assigned, output just headers
                    echo json_encode($sampleData, JSON_PRETTY_PRINT);
                    exit;
                }
            }

            $enrollments = $this->EnrollmentModel
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->orderBy('students.first_name', 'ASC')
                ->findAll();

            foreach ($enrollments as $enr) {
                $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
                $studentName = preg_replace('/\s+/', ' ', $studentName);

                // Get existing marks for this student
                $existingMarks = $this->MarkModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject_id)
                    ->where('student_id', $enr->student_id)
                    ->findAll();

                $markMap = [];
                $isAbsent = 'No';
                foreach ($existingMarks as $mark) {
                    $dist = $this->MarkDistributionModel->find($mark->distribution_id);
                    if ($dist) {
                        $markMap[$dist->code] = $mark->obtained_mark;
                    }
                    if ($mark->is_absent) {
                        $isAbsent = 'Yes';
                    }
                }

                $row = [
                    'Roll' => $enr->roll_no,
                    'Student Name' => $studentName,
                    'Absent' => $isAbsent,
                ];

                // Add existing marks
                if (!empty($distributions)) {
                    foreach ($distributions as $dist) {
                        $row[$dist['code']] = $markMap[$dist['code']] ?? '';
                    }
                }

                $sampleData['data'][] = $row;
            }
        }

        echo json_encode($sampleData, JSON_PRETTY_PRINT);
        exit;
    }

    public function download_sample_data_csv()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $subject_id = (int) $this->request->getGet('subject_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');

        // Get exam and subject names for filename
        $examName = 'exam';
        $subjectName = 'subject';
        
        if ($exam_id) {
            $exam = $this->ExamModel->find($exam_id);
            if ($exam) {
                $examName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->title);
            }
        }
        
        if ($subject_id) {
            $subject = $this->SubjectModel->find($subject_id);
            if ($subject) {
                $subjectName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subject->title);
            }
        }

        // Include DateTime in filename for uniqueness
        $now = new DateTime();
        $filename = "marks_data_sample_for_{$examName}_{$subjectName}_{$now->format('Y-m-d_H-i-s')}.csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Standard columns: Roll, Student Name, Absent
        $columns = ['Roll', 'Student Name', 'Absent'];
        $distributions = [];

        if ($subject_id) {
            $distributions = $this->getDistributionsBySubjectData($subject_id);
            foreach ($distributions as $dist) {
                $columns[] = $dist['code'];
            }
        }

        $output = fopen('php://output', 'w');
        fputcsv($output, $columns);

        // Get actual students if filters are provided
        if ($school_id && $class_id) {
            $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1);

            if ($year_id) {
                $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
            }

            $enrollments = $this->EnrollmentModel
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->orderBy('students.first_name', 'ASC')
                ->findAll();

            foreach ($enrollments as $enr) {
                $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
                $studentName = preg_replace('/\s+/', ' ', $studentName);

                $row = [
                    $enr->roll_no,
                    $studentName,
                    'No',
                ];

                // Add empty mark columns
                if (!empty($distributions)) {
                    foreach ($distributions as $dist) {
                        $row[] = '';
                    }
                }

                fputcsv($output, $row);
            }
        } else {
            // Sample data row
            $sampleRow = ['1', 'John Doe', 'No'];
            if (!empty($distributions)) {
                foreach ($distributions as $dist) {
                    $sampleRow[] = '';
                }
            }
            fputcsv($output, $sampleRow);
        }

        fclose($output);
        exit;
    }

    public function download_sample_data_json()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $subject_id = (int) $this->request->getGet('subject_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');

        // Get exam and subject names for filename
        $examName = 'exam';
        $subjectName = 'subject';
        
        if ($exam_id) {
            $exam = $this->ExamModel->find($exam_id);
            if ($exam) {
                $examName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->title);
            }
        }
        
        if ($subject_id) {
            $subject = $this->SubjectModel->find($subject_id);
            if ($subject) {
                $subjectName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subject->title);
            }
        }

        // Include DateTime in filename for uniqueness
        $now = new DateTime();
        $filename = "marks_data_sample_for_{$examName}_{$subjectName}_{$now->format('Y-m-d_H-i-s')}.json";
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Standard columns: Roll, Student Name, Absent
        $columns = ['Roll', 'Student Name', 'Absent'];
        $distributions = [];

        if ($subject_id) {
            $distributions = $this->getDistributionsBySubjectData($subject_id);
            foreach ($distributions as $dist) {
                $columns[] = $dist['code'];
            }
        }

        $sampleData = [
            'school_id' => $school_id,
            'class_id' => $class_id,
            'subject_id' => $subject_id,
            'exam_id' => $exam_id,
            'columns' => $columns,
            'data' => []
        ];

        // Get actual students if filters are provided
        if ($school_id && $class_id) {
            $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1);

            if ($year_id) {
                $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
            }

            $enrollments = $this->EnrollmentModel
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->orderBy('students.first_name', 'ASC')
                ->findAll();

            foreach ($enrollments as $enr) {
                $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
                $studentName = preg_replace('/\s+/', ' ', $studentName);

                $row = [
                    'Roll' => $enr->roll_no,
                    'Student Name' => $studentName,
                    'Absent' => 'No',
                ];

                // Add empty mark columns
                if (!empty($distributions)) {
                    foreach ($distributions as $dist) {
                        $row[$dist['code']] = '';
                    }
                }

                $sampleData['data'][] = $row;
            }
        }

        echo json_encode($sampleData, JSON_PRETTY_PRINT);
        exit;
    }

    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList       = $this->getActiveOptions($school_id, 'YearModel');
        $classList      = $this->getActiveOptions($school_id, 'ClassModel');
        $subjectList    = $this->getActiveOptions($school_id, 'SubjectModel');
        $examList       = $this->getActiveOptions($school_id, 'ExamModel');

        return $this->jsonResponse([
            'status'       => true,
            'year_list'    => $yearList,
            'class_list'   => $classList,
            'subject_list' => $subjectList,
            'exam_list'    => $examList,
        ]);
    }
}