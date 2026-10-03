<?php
namespace App\Controllers;

use App\Models\StudentModel;
use App\Controllers\BaseController;

class Certificate extends BaseController
{
    public function index()
    {
        // Get check admin user is loggetin and have permission
        if (!is_admin()) {
            $response = check_admin_access('manage_certificate');
            return redirect()->to($response['url'])->with('error', $response['message']);
        }

        $task                 = $this->request->getGet('task');
        $exam_id              = $this->request->getGet('exam');
        $session_id           = $this->request->getGet('session');
        $student_id           = $this->request->getGet('student_id');
        $school_id            = $this->request->getGet('school_id');
        $data['exam_id']      = $exam_id;
        $data['session_id']   = $session_id;
        $data['student_id']   = $student_id;
        $data['school_id']    = $school_id;
        switch ($task) {
            case 'certificate':
                $header_data['page_title'] = lang('Certificate.page_title_certificate');
                $header_data['body_class'] = 'nav-md';
                $header_data['admin_area'] = 'yes';
                $footer_data['admin_area'] = 'yes';
                $student_model   = new StudentModel();
                $student_model->join('student_academics', 'student_academics.student_id = students.id', 'left');
                $student_model->where('student_academics.session_id', $session_id);
                $student_model->where('student_academics.school_id', $school_id);
                if($student_id){
                    $student_data = $student_model->where('students.student_id_number', $student_id);
                }
                $student_data = $student_model->findAll();
                
                $data['student_data']   = $student_data;
                return view('header', $header_data)
                . view('certificate/certificate_page', $data)
                . view('footer', $footer_data);
            break;
            
            default:
                $header_data['page_title'] = lang('Certificate.page_title_form');
                $header_data['body_class'] = 'nav-md';
                $header_data['admin_area'] = 'yes';
                $footer_data['admin_area'] = 'yes';
        
                return view('header', $header_data)
                . view('certificate/form')
                . view('footer', $footer_data);
            break;
        }

    }

}
