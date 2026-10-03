<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ResultPublishModel extends Model
{
    protected $table      = 'examination_results_publish';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [

        'school_id',
        'school_owner_uid',

        'exam_id',

        'session_id',

        'class_id',
        'section_id',

        'is_published',

        'published_by',
        'published_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}