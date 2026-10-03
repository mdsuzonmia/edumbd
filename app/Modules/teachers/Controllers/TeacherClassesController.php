<?php

namespace App\Modules\teachers\Controllers;

use App\Controllers\BaseController;
use App\Modules\teachers\Models\TeacherClassModel;
use App\Models\UserModel;

class TeacherClassesController extends BaseController
{
    protected TeacherClassModel $ClassModel;
    protected UserModel $UserModel;

    public function __construct()
    {
        $this->ClassModel = new TeacherClassModel();
        $this->UserModel  = new UserModel();
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

        $this->ClassModel->where('school_owner_uid', $school_owner_uid);

        if ($teacher_id) {
            $this->ClassModel->where('teacher_id', (int) $teacher_id);
        }
        if ($academic_year_id) {
            $this->ClassModel->where('academic_year_id', (int) $academic_year_id);
        }
        if ($class_id) {
            $this->ClassModel->where('class_id', (int) $class_id);
        }

        $items = $this->ClassModel->findAll();

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

        $record = $this->ClassModel->find($id);
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
            'academic_year_id' => (int) $post_data['academic_year_id'],
            'class_id'         => (int) $post_data['class_id'],
            'section_id'       => !empty($post_data['section_id']) ? (int) $post_data['section_id'] : null,
            'is_class_teacher' => isset($post_data['is_class_teacher']) ? (int) $post_data['is_class_teacher'] : 1,
        ];

        try {
            $id = $this->ClassModel->insert($saveData);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Duplicate entry: This class already has a teacher assigned.',
            ], 409);
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher class assignment created successfully',
            'data'    => ['teacher_class_id' => $id],
        ]);
    }

    public function update($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->ClassModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'teacher_id'       => isset($post_data['teacher_id']) ? (int) $post_data['teacher_id'] : $record->teacher_id,
            'academic_year_id' => isset($post_data['academic_year_id']) ? (int) $post_data['academic_year_id'] : $record->academic_year_id,
            'class_id'         => isset($post_data['class_id']) ? (int) $post_data['class_id'] : $record->class_id,
            'section_id'       => isset($post_data['section_id']) ? (int) $post_data['section_id'] : $record->section_id,
            'is_class_teacher' => isset($post_data['is_class_teacher']) ? (int) $post_data['is_class_teacher'] : $record->is_class_teacher,
        ];

        try {
            $this->ClassModel->update($id, $saveData);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Duplicate entry: This class assignment already exists.',
            ], 409);
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher class assignment updated successfully',
        ]);
    }

    public function delete($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->ClassModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $this->ClassModel->delete($id);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher class assignment deleted successfully',
        ]);
    }

    public function byTeacher($teacher_id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $this->ClassModel
            ->where('school_owner_uid', $user->id)
            ->where('teacher_id', (int) $teacher_id)
            ->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }

    public function byClass($class_id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $this->ClassModel
            ->where('school_owner_uid', $user->id)
            ->where('class_id', (int) $class_id)
            ->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }
}