<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;

class Subscriptions extends BaseController
{
    protected SubscriptionModel $subscriptionModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->paymentModel = new PaymentModel();
    }

    // Subscription listing for school owner
    public function index()
    {
        $data['subscriptions'] = $this->subscriptionModel->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.user_id', session()->get('user_id'))
            ->orderBy('subscriptions.id', 'DESC')
            ->findAll();

        $header_data['page_title'] = 'Subscriptions';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/subscriptions', $data)
            . view('footer', $footer_data);
    }

    // Subscription details for school owner
    public function view($subscription_token)
    {
        $data['subscription'] = $this->subscriptionModel->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.description AS plan_description',
                'subscription_plans.features AS plan_features',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.token', $subscription_token)
            ->where('subscriptions.user_id', session()->get('user_id'))
            ->first();

        $header_data['page_title'] = 'Subscription Details';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/subscription_details', $data)
            . view('footer', $footer_data);
    }

    // Renew subscription
    public function renew()
    {
        $subscription_token = $this->request->getPost('subscription_token');
        $paymentGateway     = $this->request->getPost('payment_method');           
        $payment_token      = bin2hex(random_bytes(20));

        $subscription = $this->subscriptionModel->where('token', $subscription_token)->first();
        
        if (! $subscription) {
            return $this->response->setJSON(['success' => false, 'message' => 'Subscription not found.']);
        }

        // Verify ownership
        if ($subscription->user_id != session()->get('user_id')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized access.']);
        }

        $now = date('Y-m-d H:i:s');
        $paymentData = [
            'payment_token'       => $payment_token,
            'payment_type'        => 'renew',
            'subscription_id'     => $subscription->id,
            'user_id'             => session()->get('user_id'),
            'plan_id'             => $subscription->plan_id,
            'gateway'             => $paymentGateway,
            'billing_cycle'       => $subscription->billing_cycle ?? 'monthly',
            'amount'              => $subscription->amount,
            'currency'            => $subscription->currency ?: 'USD',
            'transaction_id'      => null,
            'status'              => 'pending',
            'payment_payload'     => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];



        // Create payment record
        if ($paymentGateway !== 'free') {
            $this->paymentModel->insert($paymentData);
        }

        // Return available payment gateway endpoints for frontend to show options
        $options = [
            'stripe' => site_url('school-owner/subscriptions/renew/stripe/' . $payment_token),
            'paypal' => site_url('school-owner/subscriptions/renew/paypal/' . $payment_token),
        ];

        return $this->response->setJSON(['success' => true, 'options' => $options]);
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
            return redirect()->to('school-owner/payments')
                ->with('error', 'Unauthorized access.');
        }

        if (strtolower($payment->gateway ?? $payment->payment_gateway ?? '') !== 'stripe') {
            return redirect()->to('school-owner/payments')->with('error', 'This payment cannot be retried with Stripe.');
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (!$subscription) {
            return redirect()->to('school-owner/payments')->with('error', 'Subscription not found.');
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
                'success_url' => base_url('school-owner/subscriptions/renew/stripe/success/'.$payment_token),
                'cancel_url' => base_url('school-owner/subscriptions/renew/stripe/cancel/' . $payment_token),
            ]);

            $this->paymentModel->update($payment->id, [
                'transaction_id' => $session->id,
                'gateway_payment_id' => $session->id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to($session->url);
        } catch (\Exception $e) {
            return redirect()->to('school-owner/payments')->with('error', $e->getMessage());
        }
    }

    public function stripe_success($payment_token)
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

            if (! $subscription || (int) $subscription->status !== 2) {
                return false;
            }

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

            // Update payment
            $this->paymentModel->update($payment->id, [
                'status'             => 'paid',
                'transaction_id'     => $transactionId,
                'gateway_payment_id' => $session->id ?? $sessionId,
                'payment_payload'    => isset($session) ? json_encode($session) : null,
                'paid_at'           => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            // Send Subscription Renewal Confirmation email to user 
            sendSubscriptionRenewalConfirmationEmail($payment, $subscription);

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

            return redirect()->to('school-owner/payments')
                ->with('error', 'Payment verification failed.');
        }
    }
    
    public function stripe_cancel()
    {
        return redirect()->to('school-owner/subscriptions')->with('error', 'Payment cancelled.');
    }

    public function paypal_checkout($token)
    {
        $payment = $this->paymentModel->where('payment_token', $token)->first();

        if (!$payment) {
            return redirect()->to('school-owner/subscriptions')->with('error', 'Payment not found.');
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (!$subscription) {
            return redirect()->to('school-owner/subscriptions')->with('error', 'Subscription not found.');
        }
        
        $paypalEmail = env('paypal.email');
        $returnUrl = base_url('school-owner/subscriptions/renew/paypal/success/' . $token);
        $cancelUrl = base_url('school-owner/subscriptions/renew/paypal/cancel/' . $token);
        $notifyUrl = base_url('paypal/ipn');
        $paypalUrl = env('paypal.mode') == 'live'
            ? 'https://www.paypal.com/cgi-bin/webscr'
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

        $html = '<html><body onload="document.forms[0].submit()">'
            . '<form action="' . $paypalUrl . '" method="post">'
            . '<input type="hidden" name="cmd" value="_xclick">'
            . '<input type="hidden" name="business" value="' . esc($paypalEmail) . '">'
            . '<input type="hidden" name="item_name" value="' . esc('Subscription Renewal') . '">'
            . '<input type="hidden" name="amount" value="' . esc($subscription->amount) . '">'
            . '<input type="hidden" name="currency_code" value="' . esc($subscription->currency ?: 'USD') . '">'
            . '<input type="hidden" name="return" value="' . $returnUrl . '">'
            . '<input type="hidden" name="cancel_return" value="' . $cancelUrl . '">'
            . '<input type="hidden" name="notify_url" value="' . $notifyUrl . '">'
            . '<input type="hidden" name="custom" value="renew:' . esc($token) . '">'
            . '</form><p>Redirecting to PayPal...</p></body></html>';

        return $this->response->setBody($html);
    }

    public function paypal_success($token)
    {
        $token = $this->request->getUri()->getSegment(6) ?? $token;
        $customToken = $token;
        $payment = $this->paymentModel->where('payment_token', $customToken)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$payment) {
            return redirect()->to('school-owner/subscriptions')->with('error', 'Payment not found.');
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (!$subscription) {
            return redirect()->to('school-owner/subscriptions')->with('error', 'Subscription not found.');
        }

        return $this->render('Payment Confirmation', 'school_owner/subscriptions/payment_success', [
            'payment' => $payment,
            'payment_status' => ($payment->status === 'paid' ? 'paid' : 'pending'),
        ]);


    }

    public function paypal_cancel($token)
    {
        return redirect()->to('school-owner/subscriptions')->with('error', 'Payment cancelled.');
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
