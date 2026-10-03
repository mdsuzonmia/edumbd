<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\PlanModel;

class Plans extends BaseController
{
    protected $PlanModel;

    public function __construct()
    {
        $this->PlanModel = new PlanModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('Plan.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        // Filters
        $text      = $this->request->getGet('text');
        $status    = $this->request->getGet('status');
        $show      = $this->request->getGet('show');

        $data = compact('text', 'status', 'show');

        $this->PlanModel->select(['subscription_plans.*']);
           
        // Plan Search
        if ($text) {
            $this->PlanModel->like('subscription_plans.name', $text);
        }

        // Plan Status
        if (isset($status) && $status !== '') {
            $this->PlanModel->where('subscription_plans.status', $status);
        }

        // Sort
        $this->PlanModel->orderBy('subscription_plans.id', 'DESC');
        
        // Pagination
        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage        = $show ? (int) $show : $defaultPerPage;

        // paginate() returns OBJECT because returnType = object
        $data['items'] = $this->PlanModel->paginate($perPage);
        $data['pager'] = $this->PlanModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';

        return view('header', $header_data)
            . view('saas_admin/plans/list', $data)
            . view('footer', $footer_data);
        
    }

    public function create()
    {
        $header_data['page_title'] = lang('Plan.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['is_edit']           = false;

        return view('header', $header_data)
            . view('saas_admin/plans/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($plan_id)
    {
        
        $header_data['page_title'] = lang('Plan.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';


        $data['plan_data']     = $this->PlanModel->findPlanById($plan_id);
        $data['is_edit']       = true;

        return view('header', $header_data)
            . view('saas_admin/plans/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {  
        // Post data
        $post_data       = $this->request->getPost();

        // Set validation rules
        $validationRule = [
            'name'           => 'required',
            'slug'           => 'required',
            'currency'       => 'required',
            'status'         => 'required'
        ];

        // Validate the file input
        $header_data['page_title'] = lang('Plan.page_title_new');
        $header_data['body_class'] = 'plans_create';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']     = $post_data;
        if (!$this->validate($validationRule)) {
            $form_data['validation'] = $this->validator;
            return view('header', $header_data)
            . view('saas_admin/plans/form', $form_data)
            . view('footer', $footer_data);
        }


        // Build multi-currency price data (BDT + USD) and store it as JSON in the prices column
        $bdt_monthly_price  = (float) number_format((float) ($post_data['bdt_monthly_price'] ?? 0), 2, '.', '');
        $bdt_yearly_price   = (float) number_format((float) ($post_data['bdt_yearly_price'] ?? 0), 2, '.', '');
        $bdt_lifetime_price = (float) number_format((float) ($post_data['bdt_lifetime_price'] ?? 0), 2, '.', '');

        $usd_monthly_price  = (float) number_format((float) ($post_data['usd_monthly_price'] ?? 0), 2, '.', '');
        $usd_yearly_price   = (float) number_format((float) ($post_data['usd_yearly_price'] ?? 0), 2, '.', '');
        $usd_lifetime_price = (float) number_format((float) ($post_data['usd_lifetime_price'] ?? 0), 2, '.', '');

        // prices JSON structure: { "BDT": {monthly_price, yearly_price, lifetime_price}, "USD": {...} }
        $prices_data = [
            'BDT' => [
                'monthly_price'  => $bdt_monthly_price,
                'yearly_price'   => $bdt_yearly_price,
                'lifetime_price' => $bdt_lifetime_price,
            ],
            'USD' => [
                'monthly_price'  => $usd_monthly_price,
                'yearly_price'   => $usd_yearly_price,
                'lifetime_price' => $usd_lifetime_price,
            ],
        ];

        // Set user data
        $user_data = [
            'name'              => $post_data['name'],
            'slug'              => $post_data['slug'],
            'description'       => $post_data['description'],
            // Legacy price columns (BDT is kept as the base currency for compatibility)
            'monthly_price'     => $bdt_monthly_price,
            'yearly_price'      => $bdt_yearly_price,
            'lifetime_price'    => $bdt_lifetime_price,
            // All prices (BDT + USD) stored together as JSON
            'prices'            => json_encode($prices_data),
            'currency'          => $post_data['currency'],
            'trial_days'        => $post_data['trial_days'],
            'student_limit'     => $post_data['student_limit'],
            'teachers_limit'    => $post_data['teachers_limit'],
            'branch_limit'      => $post_data['branch_limit'],
            'admin_limit'       => $post_data['admin_limit'],
            'sms_limit'         => $post_data['sms_limit'],
            'storage_limit_mb'  => $post_data['storage_limit_mb'],
            'custom_domain'     => $post_data['custom_domain'],
            'mobile_app_access' => $post_data['mobile_app_access'],
            'api_access'        => $post_data['api_access'],
            'is_popular'        => $post_data['is_popular'],
            'is_featured'       => $post_data['is_featured'],
            'sort_order'       => $post_data['sort_order'],
            'status'            => $post_data['status'],
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s')
        ];


        // Check exit ID for update or insert
        if($post_data['id']){
            // Get Update user
            $exit_id = $post_data['id'];
            $this->PlanModel->update($exit_id, $user_data);
        }else{
            // Get Insert user 
            $this->PlanModel->insert($user_data);
        }

        // Redirect to user list with success message
        return redirect()->to('saas-admin/plans')->with('success', lang('Plan.sys_saved'));
        
    }

    public function trash()
    {
       

        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';
            
            // Retrieve the data sent via AJAX
            $plan_id = $this->request->getPost('plan_id');

            if($plan_id){
                $plan_data['status']  = 2;
                if ($this->PlanModel->update($plan_id, $plan_data)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_trashed'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_trashed'));
                }
            }else{
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing').'<br>';
                $mesg .= lang('Common.data_error_trashed');
                $html .= message_generator('error', $mesg);
            }
    
            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

    public function empty_trash()
    {
        
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';
            
            // Retrieve the data sent via AJAX
            $plan_id = $this->request->getPost('plan_id');

            if($plan_id){

                // Get Delete Plan
                if ($this->PlanModel->delete($plan_id)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_empty_trashed'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_empty_trashed'));
                }
                

            }else{
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing').'<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }
    
            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

    public function restore()
    {
        
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';
            
            // Retrieve the data sent via AJAX
            $plan_id = $this->request->getPost('plan_id');

            if($plan_id){
                $plan_data['status']  = 1;
                if ($this->PlanModel->update($plan_id, $plan_data)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_restored'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_restored'));
                }
            }else{
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing').'<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }
    
            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

    
}