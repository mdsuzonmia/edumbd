<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportLogModel extends Model
{
    protected $table      = 'import_logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'user_id',
        'module',
        'file_type',
        'file_name',
        'total_records',
        'success_records',
        'failed_records',
        'status',
        'error_log',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    protected $validationRules = [
        'school_id' => 'required|is_natural_no_zero',
        'user_id'   => 'required|is_natural_no_zero',
        'module'    => 'required|max_length[50]',
        'file_type' => 'required|max_length[10]',
    ];

    
}