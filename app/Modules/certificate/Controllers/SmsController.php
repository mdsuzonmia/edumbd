<?php

namespace App\Modules\sms_whatapp\Controllers;

use App\Models\ModuleModel;
use App\Controllers\BaseController;
use Twilio\Rest\Client;
use Exception;


class SmsController extends BaseController
{
    
    public function index(){
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'sms_whatapp')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }

        $task        = $this->request->getGet('task');

        // Get request
        $data['send_to']     = $this->request->getGet('send_to');
        $data['exam']        = $this->request->getGet('exam');
        $data['session']     = $this->request->getGet('session');
        $data['school_id']   = $this->request->getGet('school_id');
        $data['class']       = $this->request->getGet('class');

        // Additional data
        $data['grade']       = $this->request->getGet('grade');
        $data['group']       = $this->request->getGet('group');
        $data['house']       = $this->request->getGet('house');
        $data['version']     = $this->request->getGet('version');
        $data['section']     = $this->request->getGet('section');
        $data['category']    = $this->request->getGet('category');
        $data['shift']       = $this->request->getGet('shift');
        $data['department']  = $this->request->getGet('department');

        switch ($task) {
            case 'send-sms':
                $header_data['page_title'] = lang('Sms.page_title');
                $header_data['body_class'] = 'nav-md';
                $header_data['admin_area'] = 'yes';
                $footer_data['admin_area'] = 'yes';
        
                return view('header', $header_data)
                . view('Modules\\sms_whatapp\\Views\\list', $data)
                . view('footer', $footer_data);
            break;
            
            default:
                $header_data['page_title'] = lang('Sms.page_title');
                $header_data['body_class'] = 'nav-md';
                $header_data['admin_area'] = 'yes';
                $footer_data['admin_area'] = 'yes';
        
                return view('header', $header_data)
                . view('Modules\\sms_whatapp\\Views\\index')
                . view('footer', $footer_data);
            break;
        }

       
    }

    public function send_sms()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'sms_whatapp')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }


        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';

            // Retrieve the data sent via AJAX
            $exam_id         = $this->request->getPost('exam_id');
            $session_id      = $this->request->getPost('session_id');
            $academic_id     = $this->request->getPost('academic_id');
            $school_id       = $this->request->getPost('school_id');
            $student_id      = $this->request->getPost('student_id');
            $student_phone   = $this->request->getPost('student_phone');
            $parent_phone    = $this->request->getPost('parent_phone');
            $sms_student     = $this->request->getPost('sms_student');
            $sms_parent      = $this->request->getPost('sms_parent');

            if($student_phone){
                $send_sms_student = $this->sendWhatsAppMessage_using_Twilio($student_phone, $sms_student);
                if($send_sms_student['status'] == 'success'){
                    $success_message = $send_sms_student['message'].'/'.$send_sms_student['twilio_status'];
                    $success_message .= $send_sms_student['message_sid'];
                    $response['status'] = true;
                    $html .= message_generator('success', $success_message);
                }else{
                    // Error message
                    $error_message = $send_sms_student['message'];
                    $error_message .= $send_sms_student['error'];
                    $response['status'] = false;
                    $html .= message_generator('error', $error_message);
                }
                
            }

            
            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON

        }

        
    }

    function sendWhatsAppMessage_using_Twilio($to, $message){

        $module_model = new ModuleModel();
        $module_data  = $module_model->where('slug', 'sms_whatapp')->where('status', 1)->first();

        if ($module_data) {
            $module_param_data = json_decode($module_data->params);
            $twilio_sid = $module_param_data->twilio_sid;
            $twilio_auth_token  = $module_param_data->twilio_auth_token;
            $twilio_whatsapp_number  = $module_param_data->twilio_whatsapp_number;
        }else{
            $twilio_sid = '';
            $twilio_auth_token  = '';
            $twilio_whatsapp_number  = '';
        }

        $sid    = $twilio_sid;
        $token  = $twilio_auth_token;
        $from   = $twilio_whatsapp_number; 

        try {
            $client = new Client($sid, $token);
            $messageResponse = $client->messages->create(
                "whatsapp:$to",
                [
                    'from' => $from,
                    'body' => $message
                ]
            );
    
            return [
                'status'    => 'success',
                'message'   => 'WhatsApp message sent successfully!',
                'message_sid' => $messageResponse->sid,
                'to'        => $to,
                'sent_at'   => date('Y-m-d H:i:s'),
                'twilio_status' => $messageResponse->status // e.g., queued, sent, delivered
            ];
        } catch (Exception $e) {
            return [
                'status'    => 'error',
                'message'   => 'Failed to send WhatsApp message.',
                'error'     => $e->getMessage(),
                'to'        => $to,
                'sent_at'   => date('Y-m-d H:i:s')
            ];
        }
    }


}
