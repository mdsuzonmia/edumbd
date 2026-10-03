<?php

use Config\Services;
use App\Models\UserModel;
use App\Models\SchoolModel;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;

use App\Models\UserRoleModel;
use App\Models\UserEmailVerificationModel;
use App\Models\OtpModel;
use App\Models\PaymentModel;
use App\Models\TempModel;
use App\Models\SchoolUserModel;

if (!function_exists('set_email_config')) {

    function set_email_config()
    {
        return [
            'protocol'   => 'smtp',
            'SMTPHost'   => setting('application', 'smtp_host') ?? '',
            'SMTPUser'   => setting('application', 'smtp_username') ?? '',
            'SMTPPass'   => setting('application', 'smtp_password') ?? '', // pxwibifpfjmsprui
            'SMTPPort'   => (int) setting('application', 'smtp_port') ?? 587,
            'SMTPCrypto' => 'ssl',
            'mailType'   => 'html',
            'charset'    => 'UTF-8',
            'wordWrap'   => true,
        ];
    }
}

if (!function_exists('send_email')) {
    function send_email($to, $subject, $view, $data = [])
    {
        $email = \Config\Services::email();

        $email->initialize(set_email_config());

        $email->setFrom(
            setting('application', 'smtp_username'),
            setting('application', 'app_name')
        );

        $message = view('emails/' . $view, $data);

        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);
        $email->setMailType('html');

        return $email->send();
    }
}


// send payment confirmation email to user
if(!function_exists('sendPaymentConfirmationEmail')) {
    function sendPaymentConfirmationEmail($payment, $subscription)
    {
        $planModel = new PlanModel();
        $userModel = new UserModel();
        $plan = $planModel->find($subscription->plan_id);
        $user = $userModel->find($payment->user_id);

        send_email(
            $user->email,
            'Payment Confirmation',
            'payment_confirmation',
            [
                'name' => $user->name,
                'plan_name' => $plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'currency' => $plan->currency,
                'amount' => $subscription->amount,
                'payment_gateway' => $subscription->payment_gateway,
                'transaction_id' => $payment->transaction_id,
                'payment_status' => 'paid',
            ]
        );
    }
}


// Send email verification link
if(!function_exists('sendEmailVerificationLink')) {
    function sendEmailVerificationLink($email, $token)
    {
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        $verificationLink = base_url('verify-email/' . $token);

        send_email(
            $email,
            'Registration Confirmation',
            'registration_confirmation',
            [
                'name' => $user->name,
                'verification_link' => $verificationLink,
            ]
        );
    }
}

// Send Subscription Upgraded email to user
if(!function_exists('sendSubscriptionUpgradedEmail')) {
    function sendSubscriptionUpgradedEmail($payment, $subscription)
    {
        $planModel = new PlanModel();
        $userModel = new UserModel();
        $plan = $planModel->find($subscription->plan_id);
        $user = $userModel->find($payment->user_id);

        send_email(
            $user->email,
            'Subscription Upgraded',
            'subscription_upgraded',
            [
                'name' => $user->name,
                'plan_name' => $plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'currency' => $plan->currency,
                'amount' => $subscription->amount,
                'payment_gateway' => $subscription->payment_gateway,
                'transaction_id' => $payment->transaction_id,
                'payment_status' => 'paid',
            ]
        );
    }
}


// Send Subscription Renewal Confirmation email to user
if(!function_exists('sendSubscriptionRenewalConfirmationEmail')) {
    function sendSubscriptionRenewalConfirmationEmail($payment, $subscription)
    {
        $planModel = new PlanModel();
        $userModel = new UserModel();
        $plan = $planModel->find($subscription->plan_id);
        $user = $userModel->find($payment->user_id);

        send_email(
            $user->email,
            'Subscription Renewal Confirmation',
            'subscription_renewal',
            [
                'name' => $user->name,
                'plan_name' => $plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'currency' => $plan->currency,
                'amount' => $subscription->amount,
                'payment_gateway' => $subscription->payment_gateway,
                'transaction_id' => $payment->transaction_id,
                'payment_status' => 'paid',
            ]
        );
    }
}

// Password Reset Link
if(!function_exists('sendPasswordResetLink')) {
    function sendPasswordResetLink($email, $token)
    {
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        $resetLink = base_url('reset-password/' . $token);

        send_email(
            $email,
            'Password Reset',
            'password_reset',
            [
                'name' => $user->name,
                'reset_link' => $resetLink,
            ]
        );
    }
}

// sendPasswordResetSuccessEmail
if(!function_exists('sendPasswordResetSuccessEmail')) {
    function sendPasswordResetSuccessEmail($email)
    {
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        send_email(
            $email,
            'Password Reset Success',
            'password_reset_success',
            [
                'name' => $user->name,
            ]
        );
    }
}

