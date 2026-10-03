<?php
namespace Modules\payment\Controllers;

use App\Controllers\BaseController;

class PaymentController extends BaseController
{
    public function superAdminDashboard()
    {
        //return view('Modules\\Payment\\Views\\super_admin_dashboard');
        $header_data['page_title'] = lang('Auth.page_title_dashboard');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('Modules\\payment\\Views\\super_admin_dashboard')
            . view('footer', $footer_data);

    }
}