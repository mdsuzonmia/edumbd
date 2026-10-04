<?php

namespace App\Modules\examination\Controllers;

use App\Models\AcademicsSectionModel;
use App\Models\AcademicsCategoryModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\ResultWizardProgressModel;
use App\Modules\examination\Models\SubjectDistributionModel;
use App\Services\ServicePricingService;

class ResultWizardController extends BaseController
{
    private ResultWizardProgressModel $progressModel;
    private SubjectDistributionModel $distributionModel;
    private MarkDistributionModel $markDistributionModel;

    public function __construct()
    {
        parent::__construct();
        $this->progressModel = new ResultWizardProgressModel();
        $this->distributionModel = new SubjectDistributionModel();
        $this->markDistributionModel = new MarkDistributionModel();
    }

    public function index(int $step = 0)
    {
        $requestedStep = $step;
        $step = max(1, min(6, $step ?: 1));

        if (!session()->get('logged_in')) {
            $embedded = $this->request->getGet('embed') === '1';
            $requestedLocale = (string) $this->request->getGet('lang');
            if ($embedded && in_array($requestedLocale, config('App')->supportedLocales, true)) {
                session()->set(['app_locale' => $requestedLocale, 'locale_version' => 2]);
                $this->request->setLocale($requestedLocale);
                service('language')->setLocale($requestedLocale);
            }
            $guestView = view('App\Modules\examination\Views\result_wizard_guest', [
                'step' => $step,
                'draft' => (array) session()->get('result_wizard_guest'),
                'embedded' => $embedded,
            ]);
            return $guestView;
        }

        if (session()->get('role') !== 'school-owner') {
            return redirect()->to('/unauthorized')
                ->with('error', 'Only school owners can create result sheets.');
        }

        [$schoolId, $userId] = $this->identity();
        $this->importGuestDraft($schoolId, $userId);
        $progress = $this->progress($schoolId, $userId);
        $step = $requestedStep ?: (int) $progress->current_step;
        $step = max(1, min(6, $step));
        $yearId = $this->ensureDefaults($schoolId, $userId, $progress);
        $subjectIds = json_decode((string) $progress->subject_ids, true) ?: [];

        $enrollments = [];
        if ($progress->class_id) {
            $enrollments = $this->EnrollmentModel
                ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name')
                ->join('students', 'students.id=student_enrollments.student_id')
                ->where('student_enrollments.school_id', $schoolId)
                ->where('student_enrollments.class_id', $progress->class_id)
                ->where('student_enrollments.session_id', $yearId)
                ->where('students.status', 1)->orderBy('student_enrollments.roll_no', 'ASC')->findAll();
        }

        $subjects = empty($subjectIds) ? [] : $this->SubjectModel->whereIn('id', $subjectIds)->where('school_id', $schoolId)->findAll();
        $missing = 0;
        foreach ($enrollments as $enrollment) {
            foreach ($subjects as $subject) {
                if (!$this->MarkModel->where(['school_id' => $schoolId, 'exam_id' => $progress->exam_id, 'class_id' => $progress->class_id, 'student_id' => $enrollment->student_id, 'subject_id' => $subject->id])->first()) {
                    $missing++;
                }
            }
        }
        $hasResults = $progress->exam_id && $progress->class_id
            ? $this->ExamResultModel->where(['school_id'=>$schoolId,'exam_id'=>$progress->exam_id,'class_id'=>$progress->class_id,'session_id'=>$yearId])->countAllResults() > 0
            : false;
        $pricing = null; $order = null; $isPaid = false;
        if ($progress->exam_id && count($enrollments) > 0) {
            $pricingService = new ServicePricingService();
            $serviceMode = $progress->service_mode ?? ServicePricingService::SELF_SERVICE;
            $pricing = $pricingService->quote(ServicePricingService::RESULT, count($enrollments), $serviceMode);
            $order = $pricingService->createOrUpdateOrder($schoolId, $userId, ServicePricingService::RESULT, count($enrollments), (int)$progress->exam_id, 'examination_exam', (int)$progress->exam_id, $serviceMode);
            $isPaid = in_array($order->status, ['paid','completed'], true);
            $dataDemoLimit = $pricingService->demoLimit();
        }

        $data = [
            'step' => $step, 'progress' => $progress, 'year_id' => $yearId,
            'classes' => $this->ClassModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll(),
            'sections' => $this->SectionModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll(),
            'categories' => (new AcademicsCategoryModel())->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll(),
            'exams' => $this->ExamModel->where('school_id', $schoolId)->where('year_id', $yearId)->where('status', 1)->orderBy('id', 'DESC')->findAll(),
            'all_subjects' => $this->SubjectModel->where('school_id', $schoolId)->where('status', 1)->orderBy('order_number')->findAll(),
            'subject_ids' => array_map('intval', $subjectIds), 'subjects' => $subjects, 'enrollments' => $enrollments,
            'missing_marks' => $missing,
            'pricing' => $pricing, 'order' => $order, 'is_paid' => $isPaid, 'demo_limit' => $dataDemoLimit ?? 20,
            'has_results' => $hasResults,
        ];
        return view('header', ['page_title' => lang('ResultWizard.page_title'), 'body_class' => 'nav-md', 'admin_area' => 'yes'])
            . view('App\Modules\examination\Views\result_wizard', $data)
            . view('footer', ['admin_area' => 'yes']);
    }

    public function saveGuestDraft(int $step)
    {
        if (session()->get('logged_in')) return redirect()->to('examination/result-wizard');
        $requestedLocale = (string) $this->request->getPost('lang');
        if (in_array($requestedLocale, config('App')->supportedLocales, true)) {
            session()->set(['app_locale' => $requestedLocale, 'locale_version' => 2]);
            $this->request->setLocale($requestedLocale);
            service('language')->setLocale($requestedLocale);
        }
        $step = max(1, min(5, $step));
        $draft = (array) session()->get('result_wizard_guest');
        if ($step === 1) {
            foreach (['school_name','academic_year','class_name','section_name','category_name'] as $field) $draft[$field] = trim((string)$this->request->getPost($field));
            if ($draft['school_name'] === '' || $draft['academic_year'] === '' || $draft['class_name'] === '') return redirect()->back()->withInput()->with('error', $this->guestMessage('school_required'));
        } elseif ($step === 2) {
            $draft['exam_name'] = trim((string)$this->request->getPost('exam_name'));
            if ($draft['exam_name'] === '') return redirect()->back()->withInput()->with('error', $this->guestMessage('exam_required'));
        } elseif ($step === 3) {
            $draft['subjects_text'] = trim((string)$this->request->getPost('subjects_text'));
            $draft['subjects'] = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $draft['subjects_text']) ?: []))));
            if (!$draft['subjects']) return redirect()->back()->withInput()->with('error', $this->guestMessage('subject_required'));
        } elseif ($step === 4) {
            $draft['students_text'] = trim((string)$this->request->getPost('students_text')); $students = [];
            foreach (preg_split('/\r\n|\r|\n/', $draft['students_text']) ?: [] as $line) { $p=array_map('trim',str_getcsv($line)); if(($p[0]??'')!==''&&($p[1]??'')!=='') $students[]=['name'=>$p[0],'roll'=>$p[1],'section'=>$p[2]??($draft['section_name']??'General'),'category'=>$p[3]??($draft['category_name']??'General')]; }
            if (!$students) return redirect()->back()->withInput()->with('error', $this->guestMessage('student_required'));
            $draft['students']=$students;
        } else {
            $marks=(array)$this->request->getPost('marks');
            foreach(($draft['students']??[]) as $si=>$_) foreach(($draft['subjects']??[]) as $sj=>$_s){$v=$marks[$si][$sj]??'';if($v===''||!is_numeric($v)||(float)$v<0||(float)$v>100)return redirect()->back()->withInput()->with('error',$this->guestMessage('marks_invalid'));}
            $draft['marks']=$marks;
        }
        session()->set('result_wizard_guest',$draft);
        $next = 'examination/result-wizard/step/'.($step+1);
        if ($this->request->getPost('embed') === '1') {
            $next .= '?embed=1';
            if (in_array($requestedLocale, config('App')->supportedLocales, true)) $next .= '&lang=' . $requestedLocale;
        }
        return redirect()->to($next);
    }

    private function guestMessage(string $key): string
    {
        $messages = [
            'en' => ['school_required'=>'School name, academic year/session, and class name are required.','exam_required'=>'Exam name is required.','subject_required'=>'Add at least one subject.','student_required'=>'Add at least one student with a name and roll number.','marks_invalid'=>'Every mark must be between 0 and 100.'],
            'bn' => ['school_required'=>'স্কুলের নাম, শিক্ষাবর্ষ/সেশন এবং শ্রেণির নাম আবশ্যক।','exam_required'=>'পরীক্ষার নাম আবশ্যক।','subject_required'=>'কমপক্ষে একটি বিষয় যোগ করুন।','student_required'=>'নাম ও রোলসহ কমপক্ষে একজন শিক্ষার্থী যোগ করুন।','marks_invalid'=>'প্রতিটি নম্বর ০ থেকে ১০০-এর মধ্যে হতে হবে।'],
        ];
        $locale = service('language')->getLocale();
        return $messages[$locale][$key] ?? $messages['en'][$key];
    }

    private function importGuestDraft(int $schoolId, int $userId): void
    {
        $draft=(array)session()->get('result_wizard_guest');
        if(empty($draft['marks'])||empty($draft['students'])||empty($draft['subjects']))return;
        $p=$this->progress($schoolId,$userId);$this->ensureDefaults($schoolId,$userId,$p);
        $yearTitle=trim((string)($draft['academic_year']??date('Y')));
        $year=$this->YearModel->where(['school_id'=>$schoolId,'title'=>$yearTitle])->first();
        if(!$year){$yearId=(int)$this->YearModel->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$yearTitle,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);}else{$yearId=(int)$year->id;}
        $this->progressModel->update($p->id,['year_id'=>$yearId]);$p->year_id=$yearId;
        $classId=(int)$this->ClassModel->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$draft['class_name'],'school_id'=>$schoolId,'status'=>1,'school_owner_uid'=>$userId,'created_by'=>$userId]);
        $examId=(int)$this->ExamModel->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$draft['exam_name'],'year_id'=>$yearId,'school_id'=>$schoolId,'status'=>1,'school_owner_uid'=>$userId,'created_by'=>$userId,'is_aggregate_result'=>1]);
        $subjectIds=[];foreach($draft['subjects'] as $title)$subjectIds[]=$this->createBasicSubject($title,$schoolId,$userId);
        $this->progressModel->update($p->id,['class_id'=>$classId,'exam_id'=>$examId,'subject_ids'=>json_encode($subjectIds),'current_step'=>6]);$p=$this->progressModel->find($p->id);
        foreach($draft['students'] as $student)$this->createStudent($student['name'],$student['roll'],$student['section'],$student['category'],$schoolId,$userId,$p);
        $matrix=[];foreach($draft['students'] as $si=>$student){$en=$this->EnrollmentModel->where(['school_id'=>$schoolId,'class_id'=>$classId,'session_id'=>$yearId,'roll_no'=>$student['roll']])->first();if(!$en)continue;foreach($subjectIds as $sj=>$subjectId)$matrix[$en->student_id][$subjectId]=$draft['marks'][$si][$sj];}
        $this->saveMarks($matrix,$schoolId,$userId,$p);session()->remove('result_wizard_guest');session()->setFlashdata('success','Your guest result data has been saved to your school account.');
    }

    public function save(int $step)
    {
        [$schoolId, $userId] = $this->identity();
        $p = $this->progress($schoolId, $userId);
        $yearId = $this->ensureDefaults($schoolId, $userId, $p);
        $update = ['current_step' => min(6, $step + 1)];

        if ($step === 1) {
            $classId = (int) $this->request->getPost('class_id');
            $title = trim((string) $this->request->getPost('new_class'));
            if (!$classId && $title !== '') {
                $classId = (int) $this->ClassModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => $title, 'school_id' => $schoolId, 'status' => 1, 'school_owner_uid' => $userId, 'created_by' => $userId]);
            }
            if (!$classId) return redirect()->back()->with('error', lang('ResultWizard.select_class_error'));
            $sectionId = (int) $this->request->getPost('section_id');
            $categoryId = (int) $this->request->getPost('category_id');
            if (!$sectionId || !$categoryId) return redirect()->back()->with('error', 'Please select a section and category.');
            $update['class_id'] = $classId;
            $update['section_id'] = $sectionId;
            $update['category_id'] = $categoryId;
        } elseif ($step === 2) {
            $examId = (int) $this->request->getPost('exam_id');
            $title = trim((string) $this->request->getPost('new_exam'));
            if (!$examId && $title !== '') {
                $examId = (int) $this->ExamModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => $title, 'year_id' => $yearId, 'school_id' => $schoolId, 'status' => 1, 'school_owner_uid' => $userId, 'created_by' => $userId, 'is_aggregate_result' => 1]);
            }
            if (!$examId) return redirect()->back()->with('error', lang('ResultWizard.select_exam_error'));
            $update['exam_id'] = $examId;
        } elseif ($step === 3) {
            $ids = array_map('intval', (array) $this->request->getPost('subject_ids'));
            $newSubjects = preg_split('/\r\n|\r|\n|,/', (string) $this->request->getPost('new_subjects')) ?: [];
            foreach ($newSubjects as $title) {
                $title = trim($title); if ($title === '') continue;
                $ids[] = $this->createBasicSubject($title, $schoolId, $userId);
            }
            $ids = array_values(array_unique(array_filter($ids)));
            if (!$ids) return redirect()->back()->with('error', lang('ResultWizard.select_subject_error'));
            foreach ($ids as $subjectId) $this->ensureSubjectDistribution((int) $subjectId, $schoolId, $userId);
            $enrollments = $this->EnrollmentModel->where(['school_id'=>$schoolId,'class_id'=>$p->class_id,'session_id'=>$yearId])->findAll();
            foreach ($enrollments as $enrollment) foreach ($ids as $subjectId) {
                if (!$this->StudentSubjectModel->where(['school_id'=>$schoolId,'enrollment_id'=>$enrollment->id,'subject_id'=>$subjectId])->first()) {
                    $this->StudentSubjectModel->insert(['school_id'=>$schoolId,'enrollment_id'=>$enrollment->id,'subject_id'=>$subjectId,'created_at'=>date('Y-m-d H:i:s')]);
                }
            }
            $update['subject_ids'] = json_encode($ids);
        } elseif ($step === 4) {
            $rows = preg_split('/\r\n|\r|\n/', trim((string) $this->request->getPost('manual_students'))) ?: [];
            foreach ($rows as $row) {
                $parts = array_map('trim', str_getcsv($row));
                if (($parts[0] ?? '') !== '') $this->createStudent($parts[0], $parts[1] ?? '', $parts[2] ?? '', $parts[3] ?? '', $schoolId, $userId, $p);
            }
        } elseif ($step === 5) {
            $this->saveMarks((array) $this->request->getPost('marks'), $schoolId, $userId, $p);
        } elseif ($step === 6) {
            $mode = strtoupper((string) $this->request->getPost('service_mode'));
            if (!in_array($mode, [ServicePricingService::SELF_SERVICE, ServicePricingService::MANAGED_SERVICE], true)) {
                return redirect()->back()->with('error', 'Please select a valid result service.');
            }
            $update['service_mode'] = $mode;
        }

        $this->progressModel->update($p->id, $update);
        return redirect()->to('examination/result-wizard/step/' . $update['current_step'])->with('success', lang('ResultWizard.step_saved'));
    }

    public function studentTemplate()
    {
        return $this->csvDownload('students-template.csv', [
            [lang('ResultWizard.student_name'), lang('ResultWizard.roll'), 'Section', 'Category'],
            [lang('ResultWizard.sample_student_1'), '1', 'A', 'Regular'],
            [lang('ResultWizard.sample_student_2'), '2', 'A', 'Regular'],
        ]);
    }

    public function studentPreview()
    {
        [$schoolId, $userId] = $this->identity(); $p = $this->progress($schoolId, $userId);
        $parsed = $this->parseCsvUpload('student_file', [lang('ResultWizard.student_name'), lang('ResultWizard.roll'), 'Section', 'Category']);
        if ($parsed['errors']) return redirect()->back()->with('error', implode(' ', $parsed['errors']));
        $this->progressModel->update($p->id, ['student_preview' => json_encode($parsed['rows'], JSON_UNESCAPED_UNICODE)]);
        return redirect()->to('examination/result-wizard/step/4')->with('success', lang('ResultWizard.preview_ready'));
    }

    public function studentImport()
    {
        [$schoolId, $userId] = $this->identity(); $p = $this->progress($schoolId, $userId);
        $rows = json_decode((string) $p->student_preview, true) ?: [];
        foreach ($rows as $row) $this->createStudent($row[0], $row[1] ?? '', $row[2] ?? '', $row[3] ?? '', $schoolId, $userId, $p);
        $this->progressModel->update($p->id, ['student_preview' => null, 'current_step' => 5]);
        return redirect()->to('examination/result-wizard/step/5')->with('success', lang('ResultWizard.students_saved', [count($rows)]));
    }

    public function marksTemplate()
    {
        [$schoolId, $userId] = $this->identity(); $p = $this->progress($schoolId, $userId);
        $ids = json_decode((string) $p->subject_ids, true) ?: [];
        $subjects = $ids ? $this->SubjectModel->whereIn('id', $ids)->findAll() : [];
        $students = $this->EnrollmentModel->select('student_enrollments.roll_no, students.first_name, students.middle_name, students.last_name')->join('students', 'students.id=student_enrollments.student_id')->where(['student_enrollments.school_id' => $schoolId, 'student_enrollments.class_id' => $p->class_id, 'student_enrollments.session_id' => $p->year_id])->findAll();
        $header = [lang('ResultWizard.roll'), lang('ResultWizard.student_name')]; foreach ($subjects as $s) $header[] = $s->title;
        $rows = [$header]; foreach ($students as $s) $rows[] = array_merge([$s->roll_no, trim($s->first_name . ' ' . $s->middle_name . ' ' . $s->last_name)], array_fill(0, count($subjects), ''));
        return $this->csvDownload('marks-template.csv', $rows);
    }

    public function marksImport()
    {
        [$schoolId, $userId] = $this->identity(); $p = $this->progress($schoolId, $userId);
        $parsed = $this->parseCsvUpload('marks_file', [lang('ResultWizard.roll'), lang('ResultWizard.student_name')]);
        if ($parsed['errors']) return redirect()->back()->with('error', implode(' ', $parsed['errors']));
        $ids = json_decode((string) $p->subject_ids, true) ?: [];
        $matrix = []; $errors = [];
        foreach ($parsed['rows'] as $line => $row) {
            $enrollment = $this->EnrollmentModel->where(['school_id' => $schoolId, 'class_id' => $p->class_id, 'session_id' => $p->year_id, 'roll_no' => $row[0]])->first();
            if (!$enrollment) { $errors[] = lang('ResultWizard.roll_not_found', [$line + 2, $row[0]]); continue; }
            foreach ($ids as $i => $subjectId) {
                $value = trim((string) ($row[$i + 2] ?? ''));
                if ($value === '' || !is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                    $errors[] = lang('ResultWizard.invalid_mark', [$line + 2]);
                } else $matrix[$enrollment->student_id][$subjectId] = $value;
            }
        }
        if ($errors) return redirect()->back()->with('error', implode(' ', array_slice($errors, 0, 10)));
        $this->saveMarks($matrix, $schoolId, $userId, $p);
        $this->progressModel->update($p->id, ['current_step' => 6]);
        return redirect()->to('examination/result-wizard/step/6')->with('success', lang('ResultWizard.marks_saved'));
    }

    public function studentResults()
    {
        [$schoolId, $userId] = $this->identity();
        $p = $this->progress($schoolId, $userId);
        if (!$p->class_id || !$p->exam_id || !$p->year_id) {
            return redirect()->to('examination/result-wizard')->with('error', 'Complete the result wizard first.');
        }

        $students = $this->EnrollmentModel
            ->select('student_enrollments.roll_no, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, examination_results.token AS result_token, examination_results.result_status')
            ->join('students', 'students.id = student_enrollments.student_id')
            ->join('examination_results', 'examination_results.student_id = students.id AND examination_results.school_id = ' . $schoolId . ' AND examination_results.exam_id = ' . (int)$p->exam_id . ' AND examination_results.class_id = ' . (int)$p->class_id . ' AND examination_results.session_id = ' . (int)$p->year_id, 'left')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.class_id', $p->class_id)
            ->where('student_enrollments.session_id', $p->year_id)
            ->where('students.status', 1)
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->findAll();

        $data = [
            'students' => $students,
            'class' => $this->ClassModel->find($p->class_id),
            'exam' => $this->ExamModel->find($p->exam_id),
            'year' => $this->YearModel->find($p->year_id),
        ];
        return view('header', ['page_title'=>'Student Results','body_class'=>'nav-md','admin_area'=>'yes'])
            . view('App\Modules\examination\Views\result_wizard_students', $data)
            . view('footer', ['admin_area'=>'yes']);
    }

    private function identity(): array { return [(int) session('school_id'), (int) session('user_id')]; }
    private function progress(int $schoolId, int $userId): object
    {
        $p = $this->progressModel->where(['school_id' => $schoolId, 'user_id' => $userId, 'completed_at' => null])->orderBy('id', 'DESC')->first();
        if (!$p) { $id = $this->progressModel->insert(['token' => bin2hex(random_bytes(20)), 'school_id' => $schoolId, 'user_id' => $userId, 'current_step' => 1]); $p = $this->progressModel->find($id); }
        return $p;
    }

    private function ensureDefaults(int $schoolId, int $userId, object $p): int
    {
        $year = $p->year_id ? $this->YearModel->find($p->year_id) : $this->YearModel->where(['school_id' => $schoolId, 'status' => 1])->like('title', date('Y'))->first();
        if (!$year) { $id = $this->YearModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => date('Y'), 'school_id' => $schoolId, 'status' => 1, 'created_by' => $userId, 'school_owner_uid' => $userId]); $year = $this->YearModel->find($id); }
        $sectionModel = new AcademicsSectionModel(); $section = $p->section_id ? $sectionModel->find($p->section_id) : $sectionModel->where(['school_id' => $schoolId, 'status' => 1])->first();
        if (!$section) { $id = $sectionModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => 'General', 'school_id' => $schoolId, 'status' => 1, 'created_by' => $userId, 'school_owner_uid' => $userId]); $section = $sectionModel->find($id); }
        $categoryModel = new AcademicsCategoryModel(); $category = $p->category_id ? $categoryModel->find($p->category_id) : $categoryModel->where(['school_id' => $schoolId, 'status' => 1])->first();
        if (!$category) { $id = $categoryModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => 'General', 'school_id' => $schoolId, 'status' => 1, 'created_by' => $userId, 'school_owner_uid' => $userId]); $category = $categoryModel->find($id); }
        $school = $this->SchoolModel->find($schoolId);
        $params = $school && $school->params ? (json_decode($school->params, true) ?: []) : [];
        $params['academic_class_roll_enabled'] = 1; $params['academic_section_enabled'] = 1; $params['academic_category_enabled'] = 1;
        if ($school) $this->SchoolModel->update($schoolId, ['params' => json_encode($params)]);
        $this->ensureGrade($schoolId, $userId);
        $this->progressModel->update($p->id, ['year_id' => $year->id, 'section_id' => $section->id, 'category_id' => $category->id]); $p->year_id = $year->id; $p->section_id = $section->id; $p->category_id = $category->id;
        return (int) $year->id;
    }

    private function ensureGrade(int $schoolId, int $userId): int
    {
        $g = $this->GradeSystemModel->where(['school_id' => $schoolId, 'status' => 1])->first(); if ($g) return (int) $g->id;
        $id = $this->GradeSystemModel->insert(['token' => bin2hex(random_bytes(16)), 'title' => 'General Grading (100)', 'total_mark' => 100, 'school_id' => $schoolId, 'status' => 1, 'created_by' => $userId, 'school_owner_uid' => $userId]);
        foreach ([['A+',5,80,100],['A',4,70,79.99],['A-',3.5,60,69.99],['B',3,50,59.99],['C',2,40,49.99],['D',1,33,39.99],['F',0,0,32.99]] as $i=>$r) $this->GradeRulesModel->insert(['grade_system_id'=>$id,'title'=>$r[0],'grade_point'=>$r[1],'mark_from'=>$r[2],'mark_to'=>$r[3],'field_order'=>$i+1,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
        return (int) $id;
    }

    private function createBasicSubject(string $title, int $schoolId, int $userId): int
    {
        $existing = $this->SubjectModel->where(['school_id'=>$schoolId,'title'=>$title,'status'=>1])->first(); if ($existing) return (int)$existing->id;
        $subjectId = (int)$this->SubjectModel->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$title,'short_title'=>$title,'grade_system_id'=>$this->ensureGrade($schoolId,$userId),'mark_calculation'=>1,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
        $dist = $this->markDistributionModel->where(['school_id'=>$schoolId,'code'=>'total'])->first();
        if (!$dist) { $id=$this->markDistributionModel->insert(['token'=>bin2hex(random_bytes(16)),'name'=>'Total Marks','code'=>'total','sort_order'=>1,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]); $dist=$this->markDistributionModel->find($id); }
        $this->distributionModel->insert(['token'=>bin2hex(random_bytes(16)),'subject_id'=>$subjectId,'distribution_id'=>$dist->id,'full_mark'=>100,'pass_mark'=>33,'weight_percent'=>100,'sort_order'=>1,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
        return $subjectId;
    }

    private function ensureSubjectDistribution(int $subjectId, int $schoolId, int $userId): void
    {
        if ($this->distributionModel->where(['school_id'=>$schoolId,'subject_id'=>$subjectId,'status'=>1])->first()) return;
        $dist=$this->markDistributionModel->where(['school_id'=>$schoolId,'code'=>'total'])->first();
        if(!$dist){$id=$this->markDistributionModel->insert(['token'=>bin2hex(random_bytes(16)),'name'=>'Total Marks','code'=>'total','sort_order'=>1,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);$dist=$this->markDistributionModel->find($id);}
        $this->distributionModel->insert(['token'=>bin2hex(random_bytes(16)),'subject_id'=>$subjectId,'distribution_id'=>$dist->id,'full_mark'=>100,'pass_mark'=>33,'weight_percent'=>100,'sort_order'=>1,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
    }

    private function createStudent(string $name, string $roll, string $sectionName, string $categoryName, int $schoolId, int $userId, object $p): void
    {
        $name=trim($name); $roll=trim($roll); if($name===''||$roll==='')return;
        $sectionId = $this->resolveSection($sectionName, $schoolId, $userId, (int) $p->section_id);
        $categoryId = $this->resolveCategory($categoryName, $schoolId, $userId, (int) $p->category_id);
        $existing=$this->EnrollmentModel->where(['school_id'=>$schoolId,'class_id'=>$p->class_id,'session_id'=>$p->year_id,'section_id'=>$sectionId,'roll_no'=>$roll])->first(); if($existing)return;
        $studentId=(int)$this->StudentModel->insert(['token'=>bin2hex(random_bytes(16)),'school_id'=>$schoolId,'school_owner_uid'=>$userId,'student_code'=>'S'.date('ymd').random_int(1000,9999),'first_name'=>$name,'status'=>1,'student_status'=>'active','created_by'=>$userId]);
        $enrollmentId=(int)$this->EnrollmentModel->insert(['school_id'=>$schoolId,'school_owner_uid'=>$userId,'student_id'=>$studentId,'session_id'=>$p->year_id,'class_id'=>$p->class_id,'section_id'=>$sectionId,'category_id'=>$categoryId,'roll_no'=>$roll,'status'=>1,'created_by'=>$userId]);
        foreach(json_decode((string)$p->subject_ids,true)?:[] as $sid) $this->StudentSubjectModel->insert(['school_id'=>$schoolId,'enrollment_id'=>$enrollmentId,'subject_id'=>$sid,'created_at'=>date('Y-m-d H:i:s')]);
    }

    private function resolveSection(string $title, int $schoolId, int $userId, int $fallbackId): int
    {
        $title = trim($title); if ($title === '') return $fallbackId;
        $model = new AcademicsSectionModel(); $item = $model->where(['school_id' => $schoolId, 'title' => $title])->first();
        if ($item) return (int) $item->id;
        return (int) $model->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$title,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
    }

    private function resolveCategory(string $title, int $schoolId, int $userId, int $fallbackId): int
    {
        $title = trim($title); if ($title === '') return $fallbackId;
        $model = new AcademicsCategoryModel(); $item = $model->where(['school_id' => $schoolId, 'title' => $title])->first();
        if ($item) return (int) $item->id;
        return (int) $model->insert(['token'=>bin2hex(random_bytes(16)),'title'=>$title,'school_id'=>$schoolId,'status'=>1,'created_by'=>$userId,'school_owner_uid'=>$userId]);
    }

    private function saveMarks(array $matrix, int $schoolId, int $userId, object $p): void
    {
        foreach($matrix as $studentId=>$subjectMarks){ $en=$this->EnrollmentModel->where(['student_id'=>(int)$studentId,'class_id'=>$p->class_id,'session_id'=>$p->year_id])->first(); if(!$en)continue;
            foreach($subjectMarks as $subjectId=>$value){ if($value===''||!is_numeric($value)||$value<0||$value>100)continue; $d=$this->distributionModel->where(['school_id'=>$schoolId,'subject_id'=>(int)$subjectId,'status'=>1])->first(); if(!$d)continue;
                $where=['school_id'=>$schoolId,'exam_id'=>$p->exam_id,'class_id'=>$p->class_id,'subject_id'=>(int)$subjectId,'student_id'=>(int)$studentId,'distribution_id'=>$d->distribution_id]; $old=$this->MarkModel->where($where)->first();
                $data=$where+['token'=>bin2hex(random_bytes(16)),'school_owner_uid'=>$userId,'session_id'=>$p->year_id,'section_id'=>$en->section_id,'enrollment_id'=>$en->id,'roll_no'=>$en->roll_no,'full_mark'=>$d->full_mark,'obtained_mark'=>(float)$value,'status'=>1,'created_by'=>$userId,'updated_by'=>$userId]; $old?$this->MarkModel->update($old->id,$data):$this->MarkModel->insert($data);
            }
        }
        $sectionIds = $this->EnrollmentModel->select('section_id')->distinct()->where(['school_id'=>$schoolId,'class_id'=>$p->class_id,'session_id'=>$p->year_id])->findColumn('section_id') ?: [(int) $p->section_id];
        foreach ($sectionIds as $sectionId) foreach (json_decode((string) $p->subject_ids, true) ?: [] as $subjectId) {
            $this->MarkLockModel->setLock(['school_id'=>$schoolId,'school_owner_uid'=>$userId,'exam_id'=>$p->exam_id,'session_id'=>$p->year_id,'class_id'=>$p->class_id,'section_id'=>(int)$sectionId,'subject_id'=>(int)$subjectId,'is_locked'=>1,'locked_by'=>$userId,'lock_reason'=>'Result Wizard']);
        }
    }

    private function parseCsvUpload(string $field, array $expected): array
    {
        $file=$this->request->getFile($field); if(!$file||!$file->isValid())return ['rows'=>[],'errors'=>[lang('ResultWizard.file_missing')]];
        $h=fopen($file->getTempName(),'rb'); $head=fgetcsv($h); if(isset($head[0]))$head[0]=preg_replace('/^\xEF\xBB\xBF/','',$head[0]); $errors=[];$rows=[];$line=1;
        if(!$head || count($head) < count($expected)) $errors[]=lang('ResultWizard.wrong_template');
        else foreach ($expected as $i => $column) if (trim((string) ($head[$i] ?? '')) !== $column) { $errors[]=lang('ResultWizard.wrong_template'); break; }
        while(($r=fgetcsv($h))!==false){$line++;$r=array_map('trim',$r);if(($r[0]??'')===''){$errors[]=lang('ResultWizard.student_row_missing', [$line]);continue;}$rows[]=$r;} fclose($h);
        if(!$rows)$errors[]=lang('ResultWizard.file_empty'); return compact('rows','errors');
    }

    private function csvDownload(string $name,array $rows)
    {
        $h=fopen('php://temp','w+'); fwrite($h,"\xEF\xBB\xBF");foreach($rows as $r)fputcsv($h,$r);rewind($h);$body=stream_get_contents($h);fclose($h);return $this->response->download($name,$body)->setContentType('text/csv; charset=UTF-8');
    }
}
