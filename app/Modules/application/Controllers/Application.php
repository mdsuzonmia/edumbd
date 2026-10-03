<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\GuardianModel;
use App\Models\UserModel;
use App\Models\AcademicsModel;
use App\Models\FieldbuilderModel;
use App\Controllers\BaseController;

class Application extends BaseController
{

    public function form()
    {
        
        $header_data['page_title'] = lang('Student.application_form_title');
        $header_data['body_class'] = 'application';
        $header_data['admin_area'] = '';
        $footer_data['admin_area'] = 'yes';

        
        $data['student_data']  = '';
        $data['guardian_data'] = '';
        $data['user_data']     = '';
        $data['academic_data'] = '';
        $data['is_edit']       = false;

        return view('application/header', $header_data)
            . view('application/form', $data)
            . view('application/footer', $footer_data);
    }

    public function thankyou($registration_id)
    {
        
        $header_data['page_title'] = lang('Student.thankyou_title');
        $header_data['body_class'] = 'application';
        $header_data['admin_area'] = '';
        $footer_data['admin_area'] = 'yes';

        $student_model   = new StudentModel();

        // Set Student Data
        $student_data = $student_model->where('registration_id', $registration_id)->first();

        $data['student_data']  = $student_data;
        
        return view('application/header', $header_data)
            . view('application/thankyou', $data)
            . view('application/footer', $footer_data);
    }

    public function list()
    {
        // Get check user is loggetin and have permission
        $check_role = checkRole();
        if(!empty($check_role['url'])){
            return redirect()->to($check_role['url'])->with('error', $check_role['message']);
        }

        $header_data['page_title'] = lang('Student.application_list');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $text           = $this->request->getGet('text');
        $status         = $this->request->getGet('status');
        $data['text']   = $text;
        $data['status'] = $status;

        $student_model   = new StudentModel();

        if(isset($text) && isset($status)){
            if($status){
                $student_model->where('status', $status);
            }elseif($status==0){
                $student_model->where('status', $status);
            }else{
                $student_model->where('status', 3);
            }

            

            if($text){
                $student_model->like('name', $text);
            }

            $results = $student_model->findAll();
        }else{
            $student_model->where('status', 3);
            $results = $student_model->findAll();
        }

        $data['items'] = $results;
        return view('header', $header_data)
            . view('application/list', $data)
            . view('footer', $footer_data);
        
    }
    
    public function approve()
    {
        
        $student_model   = new StudentModel();
        $user_model      = new UserModel();
        $academic_model  = new AcademicsModel();
        $post_data       = $this->request->getPost();

        // Get Setting Value
        $student_account        = esc(get_setting_value('student_account'));
        $class_roll             = esc(get_setting_value('class_roll'));

       
        // If student account is active
        if($student_account){
            $validationRule['student_username']  = 'required|max_length[30]';
            $validationRule['student_email']     = 'required|max_length[254]|valid_email';
            if(!$post_data['user_id']){
                $validationRule['password']          = 'required|max_length[255]|min_length[6]';
            }
        }

       
        // Data for Student
        $student_data = ['status' => 1 ];
        $user_data    = ['is_active' => 1];

        // Handle Student Account
        if($student_account){
            // Check exit user
            if($post_data['user_id']){
                // Get Update user
                $user_id = $post_data['user_id'];
                $user_model->update($user_id, $user_data);
            }
        }

        // Check exit student
        if($post_data['student_id']){
            // Get Update student
            $student_id = $post_data['student_id'];
            $student_model->update($student_id, $student_data);
        }

        // If student class roll is active
        $academic_data['status']  = 1;
        if($class_roll){
            $academic_data['roll']  = $post_data['roll'];
        }
        // Check exit student academic id
        if($post_data['academic_id']){
            // Get Update student
            $academic_id = $post_data['academic_id'];
            $academic_model->update($academic_id, $academic_data);
        }

        return redirect()->to('/application-list/')->with('success', lang('Student.application_approved'));
        
    }
    
    public function save()
    {
        
        $student_model   = new StudentModel();
        $guardian_model  = new GuardianModel();
        $user_model      = new UserModel();
        $academic_model  = new AcademicsModel();
        $post_data       = $this->request->getPost();

        // Get Setting Value
        $student_account        = esc(get_setting_value('student_account'));
        $guardian_info          = esc(get_setting_value('guardian_info'));
        $registration_digit     = esc(get_setting_value('registration_digit'));
        $max_size               = get_setting_value('max_size');
        $ext_in                 = get_setting_value('ext_in');
        $academic_shift         = esc(get_setting_value('academic_shift'));
        $academic_department    = esc(get_setting_value('academic_department'));
        $academic_category      = esc(get_setting_value('academic_category'));
        $extra_skill            = esc(get_setting_value('extra_skill'));
        $student_phone_enabled  = esc(get_setting_value('student_phone'));

        $validationRule = [
            'student_name'     => 'required',
            'year'             => 'required',
            'class'            => 'required',
            'subject'          => 'required'
        ];

        

        // If student academic shift is active
        if($academic_shift){
            $validationRule['shift']  = 'required';
        }

        // If student academic department is active
        if($academic_department){
            $validationRule['department']  = 'required';
        }

        // If student academic category is active
        if($academic_category){
            $validationRule['category']  = 'required';
        }

        // If student academic skill is active
        if($extra_skill){
            $validationRule['skill']  = 'required';
        }

        // If student student_phone is active
        if($student_phone_enabled){
            $validationRule['student_phone']  = 'required';
        }

        // If student account is active
        if($student_account){
            $validationRule['student_username']  = 'required|max_length[30]';
            $validationRule['student_email']     = 'required|max_length[254]|valid_email';
            if(!$post_data['user_id']){
                $validationRule['password']          = 'required|max_length[255]|min_length[6]';
            }
        }

        // if student photo is not empty
        $student_photo = $this->request->getFile('student_photo');
        if ($student_photo->isValid() && !$student_photo->hasMoved()) {
            $validationRule['student_photo'] = [
                'label' => 'Student Photo',
                'rules' => 'uploaded[student_photo]|max_size[student_photo,'.$max_size.']|ext_in[student_photo,'.$ext_in.']'
            ];
        }

        // if guardian photo is not empty
        if($guardian_info){
            $guardian_photo = $this->request->getFile('guardian_photo');
            if ($guardian_photo->isValid() && !$guardian_photo->hasMoved()) {
                $validationRule['guardian_photo'] = [
                    'label' => 'Guardian Photo',
                    'rules' => 'uploaded[guardian_photo]|max_size[guardian_photo,'.$max_size.']|ext_in[guardian_photo,'.$ext_in.']'
                ];
            }
        }

        // Validate the file input
        $header_data['page_title'] = lang('Student.application_form_title');
        $header_data['body_class'] = 'application';
        $header_data['admin_area'] = '';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']     = $post_data;
        $form_data['student_data']  = '';
        $form_data['guardian_data'] = '';
        $form_data['user_data']     = '';
        $form_data['academic_data'] = '';
        $form_data['is_edit']       = false;
        if (!$this->validate($validationRule)) {
            $form_data['validation'] = $this->validator;
            return view('application/header', $header_data)
            . view('application/form', $form_data)
            . view('application/footer', $footer_data);
        }

        
        // Set Registration Number
        $registration_id    = generate_unique_random_number($registration_digit);

        // Data for Student
        $student_data = [
            'name'                => $post_data['student_name'],
            'registration_id'     => $registration_id,
            'status'              => 3
        ];

        $user_data = [
            'name'         => $post_data['student_name'],
            'role_id'      => 3,
            'is_active'    => 0
        ];

        // Handle the student photo upload
        $student_photo_name = '';
        if ($student_photo->isValid() && !$student_photo->hasMoved()) {

            // Get delete old photo if exit
            $old_student_photo = $post_data['old_student_photo'];
            if(!empty($old_student_photo)){
                $delete_student_photo = realpath(WRITEPATH .'uploads/'.$old_student_photo);
                if(file_exists($delete_student_photo)){
                   unlink($delete_student_photo);
                } 
            }
            
            // Get start new upload
            $student_photo_name = $student_photo->getRandomName();
            $student_photo->move(WRITEPATH . 'uploads', $student_photo_name);
            $student_data['photo'] = $student_photo_name; 
            if($student_account){
                $user_data['photo'] = $student_photo_name; 
            }
        } 

        // Handle Student Account
        if($student_account){
            

            if(isset($post_data['student_username']) && !empty($post_data['student_username'])){
                $user_data['username'] = $post_data['student_username'];
            }

            if(isset($post_data['student_email']) && !empty($post_data['student_email'])){
                $user_data['email'] = $post_data['student_email'];
            }

            if(isset($post_data['password']) && !empty($post_data['password'])){
                $user_data['password'] = password_hash($post_data['password'], PASSWORD_DEFAULT);
            }

            // Check exit user
            if($post_data['user_id']){
                // Get Update user
                $user_id = $post_data['user_id'];
                $user_model->update($user_id, $user_data);
            }else{
               // Get Insert user 
               $user_model->insert($user_data);
               $user_id = $user_model->insertID();  
            }

        }else{
            $user_id = '';
        }

        $student_data['user_id'] = $user_id;

        
        // If student student_phone is active
        if($student_phone_enabled){
            $student_data['phone']  = $post_data['student_phone'];
        }

        

        // Check exit student
        if($post_data['student_id']){
            // Get Update student
            $student_id = $post_data['student_id'];
            $student_model->update($student_id, $student_data);
        }else{
           // Get Insert student 
           $student_model->insert($student_data);
           $student_id = $student_model->insertID();  
        }

        // Get Custom Fields for student
        $field_model = new FieldbuilderModel();
        $student_section_id = get_item('id', 'fields_section', 'title', 'Student');
        $fields = $field_model->where('section', $student_section_id)->orderBy('field_order', 'ASC')->findAll();

        if(!empty($student_id) && !empty($student_section_id)){
            foreach ($fields as $field) {
                $field_id       = $field->id;
                $field_name     ='field_'.$field_id;
                $field_value = isset($post_data[$field_name]) ? $post_data[$field_name]: '';
                save_field_data($field_id, $student_section_id, $student_id, $field_value);
            }
        }
        
        // Get subject value
        if($post_data['subject']){
            $subject_value = implode(',', $post_data['subject']);
        }else{
            $subject_value = '';
        }

        
        
        // Data for Student Academic
        $academic_data = [
            'name'            => $post_data['student_name'],
            'student_id'      => $student_id,
            'class_id'        => $post_data['class'],
            'year_id'         => $post_data['year'],
            'subject_ids'     => $subject_value
        ];

        
        // If student academic shift is active
        if($academic_shift){
            $academic_data['shift_id']  = $post_data['shift'];
        }

        // If student academic department is active
        if($academic_department){
            $academic_data['department_id']  = $post_data['department'];
        }

        // If student academic category is active
        if($academic_category){
            $academic_data['category_id']  = $post_data['category'];
        }

        // If student academic skill is active
        if($extra_skill){
            // Get skill value
            if($post_data['skill']){
                $skill_value = implode(',', $post_data['skill']);
            }else{
                $skill_value = '';
            }
            $academic_data['skill_ids']  = $skill_value;
        }
        

        // Check exit student academic id
        if($post_data['academic_id']){
            // Get Update student
            $academic_id = $post_data['academic_id'];
            $academic_model->update($academic_id, $academic_data);
        }else{
           // Get Insert student 
           $academic_model->insert($academic_data);
           $academic_id = $academic_model->insertID();  
        }

        if($guardian_info):

            // Get guardian
            $guardian_data = [
                'name'                => $post_data['guardian_name'],
                'email'               => $post_data['guardian_email'],
                'phone'               => $post_data['guardian_phone'],
                'student_id'          => $student_id
            ];

            // Handle the student photo upload
            if ($guardian_photo->isValid() && !$guardian_photo->hasMoved()) {

                // Get delete old photo if exit
                $old_guardian_photo = $post_data['old_guardian_photo'];
                if(!empty($old_guardian_photo)){
                    $delete_guardian_photo = realpath(WRITEPATH .'uploads/'.$old_guardian_photo);
                    if(file_exists($delete_guardian_photo)){
                    unlink($delete_guardian_photo);
                    } 
                }
                
                // Get started new upload
                $guardian_photo_name = $guardian_photo->getRandomName();
                $guardian_photo->move(WRITEPATH . 'uploads', $guardian_photo_name);
                $guardian_data['photo'] = $guardian_photo_name; 
            } 

            // Check exit guardian
            if($post_data['guardian_id']){
                // Get Update guardian
                $guardian_id = $post_data['guardian_id'];
                $guardian_model->update($guardian_id, $guardian_data);
            }else{
                // Get Insert guardian 
                $guardian_model->insert($guardian_data);
                $guardian_id = $guardian_model->insertID();  
            }

            // Get Custom Fields for guardian
            $guardian_section_id = get_item('id', 'fields_section', 'title', 'Guardian');
            $guardian_fields     = $field_model->where('section', $guardian_section_id)->orderBy('field_order', 'ASC')->findAll();

            if(!empty($guardian_id) && !empty($guardian_section_id)){
                foreach ($guardian_fields as $field) {
                    $field_id       = $field->id;
                    $field_name     ='field_'.$field_id;
                    $field_value = isset($post_data[$field_name]) ? $post_data[$field_name]: '';
                    save_field_data($field_id, $guardian_section_id, $guardian_id, $field_value);
                }
            }
        endif;

        return redirect()->to('/application/thankyou/'.$registration_id)->with('success', lang('Student.sys_saved'));
        
    }

   
    public function review($student_id)
    {
        // Get check user is loggetin and have permission
        $check_role = checkRole();
        if(!empty($check_role['url'])){
            return redirect()->to($check_role['url'])->with('error', $check_role['message']);
        }

        $header_data['page_title'] = lang('Student.page_title_student_profile');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $student_model   = new StudentModel();
        $guardian_model  = new GuardianModel();
        $user_model      = new UserModel();
        $academic_model  = new AcademicsModel();

        // Set Student Data
        $student_data = $student_model->where('id', $student_id)->first();

        // Set User Data
        //$user_id = $student_data->user_id;
        //$user_data = $user_model->where('id', $user_id)->first();


        // Set Guardian Data
        $guardian_data = $guardian_model->where('student_id', $student_id)->first();

        // Set Academic Data
        $year_id              = esc(get_setting_value('year'));
        $academic_single_data = $academic_model->where('student_id', $student_id)->where('year_id', $year_id)->first();
        $academic_id          = $academic_single_data->id;
        $academic_data = get_academic_data($student_id);
        
        $data['student_data']  = $student_data;
        $data['academic_id']   = $academic_id;
        $data['guardian_data'] = $guardian_data;
        $data['academic_data'] = $academic_data;
        $data['is_edit']       = true;

        return view('header', $header_data)
            . view('application/review', $data)
            . view('footer', $footer_data);
    }
    
}
