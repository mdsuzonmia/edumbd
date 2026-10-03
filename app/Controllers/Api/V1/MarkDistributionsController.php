<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\MarkDistributionModel;

class MarkDistributionsController extends BaseController
{
    protected MarkDistributionModel $MarkDistributionModel;

    public function __construct()
    {
        $this->MarkDistributionModel = new MarkDistributionModel();
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

        $this->MarkDistributionModel->where('school_id', $school_id);
        if ($status !== null) {
            $this->MarkDistributionModel->where('status', (int) $status);
        }

        $items = $this->MarkDistributionModel->findAll();

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

        $record = $this->MarkDistributionModel->find($id);
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
            'name' => 'required|max_length[100]',
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
            'name' => $post_data['name'],
            'code' => $post_data['code'] ?? null,
            'description' => $post_data['description'] ?? null,
            'sort_order' => (int) ($post_data['sort_order'] ?? 0),
            'school_id' => (int) $post_data['school_id'],
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $id = $this->MarkDistributionModel->insert($saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Mark Distribution created successfully',
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

        $record = $this->MarkDistributionModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'name' => $post_data['name'],
            'code' => $post_data['code'] ?? null,
            'description' => $post_data['description'] ?? null,
            'sort_order' => (int) ($post_data['sort_order'] ?? 0),
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'updated_by' => $user->id,
        ];

        $this->MarkDistributionModel->update($id, $saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Mark Distribution updated successfully',
        ]);
    }
}