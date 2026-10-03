<?php

namespace App\Modules\teachers\Controllers;

use App\Controllers\BaseController;
use App\Modules\teachers\Models\TeacherQualificationModel;
use App\Models\UserModel;

class TeacherQualificationsController extends BaseController
{
    protected TeacherQualificationModel $QualificationModel;
    protected UserModel $UserModel;

    public function __construct()
    {
        $this->QualificationModel = new TeacherQualificationModel();
        $this->UserModel          = new UserModel();
    }

    // ─── Token Helpers ───────────────────────────────────────────────

    protected function getToken(): ?string
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return $this->request->getPost('token') ?? $this->request->getGet('token');
    }

    protected function verifyToken(string $token): ?object
    {
        $user = $this->UserModel->where('api_token', $token)->where('status', 1)->first();
        return $user ?: null;
    }

    protected function jsonResponse(array $response, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($response);
    }

    // ─── CRUD ────────────────────────────────────────────────────────

    public function index()
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $school_owner_uid = $user->id;
        $teacher_id       = $this->request->getGet('teacher_id');

        $this->QualificationModel->where('school_owner_uid', $school_owner_uid);

        if ($teacher_id) {
            $this->QualificationModel->where('teacher_id', (int) $teacher_id);
        }

        $items = $this->QualificationModel->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }

    public function show($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->QualificationModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        return $this->jsonResponse([
            'status' => true,
            'data'   => $record,
        ]);
    }

    public function store()
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $post_data = $this->request->getPost();

        $validationRule = [
            'teacher_id'  => 'required|is_natural_no_zero',
            'degree_name' => 'required|max_length[150]',
        ];

        if (!$this->validate($validationRule)) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $this->validator->getErrors(),
            ], 400);
        }

        $saveData = [
            'school_owner_uid' => $user->id,
            'teacher_id'       => (int) $post_data['teacher_id'],
            'degree_name'      => $post_data['degree_name'],
            'group_name'       => $post_data['group_name'] ?? null,
            'subject_name'     => $post_data['subject_name'] ?? null,
            'institution_name' => $post_data['institution_name'] ?? null,
            'board_university' => $post_data['board_university'] ?? null,
            'passing_year'     => !empty($post_data['passing_year']) ? (int) $post_data['passing_year'] : null,
            'result'           => $post_data['result'] ?? null,
            'result_type'      => $post_data['result_type'] ?? 'CGPA',
            'attachment'       => $post_data['attachment'] ?? null,
            'remarks'          => $post_data['remarks'] ?? null,
        ];

        $id = $this->QualificationModel->insert($saveData);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Qualification created successfully',
            'data'    => ['qualification_id' => $id],
        ]);
    }

    public function update($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->QualificationModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'degree_name'      => $post_data['degree_name'] ?? $record->degree_name,
            'group_name'       => $post_data['group_name'] ?? $record->group_name,
            'subject_name'     => $post_data['subject_name'] ?? $record->subject_name,
            'institution_name' => $post_data['institution_name'] ?? $record->institution_name,
            'board_university' => $post_data['board_university'] ?? $record->board_university,
            'passing_year'     => isset($post_data['passing_year']) ? (int) $post_data['passing_year'] : $record->passing_year,
            'result'           => $post_data['result'] ?? $record->result,
            'result_type'      => $post_data['result_type'] ?? $record->result_type,
            'attachment'       => $post_data['attachment'] ?? $record->attachment,
            'remarks'          => $post_data['remarks'] ?? $record->remarks,
        ];

        $this->QualificationModel->update($id, $saveData);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Qualification updated successfully',
        ]);
    }

    public function delete($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->QualificationModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $this->QualificationModel->delete($id);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Qualification deleted successfully',
        ]);
    }

    public function byTeacher($teacher_id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $this->QualificationModel
            ->where('school_owner_uid', $user->id)
            ->where('teacher_id', (int) $teacher_id)
            ->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }
}