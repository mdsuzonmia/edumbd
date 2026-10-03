<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\MarkModel;

class MarksController extends BaseController
{
    protected MarkModel $MarkModel;

    public function __construct()
    {
        $this->MarkModel = new MarkModel();
    }

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
        $model = new \App\Models\UserModel();
        $user = $model->where('api_token', $token)->where('status', 1)->first();
        return $user ?: null;
    }

    protected function jsonResponse(array $response, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($response);
    }

    public function index()
    {
        $token = $this->getToken();
        $user = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $school_id = (int) $this->request->getGet('school_id');
        $status = $this->request->getGet('status');

        $this->MarkModel->where('school_id', $school_id);
        if ($status !== null) {
            $this->MarkModel->where('status', (int) $status);
        }

        $items = $this->MarkModel->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data' => $items,
        ]);
    }

    public function show($id)
    {
        $token = $this->getToken();
        $user = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->MarkModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        return $this->jsonResponse([
            'status' => true,
            'data' => $record,
        ]);
    }

    public function store()
    {
        $token = $this->getToken();
        $user = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $post_data = $this->request->getPost();

        $validationRule = [
            'exam_id' => 'required|is_natural_no_zero',
            'student_id' => 'required|is_natural_no_zero',
            'class_id' => 'required|is_natural_no_zero',
            'subject_id' => 'required|is_natural_no_zero',
            'school_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
            ], 400);
        }

        $saveData = [
            'exam_id' => (int) $post_data['exam_id'],
            'student_id' => (int) $post_data['student_id'],
            'class_id' => (int) $post_data['class_id'],
            'section_id' => !empty($post_data['section_id']) ? (int) $post_data['section_id'] : null,
            'subject_id' => (int) $post_data['subject_id'],
            'distribution_id' => (int) $post_data['distribution_id'],
            'full_mark' => (float) ($post_data['full_mark'] ?? 0),
            'obtained_mark' => (float) ($post_data['obtained_mark'] ?? 0),
            'is_absent' => !empty($post_data['is_absent']) ? 1 : 0,
            'remarks' => $post_data['remarks'] ?? null,
            'school_id' => (int) $post_data['school_id'],
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $id = $this->MarkModel->insert($saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Mark created successfully',
            'data' => ['id' => $id],
        ]);
    }

    public function update($id)
    {
        $token = $this->getToken();
        $user = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->MarkModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'exam_id' => (int) $post_data['exam_id'],
            'student_id' => (int) $post_data['student_id'],
            'class_id' => (int) $post_data['class_id'],
            'section_id' => !empty($post_data['section_id']) ? (int) $post_data['section_id'] : null,
            'subject_id' => (int) $post_data['subject_id'],
            'distribution_id' => (int) $post_data['distribution_id'],
            'full_mark' => (float) ($post_data['full_mark'] ?? 0),
            'obtained_mark' => (float) ($post_data['obtained_mark'] ?? 0),
            'is_absent' => !empty($post_data['is_absent']) ? 1 : 0,
            'remarks' => $post_data['remarks'] ?? null,
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'updated_by' => $user->id,
        ];

        $this->MarkModel->update($id, $saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Mark updated successfully',
        ]);
    }
}