<?php

namespace App\Controllers;

use App\Models\AuthModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Email\Email;
use App\Models\PlanModel;
use App\Models\UserModel;
use App\Models\SchoolModel;
use App\Models\SubscriptionModel;
use App\Models\UserRoleModel;
use App\Models\UserEmailVerificationModel;
use App\Models\OtpModel;
use App\Models\PrtModel;
use App\Models\PaymentModel;


class Auth extends BaseController
{

    protected AuthModel $authModel;
    protected PlanModel $planModel;
    protected UserModel $userModel;
    protected SchoolModel $schoolModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserRoleModel $userRoleModel;
    protected UserEmailVerificationModel $userEmailVerificationModel;
    protected OtpModel $otpModel;
    protected PrtModel $prtModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->planModel = new PlanModel();
        $this->userModel = new UserModel();
        $this->schoolModel = new SchoolModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->userRoleModel = new UserRoleModel();
        $this->userEmailVerificationModel = new UserEmailVerificationModel();
        $this->otpModel = new OtpModel();
        $this->prtModel = new PrtModel();
        $this->paymentModel = new PaymentModel();
    }

    



    // =====================================================
    // FORGOT PASSWORD PAGE
    // =====================================================
    public function forgot_password()
    {
        $header_data['page_title'] = lang('Auth.page_title_forgot_password');
        $header_data['body_class'] = 'reset';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('auth/forgot_password')
            . view('footer', $footer_data);
    }

    // sendResetLink
    public function sendResetLink()
    {
        $email = $this->request->getPost('email');
        $user = $this->userModel->where('email', $email)->first();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $this->userEmailVerificationModel->insert([
                'user_id'     => $user->id,
                'email'       => $user->email,
                'token'       => $token,
                'type'        => 'reset_password',
                'expires_at'  => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            // TODO:
            // Send reset email here
            sendPasswordResetLink($email, $token);
            return redirect()->back()->with('success', 'Password reset link sent to your email.');
        } else {
            return redirect()->back()->with('error', 'Email not found.');
        }
    }

    // reset_password
    public function reset_password($token = null)
    {
        if (!$token) {
            return redirect()->to('login');
        }
        $user = $this->userEmailVerificationModel->where('token', $token)->where('type', 'reset_password')->first();
        if (!$user) {
            return redirect()->to('login')->with('error', 'Invalid reset link.');
        }
        // Expired check
        if (strtotime($user->expires_at) < time()) {
            return redirect()->to('forgot-password')->with('error', 'Reset link expired.');
        }

        // Already verified
        if ($user->is_verified) {
            return redirect()->to('login')->with('info', 'Your link has already been used.');
        }

        $header_data['page_title'] = lang('Auth.page_title_reset_password');
        $header_data['body_class'] = 'reset';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('auth/reset_password', ['token' => $token])
            . view('footer', $footer_data);
    }



    // =====================================================
    // RESET PASSWORD SUBMIT
    // =====================================================
    public function doResetPassword()
    {
        $token = $this->request->getPost('token');

        $verification = $this->userEmailVerificationModel
            ->where('token', $token)
            ->where('type', 'reset_password')
            ->where('is_verified', 0)
            ->first();

        if (!$verification) {

            return redirect()->to('login')
                ->with('error', 'Invalid token.');
        }

        // Expired check
        if (strtotime($verification->expires_at) < time()) {

            return redirect()->to('forgot-password')
                ->with('error', 'Token expired.');
        }

        $password = $this->request->getPost('password');
        $confirm  = $this->request->getPost('confirm_password');

        if ($password !== $confirm) {

            return redirect()->back()
                ->with('error', 'Passwords do not match.');
        }

        // Update password
        $this->userModel->update($verification->user_id, [
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        // Mark token used
        $this->userEmailVerificationModel->update($verification->id, [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        // Send email
        sendPasswordResetSuccessEmail($verification->email);

        return redirect()->to('login')
            ->with('success', 'Password reset successful.');
    }
    

    public function my_profile()
    {
        // Assuming you store the logged-in user's ID in the session
        $userId = session()->get('user_id');
        $data['user'] = $this->authModel->find($userId);

        // get email verification status (may be null for students)
        $email_verification = $this->userEmailVerificationModel->where('user_id', $userId)->where('type', 'register')->first();
        $data['email_verification'] = $email_verification ?? (object)['is_verified' => 0, 'verified_at' => null];

        // Get subscriptions
        $data['subscription'] = $this->subscriptionModel->where('user_id', $userId)->findAll();
       

        $header_data['page_title'] = lang('Auth.page_title_my_profile');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('auth/my_profile', $data)
            . view('footer', $footer_data);

    }

    public function edit_profile()
    {
        // Get check user is loggetin and have permission
        $userId = session()->get('user_id');
        $data['user'] = $this->authModel->find($userId);

        $header_data['page_title'] = lang('Auth.page_title_edit_profile');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('auth/edit_profile', $data)
            . view('footer', $footer_data);

    }

    public function update_profile()
    {
        // Get check user is loggetin and have permission
        $userId = session()->get('user_id');

        $file = $this->request->getFile('photo');
        $photoName = $this->request->getPost('old_photo');

        if ($file->isValid() && !$file->hasMoved()) {
            $photoName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $photoName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'photo' => $photoName
        ];

        $this->authModel->update($userId, $data);

        return redirect()->to('/auth/my-profile')->with('success', lang('Auth.sys_profile_changed'));
    }

    public function change_password()
    {
        $header_data['page_title'] = lang('Auth.page_title_change_password');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('auth/change_password')
            . view('footer', $footer_data);
    }

    public function update_password()
    {
       // Get check user is loggetin and have permission
        $userId = session()->get('user_id');

        $oldPassword = $this->request->getPost('old_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        $user = $this->authModel->find($userId);

        if (!password_verify($oldPassword, $user->password)) {
            return redirect()->back()->with('error', lang('Auth.sys_old_password_incorrect'));
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', lang('Auth.sys_password_not_match'));
        }

        $this->authModel->update($userId, ['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        return redirect()->to('/auth/my-profile')->with('success', lang('Auth.sys_password_changed'));
    }

    
    public function login()
    {
        if(session()->get('logged_in')){
            $role = session()->get('role');
            $redirectUrl = $this->redirectByRole($role);
            return redirect()->to($redirectUrl);
        }

        $header_data['page_title'] = lang('Auth.page_title_login');
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('auth/login')
            . view('footer', $footer_data);
    }

    

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }


    /**
     * Handle Login
     */
    public function doLogin()
    {
        $identifier = trim((string) $this->request->getPost('mobile'));
        if ($identifier === '') {
            $identifier = trim((string) $this->request->getPost('email'));
        }
        $password = $this->request->getPost('password');

        $result = $this->authModel->attemptLogin($identifier, $password);

        if (!$result['status']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        // 1️⃣ Set session
        session()->set([
            'user_id'   => $result['user']->id,
            'plan_id'   => $result['user']->plan_id,
            'user_name' => $result['user']->name,
            'role'      => $result['role']->slug,
            'school_id' => $result['school_id'],
            'logged_in' => true
        ]);

        // Set school owner id in session if available
        if($result['role']->slug == 'school-owner') {
            session()->set('school_owner_id', $result['user']->id);
        }

        // handle user photo in session if needed
        if ($result['user']->photo) {
            session()->set('user_photo', $result['user']->photo);
        }

        // 2️⃣ Redirect by role
        return redirect()->to($this->redirectByRole($result['role']->slug));
    }




    private function redirectByRole(string $role): string
    {
        return match ($role) {
            // Super Admin
            'super-admin' => '/saas-admin/dashboard',

            // School Owner
            'school-owner' => '/school-owner/dashboard',

            // School Admin
            'school-admin' => '/school-admin/dashboard',
            
            // Teacher
            'teacher' => '/teacher/dashboard',
            // Student
            'student' => '/student/dashboard',
            // Parent
            'parent' => '/parent/dashboard',

            // Accountant
            'accountant' => '/accountant/dashboard',

            // Librarian
            'librarian' => '/librarian/dashboard',
            
            // Receptionist
            'receptionist' => '/receptionist/dashboard',
            default => '/login'
        };
    }

    
}
