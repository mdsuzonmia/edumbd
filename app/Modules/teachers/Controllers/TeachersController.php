<?php

namespace App\Modules\teachers\Controllers;

use App\Controllers\BaseController;
use App\Modules\teachers\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\UserRoleModel;

class TeachersController extends BaseController
{
    protected TeacherModel $TeacherModel;
    protected UserModel $UserModel;
    protected UserRoleModel $UserRoleModel;

    public function __construct()
    {
        $this->TeacherModel  = new TeacherModel();
        $this->UserModel     = new UserModel();
        $this->UserRoleModel = new UserRoleModel();
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
        $school_id        = (int) $this->request->getGet('school_id');
        $status           = $this->request->getGet('status');
        $search           = $this->request->getGet('search');

        // Only show records where school_owner_uid matches the authenticated user
        $this->TeacherModel->where('school_owner_uid', $school_owner_uid);

        if ($status !== null) {
            $this->TeacherModel->where('status', $status);
        }

        if ($search) {
            $this->TeacherModel->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('employee_code', $search)
                ->orLike('phone', $search)
                ->orLike('email', $search)
            ->groupEnd();
        }

        $items = $this->TeacherModel->findAll();

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

        $record = $this->TeacherModel->find($id);
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
            'employee_code' => 'required|max_length[30]',
            'first_name'    => 'required|max_length[100]',
            'gender'        => 'required|in_list[Male,Female,Other]',
            'phone'         => 'required|max_length[30]',
        ];

        if (!$this->validate($validationRule)) {
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $this->validator->getErrors(),
            ], 400);
        }

        // Generate teacher_uid if not provided
        $teacher_uid = $post_data['teacher_uid'] ?? generate_unique_random_number(10);

        $saveData = [
            'school_owner_uid' => $user->id,
            'teacher_uid'      => $teacher_uid,
            'employee_code'    => $post_data['employee_code'],
            'user_id'          => !empty($post_data['user_id']) ? (int) $post_data['user_id'] : null,
            'first_name'       => $post_data['first_name'],
            'last_name'        => $post_data['last_name'] ?? null,
            'gender'           => $post_data['gender'],
            'date_of_birth'    => $post_data['date_of_birth'] ?? null,
            'phone'            => $post_data['phone'],
            'email'            => $post_data['email'] ?? null,
            'designation_id'   => !empty($post_data['designation_id']) ? (int) $post_data['designation_id'] : null,
            'department_id'    => !empty($post_data['department_id']) ? (int) $post_data['department_id'] : null,
            'joining_date'     => $post_data['joining_date'] ?? null,
            'employment_type'  => $post_data['employment_type'] ?? 'Permanent',
            'salary'           => !empty($post_data['salary']) ? (float) $post_data['salary'] : null,
            'photo'            => $post_data['photo'] ?? null,
            'status'           => $post_data['status'] ?? 'Active',
            'remarks'          => $post_data['remarks'] ?? null,
            'created_by'       => $user->id,
            'updated_by'       => $user->id,
        ];

        $id = $this->TeacherModel->insert($saveData);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher created successfully',
            'data'    => ['teacher_id' => $id],
        ]);
    }

    public function update($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->TeacherModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $post_data = $this->request->getPost();

        $saveData = [
            'employee_code'   => $post_data['employee_code'] ?? $record->employee_code,
            'user_id'         => isset($post_data['user_id']) ? (int) $post_data['user_id'] : $record->user_id,
            'first_name'      => $post_data['first_name'] ?? $record->first_name,
            'last_name'       => $post_data['last_name'] ?? $record->last_name,
            'gender'          => $post_data['gender'] ?? $record->gender,
            'date_of_birth'   => $post_data['date_of_birth'] ?? $record->date_of_birth,
            'phone'           => $post_data['phone'] ?? $record->phone,
            'email'           => $post_data['email'] ?? $record->email,
            'designation_id'  => isset($post_data['designation_id']) ? (int) $post_data['designation_id'] : $record->designation_id,
            'department_id'   => isset($post_data['department_id']) ? (int) $post_data['department_id'] : $record->department_id,
            'joining_date'    => $post_data['joining_date'] ?? $record->joining_date,
            'employment_type' => $post_data['employment_type'] ?? $record->employment_type,
            'salary'          => isset($post_data['salary']) ? (float) $post_data['salary'] : $record->salary,
            'photo'           => $post_data['photo'] ?? $record->photo,
            'status'          => $post_data['status'] ?? $record->status,
            'remarks'         => $post_data['remarks'] ?? $record->remarks,
            'updated_by'      => $user->id,
        ];

        $this->TeacherModel->update($id, $saveData);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher updated successfully',
        ]);
    }

    public function delete($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->TeacherModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $this->TeacherModel->delete($id);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Teacher deleted successfully',
        ]);
    }

    public function changeStatus($id)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $record = $this->TeacherModel->find($id);
        if (!$record) {
            return $this->jsonResponse(['status' => false, 'message' => 'Not found'], 404);
        }

        $status = $this->request->getPost('status');
        if (!$status || !in_array($status, ['Active', 'Inactive', 'On Leave', 'Resigned'])) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid status'], 400);
        }

        $this->TeacherModel->update($id, [
            'status'     => $status,
            'updated_by' => $user->id,
        ]);

        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Status updated successfully',
        ]);
    }

    public function bySchool($school_owner_uid)
    {
        $token = $this->getToken();
        $user  = $this->verifyToken($token);
        if (!$user) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $status = $this->request->getGet('status');

        $this->TeacherModel->where('school_owner_uid', $school_owner_uid);

        if ($status !== null) {
            $this->TeacherModel->where('status', $status);
        }

        $items = $this->TeacherModel->findAll();

        return $this->jsonResponse([
            'status' => true,
            'data'   => $items,
        ]);
    }
}