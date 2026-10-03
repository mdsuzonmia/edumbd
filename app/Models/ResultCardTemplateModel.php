<?php

namespace App\Models;

use CodeIgniter\Model;

class ResultCardTemplateModel extends Model
{
    protected $table      = 'result_card_templates';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'template_name',
        'template_content',
        'is_default',
        'support_multiple_exams',
        'support_aggregated_result',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}