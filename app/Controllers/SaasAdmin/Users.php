<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserEmailVerificationModel;
use App\Models\UserRoleModel;

class Users extends BaseController
{
    protected $UserModel;
    protected $UserRoleModel;
    protected $UserEmailVerificationModel;

    public function __construct()
    {
        $this->UserModel = new UserModel();
        $this->UserRoleModel = new UserRoleModel();
        $this->UserEmailVerificationModel = new UserEmailVerificationModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('User.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        // Filters
        $text      = $this->request->getGet('text');
        $status    = $this->request->getGet('status');
        $show      = $this->request->getGet('show');
        $role_id   = $this->request->getGet('role_id');
        $school_id = $this->request->getGet('school_id');

        $data = compact('text', 'status', 'role_id', 'school_id', 'show');

        $this->UserModel
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.photo',
                'users.status',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',

                'schools.id AS school_id',
                'schools.name AS school_name',
                'schools.slug AS school_slug',

                'GROUP_CONCAT(DISTINCT edum_roles.name ORDER BY edum_roles.name SEPARATOR ", ") AS roles',
                'GROUP_CONCAT(DISTINCT edum_roles.slug ORDER BY edum_roles.slug SEPARATOR ", ") AS role_slugs',
                // Get is_email_verified_status
                'edum_email_verifications.is_verified AS is_email_verified_status',
                'edum_email_verifications.verified_at AS email_verified_at',
                'IF(edum_email_verifications.verified_at IS NULL, 0, 1) AS is_email_verified'

                //'GROUP_CONCAT(DISTINCT roles.name SEPARATOR ", ") AS roles'
            ])
            ->join('school_user_relation', 'users.id = school_user_relation.user_id', 'left')
            ->join('schools', 'schools.id = school_user_relation.school_id', 'left')
            ->join('user_roles', 'user_roles.user_id = users.id', 'left')
            ->join('edum_roles', 'edum_roles.id = user_roles.role_id', 'left')
            ->join(
            'edum_email_verifications',
            'edum_email_verifications.user_id = users.id 
            AND edum_email_verifications.verified_at IS NOT NULL',
            'left'
        );

        /* ------------------------------
        * WHERE
        * ------------------------------ */
        if ($text) {
            $this->UserModel->like('users.name', $text);
            $this->UserModel->orLike('users.email', $text);
            $this->UserModel->orLike('users.phone', $text);
        }
        if ($status) {
            $this->UserModel->where('users.status', $status);
        }
        if ($role_id) {
            $this->UserModel->where('user_roles.role_id', $role_id);
        }
        if ($school_id) {
            $this->UserModel->where('schools.id', $school_id);
        }



        $this->UserModel->groupBy('users.id')
        ->orderBy('users.id', 'DESC');
        /* ------------------------------
        * Pagination
        * ------------------------------ */
        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage        = $show ? (int) $show : $defaultPerPage;

        // 🔥 paginate() returns OBJECT because returnType = object
        $data['items'] = $this->UserModel->paginate($perPage);
        $data['pager'] = $this->UserModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';

        return view('header', $header_data)
            . view('saas_admin/users/list', $data)
            . view('footer', $footer_data);
        
    }

    public function form()
    {
        $header_data['page_title'] = lang('User.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['user_data']         = '';
        $data['is_edit']           = false;

        return view('header', $header_data)
            . view('saas_admin/users/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($user_id)
    {
        
        $header_data['page_title'] = lang('User.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

       
        $this->UserModel
            ->select([
                'users.id',
                'users.plan_id',
                'users.name',
                'users.email',
                'users.phone',
                'users.photo',
                'users.status',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',

                'schools.id AS school_id',
                'schools.name AS school_name',
                'schools.slug AS school_slug',

                // Get role id
                'GROUP_CONCAT(DISTINCT edum_roles.id ORDER BY edum_roles.id SEPARATOR ", ") AS role_id',
                'GROUP_CONCAT(DISTINCT edum_roles.name ORDER BY edum_roles.name SEPARATOR ", ") AS roles',
                'GROUP_CONCAT(DISTINCT edum_roles.slug ORDER BY edum_roles.slug SEPARATOR ", ") AS role_slugs',

                // Get is_email_verified_status
                'edum_email_verifications.is_verified AS is_email_verified_status',
                'edum_email_verifications.verified_at AS email_verified_at'



                //'IF(edum_email_verifications.verified_at IS NULL, 0, 1) AS is_email_verified'

                //'GROUP_CONCAT(DISTINCT roles.name SEPARATOR ", ") AS roles'
            ])
            ->join('school_user_relation', 'users.id = school_user_relation.user_id', 'left')
            ->join('schools', 'schools.id = school_user_relation.school_id', 'left')
            ->join('user_roles', 'user_roles.user_id = users.id', 'left')
            ->join('edum_roles', 'edum_roles.id = user_roles.role_id', 'left')
            ->join(
            'edum_email_verifications',
            'edum_email_verifications.user_id = users.id 
            AND edum_email_verifications.verified_at IS NOT NULL',
            'left'
        );

        $user_data = $this->UserModel->where('users.id', $user_id)->first();

        $data['user_data']     = $user_data;
        $data['is_edit']       = true;

        return view('header', $header_data)
            . view('saas_admin/users/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {  
        // Post data
        $post_data       = $this->request->getPost();

        // Get photo max size and allowed extensions from settings
        $photo_max_size = esc(setting('application', 'photo_max_size'));

        // allowed_photo_extensions
        $allowed_photo_extensions = esc(setting('application', 'allowed_photo_extensions'));
        
        // Set validation rules
        $validationRule = [
            'email'          => 'required|max_length[254]|valid_email',
            'role_id'        => 'required',
            'status'         => 'required'
        ];

        if(!$post_data['user_id']){
            $validationRule['password']          = 'required|max_length[255]|min_length[6]';
        }
        
        // if user photo is not empty
        $user_photo = $this->request->getFile('photo');
        if ($user_photo->isValid() && !$user_photo->hasMoved()) {
            $validationRule['photo'] = [
                'label' => 'User Photo',
                'rules' => 'uploaded[photo]|max_size[photo,'.$photo_max_size.']|ext_in[photo,'.$allowed_photo_extensions.']'
            ];
        }


        // Validate the file input
        $header_data['page_title'] = lang('User.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']     = $post_data;
        $form_data['user_data']     = '';
        $form_data['is_edit']       = true;
        if (!$this->validate($validationRule)) {
            $form_data['validation'] = $this->validator;
            return view('header', $header_data)
            . view('saas_admin/users/form', $form_data)
            . view('footer', $footer_data);
        }


        // Get logged in user id from session
        $logget_user_id = session()->get('user_id');

       
        // Set user data
        $user_data = [
            'name'         => $post_data['name'],
            'email'        => $post_data['email'],
            'phone'        => $post_data['phone'],
            'plan_id'      => $post_data['plan_id'] ? $post_data['plan_id'] : null,
            'status'       => $post_data['status'],
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
            'created_by'   =>  $logget_user_id,
            'updated_by'   => $logget_user_id
        ];

        // Password include user_data only if password is not empty
        if(!empty($post_data['password'])){
            $user_data['password'] = password_hash($post_data['password'], PASSWORD_DEFAULT);
        }

        

        // Handle the user photo upload
        $user_photo_name = '';
        if ($user_photo->isValid() && !$user_photo->hasMoved()) {

            // Get delete old photo if exit
            $old_user_photo = $post_data['old_photo'];
            if(!empty($old_user_photo)){
                $user_photo_path = realpath(WRITEPATH .'uploads/'.$old_user_photo);
                if(file_exists($user_photo_path)){
                   unlink($user_photo_path);
                } 
            }
            
            // Get start new upload
            $user_photo_name = $user_photo->getRandomName();
            $user_photo->move(WRITEPATH . 'uploads', $user_photo_name);
            $user_data['photo'] = $user_photo_name; 
        } 

        // Check exit user
        if($post_data['user_id']){
            // Get Update user
            $user_id = $post_data['user_id'];
            $this->UserModel->update($user_id, $user_data);
        }else{
            // Get Insert user 
            $this->UserModel->insert($user_data);
            $user_id = $this->UserModel->insertID();  
        }

        // Set user role data
        $user_role_data = [
            'role_id' => $post_data['role_id'],
            'user_id' => $user_id
        ];

        // Check exit user role
        $exit_user_role = $this->UserRoleModel->where('user_id', $user_id)->first();
        
        if ($exit_user_role) {
            // Update user role
            $this->UserRoleModel->update($exit_user_role->id, $user_role_data);
        } else {
            // Insert user role
            $this->UserRoleModel->insert($user_role_data);
        }

        
        // if verified then update status to verified $post_data['is_verified']
        if(isset($post_data['is_verified'])){
            // Check exit user email verification
            $exit_user_email_verification = $this->UserEmailVerificationModel->where('user_id', $user_id)->first();
            if($exit_user_email_verification){
                // Get Update user email verification
                $verfied_data = [
                    'status' => $post_data['is_verified'],
                    'verified_at'  => date('Y-m-d H:i:s')
                ];
                $this->UserEmailVerificationModel->update($exit_user_email_verification->id, $verfied_data);
                
            }else{
                // Get Insert user email verification 
                $this->UserEmailVerificationModel->insert([
                    'user_id' => $user_id,
                    'email'   => $post_data['email'],
                    'status'  => $post_data['is_verified'],
                    'token'    => bin2hex(random_bytes(16)),
                    'verified_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Redirect to user list with success message
        return redirect()->to('saas-admin/users')->with('success', lang('User.sys_saved'));
        
    }

    public function trash()
    {
       

        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';
            
            // Retrieve the data sent via AJAX
            $user_id = $this->request->getPost('user_id');

            if($user_id){
                $user_data['status']  = 2;
                if ($this->UserModel->update($user_id, $user_data)) {
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
            $user_id = $this->request->getPost('user_id');

            if($user_id){

                // Get delete photo if exit
                $user_photo = get_item('photo', 'users', 'id', $user_id);
                if(!empty($user_photo)){
                    $user_photo_path = realpath(WRITEPATH .'uploads/'.$user_photo);
                    if(file_exists($user_photo_path)){
                    unlink($user_photo_path);
                    } 
                }

                // Get delete user roles
                $this->UserRoleModel->where('user_id', $user_id)->delete();

                // Get delete user email verifications
                $this->UserEmailVerificationModel->where('user_id', $user_id)->delete();

                // Get Delete User
                if ($this->UserModel->delete($user_id)) {
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
            $user_id = $this->request->getPost('user_id');

            if($user_id){
                $user_data['status']  = 1;
                if ($this->UserModel->update($user_id, $user_data)) {
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

    public function profile($user_id)
    {
       

        $header_data['page_title'] = lang('Student.page_title_student_profile');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $user_model      = new UserModel();

      
        // Set User Data
        $user_data = $user_model->where('id', $user_id)->first();

        $data['user_data']     = $user_data;
        $data['is_edit']       = true;

        return view('header', $header_data)
            . view('user/profile', $data)
            . view('footer', $footer_data);
    }

}