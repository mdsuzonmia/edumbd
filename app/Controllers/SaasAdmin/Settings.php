<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use Exception;

class Settings extends BaseController
{
    protected $settings;

    public function __construct()
    {
        $this->settings = new SettingModel();
    }

    /**
     * Application settings page
     */
    public function index()
    {
        $data = [
            'title'    => 'Application Settings',
            'settings' => $this->settings->getByGroup('application'),
        ];

        $header_data['page_title'] = lang('Auth.page_title_saas_settings');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('saas_admin/settings/index', $data)
            . view('footer', $footer_data);
    }

    /**
     * Save settings
     */
    public function save()
    {
        $post = $this->request->getPost();
        $files = $this->request->getFiles();

        // Handle file uploads
        foreach ($files['settings'] as $key => $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $new_name = $file->getRandomName();
                $file->move(ROOTPATH . 'public/uploads/settings', $new_name);
                // Update the post data to save the new file name
                $this->settings->saveSetting(
                    'application',
                    $key,
                    $new_name,
                    'file'
                );
            } 
        }
        
        // Save other settings
        foreach ($post['settings'] as $key => $value) {
            $this->settings->saveSetting(
                'application',
                $key,
                is_array($value) ? json_encode($value) : $value
            );
        }


        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Test email configuration
     */
    public function testEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $recipientEmail = $this->request->getPost('email');

        if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid email address']);
        }

        try {
            // Load email helper
            helper('email');

            // Use email helper pattern
            $emailService = \Config\Services::email();
            $emailService->initialize(set_email_config());

            // Set email details
            $fromEmail = setting('application', 'smtp_username');
            $fromName = setting('application', 'app_name') ?? 'School Management System';
            
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setTo($recipientEmail);
            $emailService->setSubject('Test Email - SMTP Configuration');
            $emailService->setMessage('<h2>Test Email</h2><p>This is a test email to verify your SMTP configuration is working correctly.</p><p><strong>From:</strong> ' . $fromName . '</p><p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>');
            $emailService->setMailType('html');

            if ($emailService->send()) {
                return $this->response->setJSON(['success' => true, 'message' => 'Test email sent successfully! Please check your inbox.']);
            } else {
                $error = $emailService->printDebugger(['headers']);
                log_message('error', 'Test email failed: ' . $error);
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to send test email. Please check your SMTP configuration.']);
            }

        } catch (Exception $e) {
            log_message('error', 'Test email exception: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
