<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ResultTemplateModel extends Model
{
    protected $table      = 'examination_result_templates';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'template_name',
        'template_type',
        'template_content',
        'template_style',
        'is_default',
        'support_multiple_exams',
        'support_aggregated_result',
        'template_bg',
        'class_teacher_signature',
        'principal_signature',
        'orientation'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get templates for a user, optionally filtered by school
     */
    public function getTemplatesByUser(int $user_id, int $school_id = 0): array
    {
        $builder = $this->builder()
            ->where('school_owner_uid', $user_id);

        if ($school_id > 0) {
            $builder->where('school_id', $school_id);
        }

        return $builder
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }
}