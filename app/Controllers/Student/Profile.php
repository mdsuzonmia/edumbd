<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel;

class Profile extends BaseController
{
    protected StudentModel $StudentModel;
    protected UserModel $UserModel;

    public function __construct()
    {
        $this->StudentModel = new StudentModel();
        $this->UserModel    = new UserModel();
    }

    public function index()
    {
        $user_id = session('user_id');
        if (!$user_id) {
            return redirect()->to('login');
        }

        $student = $this->StudentModel
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return redirect()->to('login')->with('error', 'Student not found.');
        }

        $header_data['page_title'] = 'My Profile';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['student'] = $student;

        return view('header', $header_data)
            . view('student/profile', $data)
            . view('footer', $footer_data);
    }

    public function update()
    {
        $user_id = session('user_id');
        if (!$user_id) {
            return redirect()->to('login');
        }

        $student = $this->StudentModel
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return redirect()->to('login')->with('error', 'Student not found.');
        }

        $post_data = $this->request->getPost();

        $updateData = [
            'first_name'  => $post_data['first_name'] ?? $student->first_name,
            'middle_name' => $post_data['middle_name'] ?? null,
            'last_name'   => $post_data['last_name'] ?? $student->last_name,
            'gender'      => $post_data['gender'] ?? $student->gender,
            'date_of_birth' => $post_data['date_of_birth'] ?? $student->date_of_birth,
            'phone'       => $post_data['phone'] ?? $student->phone,
            'email'       => $post_data['email'] ?? $student->email,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $this->StudentModel->update($student->id, $updateData);

        // Update user account if exists
        if (!empty($student->user_id)) {
            $userData = [
                'name'  => trim(($post_data['first_name'] ?? $student->first_name) . ' ' . ($post_data['last_name'] ?? $student->last_name)),
                'email' => $post_data['email'] ?? $student->email,
                'phone' => $post_data['phone'] ?? null,
            ];

            if (!empty($post_data['password'])) {
                $userData['password'] = password_hash($post_data['password'], PASSWORD_DEFAULT);
            }

            $this->UserModel->update($student->user_id, $userData);
        }

        return redirect()->to('student/profile')->with('success', 'Profile updated successfully.');
    }
}