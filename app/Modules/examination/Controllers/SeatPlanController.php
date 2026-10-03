<?php

namespace App\Modules\examination\Controllers;

use App\Services\ServicePricingService;

use App\Models\AcademicsDepartmentModel;
use App\Models\AcademicsShiftModel;
use App\Modules\examination\Models\ExamRoomModel;
use App\Modules\examination\Models\ExamSeatAllocationModel;
use App\Modules\examination\Models\ExamSeatPlanModel;
use App\Modules\examination\Models\ExamSeatPlanRoomModel;
use App\Modules\examination\Services\SeatAllocationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use InvalidArgumentException;
use Throwable;

class SeatPlanController extends BaseController
{
    protected ExamRoomModel $RoomModel;
    protected ExamSeatPlanModel $SeatPlanModel;
    protected ExamSeatPlanRoomModel $SeatPlanRoomModel;
    protected ExamSeatAllocationModel $AllocationModel;
    protected AcademicsShiftModel $ShiftModel;
    protected AcademicsDepartmentModel $DepartmentModel;
    protected SeatAllocationService $AllocationService;

    public function __construct()
    {
        parent::__construct();
        $this->RoomModel = new ExamRoomModel();
        $this->SeatPlanModel = new ExamSeatPlanModel();
        $this->SeatPlanRoomModel = new ExamSeatPlanRoomModel();
        $this->AllocationModel = new ExamSeatAllocationModel();
        $this->ShiftModel = new AcademicsShiftModel();
        $this->DepartmentModel = new AcademicsDepartmentModel();
        $this->AllocationService = new SeatAllocationService();
    }

    public function index()
    {
        if ($response = $this->requirePermission('exam_seat_plan_view')) {
            return $response;
        }

        $schoolIds = $this->authorizedSchoolIds();
        $schoolId = (int) $this->request->getGet('school_id');
        if ($schoolId && !in_array($schoolId, $schoolIds, true)) {
            return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.access_denied'));
        }

        $text = trim((string) $this->request->getGet('text'));
        $status = trim((string) $this->request->getGet('status'));
        $show = (int) $this->request->getGet('show');

        $this->SeatPlanModel
            ->select('examination_seat_plans.*, schools.name AS school_name, examination_exams.title AS exam_name, COUNT(DISTINCT seat_allocations.id) AS student_count, COUNT(DISTINCT seat_plan_rooms.id) AS room_count')
            ->join('schools', 'schools.id = examination_seat_plans.school_id', 'left')
            ->join('examination_exams', 'examination_exams.id = examination_seat_plans.exam_id', 'left')
            ->join('examination_seat_allocations AS seat_allocations', 'seat_allocations.seat_plan_id = examination_seat_plans.id AND seat_allocations.school_id = examination_seat_plans.school_id', 'left')
            ->join('examination_seat_plan_rooms AS seat_plan_rooms', 'seat_plan_rooms.seat_plan_id = examination_seat_plans.id AND seat_plan_rooms.school_id = examination_seat_plans.school_id', 'left');

        if ($schoolIds === []) {
            $this->SeatPlanModel->where('1 = 0');
        } else {
            $this->SeatPlanModel->whereIn('examination_seat_plans.school_id', $schoolIds);
        }
        if ($schoolId) {
            $this->SeatPlanModel->where('examination_seat_plans.school_id', $schoolId);
        }
        if ($text !== '') {
            $this->SeatPlanModel->groupStart()
                ->like('examination_seat_plans.title', $text)
                ->orLike('examination_exams.title', $text)
                ->groupEnd();
        }
        if ($status !== '') {
            $this->SeatPlanModel->where('examination_seat_plans.status', $status);
        }

        $perPage = $show > 0 ? min($show, 1000) : ((int) setting('application', 'per_item_in_list') ?: 10);
        $items = $this->SeatPlanModel
            ->groupBy('examination_seat_plans.id')
            ->orderBy('examination_seat_plans.id', 'DESC')
            ->paginate($perPage);

        return $this->render('list', lang('SeatPlan.page_title_list'), [
            'items' => $items,
            'pager' => $this->SeatPlanModel->pager,
            'pagerTemplate' => 'custom_pagination',
            'school_list' => $this->getSchoolDropdown(),
            'selected_school' => $schoolId,
            'text' => $text,
            'status' => $status,
            'show' => $show,
            'can_generate' => $this->hasPermission('exam_seat_plan_generate'),
            'can_print' => $this->hasPermission('exam_seat_plan_print'),
        ]);
    }

    public function create()
    {
        if ($response = $this->requirePermission('exam_seat_plan_generate')) {
            return $response;
        }

        $schools = $this->getSchoolDropdown();
        return $this->render('form', lang('SeatPlan.page_title_new'), [
            'school_list' => $schools,
            'default_school_id' => count($schools) === 1 ? array_key_first($schools) : 0,
        ]);
    }

    public function academicData()
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_generate')) {
            return $response;
        }

        $schoolId = (int) $this->request->getPost('school_id');
        if (!$this->isAuthorizedSchool($schoolId)) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.access_denied')], 403);
        }

        $years = $this->YearModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'DESC')->findAll();
        $exams = $this->ExamModel->where('school_id', $schoolId)->where('status', 1)->orderBy('exam_order', 'ASC')->orderBy('title', 'ASC')->findAll();
        $classes = $this->ClassModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'ASC')->findAll();
        $sections = $this->SectionModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'ASC')->findAll();
        $shifts = $this->ShiftModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'ASC')->findAll();
        $departments = $this->DepartmentModel->where('school_id', $schoolId)->where('status', 1)->orderBy('title', 'ASC')->findAll();
        $rooms = $this->RoomModel->where('school_id', $schoolId)->where('status', 1)->orderBy('room_name', 'ASC')->findAll();

        return $this->jsonResponse([
            'status' => true,
            'years' => $this->optionRows($years),
            'exams' => array_map(static fn ($row): array => [
                'id' => (int) $row->id,
                'title' => $row->title,
                'year_id' => (int) $row->year_id,
                'exam_date' => $row->exam_date,
            ], $exams),
            'classes' => $this->optionRows($classes),
            'sections' => $this->optionRows($sections),
            'shifts' => $this->optionRows($shifts),
            'departments' => $this->optionRows($departments),
            'rooms' => array_map(static fn ($room): array => [
                'id' => (int) $room->id,
                'room_name' => $room->room_name,
                'room_no' => $room->room_no,
                'building' => $room->building,
                'capacity' => (int) $room->capacity,
                'rows_count' => (int) $room->rows_count,
                'columns_count' => (int) $room->columns_count,
            ], $rooms),
        ]);
    }

    public function students()
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_generate')) {
            return $response;
        }

        try {
            $filters = $this->validatedSelectionFilters(false);
            $students = $this->loadEligibleStudents(
                $filters['school_id'],
                $filters['session_id'],
                $filters['class_ids'],
                $filters['section_ids'],
                $filters['shift_id'],
                $filters['department_id']
            );

            return $this->jsonResponse([
                'status' => true,
                'students' => array_map([$this, 'studentResponseRow'], $students),
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->jsonResponse(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function preview()
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_generate')) {
            return $response;
        }

        try {
            $generation = $this->buildGenerationData();
            $html = view('App\Modules\examination\Views\seat_plans\preview', [
                'summary' => $generation['result'],
                'students' => $generation['student_map'],
                'rooms' => $generation['room_map'],
            ]);
            return $this->jsonResponse([
                'status' => true,
                'html' => $html,
                'summary' => [
                    'students' => $generation['result']['students_count'],
                    'seats' => $generation['result']['available_seats'],
                    'unused' => $generation['result']['unused_seats'],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->jsonResponse(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function generate()
    {
        if ($response = $this->requirePermission('exam_seat_plan_generate')) {
            return $response;
        }
        $subscriptionCheck = check_subscription('examination/seat-plans');
        if ($subscriptionCheck) {
            return $subscriptionCheck;
        }

        try {
            $generation = $this->buildGenerationData();
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $schoolId = $generation['filters']['school_id'];
        $exam = $generation['exam'];
        $now = date('Y-m-d H:i:s');
        $this->db->transBegin();

        try {
            $planId = $this->SeatPlanModel->insert([
                'token' => bin2hex(random_bytes(16)),
                'school_id' => $schoolId,
                'school_owner_uid' => $this->getUserId(),
                'exam_id' => $exam->id,
                'session_id' => $generation['filters']['session_id'],
                'shift_id' => $generation['filters']['shift_id'],
                'department_id' => $generation['filters']['department_id'],
                'class_ids_json' => json_encode($generation['filters']['class_ids']),
                'section_ids_json' => json_encode($generation['filters']['section_ids']),
                'title' => $generation['filters']['title'],
                'exam_date' => $generation['filters']['exam_date'],
                'allocation_method' => $generation['filters']['allocation_method'],
                'seat_number_format' => $generation['filters']['seat_number_format'],
                'random_seed' => $generation['filters']['random_seed'],
                'status' => 'generated',
                'is_locked' => 0,
                'created_by' => $this->getUserId(),
                'updated_by' => $this->getUserId(),
            ], true);
            if (!$planId) {
                throw new InvalidArgumentException(lang('SeatPlan.save_failed'));
            }

            $roomRows = [];
            foreach ($generation['rooms'] as $sortOrder => $room) {
                $roomRows[] = [
                    'school_id' => $schoolId,
                    'seat_plan_id' => $planId,
                    'room_id' => $room['room_id'],
                    'room_name_snapshot' => $room['room_name'],
                    'room_no_snapshot' => $room['room_no'],
                    'capacity' => $room['capacity'],
                    'rows_count' => $room['rows_count'],
                    'columns_count' => $room['columns_count'],
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!$this->SeatPlanRoomModel->insertBatch($roomRows)) {
                throw new InvalidArgumentException(lang('SeatPlan.save_failed'));
            }

            $allocationRows = [];
            foreach ($generation['result']['allocations'] as $allocation) {
                $allocationRows[] = [
                    'school_id' => $schoolId,
                    'seat_plan_id' => $planId,
                    'exam_id' => $exam->id,
                    'room_id' => $allocation['room_id'],
                    'student_id' => $allocation['student_id'],
                    'enrollment_id' => $allocation['enrollment_id'],
                    'session_id' => $allocation['session_id'],
                    'class_id' => $allocation['class_id'],
                    'section_id' => $allocation['section_id'],
                    'roll_no_snapshot' => $allocation['roll_no'],
                    'seat_no' => $allocation['seat_no'],
                    'row_no' => $allocation['row_no'],
                    'column_no' => $allocation['column_no'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!$this->AllocationModel->insertBatch($allocationRows)) {
                throw new InvalidArgumentException(lang('SeatPlan.save_failed'));
            }

            if ($this->db->transStatus() === false) {
                throw new InvalidArgumentException(lang('SeatPlan.save_failed'));
            }
            $plan = $this->SeatPlanModel->find($planId);
            $billing = new ServicePricingService();
            $order = $billing->createOrUpdateOrder($schoolId, $this->getUserId(), ServicePricingService::SEAT_PLAN, (int)$generation['result']['students_count'], (int)$exam->id, 'exam_seat_plan', (int)$planId);
            if ($this->db->transStatus() === false) {
                throw new InvalidArgumentException(lang('SeatPlan.save_failed'));
            }
            $this->db->transCommit();
            if (!in_array($order->status, ['paid','completed'], true)) {
                return redirect()->to('school-owner/billing/' . $order->token)->with('success', 'Seat Plan তৈরি হয়েছে। Final Print/PDF-এর জন্য পেমেন্ট সম্পন্ন করুন।');
            }
            return redirect()->to('examination/seat-plans/view/' . $plan->token)->with('success', lang('SeatPlan.saved'));
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Seat Plan] Generation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', lang('SeatPlan.save_failed'));
        }
    }

    public function view(string $token)
    {
        if ($response = $this->requirePermission('exam_seat_plan_view')) {
            return $response;
        }

        $plan = $this->findAuthorizedPlan($token);
        if (!$plan) {
            return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.not_found'));
        }

        $rooms = $this->SeatPlanRoomModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        $allocations = $this->AllocationModel
            ->select("examination_seat_allocations.*, TRIM(CONCAT_WS(' ', NULLIF(seat_students.first_name, ''), NULLIF(seat_students.middle_name, ''), NULLIF(seat_students.last_name, ''))) AS student_name, seat_students.student_code, academic_classes.title AS class_name, academic_sections.title AS section_name")
            ->join('students AS seat_students', 'seat_students.id = examination_seat_allocations.student_id AND seat_students.school_id = examination_seat_allocations.school_id', 'left')
            ->join('academic_classes', 'academic_classes.id = examination_seat_allocations.class_id AND academic_classes.school_id = examination_seat_allocations.school_id', 'left')
            ->join('academic_sections', 'academic_sections.id = examination_seat_allocations.section_id AND academic_sections.school_id = examination_seat_allocations.school_id', 'left')
            ->where('examination_seat_allocations.school_id', $plan->school_id)
            ->where('examination_seat_allocations.seat_plan_id', $plan->id)
            ->orderBy('examination_seat_allocations.room_id', 'ASC')
            ->orderBy('examination_seat_allocations.row_no', 'ASC')
            ->orderBy('examination_seat_allocations.column_no', 'ASC')
            ->findAll();
        $exam = $this->ExamModel->where('school_id', $plan->school_id)->find($plan->exam_id);
        $school = $this->SchoolModel->find($plan->school_id);

        $seatPositions = $this->seatPositions($rooms, $plan->seat_number_format);
        $allocatedStudentIds = array_map(static fn ($allocation): int => (int) $allocation->student_id, $allocations);
        $classIds = $this->decodedIds($plan->class_ids_json ?? null);
        $sectionIds = $this->decodedIds($plan->section_ids_json ?? null);
        if ($classIds === []) {
            $classIds = array_values(array_unique(array_map(static fn ($allocation): int => (int) $allocation->class_id, $allocations)));
        }
        $unallocatedStudents = [];
        if ($classIds !== [] && !(int) $plan->is_locked) {
            $eligible = $this->loadEligibleStudents(
                (int) $plan->school_id,
                (int) $plan->session_id,
                $classIds,
                $sectionIds,
                $plan->shift_id ? (int) $plan->shift_id : null,
                $plan->department_id ? (int) $plan->department_id : null
            );
            $unallocatedStudents = array_values(array_filter($eligible, static fn ($student): bool => !in_array((int) $student->student_id, $allocatedStudentIds, true)));
        }

        return $this->render('view', $plan->title, [
            'plan' => $plan,
            'rooms' => $rooms,
            'allocations' => $allocations,
            'exam' => $exam,
            'school' => $school,
            'seat_positions' => $seatPositions,
            'unallocated_students' => $unallocatedStudents,
            'can_edit' => $this->hasPermission('exam_seat_plan_edit'),
            'can_generate' => $this->hasPermission('exam_seat_plan_generate'),
            'can_lock' => $this->hasPermission('exam_seat_plan_lock'),
            'can_unlock' => $this->hasPermission('exam_seat_plan_unlock'),
            'can_print' => $this->hasPermission('exam_seat_plan_print'),
        ]);
    }

    public function printReport(string $token, string $report)
    {
        if ($response = $this->requirePermission('exam_seat_plan_print')) {
            return $response;
        }
        if ($blocked = $this->requirePaidSeatPlan($token)) return $blocked;

        $data = $this->seatPlanReportData($token, $report);
        if ($data === null) {
            return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.report_not_found'));
        }

        $data['is_pdf'] = false;
        $data['auto_print'] = (int) $this->request->getGet('auto_print') === 1;
        return view('App\Modules\examination\Views\seat_plans\report', $data);
    }

    public function seatSlips(string $token)
    {
        if ($response = $this->requirePermission('exam_seat_plan_print')) {
            return $response;
        }
        if ($blocked = $this->requirePaidSeatPlan($token)) return $blocked;

        $data = $this->seatPlanReportData($token, 'seat-slips');
        if ($data === null) {
            return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.report_not_found'));
        }

        return $this->render('seat_slips', lang('SeatPlan.seat_slips'), $data);
    }

    public function downloadPdf(string $token, string $report)
    {
        if ($response = $this->requirePermission('exam_seat_plan_print')) {
            return $response;
        }
        if ($blocked = $this->requirePaidSeatPlan($token)) return $blocked;

        $data = $this->seatPlanReportData($token, $report);
        if ($data === null) {
            return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.report_not_found'));
        }

        $data['is_pdf'] = true;
        $html = view('App\Modules\examination\Views\seat_plans\report', $data);
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $report === 'class-list' ? 'landscape' : 'portrait');
        $dompdf->render();

        $filename = preg_replace(
            '/[^a-zA-Z0-9_.-]/',
            '_',
            $data['plan']->title . '_' . $data['report_titles'][$report] . '.pdf'
        );

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public function moveAllocation(string $token)
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_edit')) {
            return $response;
        }
        $plan = $this->editablePlan($token);
        if (!$plan) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.locked_edit_denied')], 422);
        }

        $allocationId = (int) $this->request->getPost('allocation_id');
        $allocation = $this->AllocationModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->find($allocationId);
        $position = $this->validatedPosition($plan, (string) $this->request->getPost('position'));
        if (!$allocation || !$position) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_adjustment')], 422);
        }
        if ($this->positionOccupied($plan, $position, $allocationId)) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.duplicate_seat')], 422);
        }

        $updated = $this->AllocationModel->update($allocationId, $this->positionData($position));
        return $this->jsonResponse(['status' => (bool) $updated, 'message' => $updated ? lang('SeatPlan.allocation_moved') : lang('SeatPlan.adjustment_failed')]);
    }

    public function swapAllocations(string $token)
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_edit')) {
            return $response;
        }
        $plan = $this->editablePlan($token);
        $firstId = (int) $this->request->getPost('first_allocation_id');
        $secondId = (int) $this->request->getPost('second_allocation_id');
        if (!$plan || $firstId <= 0 || $secondId <= 0 || $firstId === $secondId) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_adjustment')], 422);
        }

        $rows = $this->AllocationModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->whereIn('id', [$firstId, $secondId])
            ->findAll();
        if (count($rows) !== 2) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_adjustment')], 422);
        }
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row->id] = $row;
        }

        $this->db->transBegin();
        try {
            $temporarySeat = '__swap_' . $firstId;
            $this->AllocationModel->update($firstId, ['room_id' => 0, 'row_no' => 1, 'column_no' => 1, 'seat_no' => $temporarySeat]);
            $this->AllocationModel->update($secondId, $this->allocationPositionData($byId[$firstId]));
            $this->AllocationModel->update($firstId, $this->allocationPositionData($byId[$secondId]));
            if ($this->db->transStatus() === false) {
                throw new InvalidArgumentException(lang('SeatPlan.adjustment_failed'));
            }
            $this->db->transCommit();
            return $this->jsonResponse(['status' => true, 'message' => lang('SeatPlan.allocations_swapped')]);
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Seat Plan] Swap failed: ' . $e->getMessage());
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.adjustment_failed')], 422);
        }
    }

    public function removeAllocation(string $token)
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_edit')) {
            return $response;
        }
        $plan = $this->editablePlan($token);
        $allocationId = (int) $this->request->getPost('allocation_id');
        $allocation = $plan ? $this->AllocationModel->where('school_id', $plan->school_id)->where('seat_plan_id', $plan->id)->find($allocationId) : null;
        if (!$allocation) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_adjustment')], 422);
        }
        $deleted = $this->AllocationModel->delete($allocationId);
        return $this->jsonResponse(['status' => (bool) $deleted, 'message' => $deleted ? lang('SeatPlan.allocation_removed') : lang('SeatPlan.adjustment_failed')]);
    }

    public function addAllocation(string $token)
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_edit')) {
            return $response;
        }
        $plan = $this->editablePlan($token);
        $studentId = (int) $this->request->getPost('student_id');
        $position = $plan ? $this->validatedPosition($plan, (string) $this->request->getPost('position')) : null;
        if (!$plan || $studentId <= 0 || !$position || $this->positionOccupied($plan, $position)) {
            return $this->jsonResponse(['status' => false, 'message' => $position ? lang('SeatPlan.duplicate_seat') : lang('SeatPlan.invalid_adjustment')], 422);
        }
        if ($this->AllocationModel->where('school_id', $plan->school_id)->where('seat_plan_id', $plan->id)->where('student_id', $studentId)->countAllResults()) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.duplicate_student')], 422);
        }

        $classIds = $this->decodedIds($plan->class_ids_json ?? null);
        $sectionIds = $this->decodedIds($plan->section_ids_json ?? null);
        if ($classIds === []) {
            $classRows = $this->AllocationModel->select('class_id')->where('school_id', $plan->school_id)->where('seat_plan_id', $plan->id)->distinct()->findAll();
            $classIds = array_map(static fn ($row): int => (int) $row->class_id, $classRows);
        }
        if ($classIds === []) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_student')], 422);
        }
        $student = $this->EnrollmentModel
            ->select('student_enrollments.*, students.status AS student_record_status, students.student_status')
            ->join('students', 'students.id = student_enrollments.student_id', 'inner')
            ->where('student_enrollments.school_id', $plan->school_id)
            ->where('students.school_id', $plan->school_id)
            ->where('student_enrollments.session_id', $plan->session_id)
            ->whereIn('student_enrollments.class_id', $classIds)
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.status', 1)
            ->where('students.status', 1)
            ->where('students.student_status', 'Active')
            ;
        if ($sectionIds !== []) {
            $student->whereIn('student_enrollments.section_id', $sectionIds);
        }
        if ($plan->shift_id) {
            $student->where('student_enrollments.shift_id', $plan->shift_id);
        }
        if ($plan->department_id) {
            $student->where('student_enrollments.department_id', $plan->department_id);
        }
        $student = $student->first();
        if (!$student) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_student')], 422);
        }

        $inserted = $this->AllocationModel->insert(array_merge([
            'school_id' => $plan->school_id,
            'seat_plan_id' => $plan->id,
            'exam_id' => $plan->exam_id,
            'student_id' => $student->student_id,
            'enrollment_id' => $student->id,
            'session_id' => $student->session_id,
            'class_id' => $student->class_id,
            'section_id' => $student->section_id,
            'roll_no_snapshot' => $student->roll_no,
        ], $this->positionData($position))) !== false;
        return $this->jsonResponse(['status' => $inserted, 'message' => $inserted ? lang('SeatPlan.allocation_added') : lang('SeatPlan.adjustment_failed')]);
    }

    public function lock(string $token)
    {
        return $this->changeLock($token, true);
    }

    public function regenerate(string $token)
    {
        if ($response = $this->requireAjaxPermission('exam_seat_plan_generate')) {
            return $response;
        }
        $plan = $this->editablePlan($token);
        if (!$plan) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.locked_edit_denied')], 422);
        }

        $currentAllocations = $this->AllocationModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->findAll();
        $rooms = $this->SeatPlanRoomModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        if ($currentAllocations === [] || $rooms === []) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.regenerate_empty')], 422);
        }

        $enrollmentIds = array_map(static fn ($allocation): int => (int) $allocation->enrollment_id, $currentAllocations);
        $students = $this->EnrollmentModel
            ->select("student_enrollments.id AS enrollment_id, student_enrollments.student_id, student_enrollments.session_id, student_enrollments.class_id, student_enrollments.section_id, student_enrollments.roll_no, students.first_name, students.middle_name, students.last_name")
            ->join('students', 'students.id = student_enrollments.student_id', 'inner')
            ->where('student_enrollments.school_id', $plan->school_id)
            ->where('students.school_id', $plan->school_id)
            ->whereIn('student_enrollments.id', $enrollmentIds)
            ->where('student_enrollments.status', 1)
            ->where('students.status', 1)
            ->where('students.student_status', 'Active')
            ->findAll();
        if (count($students) !== count($enrollmentIds)) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.regenerate_inactive')], 422);
        }

        $method = trim((string) ($this->request->getPost('allocation_method') ?: $plan->allocation_method));
        $seed = $method === SeatAllocationService::METHOD_RANDOM ? bin2hex(random_bytes(16)) : null;
        $serviceRooms = array_map(static fn ($room): array => [
            'room_id' => (int) $room->room_id,
            'capacity' => (int) $room->capacity,
            'rows_count' => (int) $room->rows_count,
            'columns_count' => (int) $room->columns_count,
            'sort_order' => (int) $room->sort_order,
        ], $rooms);
        try {
            $result = $this->AllocationService->generate($students, $serviceRooms, $method, $plan->seat_number_format, $seed);
        } catch (InvalidArgumentException $e) {
            return $this->jsonResponse(['status' => false, 'message' => $e->getMessage()], 422);
        }

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($result['allocations'] as $allocation) {
            $rows[] = [
                'school_id' => $plan->school_id,
                'seat_plan_id' => $plan->id,
                'exam_id' => $plan->exam_id,
                'room_id' => $allocation['room_id'],
                'student_id' => $allocation['student_id'],
                'enrollment_id' => $allocation['enrollment_id'],
                'session_id' => $allocation['session_id'],
                'class_id' => $allocation['class_id'],
                'section_id' => $allocation['section_id'],
                'roll_no_snapshot' => $allocation['roll_no'],
                'seat_no' => $allocation['seat_no'],
                'row_no' => $allocation['row_no'],
                'column_no' => $allocation['column_no'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->transBegin();
        try {
            $this->AllocationModel->where('school_id', $plan->school_id)->where('seat_plan_id', $plan->id)->delete();
            if (!$this->AllocationModel->insertBatch($rows)) {
                throw new InvalidArgumentException(lang('SeatPlan.adjustment_failed'));
            }
            $this->SeatPlanModel->update($plan->id, [
                'allocation_method' => $method,
                'random_seed' => $seed,
                'status' => 'generated',
                'updated_by' => $this->getUserId(),
            ]);
            if ($this->db->transStatus() === false) {
                throw new InvalidArgumentException(lang('SeatPlan.adjustment_failed'));
            }
            $this->db->transCommit();
            return $this->jsonResponse(['status' => true, 'message' => lang('SeatPlan.plan_regenerated')]);
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Seat Plan] Regeneration failed: ' . $e->getMessage());
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.adjustment_failed')], 422);
        }
    }

    public function unlock(string $token)
    {
        return $this->changeLock($token, false);
    }

    private function changeLock(string $token, bool $lock)
    {
        $permission = $lock ? 'exam_seat_plan_lock' : 'exam_seat_plan_unlock';
        if ($response = $this->requireAjaxPermission($permission)) {
            return $response;
        }
        $plan = $this->findAuthorizedPlan($token);
        if (!$plan) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.not_found')], 404);
        }
        $updated = $this->SeatPlanModel->update($plan->id, [
            'is_locked' => $lock ? 1 : 0,
            'status' => $lock ? 'locked' : 'generated',
            'locked_at' => $lock ? date('Y-m-d H:i:s') : null,
            'locked_by' => $lock ? $this->getUserId() : null,
            'updated_by' => $this->getUserId(),
        ]);
        return $this->jsonResponse(['status' => (bool) $updated, 'message' => $updated ? lang($lock ? 'SeatPlan.plan_locked' : 'SeatPlan.plan_unlocked') : lang('SeatPlan.adjustment_failed')]);
    }

    private function buildGenerationData(): array
    {
        $filters = $this->validatedSelectionFilters(true);
        $exam = $this->ExamModel
            ->where('school_id', $filters['school_id'])
            ->where('year_id', $filters['session_id'])
            ->where('status', 1)
            ->find($filters['exam_id']);
        if (!$exam) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_exam'));
        }

        $eligibleStudents = $this->loadEligibleStudents(
            $filters['school_id'],
            $filters['session_id'],
            $filters['class_ids'],
            $filters['section_ids'],
            $filters['shift_id'],
            $filters['department_id']
        );
        $eligibleMap = [];
        foreach ($eligibleStudents as $student) {
            $eligibleMap[(int) $student->student_id] = $student;
        }
        $students = [];
        foreach ($filters['student_ids'] as $studentId) {
            if (!isset($eligibleMap[$studentId])) {
                throw new InvalidArgumentException(lang('SeatPlan.invalid_student'));
            }
            $students[] = $eligibleMap[$studentId];
        }

        $roomRecords = $this->RoomModel
            ->where('school_id', $filters['school_id'])
            ->where('status', 1)
            ->whereIn('id', $filters['room_ids'])
            ->findAll();
        $roomRecordMap = [];
        foreach ($roomRecords as $room) {
            $roomRecordMap[(int) $room->id] = $room;
        }
        if (count($roomRecordMap) !== count($filters['room_ids'])) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_room'));
        }

        $rooms = [];
        foreach ($filters['room_ids'] as $sortOrder => $roomId) {
            $room = $roomRecordMap[$roomId];
            $rooms[] = [
                'room_id' => (int) $room->id,
                'room_name' => $room->room_name,
                'room_no' => $room->room_no,
                'capacity' => (int) $room->capacity,
                'rows_count' => (int) $room->rows_count,
                'columns_count' => (int) $room->columns_count,
                'sort_order' => $sortOrder,
            ];
        }

        $result = $this->AllocationService->generate(
            $students,
            $rooms,
            $filters['allocation_method'],
            $filters['seat_number_format'],
            $filters['random_seed']
        );

        return [
            'filters' => $filters,
            'exam' => $exam,
            'students' => $students,
            'student_map' => $eligibleMap,
            'rooms' => $rooms,
            'room_map' => $roomRecordMap,
            'result' => $result,
        ];
    }

    private function validatedSelectionFilters(bool $requireStudents): array
    {
        $schoolId = (int) $this->request->getPost('school_id');
        $sessionId = (int) $this->request->getPost('session_id');
        $examId = (int) $this->request->getPost('exam_id');
        $shiftId = (int) $this->request->getPost('shift_id');
        $departmentId = (int) $this->request->getPost('department_id');
        $classIds = $this->positiveUniqueIds($this->request->getPost('class_ids'));
        $sectionIds = $this->positiveUniqueIds($this->request->getPost('section_ids'));
        $studentIds = $this->positiveUniqueIds($this->request->getPost('student_ids'));
        $roomIds = $this->positiveUniqueIds($this->request->getPost('room_ids'));

        if (!$this->isAuthorizedSchool($schoolId)) {
            throw new InvalidArgumentException(lang('SeatPlan.access_denied'));
        }
        if ($sessionId <= 0 || $classIds === []) {
            throw new InvalidArgumentException(lang('SeatPlan.select_session_class'));
        }
        if (!$this->recordsBelongToSchool($this->YearModel, [$sessionId], $schoolId)
            || !$this->recordsBelongToSchool($this->ClassModel, $classIds, $schoolId)
            || ($sectionIds !== [] && !$this->recordsBelongToSchool($this->SectionModel, $sectionIds, $schoolId))) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_academic_selection'));
        }
        if ($shiftId > 0 && !$this->recordsBelongToSchool($this->ShiftModel, [$shiftId], $schoolId)) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_shift'));
        }
        if ($departmentId > 0 && !$this->recordsBelongToSchool($this->DepartmentModel, [$departmentId], $schoolId)) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_department'));
        }
        if ($requireStudents && ($examId <= 0 || $studentIds === [] || $roomIds === [])) {
            throw new InvalidArgumentException(lang('SeatPlan.select_exam_students_rooms'));
        }

        $title = trim((string) $this->request->getPost('title'));
        if ($requireStudents && ($title === '' || mb_strlen($title) > 255)) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_title'));
        }
        $examDate = trim((string) $this->request->getPost('exam_date'));
        if ($examDate !== '' && !$this->validDate($examDate)) {
            throw new InvalidArgumentException(lang('SeatPlan.invalid_date'));
        }

        $allocationMethod = trim((string) (
            $this->request->getPost('allocation_method')
            ?: SeatAllocationService::METHOD_SEQUENTIAL
        ));

        return [
            'school_id' => $schoolId,
            'session_id' => $sessionId,
            'exam_id' => $examId,
            'shift_id' => $shiftId > 0 ? $shiftId : null,
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'class_ids' => $classIds,
            'section_ids' => $sectionIds,
            'student_ids' => $studentIds,
            'room_ids' => $roomIds,
            'title' => $title,
            'exam_date' => $examDate !== '' ? $examDate : null,
            'allocation_method' => $allocationMethod,
            'seat_number_format' => trim((string) ($this->request->getPost('seat_number_format') ?: SeatAllocationService::FORMAT_NUMERIC)),
            'random_seed' => $allocationMethod === SeatAllocationService::METHOD_RANDOM
                ? $this->validatedRandomSeed((string) $this->request->getPost('random_seed'))
                : null,
        ];
    }

    private function validatedRandomSeed(string $seed): string
    {
        $seed = strtolower(trim($seed));
        return preg_match('/^[a-f0-9]{32,64}$/', $seed) ? $seed : bin2hex(random_bytes(16));
    }

    private function loadEligibleStudents(
        int $schoolId,
        int $sessionId,
        array $classIds,
        array $sectionIds,
        ?int $shiftId = null,
        ?int $departmentId = null
    ): array
    {
        $builder = $this->EnrollmentModel
            ->select("student_enrollments.id AS enrollment_id, student_enrollments.student_id, student_enrollments.session_id, student_enrollments.class_id, student_enrollments.section_id, student_enrollments.shift_id, student_enrollments.department_id, student_enrollments.group_id, student_enrollments.roll_no, students.student_code, students.first_name, students.middle_name, students.last_name, academic_classes.title AS class_name, academic_sections.title AS section_name")
            ->join('students', 'students.id = student_enrollments.student_id', 'inner')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = student_enrollments.section_id', 'left')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('students.school_id', $schoolId)
            ->where('student_enrollments.session_id', $sessionId)
            ->whereIn('student_enrollments.class_id', $classIds)
            ->where('student_enrollments.status', 1)
            ->where('students.status', 1)
            ->where('students.student_status', 'Active');
        if ($sectionIds !== []) {
            $builder->whereIn('student_enrollments.section_id', $sectionIds);
        }
        if ($shiftId) {
            $builder->where('student_enrollments.shift_id', $shiftId);
        }
        if ($departmentId) {
            $builder->where('student_enrollments.department_id', $departmentId);
        }

        return $builder
            ->orderBy('student_enrollments.class_id', 'ASC')
            ->orderBy('student_enrollments.section_id', 'ASC')
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->findAll();
    }

    private function studentResponseRow(object $student): array
    {
        return [
            'student_id' => (int) $student->student_id,
            'enrollment_id' => (int) $student->enrollment_id,
            'student_code' => $student->student_code,
            'student_name' => trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name]))),
            'class_name' => $student->class_name,
            'section_name' => $student->section_name,
            'roll_no' => $student->roll_no,
        ];
    }

    private function positiveUniqueIds($values): array
    {
        if (!is_array($values)) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $values), static fn (int $id): bool => $id > 0)));
        return $ids;
    }

    private function decodedIds(?string $json): array
    {
        $values = json_decode((string) $json, true);
        return $this->positiveUniqueIds(is_array($values) ? $values : []);
    }

    private function recordsBelongToSchool($model, array $ids, int $schoolId): bool
    {
        return $ids !== [] && $model->where('school_id', $schoolId)->where('status', 1)->whereIn('id', $ids)->countAllResults() === count($ids);
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function optionRows(array $rows): array
    {
        return array_map(static fn ($row): array => ['id' => (int) $row->id, 'title' => $row->title], $rows);
    }

    private function seatPlanReportData(string $token, string $report): ?array
    {
        $reportTitles = [
            'visual' => lang('SeatPlan.visual_plan'),
            'room-list' => lang('SeatPlan.room_student_list'),
            'class-list' => lang('SeatPlan.class_wise_list'),
            'door-notice' => lang('SeatPlan.door_notice'),
            'seat-slips' => lang('SeatPlan.seat_slips'),
        ];
        if (!isset($reportTitles[$report])) {
            return null;
        }

        $plan = $this->findAuthorizedPlan($token);
        if (!$plan) {
            return null;
        }

        $rooms = $this->SeatPlanRoomModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        $allocations = $this->AllocationModel
            ->select("examination_seat_allocations.*, TRIM(CONCAT_WS(' ', NULLIF(report_students.first_name, ''), NULLIF(report_students.middle_name, ''), NULLIF(report_students.last_name, ''))) AS student_name, report_students.student_code, academic_classes.title AS class_name, academic_sections.title AS section_name")
            ->join('students AS report_students', 'report_students.id = examination_seat_allocations.student_id AND report_students.school_id = examination_seat_allocations.school_id', 'left')
            ->join('academic_classes', 'academic_classes.id = examination_seat_allocations.class_id AND academic_classes.school_id = examination_seat_allocations.school_id', 'left')
            ->join('academic_sections', 'academic_sections.id = examination_seat_allocations.section_id AND academic_sections.school_id = examination_seat_allocations.school_id', 'left')
            ->where('examination_seat_allocations.school_id', $plan->school_id)
            ->where('examination_seat_allocations.seat_plan_id', $plan->id)
            ->orderBy('examination_seat_allocations.room_id', 'ASC')
            ->orderBy('examination_seat_allocations.row_no', 'ASC')
            ->orderBy('examination_seat_allocations.column_no', 'ASC')
            ->findAll();

        $allAllocations = $allocations;
        $selectedRoomId = 0;
        $selectedStudentIds = [];
        if ($report === 'seat-slips') {
            $roomFilter = $this->request->getGet('room_id');
            if ($roomFilter !== null && $roomFilter !== ''
                && (!is_scalar($roomFilter) || !ctype_digit((string) $roomFilter))) {
                return null;
            }
            $selectedRoomId = max(0, (int) $roomFilter);
            $roomIds = array_map(static fn ($room): int => (int) $room->room_id, $rooms);
            if ($selectedRoomId > 0 && !in_array($selectedRoomId, $roomIds, true)) {
                return null;
            }

            $studentFilter = $this->request->getGet('student_ids');
            if ($studentFilter !== null && !is_array($studentFilter)) {
                return null;
            }
            foreach ($studentFilter ?? [] as $studentId) {
                if (!ctype_digit((string) $studentId) || (int) $studentId <= 0) {
                    return null;
                }
            }
            $selectedStudentIds = $this->positiveUniqueIds($studentFilter);
            $allocatedStudentIds = array_map(static fn ($allocation): int => (int) $allocation->student_id, $allAllocations);
            if (array_diff($selectedStudentIds, $allocatedStudentIds) !== []) {
                return null;
            }

            $allocations = array_values(array_filter(
                $allocations,
                static fn ($allocation): bool => ($selectedRoomId === 0 || (int) $allocation->room_id === $selectedRoomId)
                    && ($selectedStudentIds === [] || in_array((int) $allocation->student_id, $selectedStudentIds, true))
            ));
        }

        $allocationsByRoom = [];
        foreach ($rooms as $room) {
            $allocationsByRoom[(int) $room->room_id] = [];
        }
        foreach ($allocations as $allocation) {
            $allocationsByRoom[(int) $allocation->room_id][] = $allocation;
        }

        $seatSlipAllocations = [];
        foreach ($rooms as $room) {
            foreach ($allocationsByRoom[(int) $room->room_id] as $allocation) {
                $seatSlipAllocations[] = $allocation;
            }
        }

        $classAllocations = $allocations;
        usort($classAllocations, static function ($left, $right): int {
            foreach (['class_name', 'section_name', 'roll_no_snapshot', 'student_name'] as $field) {
                $comparison = strnatcasecmp((string) ($left->{$field} ?? ''), (string) ($right->{$field} ?? ''));
                if ($comparison !== 0) {
                    return $comparison;
                }
            }
            return 0;
        });

        $school = $this->SchoolModel->find($plan->school_id);
        return [
            'plan' => $plan,
            'exam' => $this->ExamModel->where('school_id', $plan->school_id)->find($plan->exam_id),
            'school' => $school,
            'school_logo_url' => $school && !empty($school->logo)
                ? base_url('uploads/' . ltrim($school->logo, '/'))
                : null,
            'rooms' => $rooms,
            'allocations' => $allocations,
            'all_allocations' => $allAllocations,
            'allocations_by_room' => $allocationsByRoom,
            'class_allocations' => $classAllocations,
            'seat_slip_allocations' => $seatSlipAllocations,
            'selected_room_id' => $selectedRoomId,
            'selected_student_ids' => $selectedStudentIds,
            'report_type' => $report,
            'report_titles' => $reportTitles,
        ];
    }

    private function authorizedSchoolIds(): array
    {
        return array_values(array_map(static fn ($school): int => (int) $school->id, $this->getUserSchools()));
    }

    private function isAuthorizedSchool(int $schoolId): bool
    {
        return $schoolId > 0 && in_array($schoolId, $this->authorizedSchoolIds(), true);
    }

    private function findAuthorizedPlan(string $token): ?object
    {
        $schoolIds = $this->authorizedSchoolIds();
        if ($token === '' || $schoolIds === []) {
            return null;
        }
        return $this->SeatPlanModel->where('token', $token)->whereIn('school_id', $schoolIds)->first();
    }

    private function editablePlan(string $token): ?object
    {
        $plan = $this->findAuthorizedPlan($token);
        return $plan && !(int) $plan->is_locked ? $plan : null;
    }

    private function seatPositions(array $rooms, string $format): array
    {
        $positions = [];
        foreach ($rooms as $room) {
            $seatIndex = 0;
            $capacity = min((int) $room->capacity, (int) $room->rows_count * (int) $room->columns_count);
            for ($row = 1; $row <= (int) $room->rows_count; $row++) {
                for ($column = 1; $column <= (int) $room->columns_count; $column++) {
                    if ($seatIndex >= $capacity) {
                        break 2;
                    }
                    $seatIndex++;
                    $seatNo = $format === SeatAllocationService::FORMAT_ALPHA_NUMERIC
                        ? 'A' . str_pad((string) $seatIndex, max(2, strlen((string) $capacity)), '0', STR_PAD_LEFT)
                        : (string) $seatIndex;
                    $key = (int) $room->room_id . ':' . $row . ':' . $column;
                    $positions[$key] = [
                        'key' => $key,
                        'room_id' => (int) $room->room_id,
                        'room_name' => $room->room_name_snapshot,
                        'room_no' => $room->room_no_snapshot,
                        'row_no' => $row,
                        'column_no' => $column,
                        'seat_no' => $seatNo,
                    ];
                }
            }
        }
        return $positions;
    }

    private function validatedPosition(object $plan, string $positionKey): ?array
    {
        $rooms = $this->SeatPlanRoomModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        $positions = $this->seatPositions($rooms, $plan->seat_number_format);
        return $positions[$positionKey] ?? null;
    }

    private function positionOccupied(object $plan, array $position, ?int $exceptAllocationId = null): bool
    {
        $builder = $this->AllocationModel
            ->where('school_id', $plan->school_id)
            ->where('seat_plan_id', $plan->id)
            ->where('room_id', $position['room_id'])
            ->where('row_no', $position['row_no'])
            ->where('column_no', $position['column_no']);
        if ($exceptAllocationId) {
            $builder->where('id !=', $exceptAllocationId);
        }
        return $builder->countAllResults() > 0;
    }

    private function positionData(array $position): array
    {
        return [
            'room_id' => $position['room_id'],
            'seat_no' => $position['seat_no'],
            'row_no' => $position['row_no'],
            'column_no' => $position['column_no'],
        ];
    }

    private function allocationPositionData(object $allocation): array
    {
        return [
            'room_id' => (int) $allocation->room_id,
            'seat_no' => $allocation->seat_no,
            'row_no' => (int) $allocation->row_no,
            'column_no' => (int) $allocation->column_no,
        ];
    }

    private function hasPermission(string $slug): bool
    {
        $permission = $this->db->table('permissions')->where('slug', $slug)->where('status', 1)->get()->getRow();
        if (!$permission) {
            return true;
        }
        return $this->db->table('role_permissions rp')
            ->join('roles r', 'r.id = rp.role_id')
            ->where('r.slug', (string) session('role'))
            ->where('rp.permission_id', $permission->id)
            ->countAllResults() > 0;
    }

    private function requirePermission(string $slug)
    {
        return $this->hasPermission($slug)
            ? null
            : redirect()->to('unauthorized')->with('error', lang('SeatPlan.access_denied'));
    }

    private function requireAjaxPermission(string $slug)
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.invalid_request')], 400);
        }
        return $this->hasPermission($slug)
            ? null
            : $this->jsonResponse(['status' => false, 'message' => lang('SeatPlan.access_denied')], 403);
    }

    private function requirePaidSeatPlan(string $token)
    {
        $plan = $this->findAuthorizedPlan($token);
        if (!$plan) return redirect()->to('examination/seat-plans')->with('error', lang('SeatPlan.not_found'));
        return (new ServicePricingService())->isPaid((int)$plan->school_id, ServicePricingService::SEAT_PLAN, (int)$plan->exam_id)
            ? null
            : redirect()->to('school-owner/billing')->with('error', 'Final Seat Plan Print/PDF-এর জন্য পেমেন্ট সম্পন্ন করুন।');
    }

    private function render(string $view, string $title, array $data)
    {
        return view('header', ['page_title' => $title, 'body_class' => 'nav-md', 'admin_area' => 'yes'])
            . view("App\\Modules\\examination\\Views\\seat_plans\\{$view}", $data)
            . view('footer', ['admin_area' => 'yes']);
    }

    protected function jsonResponse(array $response, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
