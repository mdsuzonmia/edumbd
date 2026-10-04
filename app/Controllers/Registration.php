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


class Registration extends BaseController
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

    public function registration_form(){

        
        

        if(session()->get('logged_in')){
            return redirect()->to('/');
        }

        $header_data['page_title'] = lang('Registration.page_title_register');
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('registration/form')
            . view('footer', $footer_data);
    }

    /**
     * Fetch the current BDT -> USD exchange rate (how many BDT make 1 USD).
     *
     * Uses a free currency-rate API and falls back to a sane default (110)
     * whenever the request fails or returns no usable data.
     */
    protected function getUsdToBdtRate(): float
    {
        $fallback = 110.0;

        try {
            $client = \Config\Services::curlrequest();

            $response = $client->request('GET', 'https://open.er-api.com/v6/latest/USD', [
                'timeout'         => 5,
                'connect_timeout' => 3,
            ]);

            $payload = json_decode((string) $response->getBody(), true);

            if (isset($payload['rates']['BDT'])) {
                $rate = (float) $payload['rates']['BDT'];
                if ($rate > 0) {
                    return $rate;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Exchange rate fetch failed: ' . $e->getMessage());
        }

        return $fallback;
    }

    public function do_registration(){
        $schoolName = trim((string) $this->request->getPost('school_name'));
        $mobile = preg_replace('/\D+/', '', (string) $this->request->getPost('phone')) ?? '';
        if (str_starts_with($mobile, '88') && strlen($mobile) === 13) {
            $mobile = substr($mobile, 2);
        }
        $password = (string) $this->request->getPost('password');

        // The public form is deliberately limited to these three fields.
        if ($schoolName === '' || !preg_match('/^01[3-9]\d{8}$/', $mobile) || strlen($password) < 6) {
            return redirect()->back()->withInput()->with(
                'error',
                'সঠিক স্কুলের নাম, ১১ সংখ্যার মোবাইল নম্বর এবং কমপক্ষে ৬ অক্ষরের পাসওয়ার্ড দিন।'
            );
        }

        if ($this->userModel->where('phone', $mobile)->first()) {
            return redirect()->back()->withInput()->with('error', 'এই মোবাইল নম্বর দিয়ে আগে থেকেই অ্যাকাউন্ট আছে।');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $schoolId = $this->schoolModel->insert([
            'name' => $schoolName,
            'slug' => url_title($schoolName, '-', true) . '-' . substr($mobile, -4),
            'country' => 'Bangladesh',
            'phone_code' => '+880',
            'phone' => $mobile,
            'status' => 1,
        ]);

        // Legacy screens expect an email value, so keep an internal address.
        // It is never shown as a registration requirement or verified.
        $userId = $this->userModel->insert([
            'token' => bin2hex(random_bytes(20)),
            'name' => 'শিক্ষক',
            'email' => $mobile . '@mobile.edum.local',
            'phone' => $mobile,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 1,
        ]);

        $this->schoolUserModel->insert(['school_id' => $schoolId, 'user_id' => $userId]);
        $ownerRole = $db->table('edum_roles')->select('id')->where('slug', 'school-owner')->get()->getRow();
        if (!$ownerRole) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'অ্যাকাউন্ট তৈরি করা যায়নি। সহায়তায় যোগাযোগ করুন।');
        }
        $this->userRoleModel->insert(['role_id' => $ownerRole->id, 'user_id' => $userId]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'অ্যাকাউন্ট তৈরি করা যায়নি। আবার চেষ্টা করুন।');
        }

        session()->set([
            'user_id' => (int) $userId,
            'plan_id' => null,
            'user_name' => 'শিক্ষক',
            'role' => 'school-owner',
            'school_id' => (int) $schoolId,
            'school_owner_id' => (int) $userId,
            'logged_in' => true,
        ]);

        $target = session()->has('result_wizard_guest') ? '/examination/result-wizard' : '/school-owner/dashboard';
        return redirect()->to($target)->with('success', 'স্বাগতম! আপনার স্কুলের অ্যাকাউন্ট তৈরি হয়েছে।');

        // Paid-registration callbacks below are retained for existing records.
        /* @codeCoverageIgnoreStart */
        $post = $this->request->getPost();

        $tempToken = bin2hex(random_bytes(20));

        // Set temp_token
        $post['temp_token'] = $tempToken;  
        $post['school_name'] = $post['school_name'] ?? '';
        $post['school_email'] = $post['school_email'] ?? '';
        $post['country'] = $post['country'] ?? '';
        $post['phone'] = $post['phone'] ?? '';
        $post['admin_name'] = $post['admin_name'] ?? '';
        $post['admin_email'] = $post['admin_email'] ?? '';
        $post['password_hash'] = password_hash($post['password'], PASSWORD_BCRYPT);
        $post['plan_id'] = $post['plan_id'] ?? 0;
        $post['billing_cycle'] = $post['billing_cycle'] ?? '';
        $post['billing_price'] = $post['billing_price'] ?? 0;
        $post['currency'] = $post['currency'] ?? 'USD';
        $post['payment_gateway'] = $post['payment_gateway'] ?? 'manual';
        $post['payment_status'] = 'pending';




        // trial
        if($post['is_trial']){
            $post['is_trial'] = 1;
            $post['plan_id'] = 0;
            $post['billing_cycle'] = 'trial';
            $post['billing_price'] = 0;
             // Set currency to USD for trial
            $post['currency'] = NULL;
            $post['payment_gateway'] = 'trial';
             // Set payment status to paid for trial
            $post['payment_status'] = 'trial';
        }

        if ($post['payment_gateway'] == 'manual') {
            // transaction_id
            $post['transaction_id'] = $post['manual_transaction_id'] ?? '';
            // manual_note
            $post['note'] = $post['manual_note'] ?? '';
        }

       
        // Check if user already exists  by email
        $existing_user = $this->userModel
            ->where('email', $post['admin_email'])
            ->first();           

        if ($existing_user) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Admin email already exists. Please use a different email.');
        }
        // Insert to temp table
        $this->tempModel->insert($post);

        if($post['is_trial']){
            return redirect()->to(base_url("register/manual-success/$tempToken"));
        }

        // redirect based on gateway
        if ($post['payment_gateway'] == 'stripe') {
            return redirect()->to(base_url("payment/stripe/$tempToken"));
        }

        if ($post['payment_gateway'] == 'paypal') {
            return redirect()->to(base_url("payment/paypal/$tempToken"));
        }

        if ($post['payment_gateway'] == 'manual') {
            return redirect()->to(base_url("register/manual-success/$tempToken"));
        }
    }

    public function registration_success($tempToken = null){

        if ($tempToken) {
            $temp = $this->tempModel->where('temp_token', $tempToken)->first();

            if (!$temp) {
                return redirect()->to('registration') 
                ->with('error', 'Invalid registration session.');
            }else{
                $data['data'] = $temp;
            }
            
        }else{
            // If no temp token, just show success page (for manual registrations)
            return redirect()->to('registration') 
                ->with('error', 'Invalid registration session.');
        }
        return view('registration/success', $data);
    }

    // manualSuccess
    public function manualSuccess($tempToken = null){
        
        if (!$tempToken) {
            return redirect()->to('registration') 
                ->with('error', 'Invalid registration session.');
        }

        $temp = $this->tempModel->where('temp_token', $tempToken)->first();

        if (!$temp) {
            return redirect()->to('registration') 
                ->with('error', 'Registration not found.');
        }

        if (!empty($temp->is_processed)) {
            return redirect()->to('registration-success');
        }   

        // Set payment_payload
        $payment_payload = [
            'transaction_id' => $temp->transaction_id,
            'note' => $temp->note,
        ];
         
        $temp->subscription_status = 1; // Active
        $temp->transaction_id     = $temp->transaction_id;
        $temp->gateway_payment_id = $temp->transaction_id;
        $temp->payment_status     = 'pending';
        $temp->payment_payload    = json_encode($payment_payload);

        if(createSchoolFromTemp($temp)){

            // Mark temp as processed
            $this->tempModel->update($temp->id, [
                'is_processed' => 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to('registration-success/'.$tempToken);
        }else{
            return redirect()->to('registration') 
                ->with('error', 'Failed to create school. Please contact support.');
        }

        
    }

    public function payment()
    {
        $schoolId = session()->get('register_school_id');

        if (!$schoolId) {
            return redirect()->to('registration');
        }

        $school = $this->schoolModel->find($schoolId);

        return view('registration/payment', [
            'school' => $school
        ]);
    }

    // stripeCheckout
    public function stripeCheckout($tempToken = null)
    {
        if (!$tempToken) {

            return redirect()->to('registration')
                ->with('error', 'Invalid payment session.');
        }

        // =====================================================
        // GET TEMP REGISTRATION
        // =====================================================

        $temp = $this->tempModel
            ->where('temp_token', $tempToken)
            ->first();

        if (!$temp) {

            return redirect()->to('register')
                ->with('error', 'Registration not found.');
        }

        // =====================================================
        // PREVENT DUPLICATE PAYMENT
        // =====================================================

        if (!empty($temp->is_processed)) {

            return redirect()->to('registration-success');
        }

        // =====================================================
        // LOAD STRIPE
        // =====================================================

        \Stripe\Stripe::setApiKey(env('stripe.secretKey'));

        try {

            // =================================================
            // CREATE STRIPE SESSION
            // =================================================

            $session = \Stripe\Checkout\Session::create([

                'payment_method_types' => ['card'],

                'mode' => 'payment',

                'customer_email' => $temp->school_email,

                'line_items' => [[

                    'price_data' => [

                        // Stripe currency is configured in the environment (e.g. USD).
                        'currency' => strtolower(env('stripe.currency', $temp->currency ?: 'USD')),

                        'product_data' => [
                            'name' => 'School Subscription Plan',
                        ],

                        // Stripe amount = cents
                        'unit_amount' => (int) ($temp->billing_price * 100),

                    ],

                    'quantity' => 1,

                ]],

                'metadata' => [

                    'temp_token' => $tempToken,

                ],

                'success_url' => base_url(
                    'payment/stripe/success/'.$tempToken
                ),

                'cancel_url' => base_url(
                    'payment/stripe/cancel/'
                ),

            ]);

            // =================================================
            // SAVE STRIPE SESSION
            // =================================================

            $this->tempModel->update($temp->id, [

                'transaction_id' => $session->id,

                'payment_gateway' => 'stripe',

                'updated_at' => date('Y-m-d H:i:s'),

            ]);

            // =================================================
            // REDIRECT STRIPE
            // =================================================

            return redirect()->to($session->url);

        } catch (\Exception $e) {

            return redirect()->to('registration')
                ->with('error', $e->getMessage());
        }
    }

    // stripeSuccess
    public function stripeSuccess($tempToken = null)
    {
        // Get Temp data by token
        $temp = $this->tempModel->where('temp_token', $tempToken)->first();
        if (!$temp) {
            return redirect()->to('register')->with('error', 'Registration not found.');
        }

        $sessionId = $temp->transaction_id;
        if (!$sessionId) {
            return redirect()->to('register')->with('error', 'Invalid Stripe session.');
        }

        // Load Stripe
        \Stripe\Stripe::setApiKey(env('stripe.secretKey'));

        try {

            // Get Stripe session
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            if (!$session) {
                return redirect()->to('register')->with('error', 'Stripe session not found.');
            }

            if ($session->payment_status !== 'paid') {
                return view('payment/waiting_payment', ['message' => 'Waiting for Stripe payment confirmation...']);
            }

            // get temp data
            $temp = $this->tempModel->where('temp_token', $tempToken)->first();
            if (!$temp) {
                return redirect()->to('registration')->with('error', 'Registration not found.');
            }

            // Prevent duplicate payment
            if (!empty($temp->is_processed)) {
                return redirect()->to('registration-success/'.$tempToken);
            }

            // update temp
            $this->tempModel->update($temp->id, [
                'payment_status' => 'paid',
                'transaction_id' => $sessionId,
                'payment_gateway' => 'stripe',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Create school
            $transactionId = 'STRIPE-' . time() . '-' . rand(1000, 9999);
            if($session->payment_status === 'paid') {
                $temp->subscription_status = 2; // Active
                $temp->transaction_id     = $transactionId;
                $temp->gateway_payment_id = $sessionId;
                $temp->payment_status     = 'paid';
                $temp->payment_payload    = json_encode($session);

            }

            createSchoolFromTemp($temp);

            // Update temp
            $this->tempModel->update($temp->id, [
                'is_processed' => 1,
                'processed_at' => date('Y-m-d H:i:s')
            ]);

            return redirect()->to('registration-success/'.$tempToken);

        } catch (\Exception $e) {

            return redirect()->to('registration')
                ->with('error', $e->getMessage());
        }
    }

    // stripeCancel
    public function stripeCancel()
    {
        $tempToken = $this->request->getGet('token');

        if ($tempToken) {

            $temp = $this->tempModel
                ->where('temp_token', $tempToken)
                ->first();

            if ($temp && empty($temp->is_processed)) {

                $this->tempModel->update($temp->id, [

                    'payment_status' => 'failed',

                    'updated_at' => date('Y-m-d H:i:s'),

                ]);
            }
        }

        return redirect()->to('registration')
            ->with('error', 'Payment cancelled.');
    }

    // paypalCheckout
    public function paypalCheckout($tempToken = null) {
        if (!$tempToken) {
            return redirect()->to('registration');
        }

        // Get temp registration
        $temp = $this->tempModel
            ->where('temp_token', $tempToken)
            ->first();

        if (!$temp) {
            return redirect()->to('registration')
                ->with('error', 'Invalid registration session.');
        }

        $planId       = $temp->plan_id;
        $billingCycle = $temp->billing_cycle;
        $amount       = $temp->billing_price;

        if (!$planId || !$amount) {
            return redirect()->to('registration')
                ->with('error', 'Invalid payment data.');
        }

        // Generate transaction ID
        $transactionId = 'PAYPAL-' . time() . '-' . rand(1000, 9999);

        // Update temp table (IMPORTANT)
        $this->tempModel->update($temp->id, [
            'transaction_id'   => $transactionId,
            'payment_gateway'  => 'paypal',
            'payment_status'   => 'pending',
        ]);

        // PayPal config
        $paypalEmail = env('paypal.email');

        $returnUrl = base_url('payment/paypal/success/' . $tempToken);
        $cancelUrl = base_url('payment/paypal/cancel');
        $notifyUrl = base_url('paypal/ipn');

        $paypalUrl = env('paypal.mode') == 'live'
            ? 'https://www.paypal.com/cgi-bin/webscr'
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

        // PayPal form
        $html = '
        <html>
        <body onload="document.forms[0].submit()">

            <form action="'.$paypalUrl.'" method="post">

                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="'.$paypalEmail.'">

                <input type="hidden" name="item_name" value="School Subscription Plan">
                <input type="hidden" name="amount" value="'.$amount.'">
                <input type="hidden" name="currency_code" value="'.$temp->currency  .'">

                <input type="hidden" name="return" value="'.$returnUrl.'">
                <input type="hidden" name="cancel_return" value="'.$cancelUrl.'">
                <input type="hidden" name="notify_url" value="'.$notifyUrl.'">

                <!-- CRITICAL: temp token for IPN -->
                <input type="hidden" name="custom" value="register:'.$tempToken.'">

            </form>

            <p>Redirecting to PayPal...</p>

        </body>
        </html>';

        return $this->response->setBody($html);
    }

    
    public function paypalSuccess( $tempToken = null) {

        $tempToken = $this->request->getUri()->getSegment(4);

        // Get temp registration
        if(!$tempToken) {
            return redirect()->to('registration')->with('error', 'Invalid payment session.');
        }

        $temp = $this->tempModel->where('temp_token', $tempToken)->first();
        if (!$temp) {
            return redirect()->to('registration')->with('error', 'Registration not found.');
        }

        return redirect()->to(base_url('registration-success/'.$tempToken));
    }

    
}
