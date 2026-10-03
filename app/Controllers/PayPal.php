<?php

namespace App\Controllers;

use App\Controllers\BaseController;

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


class PayPal extends BaseController
{

    protected PlanModel $planModel;
    protected UserModel $userModel;
    protected SchoolModel $schoolModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserRoleModel $userRoleModel;
    protected UserEmailVerificationModel $userEmailVerificationModel;
    protected OtpModel $otpModel;
    protected PaymentModel $paymentModel;
    protected TempModel $tempModel;
    protected SchoolUserModel $schoolUserModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
        $this->userModel = new UserModel();
        $this->schoolModel = new SchoolModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->userRoleModel = new UserRoleModel();
        $this->userEmailVerificationModel = new UserEmailVerificationModel();
        $this->otpModel = new OtpModel();
        $this->paymentModel = new PaymentModel();
        $this->tempModel = new TempModel();
        $this->schoolUserModel = new SchoolUserModel();
    }

    public function paypalIpn()
    {
        $postData = $_POST;

        if (empty($postData)) {
            $postData = $this->request->getPost();
        }

        if (empty($postData)) {
            parse_str(file_get_contents('php://input'), $postData);
        }

        if (empty($postData)) {
            return;
        }

        // Validate IPN with PayPal
        $paypalUrl = env('paypal.mode') == 'live'
            ? 'https://ipnpb.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

        $ch = curl_init($paypalUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'cmd=_notify-validate&' . http_build_query($postData),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== 'VERIFIED') {
            return;
        }

        $custom = $postData['custom'] ?? '';

        // Registration Payment
        if (str_starts_with($custom, 'register:')) {

            $tempToken = substr($custom, strlen('register:'));

            $this->processRegistrationPayment($tempToken, $postData);

            return;
        }

        // Plan Upgrade 
        if (str_starts_with($custom, 'upgrade:')) {

            $token = substr($custom, strlen('upgrade:'));

            $this->processSubscriptionPayment($token, $postData);

            return;
        }

        // Renewal Payment
        if (str_starts_with($custom, 'renew:')) {

            $token = substr($custom, strlen('renew:'));

            $this->processRenewalPayment($token, $postData);

            return;
        }

        // Retry Payment
        if (str_starts_with($custom, 'retry:')) {

            $token = substr($custom, strlen('retry:'));

            $this->processRetryPayment($token, $postData);

            return;
        }


    }

    private function processRegistrationPayment(string $tempToken, array $postData)
    {
        $temp = $this->tempModel->where('temp_token', $tempToken)->first();

        if (!$temp) {
            return;
        }

        $this->tempModel->update($temp->id, [
            'payment_status'  => 'paid',
            'transaction_id'  => $postData['txn_id'] ?? null,
            'payment_gateway' => 'paypal',
        ]);

        $temp->transaction_id = $postData['txn_id'] ?? null;
        $temp->payment_payload = json_encode($postData);

        // Create School
        createSchoolFromTemp($temp);

        $this->tempModel->update($temp->id, [
            'is_processed' => 1,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);
    }


    private function processSubscriptionPayment(string $token, array $postData): bool
    {
        $transactionId = 'PAYPAL-' . time() . '-' . rand(1000, 9999);

        $payment = $this->paymentModel->getPaymentDetailsByToken($token);
        if (! $payment || empty($payment->subscription_id)) {
            return false;
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (! $subscription || (int) $subscription->status === 2) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $userId = (int) $subscription->user_id;

        $this->paymentModel->update($payment->id, [
            'status' => 'paid',
            'paid_at' => $now,
            'gateway_payment_id' => $postData['txn_id'] ?? null,
            'transaction_id' => $transactionId,
            'payment_payload' => json_encode($postData),
            'updated_at' => $now,
        ]);

        // Cancel existing subscription
        $current = $this->subscriptionModel->getActiveSubscriptionByUserId($userId);
        if ($current && (int) $current->id !== $subscription->id) {
            $this->subscriptionModel->update($current->id, [
                'status' => 5, // cancelled
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
        }

        // Update new subscription
        $this->subscriptionModel->update($subscription->id, [
            'status' => 2, // active
            'updated_at' => $now,
            'updated_by' => $userId,
        ]);

        // Send Subscription Upgraded email to user 
        sendSubscriptionUpgradedEmail($payment, $subscription);

        $this->userModel->update($userId, [
            'plan_id' => $subscription->plan_id,
            'updated_at' => $now,
            'updated_by' => $userId,
        ]);

        if ((int) session()->get('user_id') === $userId) {
            session()->set('plan_id', $subscription->plan_id);
        }

        return true;
    }


    // renewal payment process
    private function processRenewalPayment(string $token, array $postData): bool
    {
        $payment = $this->paymentModel->where('payment_token', $token)->first();

        if (! $payment || empty($payment->subscription_id)) {
            return false;
        }
        $subscription = $this->subscriptionModel->find($payment->subscription_id);

        if (! $subscription || (int) $subscription->status !== 2) {
            return false;
        }

        $transactionId = 'PAYPAL-RENEW-' . time() . '-' . rand(1000, 9999);
        $now = date('Y-m-d H:i:s');
        $userId = (int) $subscription->user_id;

        // Calculate new end date based on billing cycle
        $newEndDate = null;

        if ($subscription->billing_cycle === 'monthly') {
            $newEndDate = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($subscription->end_date)));
        } elseif ($subscription->billing_cycle === 'yearly') {
            $newEndDate = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($subscription->end_date)));
        }

        $this->subscriptionModel->update($subscription->id, [
            'end_date'   => $newEndDate,
            'updated_at' => $now,
            'updated_by' => $userId,
        ]);

        $this->paymentModel->update($payment->id, [
            'updated_at'         => $now,
            'status'             => 'paid',
            'transaction_id'     => $transactionId,
            'gateway_payment_id' => $postData['txn_id'] ?? null,
            'payment_payload'    => json_encode($postData),
            'paid_at'            => $now,
        ]);


        // Send Subscription Renewal Confirmation email to user 
        sendSubscriptionRenewalConfirmationEmail($payment, $subscription);

        return true;
    }

    // retry payment process
    private function processRetryPayment(string $token, array $postData): bool
    {
        $payment = $this->paymentModel->where('payment_token', $token)->first();

        if (! $payment || empty($payment->subscription_id)) {
            return false;
        }
        $subscription = $this->subscriptionModel->find($payment->subscription_id);

        if (! $subscription) {
            return false;
        }

        $userId = (int) $subscription->user_id;

        $payment_type = $payment->payment_type ?? 'renew';

        if ($payment_type === 'renew' ) {
           $transactionId = 'PAYPAL-RENEW-' . time() . '-' . rand(1000, 9999);
        }elseif ($payment_type === 'upgrade') {
            $transactionId = 'PAYPAL-UPGRADE-' . time() . '-' . rand(1000, 9999);
        }else{ 
            $transactionId = 'PAYPAL-' . time() . '-' . rand(1000, 9999);
        }

        $now = date('Y-m-d H:i:s');

        $this->paymentModel->update($payment->id, [
            'updated_at'         => $now,
            'status'             => 'paid',
            'transaction_id'     => $transactionId,
            'gateway_payment_id' => $postData['txn_id'] ?? null,
            'payment_payload'    => json_encode($postData),
            'paid_at'            => $now,
        ]);


        // Send Payment Confirmation email to user 
        sendPaymentConfirmationEmail($payment, $subscription);

        return true;
    }


    
}