<?php

namespace App\Modules\backup\Controllers;

use App\Models\ModuleModel;
use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class BackupController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'backup')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }

        $header_data['page_title'] = lang('Backup.page_title');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('Modules\\backup\\Views\\index')
            . view('footer', $footer_data);
    }

    public function download()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'backup')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }

        $prefix = $this->db->getPrefix();
        $tableList = [
            'academic_sessions','academic_category','academic_shift','academic_department','academic_group','academic_house','academic_version','academic_section','academic_subject','academic_grade','academic_class','academic_skill',
            'students','parents','teachers','fields','fields_data','documents','document_data','users','parent_student_relation',
            'student_academics',
            'academic_exam', 'academic_grading','academic_grading_category',
            'academic_mark_distribution','academic_mark_distribution_values','academic_skill_values','academic_remarks','academic_results',
            'schools', 'settings'
        ];

        $jsonData = [];

        foreach ($tableList as $table) {
            $table = $prefix . $table;
            $query = $this->db->query("SELECT * FROM " . $this->db->escapeIdentifiers($table));
            $jsonData[$table] = $query->getResultArray();
        }

        $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
        
        $module_model = new ModuleModel();
        $module_data  = $module_model->where('slug', 'backup')->where('status', 1)->first();

        if ($module_data) {
            $module_param_data = json_decode($module_data->params);
            $output_file_name = $module_param_data->output_file_name;
            $date_format      = $module_param_data->date_format;
        }else{
            $output_file_name = 'backup';
            $date_format      = 'Y-m-d_H-i-s';
        }
        $filename = $output_file_name.'_' . date($date_format) . '.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($jsonString);
        
    }

    public function download_photo()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'backup')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }

        $path = WRITEPATH . 'uploads/';
        $filename = 'photo_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $path . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = glob($path . '*');
            foreach ($files as $file) {
                if (is_file($file) && !in_array(basename($file), ['Thumbs.db', '.DS_Store', 'desktop.ini'])) { // Exclude Thumbs.db
                    $zip->addFile($file, basename($file));
                }
            }

            $zip->close();
        } else {
            return redirect()->back()->with('error', 'Could not create ZIP file.');
        }

        // Download the ZIP file
        $response = $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', filesize($zipPath))
            ->setBody(file_get_contents($zipPath));

        // After sending the response, delete the ZIP file
        @unlink($zipPath);

        return $response;
    }


    public function save_json()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'backup')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }
        
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response         = array('status' => true);
            $html             = '';
            
            $file = $this->request->getFile('backup_json');

            if ($file->isValid() && !$file->hasMoved()) {
                // Get the file content
                $fileContent = file_get_contents($file->getTempName());

                // Decode the JSON data
                $jsonData = json_decode($fileContent, true);

                if ($jsonData === null) {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Backup.invalid_json_file'));
                }

                // Get the database prefix
                $prefix = $this->db->getPrefix();
                $tableList = [
                    'academic_sessions','academic_category','academic_shift','academic_department','academic_group','academic_house','academic_version','academic_section','academic_subject','academic_grade','academic_class','academic_skill',
                    'students','parents','teachers','fields','fields_data','documents','document_data','users','parent_student_relation',
                    'student_academics',
                    'academic_exam', 'academic_grading','academic_grading_category',
                    'academic_mark_distribution','academic_mark_distribution_values','academic_skill_values','academic_remarks','academic_results',
                    'schools', 'settings'
                ];

                // Iterate over the tables and restore the data
                foreach ($tableList as $table) {
                    $table = $prefix . $table;

                    // Delete existing records in the table
                    $this->db->table($table)->emptyTable();

                    if (isset($jsonData[$table])) {
                        // Insert the data from the JSON into the table
                        $this->db->table($table)->insertBatch($jsonData[$table]);
                    }
                }
                $response['status'] = true;
                $html .= message_generator('success', lang('Backup.backup_restored_successfully'));
            } else {
                $response['status'] = false;
                $html .= message_generator('error', lang('Backup.file_upload_error'));
            }

            $html .= message_generator('success', lang('Common.data_trash'));
    
            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }


    public function upload_photo()
    {
        // Check if the super admin is logged in and has permission
        if (!is_super_admin() || !(new ModuleModel())->where('slug', 'backup')->where('status', 1)->first()) {
            return redirect()->to('/no-access')->with('error', lang('System.sys_no_permission'));
        }

        if ($this->request->isAJAX()) {

            $response         = array('status' => true);
            $html             = '';

            $uploadPath = WRITEPATH . 'uploads/';
            $file = $this->request->getFile('backup_photo'); // Get uploaded file

            if (!$file->isValid() || $file->getClientExtension() !== 'zip') {
                return $this->response->setJSON(['status' => 'error', 'message' => lang('Backup.invalid_zip_file')]);
            }

            $zipPath = $file->getTempName(); // Get temporary file path

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($uploadPath); // Extract contents
                $zip->close();
                $response['status'] = true;
                $html .= message_generator('success', lang('Backup.photo_restored_successfully'));
            } else {
                $response['status'] = false;
                $html .= message_generator('error', lang('Backup.failed_to_extract_zip_file'));
            }

            $response['html']       = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

}
