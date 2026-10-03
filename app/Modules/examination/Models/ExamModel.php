<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamModel extends Model
{
    protected $table      = 'examination_exams';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'title',
        'year_id',
        'exam_date',
        'description',
        'duration',
        'start_time',
        'end_time',

        'school_id',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'school_owner_uid',
        'class_id',
        'exam_short_name',
        'exam_type',
        'is_aggregate_result',
        'weight_percentage',
        'publish_result',
        'exam_order',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}