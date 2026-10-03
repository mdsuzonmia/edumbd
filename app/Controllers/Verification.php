<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuthModel;
use App\Models\UserEmailVerificationModel;


class Verification extends BaseController
{

    protected UserEmailVerificationModel $userEmailVerificationModel;
    protected AuthModel $authModel;

    public function __construct()
    {
        $this->userEmailVerificationModel = new UserEmailVerificationModel();
        $this->authModel = new AuthModel();
    }

    public function verify_email($token = null)
    {
        if (!$token) {
            return redirect()->to('login')
                ->with('error', 'Invalid verification link.');
        }

        $verification = $this->userEmailVerificationModel
            ->where('token', $token)
            ->where('type', 'register')
            ->first();

        if (!$verification) {
            return redirect()->to('login')
                ->with('error', 'Invalid verification link.');
        }

        if (!empty($verification->is_verified)) {
            return redirect()->to('login')
                ->with('info', 'Your email is already verified.');
        }

        if (strtotime($verification->expires_at) < time()) {
            return redirect()->to('login')
                ->with('error', 'Verification link expired. Please request a new one.');
        }

        $this->userEmailVerificationModel->update($verification->id, [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => (string) $this->request->getUserAgent(),
        ]);

        return redirect()->to('login')
            ->with('success', 'Email verified successfully. You can now log in.');
    }

    public function resendVerificationEmail()
    {
        $userId = session('user_id');

        $user = $this->authModel->find($userId);

        if (! $user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $email = $user->email;

        $verification = $this->userEmailVerificationModel
            ->where('user_id', $userId)
            ->first();

        // 1. Already verified
        if ($verification && $verification->is_verified) {
            return redirect()->back()->with('info', 'Your email is already verified.');
        }

        
        // 2. If token exists and NOT expired → block resend
        if ($verification && strtotime($verification->expires_at) > time()) {
            return redirect()->back()->with(
                'info',
                'A verification email has already been sent. Please check your inbox or spam folder.'
            );
        }

        // 3. If expired token exists → delete it
        if ($verification && strtotime($verification->expires_at) <= time()) {
            $this->userEmailVerificationModel->delete($verification->id);
        }

        // 4. Generate new token
        $token = bin2hex(random_bytes(32));

        $this->userEmailVerificationModel->insert([
            'user_id'     => $userId,
            'email'       => $email,
            'token'       => $token,
            'type'        => 'register',
            'expires_at'  => date('Y-m-d H:i:s', strtotime('+30 minutes')),
        ]);

        // 5. Send email
        // send_email(
        //     $email,
        //     'Email Verification',
        //     "Please click the link below to verify your email address: <br>
        //     <a href='" . base_url("verify-email/{$token}") . "'>Verify Email</a>
        //     <br><br>This link will expire in 15 minutes."
        // );

        $verificationLink = base_url("verify-email/{$token}");

        send_email(
                $email,
                'Email Verification',
                'email_verification',
                [
                    'name' => $user->name,
                    'verification_link' => $verificationLink,
                ]
            );

        return redirect()->back()->with(
            'success',
            'A new verification email has been sent to your email address.'
        );
    }
    
}
