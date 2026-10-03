<?php

namespace App\Modules\teachers\Controllers;

use App\Controllers\BaseController;
use App\Modules\teachers\Models\TeacherSubjectModel;
use App\Models\UserModel;

class TeacherSubjectsController extends BaseController
{
    protected TeacherSubjectModel $SubjectModel;
    protected UserModel $UserModel;

    public function __construct()
    {
        $this->SubjectModel = new TeacherSubjectModel();
        $this->UserModel    = new UserModel();
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
        $academic_year_id = $this->request->getGet('academic_year_id');
        $class_id         = $this->request->getGet('class_id');

        $this->SubjectModel->where('school_owner_uid', $school_owner_uid);

        if ($teacher_id) {
            $this->SubjectModel->where('teacher_id', (int) $teacher_id);
        }
        if ($academic_year_id) {
            $this->SubjectModel->where('academic_year_id', (int) $academic_year_id);
        }
        if ($class_id) {
            $this->SubjectModel->where('class_id', (int) $class_id);
        }

        $items = $this->SubjectModel->findAll();

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

        $record = $this->SubjectModel->find($id);
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
            'teacher_id'       => 'required|is_natural_no_zero',
            'academic_year_id' => 'required|is_natural_no_zero',
            'class_id'         => 'required|is_natural_no_zero',
            'subject_id'       => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $this->validator->getErrors(),
            ], 400);
        }

        $saveData = [
            'school_owner_uid'  => $user->id,
            'teacher_id'        => (int) $post_data['teacher_id'],
            'academic_year_id'  => (int) $post_data['academic_year_id'],
            'class_id'          => (int) $post_data['class_id'],
            'section_id'        => !empty($post_data['section_id']) ? (int) $post_data['section_id'] : null,
            'subject_id'        => (int) $post_data['subject_id'],
            'is_primary_teacher' => !empty($post_data['is_primary_teacher']) ? 1 : 0,
        ];

        try {
            $id = $this->SubjectModel->insert($saveData);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Duplicate entry: This subject assignment already exists for this teacher.',
            ], 409);
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher subject created successfully',
            'data'    => ['teacher_subject_id' => $id],
        ]);
    }

    public function update($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->SubjectModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'teacher_id'        => isset($post_data['teacher_id']) ? (int) $post_data['teacher_id'] : $record->teacher_id,
            'academic_year_id'  => isset($post_data['academic_year_id']) ? (int) $post_data['academic_year_id'] : $record->academic_year_id,
            'class_id'          => isset($post_data['class_id']) ? (int) $post_data['class_id'] : $record->class_id,
            'section_id'        => isset($post_data['section_id']) ? (int) $post_data['section_id'] : $record->section_id,
            'subject_id'        => isset($post_data['subject_id']) ? (int) $post_data['subject_id'] : $record->subject_id,
            'is_primary_teacher' => isset($post_data['is_primary_teacher']) ? (int) $post_data['is_primary_teacher'] : $record->is_primary_teacher,
        ];

        try {
            $this->SubjectModel->update($id, $saveData);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Duplicate entry: This subject assignment already exists.',
            ], 409);
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher subject updated successfully',
        ]);
    }

    public function delete($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->SubjectModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $this->SubjectModel->delete($id);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher subject deleted successfully',
        ]);
    }

    public function byTeacher($teacher_id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $this->SubjectModel
            ->where('school_owner_uid', $user->id)
            ->where('teacher_id', (int) $teacher_id)
            ->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }

    public function bySubject($subject_id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $this->SubjectModel
            ->where('school_owner_uid', $user->id)
            ->where('subject_id', (int) $subject_id)
            ->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }
}