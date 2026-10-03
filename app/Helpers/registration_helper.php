<?php

use Config\Services;
use App\Models\UserModel;
use App\Models\SchoolModel;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;

use App\Models\UserRoleModel;
use App\Models\UserEmailVerificationModel;
use App\Models\PaymentModel;
use App\Models\SchoolUserModel;

if(!function_exists('createSchoolFromTemp')) {
    function createSchoolFromTemp($temp){

        $schoolModel = new SchoolModel();
        $userModel = new UserModel();
        $subscriptionModel = new SubscriptionModel();
        $planModel = new PlanModel();
        $userRoleModel = new UserRoleModel();
        $userEmailVerificationModel = new UserEmailVerificationModel();
        $paymentModel = new PaymentModel();
        $schoolUserModel = new SchoolUserModel();

        // Check if school already exists
        $existing_school_id = $schoolModel->where('email', $temp->school_email)->first();

        // if not exits school insert data, if exists skip to create school
        if (!$existing_school_id) {
            $schoolSlug = url_title($temp->school_name, '-', true);
            $schoolId = $schoolModel->insert([
                'name'        => $temp->school_name,
                'slug'        => $schoolSlug,
                'email'       => $temp->school_email,
                'country'     => $temp->country,
                'phone'       => $temp->phone
            ]);
        }else{
            $schoolId = $existing_school_id->id;
        }

        // Check if user already exists
        $existing_user_id = $userModel->where('email', $temp->admin_email)->first();

        // if not exits user insert data, if exists skip to create user
        if (!$existing_user_id) {
            $user_id =$userModel->insert([
                'name'      => $temp->admin_name,
                'email'     => $temp->admin_email,
                'password'  => $temp->password_hash,
                'status'    => 1,
                'plan_id'   => $temp->plan_id,
            ]);
        }else{
            $user_id = $existing_user_id->id;
        }

        // Check if school_user_relation already exists
        $existing_school_user_relation = $schoolUserModel->where('user_id', $user_id)->first();

        // if not exits school_user_relation insert data, if exists skip to create school_user_relation
        if (!$existing_school_user_relation) {
            $schoolUserModel->insert([
                'school_id' => $schoolId,
                'user_id'   => $user_id
            ]);
        }

        // Check if user_role already exists
        $existing_user_role = $userRoleModel->where('user_id', $user_id)->first();

        // if not exits user_role insert data, if exists skip to create user_role
        if (!$existing_user_role) {
            $userRoleModel->insert([
                'role_id' => 10, // Default role id 10 for School Owner
                'user_id' => $user_id
            ]);
        }


        // is_trial
        if ($temp->is_trial) {

            // Insert subscription for trial
            $subscription_id = $subscriptionModel->insert([
                'user_id'         => $user_id,
                'plan_id'         => NULL, // no plan for trial
                'token'           => bin2hex(random_bytes(20)),
                'status'          => '1', // 0=Pending, 1=Trial, 2=Active, 3=suspended, 4=Expired, 5=Cancelled, 6=trashed
                'start_date'      => NULL, // will update when user starts trial
                'end_date'        => NULL, // will update when user starts trial

                'is_trial'        => 1,
                'trial_start'     => date('Y-m-d H:i:s'),
                'trial_end'       => date('Y-m-d', strtotime("+30 days")),
                'trial_used'      => 1, // mark trial as used immediately
                
                'amount'          => 0,
                'currency'        => NULL,
                'billing_cycle'   => 'trial',
                'payment_gateway' => 'trial',
            ]);
            
        }else{
            // Calculate expires_at based on billing cycle
            $expiresAt = null;
            if ($temp->billing_cycle == 'monthly') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
            } elseif ($temp->billing_cycle == 'yearly') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
            }

            // Get trial_days by plan_id
            $plan = $planModel->find($temp->plan_id);
            $trialDays = $plan->trial_days ?? 30;

            $subscription_id =$subscriptionModel->insert([
                'user_id'         => $user_id,
                'plan_id'         => $temp->plan_id,
                'token'           => bin2hex(random_bytes(20)),
                'status'          => $temp->subscription_status ? $temp->subscription_status : 0, // 0=Pending, 1=Trial, 2=Active, 3=suspended, 4=Expired, 5=Cancelled, 6=trashed
                'start_date'      => date('Y-m-d'),
                'end_date'        => $expiresAt,

                'is_trial'        => $trialDays > 0 ? 1 : 0,
                'trial_start'     => $trialDays > 0 ? date('Y-m-d') : null,
                'trial_end'       => date('Y-m-d', strtotime("+$trialDays days")),
                'trial_used'      => 0,
                
                'amount'          => $temp->billing_price,
                'currency'        => $temp->currency ? $temp->currency : 'USD',
                'billing_cycle'   => $temp->billing_cycle,
                'payment_gateway' => $temp->payment_gateway,
            ]);

            // 4.Create Payment record
            $payment_id = $paymentModel->insert([
                'subscription_id' => $subscription_id,
                'payment_token'   => bin2hex(random_bytes(20)),
                'payment_type'    => 'register',
                'user_id'         => $user_id,
                'plan_id'         => $temp->plan_id,
                'amount'          => $temp->billing_price,
                'currency'        => $temp->currency ? $temp->currency : 'USD',
                'billing_cycle'   => $temp->billing_cycle,
                'gateway'         => $temp->payment_gateway ?? 'manual',
                'status'          => $temp->payment_status ?? '0',
                'transaction_id'     => $temp->transaction_id ?? null,
                'gateway_payment_id' => $temp->gateway_payment_id ?? null,
                'paid_at'          => date('Y-m-d H:i:s'),
                'payment_payload'  => $temp->payment_payload ?? null,

            ]); 

            // Send Payment Confirmation Email when payment is paid
            if($payment_id && $temp->gateway_payment_id){
                $payment = $paymentModel->find($payment_id);
                $subscription = $subscriptionModel->find($payment->subscription_id);

                // Send Payment Confirmation Email
                if($temp->payment_gateway !=='manual'){
                    sendPaymentConfirmationEmail($payment, $subscription);
                }
            }

        } // end if is_trial

        // Get exiting verification for user
        $verification = $userEmailVerificationModel->where('user_id', $user_id)->first();

        if (!$verification) {
            
            // Get send registration welcome email and email verification token
            $verificationToken = bin2hex(random_bytes(32));

            $userEmailVerificationModel->insert([
                'user_id'     => $user_id,
                'email'       => $temp->admin_email,
                'token'       => $verificationToken,
                'type'        => 'register',
                'expires_at'  => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            // Send Email Verification link
            sendEmailVerificationLink($temp->admin_email, $verificationToken);
        }

        return true;
    }
}

