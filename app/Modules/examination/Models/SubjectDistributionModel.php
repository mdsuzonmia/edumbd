<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class SubjectDistributionModel extends Model
{
    protected $table      = 'examination_subject_distributions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'subject_id',
        'distribution_id',
        'full_mark',
        'pass_mark',
        'weight_percent',
        'sort_order',
        'school_id',
        'school_owner_uid',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}