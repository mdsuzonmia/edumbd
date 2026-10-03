<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;

class Invoices extends BaseController
{
    protected $InvoiceModel;

    public function __construct()
    {
        $this->InvoiceModel = new InvoiceModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('Invoice.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $text    = $this->request->getGet('text');
        $gateway = $this->request->getGet('gateway');
        $show    = $this->request->getGet('show');

        $data = compact('text', 'gateway', 'show');

        $this->InvoiceModel
            ->select([
                'payments.*',
                'schools.name AS school_name',
                'schools.email AS school_email',
                'users.name AS user_name',
                'subscription_plans.name AS plan_name',
            ])
            ->join('schools', 'schools.id = payments.school_id', 'left')
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.status', 'paid');

        if ($text) {
            $this->InvoiceModel->groupStart()
                ->like('schools.name', $text)
                ->orLike('schools.email', $text)
                ->orLike('subscription_plans.name', $text)
                ->orLike('payments.transaction_id', $text)
                ->orLike('payments.gateway_payment_id', $text)
                ->groupEnd();
        }
        if ($gateway) {
            $this->InvoiceModel->where('payments.gateway', $gateway);
        }

        $this->InvoiceModel->orderBy('payments.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage        = $show ? (int) $show : $defaultPerPage;

        $data['items'] = $this->InvoiceModel->paginate($perPage);
        $data['pager'] = $this->InvoiceModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';
        $data['InvoiceModel'] = $this->InvoiceModel;

        return view('header', $header_data)
            . view('saas_admin/invoices/list', $data)
            . view('footer', $footer_data);
    }

    public function view($invoice_id)
    {
        $header_data['page_title'] = lang('Invoice.page_title_view');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['invoice_data'] = $this->InvoiceModel->getInvoiceDetails($invoice_id);
        $data['InvoiceModel'] = $this->InvoiceModel;

        return view('header', $header_data)
            . view('saas_admin/invoices/view', $data)
            . view('footer', $footer_data);
    }
}
