<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\AcademicsSubjectModel;
use App\Models\MarkDistributionModel;
use App\Models\AcademicsGradingCategoryModel;

class SubjectsController extends BaseController
{
    protected AcademicsSubjectModel $SubjectModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected AcademicsGradingCategoryModel $GradingCategoryModel;

    public function __construct()
    {
        $this->SubjectModel          = new AcademicsSubjectModel();
        $this->MarkDistributionModel = new MarkDistributionModel();
        $this->GradingCategoryModel  = new AcademicsGradingCategoryModel();
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

        $this->SubjectModel->where('school_id', $school_id);
        if ($status !== null) {
            $this->SubjectModel->where('status', (int) $status);
        }

        $items = $this->SubjectModel->findAll();

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

        $record = $this->SubjectModel->find($id);
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
            'title' => 'required|max_length[255]',
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
            'title' => $post_data['title'],
            'short_title' => $post_data['short_title'] ?? null,
            'subject_code' => $post_data['subject_code'] ?? null,
            'grade_system_id' => !empty($post_data['grade_system_id']) ? (int) $post_data['grade_system_id'] : null,
            'school_id' => (int) $post_data['school_id'],
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $id = $this->SubjectModel->insert($saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Subject created successfully',
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

        $record = $this->SubjectModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'title' => $post_data['title'],
            'short_title' => $post_data['short_title'] ?? null,
            'subject_code' => $post_data['subject_code'] ?? null,
            'grade_system_id' => !empty($post_data['grade_system_id']) ? (int) $post_data['grade_system_id'] : null,
            'status' => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'updated_by' => $user->id,
        ];

        $this->SubjectModel->update($id, $saveData);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Subject updated successfully',
        ]);
    }
}