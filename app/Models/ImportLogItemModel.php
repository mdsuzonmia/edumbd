<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportLogItemModel extends Model
{
    protected $table      = 'import_log_items';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'import_log_id',
        'row_number',
        'row_data',
        'status',
        'error_message',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';

    protected $validationRules = [
        'import_log_id' => 'required|is_natural_no_zero',
        'row_number'    => 'required|is_natural',
        'status'        => 'required|in_list[success,failed]',
    ];

    protected $validationMessages = [
        'import_log_id' => [
            'required' => 'Import log ID is required.',
        ],
        'row_number' => [
            'required' => 'Row number is required.',
        ],
        'status' => [
            'required' => 'Status is required.',
        ],
    ];
}