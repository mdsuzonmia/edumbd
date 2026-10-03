<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\SaasAdmin\ReportModel;

class Reports extends BaseController
{
    protected $ReportModel;

    public function __construct()
    {
        $this->ReportModel = new ReportModel();
    }

    public function index()
    {
        $header_data['page_title'] = lang('Report.page_title_index');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = $this->ReportModel->getOverview();

        return view('header', $header_data)
            . view('saas_admin/reports/index', $data)
            . view('footer', $footer_data);
    }

    public function schools()
    {
        $header_data['page_title'] = lang('Report.page_title_schools');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $status = $this->request->getGet('status');

        $data['status'] = $status;
        $data['items']  = $this->ReportModel->getSchoolsReport($status);

        return view('header', $header_data)
            . view('saas_admin/reports/schools', $data)
            . view('footer', $footer_data);
    }

    public function revenue()
    {
        $header_data['page_title'] = lang('Report.page_title_revenue');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $date_from = $this->request->getGet('date_from');
        $date_to   = $this->request->getGet('date_to');
        $gateway   = $this->request->getGet('gateway');

        $data['date_from'] = $date_from;
        $data['date_to']   = $date_to;
        $data['gateway']   = $gateway;
        $data['items']     = $this->ReportModel->getRevenueReport($date_from, $date_to, $gateway);
        $data['total']     = $this->ReportModel->getRevenueTotal($date_from, $date_to);

        return view('header', $header_data)
            . view('saas_admin/reports/revenue', $data)
            . view('footer', $footer_data);
    }

    public function subscriptions()
    {
        $header_data['page_title'] = lang('Report.page_title_subscriptions');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $status        = $this->request->getGet('status');
        $billing_cycle = $this->request->getGet('billing_cycle');

        $data['status']        = $status;
        $data['billing_cycle'] = $billing_cycle;
        $data['items']         = $this->ReportModel->getSubscriptionsReport($status, $billing_cycle);

        return view('header', $header_data)
            . view('saas_admin/reports/subscriptions', $data)
            . view('footer', $footer_data);
    }
}
