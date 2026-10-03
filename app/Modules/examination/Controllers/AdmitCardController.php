<?php

namespace App\Modules\examination\Controllers;

use App\Services\ServicePricingService;

use App\Modules\examination\Models\AdmitCardModel;
use App\Modules\examination\Models\AdmitCardSettingModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class AdmitCardController extends BaseController
{
    protected AdmitCardModel $CardModel;
    protected AdmitCardSettingModel $SettingModel;

    public function __construct()
    {
        parent::__construct();
        helper('system');
        $this->CardModel = new AdmitCardModel();
        $this->SettingModel = new AdmitCardSettingModel();
    }

    public function index()
    {
        if ($response = $this->requirePermission('exam_admit_card_view')) {
            return $response;
        }

        $schoolIds = $this->authorizedSchoolIds();
        $builder = $this->db->table('examination_admit_card_settings AS setting')
            ->select('setting.*, schools.name AS school_name, exams.title AS exam_name, years.title AS session_name, COUNT(cards.id) AS card_count')
            ->join('schools', 'schools.id = setting.school_id', 'left')
            ->join('examination_exams AS exams', 'exams.id = setting.exam_id AND exams.school_id = setting.school_id', 'left')
            ->join('academic_years AS years', 'years.id = setting.session_id AND years.school_id = setting.school_id', 'left')
            ->join('examination_admit_cards AS cards', 'cards.setting_id = setting.id AND cards.school_id = setting.school_id AND cards.status = 1', 'left')
            ->groupBy('setting.id')
            ->orderBy('setting.id', 'DESC');
        $schoolIds === [] ? $builder->where('1 = 0') : $builder->whereIn('setting.school_id', $schoolIds);

        return $this->render('list', lang('AdmitCard.page_title'), [
            'items' => $builder->get()->getResult(),
            'can_generate' => $this->hasPermission('exam_admit_card_generate'),
            'can_print' => $this->hasPermission('exam_admit_card_print'),
        ]);
    }

    public function create()
    {
        if ($response = $this->requirePermission('exam_admit_card_generate')) {
            return $response;
        }
        $schools = $this->getSchoolDropdown();
        return $this->render('form', lang('AdmitCard.generate'), [
            'school_list' => $schools,
            'default_school_id' => count($schools) === 1 ? array_key_first($schools) : 0,
        ]);
    }

    public function academicData()
    {
        if ($response = $this->requireAjaxPermission('exam_admit_card_generate')) {
            return $response;
        }
        $schoolId = (int) $this->request->getPost('school_id');
        if (!$this->isAuthorizedSchool($schoolId)) {
            return $this->json(['status' => false, 'message' => lang('AdmitCard.access_denied')], 403);
        }
        return $this->json([
            'status' => true,
            'years' => $this->optionRows($this->YearModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'DESC')->findAll()),
            'exams' => array_map(static fn ($row): array => ['id' => (int) $row->id, 'title' => $row->title, 'year_id' => (int) $row->year_id], $this->ExamModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll()),
            'classes' => $this->optionRows($this->ClassModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll()),
            'sections' => $this->optionRows($this->SectionModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title')->findAll()),
        ]);
    }

    public function students()
    {
        if ($response = $this->requireAjaxPermission('exam_admit_card_generate')) {
            return $response;
        }
        $scope = $this->validatedScope();
        if ($scope === null) {
            return $this->json(['status' => false, 'message' => lang('AdmitCard.invalid_selection')], 422);
        }
        $students = $this->eligibleStudents($scope['school_id'], $scope['session_id'], $scope['class_ids'], $scope['section_ids']);
        return $this->json(['status' => true, 'students' => array_map(static fn (array $student): array => [
            'student_id' => (int) $student['student_id'],
            'student_code' => $student['student_code'],
            'student_name' => $student['student_name'],
            'roll_no' => $student['roll_no'],
            'class_name' => $student['class_name'],
            'section_name' => $student['section_name'],
        ], $students)]);
    }

    public function generate()
    {
        if ($response = $this->requirePermission('exam_admit_card_generate')) {
            return $response;
        }
        if ($subscription = check_subscription('examination/admit-cards')) {
            return $subscription;
        }

        $scope = $this->validatedScope();
        $studentIds = $this->positiveIds($this->request->getPost('student_ids'));
        $title = trim((string) $this->request->getPost('title'));
        if ($scope === null || $studentIds === [] || $title === '' || mb_strlen($title) > 255) {
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.invalid_selection'));
        }
        $eligible = $this->eligibleStudents($scope['school_id'], $scope['session_id'], $scope['class_ids'], $scope['section_ids']);
        $eligibleMap = [];
        foreach ($eligible as $student) {
            $eligibleMap[(int) $student['student_id']] = $student;
        }
        if (array_diff($studentIds, array_keys($eligibleMap)) !== []) {
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.invalid_students'));
        }

        $requireSeat = $this->request->getPost('require_seat_plan') ? 1 : 0;
        if ($requireSeat && count($this->seatAllocationMap($scope['school_id'], $scope['exam_id'], $studentIds)) !== count($studentIds)) {
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.seat_required'));
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->getUserId();
        $setting = $this->SettingModel
            ->where('school_id', $scope['school_id'])
            ->where('exam_id', $scope['exam_id'])
            ->where('session_id', $scope['session_id'])
            ->first();
        $settingData = [
            'school_id' => $scope['school_id'], 'exam_id' => $scope['exam_id'], 'session_id' => $scope['session_id'],
            'title' => $title,
            'instructions' => trim((string) $this->request->getPost('instructions')) ?: null,
            'show_student_photo' => $this->flag('show_student_photo'), 'show_student_id' => $this->flag('show_student_id'),
            'show_registration_no' => $this->flag('show_registration_no'), 'show_exam_time' => $this->flag('show_exam_time'),
            'show_room' => $this->flag('show_room'), 'show_seat' => $this->flag('show_seat'),
            'show_qr_code' => $this->flag('show_qr_code'), 'require_seat_plan' => $requireSeat,
            'updated_by' => $userId, 'updated_at' => $now,
        ];

        $this->db->transBegin();
        try {
            if ($setting) {
                $this->SettingModel->update($setting->id, $settingData);
                $settingId = (int) $setting->id;
                $settingToken = $setting->token;
            } else {
                $settingData['token'] = bin2hex(random_bytes(24));
                $settingData['created_by'] = $userId;
                $settingData['created_at'] = $now;
                $settingId = (int) $this->SettingModel->insert($settingData, true);
                $settingToken = $settingData['token'];
            }

            $existing = $this->CardModel
                ->where('school_id', $scope['school_id'])->where('exam_id', $scope['exam_id'])
                ->whereIn('student_id', $studentIds)->findAll();
            $existingMap = [];
            foreach ($existing as $card) { $existingMap[(int) $card->student_id] = $card; }
            $inserts = [];
            foreach ($studentIds as $studentId) {
                $student = $eligibleMap[$studentId];
                $data = ['setting_id' => $settingId, 'school_id' => $scope['school_id'], 'exam_id' => $scope['exam_id'],
                    'session_id' => $scope['session_id'], 'student_id' => $studentId, 'enrollment_id' => (int) $student['enrollment_id'],
                    'status' => 1, 'generated_at' => $now, 'generated_by' => $userId, 'updated_at' => $now];
                if (isset($existingMap[$studentId])) {
                    $this->CardModel->update($existingMap[$studentId]->id, $data);
                } else {
                    $data['token'] = bin2hex(random_bytes(24));
                    $data['created_at'] = $now;
                    $inserts[] = $data;
                }
            }
            if ($inserts !== [] && !$this->CardModel->insertBatch($inserts)) {
                throw new \RuntimeException('Admit Card batch insert failed.');
            }
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Admit Card transaction failed.');
            }
            $this->db->transCommit();
            $billing = new ServicePricingService();
            $order = $billing->createOrUpdateOrder($scope['school_id'], $userId, ServicePricingService::ADMIT_CARD, count($studentIds), (int)$scope['exam_id'], 'admit_card_setting', $settingId);
            if (!in_array($order->status, ['paid','completed'], true)) {
                return redirect()->to('school-owner/billing/' . $order->token)->with('success', 'Admit Card প্রস্তুত হয়েছে। Final PDF/Print-এর জন্য পেমেন্ট সম্পন্ন করুন।');
            }
            return redirect()->to('examination/admit-cards/view/' . $settingToken)->with('success', lang('AdmitCard.saved'));
        } catch (Throwable $exception) {
            $this->db->transRollback();
            log_message('error', '[Admit Card] ' . $exception->getMessage());
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.save_failed'));
        }
    }

    public function view(string $token) { return $this->adminDocument($token, false); }
    public function print(string $token) { $setting=$this->authorizedSetting($token); if(!$setting)return redirect()->to('examination/admit-cards')->with('error',lang('AdmitCard.not_found'));if($blocked=$this->requirePaidAdmitCard($setting))return $blocked;return $this->adminDocument($token, true); }

    public function settings(?string $token = null)
    {
        if ($response = $this->requirePermission('exam_admit_card_generate')) { return $response; }
        if ($token === null) {
            $schoolIds = $this->authorizedSchoolIds();
            $setting = $schoolIds === [] ? null : $this->SettingModel
                ->whereIn('school_id', $schoolIds)
                ->orderBy('updated_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();
            if (!$setting) { return redirect()->to('examination/admit-cards/create'); }
            return redirect()->to('examination/admit-cards/settings/' . rawurlencode($setting->token));
        }
        $setting = $this->authorizedSetting($token);
        if (!$setting) { return redirect()->to('examination/admit-cards')->with('error', lang('AdmitCard.not_found')); }

        return $this->render('settings', lang('AdmitCard.settings'), [
            'setting' => $setting,
            'school' => $this->SchoolModel->find($setting->school_id),
            'exam' => $this->ExamModel->where('school_id', $setting->school_id)->find($setting->exam_id),
            'session' => $this->YearModel->where('school_id', $setting->school_id)->find($setting->session_id),
        ]);
    }

    public function updateSettings(string $token)
    {
        if ($response = $this->requirePermission('exam_admit_card_generate')) { return $response; }
        if ($subscription = check_subscription('examination/admit-cards')) { return $subscription; }
        $setting = $this->authorizedSetting($token);
        if (!$setting) { return redirect()->to('examination/admit-cards')->with('error', lang('AdmitCard.not_found')); }

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '' || mb_strlen($title) > 255) {
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.invalid_settings'));
        }

        $updated = $this->SettingModel->update($setting->id, [
            'title' => $title,
            'instructions' => trim((string) $this->request->getPost('instructions')) ?: null,
            'show_student_photo' => $this->flag('show_student_photo'),
            'show_student_id' => $this->flag('show_student_id'),
            'show_registration_no' => $this->flag('show_registration_no'),
            'show_exam_time' => $this->flag('show_exam_time'),
            'show_room' => $this->flag('show_room'),
            'show_seat' => $this->flag('show_seat'),
            'show_qr_code' => $this->flag('show_qr_code'),
            'require_seat_plan' => $this->flag('require_seat_plan'),
            'updated_by' => $this->getUserId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            return redirect()->back()->withInput()->with('error', lang('AdmitCard.settings_save_failed'));
        }
        return redirect()->to('examination/admit-cards/settings/' . rawurlencode($token))->with('success', lang('AdmitCard.settings_saved'));
    }

    public function downloadPdf(string $token)
    {
        if ($response = $this->requirePermission('exam_admit_card_print')) { return $response; }
        $setting = $this->authorizedSetting($token);
        if (!$setting) { return redirect()->to('examination/admit-cards')->with('error', lang('AdmitCard.not_found')); }
        if ($blocked = $this->requirePaidAdmitCard($setting)) return $blocked;
        return $this->pdfResponse($this->documentData($setting), 'Admit_Cards_' . $token . '.pdf');
    }

    public function verify(string $token)
    {
        if (!preg_match('/\A[a-f0-9]{48}\z/', $token)) {
            $this->response->setStatusCode(404);
            return view('App\Modules\examination\Views\admit_cards\verify', ['valid' => false]);
        }

        $card = $this->CardModel->where('token', $token)->where('status', 1)->first();
        if (!$card) {
            $this->response->setStatusCode(404);
            return view('App\Modules\examination\Views\admit_cards\verify', ['valid' => false]);
        }
        $setting = $this->SettingModel->where('id', $card->setting_id)->where('school_id', $card->school_id)->first();
        $data = $setting ? $this->documentData($setting, $token) : ['cards' => []];
        $valid = !empty($data['cards']);
        if (!$valid) { $this->response->setStatusCode(404); }
        return view('App\Modules\examination\Views\admit_cards\verify', ['valid' => $valid, 'card' => $data['cards'][0] ?? null, 'school' => $data['school'] ?? null, 'exam' => $data['exam'] ?? null]);
    }

    public function studentIndex()
    {
        $student = $this->currentStudent();
        if (!$student) { return redirect()->to('login')->with('error', lang('AdmitCard.not_found')); }
        $items = $this->db->table('examination_admit_cards AS card')
            ->select('card.token, card.generated_at, exams.title AS exam_name, years.title AS session_name')
            ->join('examination_exams AS exams', 'exams.id = card.exam_id AND exams.school_id = card.school_id', 'inner')
            ->join('academic_years AS years', 'years.id = card.session_id AND years.school_id = card.school_id', 'left')
            ->where('card.school_id', $student->school_id)->where('card.student_id', $student->id)->where('card.status', 1)
            ->orderBy('card.generated_at', 'DESC')->get()->getResult();
        return $this->render('student_list', lang('AdmitCard.my_cards'), ['items' => $items]);
    }

    public function studentView(string $token) { return $this->studentDocument($token, false); }
    public function studentDownloadPdf(string $token) { return $this->studentDocument($token, true); }

    private function adminDocument(string $token, bool $autoPrint)
    {
        if ($response = $this->requirePermission($autoPrint ? 'exam_admit_card_print' : 'exam_admit_card_view')) { return $response; }
        $setting = $this->authorizedSetting($token);
        if (!$setting) { return redirect()->to('examination/admit-cards')->with('error', lang('AdmitCard.not_found')); }
        $data = $this->documentData($setting);
        $data['is_pdf'] = false; $data['auto_print'] = $autoPrint; $data['back_url'] = base_url('examination/admit-cards');
        if (!$autoPrint) {
            $data['embedded'] = true;
            $data['print_url'] = base_url('examination/admit-cards/print/' . rawurlencode($token));
            return $this->render('document', (string) $setting->title, $data);
        }
        return view('App\Modules\examination\Views\admit_cards\document', $data);
    }

    private function studentDocument(string $token, bool $pdf)
    {
        $student = $this->currentStudent();
        if (!$student) { return redirect()->to('login'); }
        $card = $this->CardModel->where('token', $token)->where('school_id', $student->school_id)->where('student_id', $student->id)->where('status', 1)->first();
        if (!$card) { return redirect()->to('examination/student/admit-cards')->with('error', lang('AdmitCard.not_found')); }
        $setting = $this->SettingModel->where('id', $card->setting_id)->where('school_id', $student->school_id)->first();
        if (!$setting) { return redirect()->to('examination/student/admit-cards')->with('error', lang('AdmitCard.not_found')); }
        $data = $this->documentData($setting, $token);
        if ($pdf) { return $this->pdfResponse($data, 'Admit_Card_' . $token . '.pdf'); }
        $data['is_pdf'] = false; $data['auto_print'] = false; $data['back_url'] = base_url('examination/student/admit-cards');
        return view('App\Modules\examination\Views\admit_cards\document', $data);
    }

    private function pdfResponse(array $data, string $filename)
    {
        $data['is_pdf'] = true; $data['auto_print'] = false;
        $options = new Options(); $options->set('isRemoteEnabled', true); $options->set('isHtml5ParserEnabled', true);
        $pdf = new Dompdf($options); $pdf->loadHtml(view('App\Modules\examination\Views\admit_cards\document', $data));
        $pdf->setPaper('A4', 'portrait'); $pdf->render();
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename) . '"')
            ->setBody($pdf->output());
    }

    private function documentData(object $setting, ?string $cardToken = null): array
    {
        $builder = $this->db->table('examination_admit_cards AS card')
            ->select("card.*, card_students.student_code, card_students.registration_no, card_students.photo, CONCAT_WS(' ', NULLIF(TRIM(card_students.first_name),''), NULLIF(TRIM(card_students.middle_name),''), NULLIF(TRIM(card_students.last_name),'')) AS student_name, enrollment.roll_no, classes.title AS class_name, sections.title AS section_name, years.title AS session_name, shifts.title AS shift_name, departments.title AS department_name")
            ->join('students AS card_students', 'card_students.id = card.student_id AND card_students.school_id = card.school_id', 'inner')
            ->join('student_enrollments AS enrollment', 'enrollment.id = card.enrollment_id AND enrollment.student_id = card.student_id AND enrollment.school_id = card.school_id', 'inner')
            ->join('academic_classes AS classes', 'classes.id = enrollment.class_id AND classes.school_id = card.school_id', 'left')
            ->join('academic_sections AS sections', 'sections.id = enrollment.section_id AND sections.school_id = card.school_id', 'left')
            ->join('academic_years AS years', 'years.id = card.session_id AND years.school_id = card.school_id', 'left')
            ->join('academic_shift AS shifts', 'shifts.id = enrollment.shift_id AND shifts.school_id = card.school_id', 'left')
            ->join('academic_departments AS departments', 'departments.id = enrollment.department_id AND departments.school_id = card.school_id', 'left')
            ->where('card.setting_id', $setting->id)->where('card.school_id', $setting->school_id)->where('card.status', 1)
            ->orderBy('classes.title')->orderBy('sections.title')->orderBy('enrollment.roll_no');
        if ($cardToken !== null) { $builder->where('card.token', $cardToken); }
        $cards = $builder->get()->getResultArray();
        $seatMap = $this->seatAllocationMap((int) $setting->school_id, (int) $setting->exam_id, array_map(static fn (array $card): int => (int) $card['student_id'], $cards));
        foreach ($cards as &$card) {
            $seat = $seatMap[(int) $card['student_id']] ?? null;
            $card['seat_no'] = $seat['seat_no'] ?? null; $card['room_name'] = $seat['room_name'] ?? null; $card['room_no'] = $seat['room_no'] ?? null;
        }
        unset($card);
        $school = $this->SchoolModel->find($setting->school_id);
        return ['setting' => $setting, 'cards' => $cards, 'school' => $school,
            'exam' => $this->ExamModel->where('school_id', $setting->school_id)->find($setting->exam_id),
            'school_logo_url' => $school && $school->logo ? base_url('uploads/' . ltrim($school->logo, '/')) : null];
    }

    private function seatAllocationMap(int $schoolId, int $examId, array $studentIds): array
    {
        if ($studentIds === []) { return []; }
        $rows = $this->db->table('examination_seat_allocations AS allocation')
            ->select('allocation.student_id, allocation.seat_no, plan_room.room_name_snapshot AS room_name, plan_room.room_no_snapshot AS room_no, plan.is_locked, plan.updated_at')
            ->join('examination_seat_plans AS plan', 'plan.id = allocation.seat_plan_id AND plan.school_id = allocation.school_id', 'inner')
            ->join('examination_seat_plan_rooms AS plan_room', 'plan_room.seat_plan_id = allocation.seat_plan_id AND plan_room.room_id = allocation.room_id AND plan_room.school_id = allocation.school_id', 'inner')
            ->where('allocation.school_id', $schoolId)->where('allocation.exam_id', $examId)->whereIn('allocation.student_id', $studentIds)
            ->orderBy('plan.is_locked', 'DESC')->orderBy('plan.updated_at', 'DESC')->get()->getResultArray();
        $map = []; foreach ($rows as $row) { $map[(int) $row['student_id']] ??= $row; } return $map;
    }

    private function eligibleStudents(int $schoolId, int $sessionId, array $classIds, array $sectionIds): array
    {
        $builder = $this->db->table('student_enrollments AS enrollment')
            ->select("enrollment.id AS enrollment_id, enrollment.student_id, enrollment.roll_no, eligible_students.student_code, CONCAT_WS(' ', NULLIF(TRIM(eligible_students.first_name),''), NULLIF(TRIM(eligible_students.middle_name),''), NULLIF(TRIM(eligible_students.last_name),'')) AS student_name, classes.title AS class_name, sections.title AS section_name")
            ->join('students AS eligible_students', 'eligible_students.id = enrollment.student_id AND eligible_students.school_id = enrollment.school_id', 'inner')
            ->join('academic_classes AS classes', 'classes.id = enrollment.class_id AND classes.school_id = enrollment.school_id', 'left')
            ->join('academic_sections AS sections', 'sections.id = enrollment.section_id AND sections.school_id = enrollment.school_id', 'left')
            ->where('enrollment.school_id', $schoolId)->where('enrollment.session_id', $sessionId)->whereIn('enrollment.class_id', $classIds)
            ->where('enrollment.status', 1)->where('eligible_students.status', 1)->where('eligible_students.student_status', 'Active');
        if ($sectionIds !== []) { $builder->whereIn('enrollment.section_id', $sectionIds); }
        return $builder->orderBy('classes.title')->orderBy('sections.title')->orderBy('enrollment.roll_no')->get()->getResultArray();
    }

    private function validatedScope(): ?array
    {
        $schoolId = (int) $this->request->getPost('school_id'); $sessionId = (int) $this->request->getPost('session_id'); $examId = (int) $this->request->getPost('exam_id');
        $classIds = $this->positiveIds($this->request->getPost('class_ids')); $sectionIds = $this->positiveIds($this->request->getPost('section_ids'));
        if (!$this->isAuthorizedSchool($schoolId) || $sessionId <= 0 || $examId <= 0 || $classIds === []) { return null; }
        if ($this->YearModel->where('school_id', $schoolId)->where('status', 1)->find($sessionId) === null
            || $this->ExamModel->where('school_id', $schoolId)->where('year_id', $sessionId)->where('status', 1)->find($examId) === null
            || $this->ClassModel->where('school_id', $schoolId)->where('status', 1)->whereIn('id', $classIds)->countAllResults() !== count($classIds)
            || ($sectionIds !== [] && $this->SectionModel->where('school_id', $schoolId)->where('status', 1)->whereIn('id', $sectionIds)->countAllResults() !== count($sectionIds))) { return null; }
        return ['school_id' => $schoolId, 'session_id' => $sessionId, 'exam_id' => $examId, 'class_ids' => $classIds, 'section_ids' => $sectionIds];
    }

    private function authorizedSetting(string $token): ?object
    {
        $ids = $this->authorizedSchoolIds(); return $ids === [] ? null : $this->SettingModel->where('token', $token)->whereIn('school_id', $ids)->first();
    }
    private function currentStudent(): ?object { $userId = (int) session('user_id'); return $userId ? $this->StudentModel->where('user_id', $userId)->where('status', 1)->first() : null; }
    private function authorizedSchoolIds(): array { return array_map(static fn ($school): int => (int) $school->id, $this->getUserSchools()); }
    private function isAuthorizedSchool(int $id): bool { return $id > 0 && in_array($id, $this->authorizedSchoolIds(), true); }
    private function positiveIds($values): array { if (!is_array($values)) return []; $ids = array_map('intval', $values); return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0))); }
    private function optionRows(array $rows): array { return array_map(static fn ($row): array => ['id' => (int) $row->id, 'title' => $row->title], $rows); }
    private function flag(string $name): int { return $this->request->getPost($name) ? 1 : 0; }
    private function hasPermission(string $slug): bool { $permission = $this->db->table('permissions')->where('slug', $slug)->where('status', 1)->get()->getRow(); if (!$permission) return true; return $this->db->table('role_permissions rp')->join('roles r', 'r.id=rp.role_id')->where('r.slug', (string) session('role'))->where('rp.permission_id', $permission->id)->countAllResults() > 0; }
    private function requirePermission(string $slug) { return $this->hasPermission($slug) ? null : redirect()->to('unauthorized')->with('error', lang('AdmitCard.access_denied')); }
    private function requireAjaxPermission(string $slug) { if (!$this->request->isAJAX()) return $this->json(['status' => false, 'message' => 'Invalid request.'], 400); return $this->hasPermission($slug) ? null : $this->json(['status' => false, 'message' => lang('AdmitCard.access_denied')], 403); }
    private function requirePaidAdmitCard(object $setting) { return (new ServicePricingService())->isPaid((int)$setting->school_id, ServicePricingService::ADMIT_CARD, (int)$setting->exam_id) ? null : redirect()->to('school-owner/billing')->with('error','Final Admit Card PDF/Print-এর জন্য পেমেন্ট সম্পন্ন করুন।'); }
    private function json(array $data, int $status = 200) { return $this->response->setStatusCode($status)->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON($data); }
    private function render(string $view, string $title, array $data) { return view('header', ['page_title' => $title, 'body_class' => 'nav-md', 'admin_area' => 'yes']) . view('App\\Modules\\examination\\Views\\admit_cards\\' . $view, $data) . view('footer', ['admin_area' => 'yes']); }
}
