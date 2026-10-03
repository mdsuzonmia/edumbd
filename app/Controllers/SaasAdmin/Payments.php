<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;

class Payments extends BaseController
{
    protected $PaymentModel;

    protected $SubscriptionModel;

    public function __construct()
    {
        $this->PaymentModel = new PaymentModel();
        $this->SubscriptionModel = new SubscriptionModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('Payment.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $text    = $this->request->getGet('text');
        $status  = $this->request->getGet('status');
        $gateway = $this->request->getGet('gateway');
        $show    = $this->request->getGet('show');

        $data = compact('text', 'status', 'gateway', 'show');

        $this->PaymentModel
            ->select([
                'payments.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
            ])
            ->join('subscriptions', 'subscriptions.id = payments.subscription_id', 'left')
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left');


        if ($text) {
            $this->PaymentModel->groupStart()
                ->like('users.name', $text)
                ->orLike('subscriptions.name', $text)
                ->orLike('subscription_plans.name', $text)
                ->orLike('payments.transaction_id', $text)
                ->orLike('payments.gateway_payment_id', $text)
                ->groupEnd();
        }
        if ($status) {
            $this->PaymentModel->where('payments.status', $status);
        }
        if ($gateway) {
            $this->PaymentModel->where('payments.gateway', $gateway);
        }

        $this->PaymentModel->orderBy('payments.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage        = $show ? (int) $show : $defaultPerPage;

        $data['items'] = $this->PaymentModel->paginate($perPage);
        $data['pager'] = $this->PaymentModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';

        return view('header', $header_data)
            . view('saas_admin/payments/list', $data)
            . view('footer', $footer_data);
    }

    public function view($payment_id)
    {
        $header_data['page_title'] = lang('Payment.page_title_view');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['payment_data'] = $this->PaymentModel->getPaymentDetails($payment_id);

        return view('header', $header_data)
            . view('saas_admin/payments/view', $data)
            . view('footer', $footer_data);
    }

    public function changeStatus($payment_id)
    {
        $status = $this->request->getPost('status');
        $allowedStatuses = ['pending', 'paid', 'failed', 'cancelled'];

        if (! in_array($status, $allowedStatuses, true)) {
            return redirect()->back()->with('error', lang('Payment.invalid_status'));
        }

        $payment = $this->PaymentModel->find($payment_id);
        if (empty($payment)) {
            return redirect()->back()->with('error', lang('Common.data_error_id_missing'));
        }

        if (strtolower($payment->gateway ?? '') !== 'manual') {
            return redirect()->back()->with('error', lang('Payment.manual_only_status'));
        }

        $updateData = [
            'status' => $status,
        ];

        if ($status === 'paid' && empty($payment->paid_at)) {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }

        $this->PaymentModel->update($payment_id, $updateData);

        $subscription = $this->SubscriptionModel->find($payment->subscription_id);

        // Subscription status update
        if ($status === 'paid') {
            $this->SubscriptionModel->update($payment->subscription_id, ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // Send Payment Confirmation Email
        sendPaymentConfirmationEmail($payment, $subscription);

        return redirect()->to(base_url('saas-admin/payments/view/' . $payment_id))
            ->with('success', lang('Payment.status_updated'));
    }
}
