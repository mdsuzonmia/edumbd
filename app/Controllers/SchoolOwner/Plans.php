<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use App\Models\PlanModel;
use App\Models\SubscriptionModel;
use App\Models\UserModel;

class Plans extends BaseController
{
    protected PlanModel $planModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserModel $userModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->userModel = new UserModel();
        $this->paymentModel = new PaymentModel();
    }

    public function upgrade_plan_form()
    {
        $userId = (int) session()->get('user_id');
        if (!$userId) {
            return redirect()->to('school-owner/dashboard')->with('error', 'User information was not found for this account.');
        }

        $data = [
            'plans' => $this->planModel
                ->where('status', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll(),
            'current_subscription' => $this->subscriptionModel->getActiveSubscriptionWithPlanByUserId($userId),
        ];

        return $this->render('Upgrade Subscription Plan', 'school_owner/plans/upgrade', $data);
    }

    public function do_upgrade_plan()
    {
        $userId             = (int) session()->get('user_id');
        $subscription_token = bin2hex(random_bytes(20));
        $payment_token      = bin2hex(random_bytes(20));

        if (!$userId) {
            return redirect()->to('school-owner/dashboard')->with('error', 'User information was not found for this account.');
        }

        $rules = [
            'plan_id' => 'required|is_natural_no_zero',
            'billing_cycle' => 'required|in_list[monthly,yearly,lifetime]',
            'payment_gateway' => 'required|in_list[paypal,stripe,manual,free]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $planId         = (int) $this->request->getPost('plan_id');
        $billingCycle   = (string) $this->request->getPost('billing_cycle');
        $paymentGateway = (string) $this->request->getPost('payment_gateway');
        $plan           = $this->planModel->where('status', 1)->find($planId);

        if (!$plan) {
            return redirect()->back()->withInput()->with('error', 'Selected plan is not available.');
        }

        $amount = $this->amountForCycle($plan, $billingCycle);
        if ($amount <= 0) {
            $paymentGateway = 'free';
        } elseif ($paymentGateway === 'free') {
            return redirect()->back()->withInput()->with('error', 'Please select a payment method for this plan.');
        }

        if ($paymentGateway === 'manual' && !$this->request->getPost('manual_transaction_id')) {
            return redirect()->back()->withInput()->with('error', 'Manual payment transaction ID is required.');
        }

        $now = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d');
        $endDate = $this->endDateForCycle($billingCycle);

        $current = $this->subscriptionModel->getActiveSubscriptionWithPlanByUserId($userId);
        if ($current && (int) $current->plan_id === $planId && $current->billing_cycle === $billingCycle) {
            return redirect()->to('school-owner/plans/upgrade')->with('error', 'You are already using this plan and billing cycle.');
        }

        $subscriptionData = [
            'token'           => $subscription_token,
            'user_id'         => $userId,
            'plan_id'         => $planId,
            'status'          => $paymentGateway === 'free' ? 2 : 0,
            'is_trial'        => 0,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'amount'          => $amount,
            'currency'        => $plan->currency ?: 'USD',
            'billing_cycle'   => $billingCycle,
            'payment_gateway' => $paymentGateway,
            'meta' => json_encode([
                'changed_from_subscription_id' => $current->id ?? null,
                'changed_by'                   => 'school-owner',
                'manual_transaction_id'        => $this->request->getPost('manual_transaction_id') ?: null,
                'manual_note'                  => $this->request->getPost('manual_note') ?: null,
            ]),
            'created_at'      => $now,
            'updated_at'      => $now,
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ];

        $this->subscriptionModel->insert($subscriptionData);
        $subscriptionId = (int) $this->subscriptionModel->insertID();

        if($subscriptionId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Failed to create subscription. Please try again.');
        }

        $paymentData = [
            'payment_token'       => $payment_token,
            'payment_type'        => 'upgrade',
            'subscription_id'     => $subscriptionId,
            'user_id'             => $userId,
            'plan_id'             => $planId,
            'gateway'             => $paymentGateway,
            'billing_cycle'       => $billingCycle,
            'amount'              => $amount,
            'currency'            => $plan->currency ?: 'USD',
            'transaction_id'      => null,
            'status'              => $paymentGateway === 'free' ? 'paid' : 'pending',
            'payment_payload'     => json_encode([
                'manual_transaction_id' => $this->request->getPost('manual_transaction_id') ?: null,
                'manual_note'           => $this->request->getPost('manual_note') ?: null,
            ]),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        

        if ($paymentGateway !== 'free') {
            $this->paymentModel->insert($paymentData);
        }

        if ($paymentGateway === 'stripe') {
            return redirect()->to(base_url("school-owner/plans/payment/stripe/$payment_token"));
        }

        if ($paymentGateway === 'paypal') {
            return redirect()->to(base_url("school-owner/plans/payment/paypal/$payment_token"));
        }

        if ($paymentGateway === 'manual') {
            return redirect()->to(base_url("school-owner/plans/payment/manual-success/$payment_token"));
        }

    }

    public function plan_history()
    {
        $userId = (int) session()->get('user_id');
        if (!$userId) {
            return redirect()->to('school-owner/dashboard')->with('error', 'User information was not found for this account.');
        }

        $data = [
            'items' => $this->subscriptionModel->getSubscriptionHistoryByUserId($userId),
        ];

        return $this->render('Subscription Plan History', 'school_owner/plans/history', $data);
    }

    public function continue_payment(string $token)
    {
        // Get Token from URL segment instead of query parameter for better security
        $token = $this->request->getUri()->getSegment(6);
        $payment = $this->paymentModel->getPaymentDetailsByToken($token);
        if (!$payment) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Pending payment was not found.');
        }

        if ($payment->payment_gateway === 'stripe') {
            return redirect()->to(base_url('school-owner/plans/payment/stripe/' . $token));
        }

        if ($payment->payment_gateway === 'paypal') {
            return redirect()->to(base_url('school-owner/plans/payment/paypal/' . $token));
        }

        return redirect()->to(base_url('school-owner/plans/payment/manual-success/' . $token));
    }

    public function manual_success(string $token)
    {
        // Get Token from URL segment instead of query parameter for better security
        $token = $this->request->getUri()->getSegment(6);
        $payment = $this->paymentModel->getPaymentDetailsByToken($token);
        if (!$payment) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Payment was not found.');
        }

        return $this->render('Payment Pending', 'school_owner/plans/payment_success', [
            'payment' => $payment,
            'payment_status' => 'pending',
        ]);
    }

    public function payment_success(string $token)
    {
        // Get Token from URL segment instead of query parameter for better security
        $token = $this->request->getUri()->getSegment(6);
        $payment = $this->paymentModel->getPaymentDetailsByToken($token);
        if (!$payment) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Payment was not found.');
        }

        return $this->render('Payment Success', 'school_owner/plans/payment_success', [
            'payment' => $payment,
            'payment_status' => ($payment->status === 'paid' ? 'paid' : 'pending'),
        ]);
    }

    public function stripe_checkout(string $payment_token)
    {
        if (empty($payment_token)) {
            return redirect()->to('school-owner/payments')->with('error', 'Invalid payment token.');
        }

        $payment = $this->paymentModel->getPaymentDetailsByToken($payment_token);
        if (empty($payment)) {
            return redirect()->to('school-owner/payments')->with('error', 'Payment not found.');
        }

        // Security: ensure payment belongs to current user
        if ((int)$payment->user_id !== (int)session('user_id')) {
            return redirect()->to('school-owner/subscriptions')
                ->with('error', 'Unauthorized access.');
        }

        if (strtolower($payment->gateway ?? $payment->payment_gateway ?? '') !== 'stripe') {
            return redirect()->to('school-owner/payments')->with('error', 'This payment cannot be retried with Stripe.');
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (!$subscription) {
            return redirect()->to('school-owner/subscriptions')->with('error', 'Subscription not found.');
        }

        try {
            \Stripe\Stripe::setApiKey(env('stripe.secretKey'));

            $userEmail = $payment->user_email ?? null;
            $lineItemName = $payment->plan_name ?: 'Subscription Payment';

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'customer_email' => $userEmail,
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payment->currency ?: 'USD'),
                        'product_data' => [
                            'name' => $lineItemName,
                        ],
                        'unit_amount' => (int) ((float) $payment->amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'subscription_id' => $payment->subscription_id,
                    'payment_id' => $payment->id,
                    'type' => 'retry',
                ],
                'success_url' => base_url('school-owner/plans/payment/stripe/success/'.$payment_token),
                'cancel_url' => base_url('school-owner/plans/payment/stripe/cancel/' . $payment_token),
            ]);

            $this->paymentModel->update($payment->id, [
                'transaction_id' => $session->id,
                'gateway_payment_id' => $session->id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to($session->url);
        } catch (\Exception $e) {
            return redirect()->to('school-owner/subscriptions')->with('error', $e->getMessage());
        }
    }

    

    public function stripe_success(string $payment_token)
    {
        $payment = $this->paymentModel->where('payment_token', $payment_token)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$payment) {
            return redirect()->to('school-owner/payments')->with('error', 'Payment not found.');
        }

        // Prevent double processing
        if ($payment->status === 'paid') {
            return $this->render(
                'Payment Confirmation',
                'school_owner/subscriptions/payment_success',
                [
                    'payment' => $payment,
                    'payment_status' => 'paid',
                ]
            );
        }

        try {
            \Stripe\Stripe::setApiKey(env('stripe.secretKey'));

            // OPTIONAL: verify Stripe session using stored session ID
            $sessionId = $payment->gateway_payment_id;

            if ($sessionId) {
                $session = \Stripe\Checkout\Session::retrieve($sessionId);

                if (!$session || $session->payment_status !== 'paid') {
                    return redirect()->to('school-owner/payments')
                        ->with('error', 'Payment not completed on Stripe.');
                }
            }

            // Transaction ID generation
            $paymentType = $payment->payment_type ?? 'payment';

            $transactionId = match ($paymentType) {
                'renew'   => 'STRIPE-RENEW-' . time() . '-' . rand(1000, 9999),
                'upgrade' => 'STRIPE-UPGRADE-' . time() . '-' . rand(1000, 9999),
                default   => 'STRIPE-' . time() . '-' . rand(1000, 9999),
            };

            $subscription = $this->subscriptionModel->find($payment->subscription_id);
            if (! $subscription || (int) $subscription->status === 2) {
                return false;
            }

            $now = date('Y-m-d H:i:s');
            $userId = (int) $subscription->user_id;

            // Update payment
            $this->paymentModel->update($payment->id, [
                'status'             => 'paid',
                'transaction_id'     => $transactionId,
                'gateway_payment_id' => $session->id ?? $sessionId,
                'payment_payload'    => isset($session) ? json_encode($session) : null,
                'paid_at'           => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
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

            return $this->render(
                'Payment Success',
                'school_owner/subscriptions/payment_success',
                [
                    'payment' => $payment,
                    'payment_status' => 'paid',
                ]
            );

        } catch (\Throwable $e) {

            log_message('error', 'Stripe Success Error: ' . $e->getMessage());

            return redirect()->to('school-owner/dashboard')
                ->with('error', 'Payment verification failed.');
        }
    }

    public function stripe_cancel()
    {
        $subscriptionId = (int) $this->request->getGet('subscription_id');

        return redirect()->to('school-owner/dashboard')
            ->with('error', 'Payment cancelled. You can continue payment from your dashboard.');
    }

    public function paypal_checkout($token)
    {
        $payment = $this->getOwnerPendingPayment($token);
        if (!$payment) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Pending payment was not found.');
        }

        $paypalEmail = env('paypal.email');
        $returnUrl = base_url('school-owner/plans/payment/paypal/success/' . $token);
        $cancelUrl = base_url('school-owner/plans/payment/paypal/cancel/' . $token);
        $notifyUrl = base_url('paypal/ipn');
        $paypalUrl = env('paypal.mode') == 'live'
            ? 'https://www.paypal.com/cgi-bin/webscr'
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

        $html = '
        <html>
        <body onload="document.forms[0].submit()">
            <form action="' . $paypalUrl . '" method="post">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="' . esc($paypalEmail) . '">
                <input type="hidden" name="item_name" value="' . esc($payment->plan_name ?: 'School Subscription Plan') . '">
                <input type="hidden" name="amount" value="' . esc($payment->amount) . '">
                <input type="hidden" name="currency_code" value="' . esc($payment->currency ?: 'USD') . '">
                <input type="hidden" name="return" value="' . $returnUrl . '">
                <input type="hidden" name="cancel_return" value="' . $cancelUrl . '">
                <input type="hidden" name="notify_url" value="' . $notifyUrl . '">
                <input type="hidden" name="custom" value="upgrade:' . esc($token) . '">
            </form>
            <p>Redirecting to PayPal...</p>
        </body>
        </html>';

        return $this->response->setBody($html);
    }

    public function paypal_success($token)
    {
        // Get Token from URL segment instead of query parameter for better security
        $token = $this->request->getUri()->getSegment(6);
        $payment = $this->getOwnerPaymentByToken($token);

        if (!$payment) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Payment was not found.');
        }

        return $this->render('Payment Verification', 'school_owner/plans/payment_success', [
            'payment' => $payment,
            'payment_status' => ($payment->status === 'paid' ? 'paid' : 'pending'),
        ]);
    }

    public function paypal_cancel($token)
    {
        return redirect()->to('school-owner/dashboard')
            ->with('error', 'Payment cancelled. You can continue payment from your dashboard.');
    }

    

    protected function amountForCycle(object $plan, string $billingCycle): float
    {
        if ($billingCycle === 'yearly') {
            return (float) ($plan->yearly_price ?? 0);
        }

        if ($billingCycle === 'lifetime') {
            return (float) ($plan->lifetime_price ?? 0);
        }

        return (float) ($plan->monthly_price ?? 0);
    }

    protected function endDateForCycle(string $billingCycle): ?string
    {
        if ($billingCycle === 'yearly') {
            return date('Y-m-d', strtotime('+1 year'));
        }

        if ($billingCycle === 'lifetime') {
            return null;
        }

        return date('Y-m-d', strtotime('+1 month'));
    }

    protected function getOwnerSubscription(string $token)
    {
        $userId = (int) session()->get('user_id');

        return $this->subscriptionModel
            ->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.token', $token)
            ->where('subscriptions.user_id', $userId)
            ->first();
    }

    protected function getOwnerPendingPayment(string $token)
    {
        $userId = (int) session()->get('user_id');

        return $this->paymentModel
            ->select([
                'payments.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
            ])
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.payment_token', $token)
            ->where('payments.user_id', $userId)
            ->where('payments.status', 'pending')
            ->first();
    }

    protected function getOwnerPaymentByToken(string $token)
    {
        $userId = (int) session()->get('user_id');

        return $this->paymentModel
            ->select([
                'payments.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
            ])
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.payment_token', $token)
            ->where('payments.user_id', $userId)
            ->first();
    }

    protected function activateSubscription(string $token, ?array $paymentData): bool
    {
        $subscription = $this->getOwnerSubscription($token);
        if (!$subscription || (int) $subscription->status === 2) {
            return (bool) $subscription;
        }

        $now = date('Y-m-d H:i:s');
        $userId = (int) $subscription->user_id;

        $current = $this->subscriptionModel->getActiveSubscriptionByUserId($userId);
        if ($current && (int) $current->id !== $subscription->id) {
            $this->subscriptionModel->update($current->id, [
                'status' => 5, // cancelled
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
        }

        $this->subscriptionModel->update($subscription->id, [
            'status' => 2, // active
            'updated_at' => $now,
            'updated_by' => $userId,
        ]);

        if (!empty($paymentData['payment_id'])) {
            $paymentUpdate = [
                'status' => 'paid',
                'paid_at' => $now,
                'updated_at' => $now,
            ];

            if ($paymentData) {
                $paymentUpdate = array_merge($paymentUpdate, array_filter($paymentData, static function ($value) {
                    return $value !== null;
                }));
            }

            $this->paymentModel->update((int) $paymentData['payment_id'], $paymentUpdate);
        }

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

    protected function render(string $title, string $view, array $data = []): string
    {
        $headerData = [
            'page_title' => $title,
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footerData = ['admin_area' => 'yes'];

        return view('header', $headerData)
            . view($view, $data)
            . view('footer', $footerData);
    }
}
