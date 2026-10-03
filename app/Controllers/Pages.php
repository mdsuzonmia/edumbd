<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PlanModel;

class Pages extends BaseController
{
    /**
     * Terms & Conditions page.
     */
    public function terms()
    {
        $header_data['page_title'] = 'Terms & Conditions';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('pages/terms')
            . view('footer', $footer_data);
    }

    /**
     * Privacy Policy page.
     */
    public function privacy()
    {
        $header_data['page_title'] = 'Privacy Policy';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('header', $header_data)
            . view('pages/privacy')
            . view('footer', $footer_data);
    }

    /**
     * Home / Landing page.
     */
    public function home()
    {
        $header_data['page_title'] = 'Home - Edum';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('frontend/header', $header_data)
            . view('pages/home')
            . view('frontend/footer', $footer_data);
    }

    // features
    public function features()
    {
        $header_data['page_title'] = 'Features - Edum';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('frontend/header', $header_data)
            . view('pages/features')
            . view('frontend/footer', $footer_data);
    }

    // how-it-works
    public function how_it_works()
    {
        $header_data['page_title'] = 'How It Works - Edum';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        return view('frontend/header', $header_data)
            . view('pages/how-it-works')
            . view('frontend/footer', $footer_data);
    }

    /**
     * Pricing page.
     */
    public function pricing()
    {
        $header_data['page_title'] = 'Pricing - Edum';
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        // Fetch plans from database
        $planModel = new PlanModel();
        $plans = $planModel->where('status', 1)
                          ->orderBy('sort_order', 'ASC')
                          ->findAll();

        // Pass plans to view
        $data['plans'] = $plans;
        $data['header_data'] = $header_data;
        $data['footer_data'] = $footer_data;

        return view('frontend/header', $header_data)
            . view('pages/pricing', $data)
            . view('frontend/footer', $footer_data);
    }
}
