<?php

namespace App\Modules\examination\Controllers;

use App\Modules\examination\Models\ExamRoomModel;
use CodeIgniter\HTTP\RedirectResponse;

class ExamRoomController extends BaseController
{
    protected ExamRoomModel $RoomModel;

    public function __construct()
    {
        parent::__construct();
        $this->RoomModel = new ExamRoomModel();
    }

    public function index()
    {
        if ($response = $this->requirePermission('exam_room_view')) {
            return $response;
        }

        $schoolId = (int) $this->request->getGet('school_id');
        $schoolIds = $this->getAuthorizedSchoolIds();
        if ($schoolId > 0 && !in_array($schoolId, $schoolIds, true)) {
            return redirect()->to('examination/exam-rooms')->with('error', lang('ExamRoom.access_denied'));
        }

        $text = trim((string) $this->request->getGet('text'));
        $status = $this->request->getGet('status');
        $show = (int) $this->request->getGet('show');

        $this->RoomModel
            ->select('examination_rooms.*, schools.name AS school_name')
            ->join('schools', 'schools.id = examination_rooms.school_id', 'left');

        if ($schoolIds === []) {
            $this->RoomModel->where('1 = 0');
        } else {
            $this->RoomModel->whereIn('examination_rooms.school_id', $schoolIds);
        }
        if ($schoolId > 0) {
            $this->RoomModel->where('examination_rooms.school_id', $schoolId);
        }
        if ($text !== '') {
            $this->RoomModel->groupStart()
                ->like('examination_rooms.room_name', $text)
                ->orLike('examination_rooms.room_no', $text)
                ->orLike('examination_rooms.building', $text)
                ->groupEnd();
        }
        if ($status !== null && $status !== '') {
            $this->RoomModel->where('examination_rooms.status', (int) $status);
        }

        $perPage = $show > 0 ? min($show, 1000) : ((int) setting('application', 'per_item_in_list') ?: 10);
        $data = [
            'items'           => $this->RoomModel->orderBy('examination_rooms.room_name', 'ASC')->paginate($perPage),
            'pager'           => $this->RoomModel->pager,
            'pagerTemplate'   => 'custom_pagination',
            'school_list'     => $this->getSchoolDropdown(),
            'selected_school' => $schoolId,
            'text'            => $text,
            'status'          => $status,
            'show'            => $show,
            'permissions'     => $this->roomPermissions(),
        ];

        return $this->render('list', lang('ExamRoom.page_title_list'), $data);
    }

    public function create()
    {
        if ($response = $this->requirePermission('exam_room_create')) {
            return $response;
        }

        $schools = $this->getSchoolDropdown();
        return $this->render('form', lang('ExamRoom.page_title_new'), [
            'post_data'   => count($schools) === 1 ? ['school_id' => array_key_first($schools), 'status' => 1] : ['status' => 1],
            'is_edit'     => false,
            'school_list' => $schools,
        ]);
    }

    public function edit(string $token)
    {
        if ($response = $this->requirePermission('exam_room_edit')) {
            return $response;
        }

        $room = $this->findAuthorizedRoom($token);
        if (!$room) {
            return redirect()->to('examination/exam-rooms')->with('error', lang('ExamRoom.not_found'));
        }

        return $this->render('form', lang('ExamRoom.page_title_edit'), [
            'post_data'   => (array) $room,
            'is_edit'     => true,
            'token'       => $room->token,
            'school_list' => $this->getSchoolDropdown(),
        ]);
    }

    public function store()
    {
        if ($response = $this->requirePermission('exam_room_create')) {
            return $response;
        }
        return $this->saveRoom();
    }

    public function update(string $token)
    {
        if ($response = $this->requirePermission('exam_room_edit')) {
            return $response;
        }
        return $this->saveRoom($token);
    }

    public function changeStatus(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-rooms');
        }
        if ($response = $this->requirePermission('exam_room_edit', true)) {
            return $response;
        }

        $room = $this->findAuthorizedRoom($token);
        $status = (int) $this->request->getPost('status');
        if (!$room || !in_array($status, [0, 1], true)) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.invalid_request')]);
        }

        $updated = $this->RoomModel->update($room->id, [
            'status'     => $status,
            'updated_by' => $this->getUserId(),
        ]);

        return $this->jsonResponse([
            'status'  => (bool) $updated,
            'message' => $updated ? lang('ExamRoom.status_saved') : lang('ExamRoom.save_failed'),
        ]);
    }

    public function trash(string $token)
    {
        return $this->setTrashStatus($token, 2, 'ExamRoom.trashed');
    }

    public function restore(string $token)
    {
        return $this->setTrashStatus($token, 1, 'ExamRoom.restored');
    }

    public function delete(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-rooms');
        }
        if ($response = $this->requirePermission('exam_room_delete', true)) {
            return $response;
        }

        $room = $this->findAuthorizedRoom($token);
        if (!$room) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.not_found')]);
        }
        if ((int) $room->status !== 2) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.trash_before_delete')]);
        }
        if ($this->roomIsReferenced((int) $room->id)) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.delete_in_use')]);
        }

        $deleted = $this->RoomModel->delete($room->id);
        return $this->jsonResponse([
            'status'  => (bool) $deleted,
            'message' => $deleted ? lang('ExamRoom.deleted') : lang('ExamRoom.delete_failed'),
        ]);
    }

    private function saveRoom(?string $token = null)
    {
        $subscriptionCheck = check_subscription('examination/exam-rooms');
        if ($subscriptionCheck) {
            return $subscriptionCheck;
        }

        $room = null;
        if ($token !== null) {
            $room = $this->findAuthorizedRoom($token);
            if (!$room) {
                return redirect()->to('examination/exam-rooms')->with('error', lang('ExamRoom.not_found'));
            }
        }

        $postData = $this->request->getPost();
        $rules = [
            'school_id'     => 'required|is_natural_no_zero',
            'room_name'     => 'required|max_length[150]',
            'room_no'       => 'required|max_length[50]',
            'building'      => 'permit_empty|max_length[150]',
            'floor'         => 'permit_empty|max_length[50]',
            'capacity'      => 'required|is_natural_no_zero|less_than_equal_to[100000]',
            'rows_count'    => 'required|is_natural_no_zero|less_than_equal_to[1000]',
            'columns_count' => 'required|is_natural_no_zero|less_than_equal_to[1000]',
            'status'        => 'required|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return $this->renderRoomForm($postData, $room, $this->validator);
        }

        $schoolId = (int) $postData['school_id'];
        if (!in_array($schoolId, $this->getAuthorizedSchoolIds(), true)) {
            return redirect()->to('examination/exam-rooms')->with('error', lang('ExamRoom.access_denied'));
        }

        $capacity = (int) $postData['capacity'];
        $rows = (int) $postData['rows_count'];
        $columns = (int) $postData['columns_count'];
        if ($capacity > ($rows * $columns)) {
            return $this->renderRoomForm($postData, $room, null, lang('ExamRoom.capacity_exceeds_layout'));
        }

        $duplicate = $this->RoomModel
            ->where('school_id', $schoolId)
            ->where('room_no', trim((string) $postData['room_no']));
        if ($room) {
            $duplicate->where('id !=', $room->id);
        }
        if ($duplicate->countAllResults() > 0) {
            return $this->renderRoomForm($postData, $room, null, lang('ExamRoom.duplicate_room_no'));
        }

        $saveData = [
            'school_id'        => $schoolId,
            'school_owner_uid' => $this->getUserId(),
            'room_name'        => trim((string) $postData['room_name']),
            'room_no'          => trim((string) $postData['room_no']),
            'building'         => $this->nullableTrim($postData['building'] ?? null),
            'floor'            => $this->nullableTrim($postData['floor'] ?? null),
            'capacity'         => $capacity,
            'rows_count'       => $rows,
            'columns_count'    => $columns,
            'status'           => (int) $postData['status'],
            'updated_by'       => $this->getUserId(),
        ];

        if ($room) {
            $saved = $this->RoomModel->update($room->id, $saveData);
        } else {
            $saveData['token'] = bin2hex(random_bytes(16));
            $saveData['created_by'] = $this->getUserId();
            $saved = $this->RoomModel->insert($saveData) !== false;
        }

        if (!$saved) {
            return $this->renderRoomForm($postData, $room, null, lang('ExamRoom.save_failed'));
        }

        return redirect()->to('examination/exam-rooms')->with('success', lang('ExamRoom.saved'));
    }

    private function setTrashStatus(string $token, int $status, string $messageKey)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-rooms');
        }
        if ($response = $this->requirePermission('exam_room_delete', true)) {
            return $response;
        }

        $room = $this->findAuthorizedRoom($token);
        if (!$room) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.not_found')]);
        }

        $updated = $this->RoomModel->update($room->id, [
            'status'     => $status,
            'updated_by' => $this->getUserId(),
        ]);
        return $this->jsonResponse([
            'status'  => (bool) $updated,
            'message' => $updated ? lang($messageKey) : lang('ExamRoom.save_failed'),
        ]);
    }

    private function renderRoomForm(array $postData, ?object $room, $validation = null, ?string $error = null)
    {
        return $this->render('form', $room ? lang('ExamRoom.page_title_edit') : lang('ExamRoom.page_title_new'), [
            'post_data'   => $postData,
            'is_edit'     => $room !== null,
            'token'       => $room->token ?? '',
            'school_list' => $this->getSchoolDropdown(),
            'validation'  => $validation,
            'error'       => $error,
        ]);
    }

    private function render(string $view, string $title, array $data)
    {
        $header = ['page_title' => $title, 'body_class' => 'nav-md', 'admin_area' => 'yes'];
        return view('header', $header)
            . view("App\\Modules\\examination\\Views\\rooms\\{$view}", $data)
            . view('footer', ['admin_area' => 'yes']);
    }

    private function getAuthorizedSchoolIds(): array
    {
        return array_values(array_map(static fn ($school): int => (int) $school->id, $this->getUserSchools()));
    }

    private function findAuthorizedRoom(string $token): ?object
    {
        $schoolIds = $this->getAuthorizedSchoolIds();
        if ($token === '' || $schoolIds === []) {
            return null;
        }

        return $this->RoomModel->where('token', $token)->whereIn('school_id', $schoolIds)->first();
    }

    private function roomIsReferenced(int $roomId): bool
    {
        foreach (['examination_seat_plan_rooms', 'examination_seat_allocations'] as $table) {
            if ($this->db->tableExists($table) && $this->db->table($table)->where('room_id', $roomId)->countAllResults() > 0) {
                return true;
            }
        }
        return false;
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function roomPermissions(): array
    {
        return [
            'create' => $this->hasPermission('exam_room_create'),
            'edit'   => $this->hasPermission('exam_room_edit'),
            'delete' => $this->hasPermission('exam_room_delete'),
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

    private function requirePermission(string $slug, bool $json = false)
    {
        if ($this->hasPermission($slug)) {
            return null;
        }

        if ($json) {
            return $this->jsonResponse(['status' => false, 'message' => lang('ExamRoom.access_denied')], 403);
        }
        return redirect()->to('unauthorized')->with('error', lang('ExamRoom.access_denied'));
    }

    protected function jsonResponse(array $response, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
