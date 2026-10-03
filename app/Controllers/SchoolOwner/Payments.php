<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;



class Payments extends BaseController
{

    
    protected PaymentModel $paymentModel;
    protected SubscriptionModel $subscriptionModel;

    public function __construct()
    {
        
        $this->paymentModel = new PaymentModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    // Payment listing for school owner
    public function index()
    {
        $data['payments'] = $this->paymentModel->getPayments();


        $header_data['page_title'] = 'Payments';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/payments', $data)
            . view('footer', $footer_data);
    }

    // Payment details for school owner
    public function show($token)
    {
        $data['payment'] = $this->paymentModel->getPaymentDetailsByToken($token);

        $header_data['page_title'] = 'Payment Details';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/payment_details', $data)
            . view('footer', $footer_data);
    }


    // retry_paypal_payment
    public function retry_paypal_payment($payment_token)
    {
       
        if (empty($payment_token)) {
            return redirect()->to('school-owner/payments')->with('error', 'Invalid payment token.');
        }

        $payment = $this->paymentModel->getPaymentDetailsByToken($payment_token);
        if (empty($payment)) {
            return redirect()->to('school-owner/payments')->with('error', 'Payment not found');
        }

        $subscription = $this->subscriptionModel->find($payment->subscription_id);
        if (!$subscription) {
            return redirect()->to('school-owner/payments')->with('error', 'Subscription not found.');
        }
        
        $paypalEmail = env('paypal.email');
        $returnUrl = base_url('school-owner/payments/paypal/success/' . $payment_token);
        $cancelUrl = base_url('school-owner/payments/paypal/cancel/' . $payment_token);
        $notifyUrl = base_url('paypal/ipn');
        $paypalUrl = env('paypal.mode') == 'live'
            ? 'https://www.paypal.com/cgi-bin/webscr'
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

        $html = '<html><body onload="document.forms[0].submit()">'
            . '<form action="' . $paypalUrl . '" method="post">'
            . '<input type="hidden" name="cmd" value="_xclick">'
            . '<input type="hidden" name="business" value="' . esc($paypalEmail) . '">'
            . '<input type="hidden" name="item_name" value="' . esc('Pending Payment') . '">'
            . '<input type="hidden" name="amount" value="' . esc($subscription->amount) . '">'
            . '<input type="hidden" name="currency_code" value="' . esc($subscription->currency ?: 'USD') . '">'
            . '<input type="hidden" name="return" value="' . $returnUrl . '">'
            . '<input type="hidden" name="cancel_return" value="' . $cancelUrl . '">'
            . '<input type="hidden" name="notify_url" value="' . $notifyUrl . '">'
            . '<input type="hidden" name="custom" value="retry:' . esc($payment_token) . '">'
            . '</form><p>Redirecting to PayPal...</p></body></html>';

        return $this->response->setBody($html);
    }

    public function retry_paypal_payment_success($payment_token)
    {
        $payment = $this->paymentModel->where('payment_token', $payment_token)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$payment) {
            return redirect()->to('school-owner/payments')->with('error', 'Payment not found.');
        }

        return $this->render('Payment Confirmation', 'school_owner/subscriptions/payment_success', [
            'payment' => $payment,
            'payment_status' => ($payment->status === 'paid' ? 'paid' : 'pending'),
        ]);
    }

    public function retry_paypal_payment_cancel($payment_token)
    {
        return redirect()->to('school-owner/payments')->with('error', 'Payment cancelled.');
    }

    // retry_stripe_payment
    public function retry_stripe_payment($payment_token)
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
                'success_url' => base_url('school-owner/payments/stripe/success/'.$payment_token),
                'cancel_url' => base_url('school-owner/payments/stripe/cance/' . $payment_token),
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

            // Update payment
            $this->paymentModel->update($payment->id, [
                'status'             => 'paid',
                'transaction_id'     => $transactionId,
                'gateway_payment_id' => $session->id ?? $sessionId,
                'payment_payload'    => isset($session) ? json_encode($session) : null,
                'paid_at'           => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

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
        return redirect()->to('school-owner/payments')
            ->with('error', 'Payment cancelled. You can retry from your payments page.');
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