<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\ResultPublishModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ExamModel;

class ResultPublishController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected ExamResultModel $ExamResultModel;
    protected FinalResultModel $FinalResultModel;
    protected ResultPublishModel $ResultPublishModel;
    protected SubjectResultModel $SubjectResultModel;
    protected ExamModel $ExamModel;

    public function __construct()
    {
        $this->SchoolModel          = new SchoolModel();
        $this->YearModel            = new AcademicsYearModel();
        $this->ClassModel           = new AcademicsClassesModel();
        $this->SectionModel         = new AcademicsSectionModel();
        $this->ExamResultModel      = new ExamResultModel();
        $this->FinalResultModel     = new FinalResultModel();
        $this->ResultPublishModel   = new ResultPublishModel();
        $this->SubjectResultModel   = new SubjectResultModel();
        $this->ExamModel            = new ExamModel();
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
        
        $orderColumn = 'title';
        $valueColumn = 'title';
        
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

    // ===================================================================
    // AJAX: Get academic data by school
    // ===================================================================
    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/result-publish');
        }

        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList       = $this->getActiveOptions($school_id, 'YearModel');
        $classList      = $this->getActiveOptions($school_id, 'ClassModel');
        $sectionList    = $this->getActiveOptions($school_id, 'SectionModel');
        $examList       = $this->getActiveOptions($school_id, 'ExamModel');

        return $this->jsonResponse([
            'status'       => true,
            'year_list'       => $yearList,
            'class_list'      => $classList,
            'section_list'    => $sectionList,
            'exam_list'       => $examList,
            'academic_section_enabled' => $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled'),
        ]);
    }

    // ===================================================================
    // INDEX: Display the publish/unpublish form
    // ===================================================================
    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('ResultPublish.page_title'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\publish\index', $data)
            . view('footer', $footer_data);
    }

    // ===================================================================
    // AJAX: Check publish status for given filters
    // ===================================================================
    public function checkStatus()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/result-publish');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');

        if (!$school_id || !$exam_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        // Check if results exist (subject results are a good indicator)
        $subjectResultCount = $this->SubjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $subjectResultCount->where('section_id', $section_id);
        }
        $count = $subjectResultCount->countAllResults();

        if ($count == 0) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'No results found for the selected criteria. Please generate results first.',
                'has_results' => false,
            ]);
        }

        // Check publish status
        $publishQuery = $this->ResultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $publishQuery->where('section_id', $section_id);
        }

        $publishRecord = $publishQuery->first();
        $isPublished = $publishRecord && $publishRecord->is_published ? true : false;

        // Get published and total student counts
        $totalStudents = $this->SubjectResultModel
            ->select('DISTINCT(student_id)')
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $totalStudents->where('section_id', $section_id);
        }
        $totalStudentCount = $totalStudents->countAllResults();

        return $this->jsonResponse([
            'status'          => true,
            'is_published'    => $isPublished,
            'has_results'     => true,
            'total_students'  => $totalStudentCount,
            'published_at'    => $publishRecord ? $publishRecord->published_at : null,
            'publish_record'  => $publishRecord ? true : false,
        ]);
    }

    // ===================================================================
    // PUBLISH: Publish results for the given filters
    // ===================================================================
    public function publish()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');

        if (!$school_id || !$exam_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        $user_id = $this->getUserId();
        $now     = date('Y-m-d H:i:s');

        // Upsert publish record
        $existing = $this->ResultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $existing->where('section_id', $section_id);
        }

        $publishRecord = $existing->first();

        $publishData = [
            'school_id'       => $school_id,
            'school_owner_uid'=> $user_id,
            'exam_id'         => $exam_id,
            'class_id'        => $class_id,
            'section_id'      => $section_id ?: 0,
            'is_published'    => 1,
            'published_by'    => $user_id,
            'published_at'    => $now,
        ];

        if ($publishRecord) {
            $this->ResultPublishModel->update($publishRecord->id, $publishData);
        } else {
            $this->ResultPublishModel->insert($publishData);
        }

        // Update exam results published_at timestamp
        $examResultUpdate = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $examResultUpdate->where('section_id', $section_id);
        }

        $examResultUpdate->set(['published_at' => $now])->update();

        // Also update final results is_published and published_at
        $finalResultUpdate = $this->FinalResultModel
            ->where('session_id', $this->getSessionIdFromExam($exam_id))
            ->where('class_id', $class_id);

        if ($section_id) {
            $finalResultUpdate->where('section_id', $section_id);
        }

        $finalResultUpdate->set([
            'is_published' => 1,
            'published_at' => $now,
        ])->update();

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Results published successfully.',
        ]);
    }

    // ===================================================================
    // UNPUBLISH: Unpublish results for the given filters
    // ===================================================================
    public function unpublish()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');

        if (!$school_id || !$exam_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        $existing = $this->ResultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $existing->where('section_id', $section_id);
        }

        $publishRecord = $existing->first();

        if ($publishRecord) {
            $this->ResultPublishModel->update($publishRecord->id, [
                'is_published' => 0,
                'published_at' => null,
            ]);
        }

        // Update exam results - clear published_at
        $examResultUpdate = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $examResultUpdate->where('section_id', $section_id);
        }

        $examResultUpdate->set(['published_at' => null])->update();

        // Also update final results
        $finalResultUpdate = $this->FinalResultModel
            ->where('session_id', $this->getSessionIdFromExam($exam_id))
            ->where('class_id', $class_id);

        if ($section_id) {
            $finalResultUpdate->where('section_id', $section_id);
        }

        $finalResultUpdate->set([
            'is_published' => 0,
            'published_at' => null,
        ])->update();

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Results unpublished successfully.',
        ]);
    }

    /**
     * Helper to get session_id from exam
     */
    private function getSessionIdFromExam(int $exam_id): int
    {
        $exam = $this->ExamModel->find($exam_id);
        return $exam ? (int) ($exam->year_id ?? 0) : 0;
    }
}