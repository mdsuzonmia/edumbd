<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class MarkDistributionModel extends Model
{
    protected $table      = 'examination_mark_distributions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'name',
        'code',
        'description',
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