<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\SubjectDistributionModel;

class SubjectDistributionsController extends BaseController
{
    protected SubjectDistributionModel $SubjectDistributionModel;

    public function __construct()
    {
        $this->SubjectDistributionModel = new SubjectDistributionModel();
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

        $this->SubjectDistributionModel->where('school_id', $school_id);
        if ($status !== null) {
            $this->SubjectDistributionModel->where('status', (int) $status);
        }

        $items = $this->SubjectDistributionModel->findAll();

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

        $record = $this->SubjectDistributionModel->find($id);
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
            'subject_id' => 'required|is_natural_no_zero',
            'distribution_id' => 'required|is_natural_no_zero',
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
            'subject_id' => (int) $post_data['subject_id'],
            'distribution_id' => (int) $post_data['distribution_id'],
            'full_mark' => (float) ($post_data['full_mark'] ?? 0),
            'pass_mark' => (float) ($post_data['pass_mark'] ?? 0),
            'weight_percent' => (float) ($post_data['weight_percent'] ?? 0),
            'sort_order' => (int) ($post_data['sort_order'] ?? 0),
            'school_id' => (int) $post_data['school_id'],
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $id = $this->SubjectDistributionModel->insert($saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Subject Distribution created successfully',
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

        $record = $this->SubjectDistributionModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'subject_id' => (int) $post_data['subject_id'],
            'distribution_id' => (int) $post_data['distribution_id'],
            'full_mark' => (float) ($post_data['full_mark'] ?? 0),
            'pass_mark' => (float) ($post_data['pass_mark'] ?? 0),
            'weight_percent' => (float) ($post_data['weight_percent'] ?? 0),
            'sort_order' => (int) ($post_data['sort_order'] ?? 0),
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'updated_by' => $user->id,
        ];

        $this->SubjectDistributionModel->update($id, $saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Subject Distribution updated successfully',
        ]);
    }
}