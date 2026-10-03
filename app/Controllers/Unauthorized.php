<?php

namespace App\Controllers;

class Unauthorized extends BaseController
{
    public function unauthorized()
    {
        $header_data = [
            'page_title' => 'Unauthorized Access',
            'body_class' => 'nav-md',
        ];
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('App\Views\unauthorized')
            . view('footer', $footer_data);
    }
}