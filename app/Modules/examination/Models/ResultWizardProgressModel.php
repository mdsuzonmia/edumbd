<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ResultWizardProgressModel extends Model
{
    protected $table = 'result_wizard_progress';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['token', 'school_id', 'user_id', 'current_step', 'class_id', 'exam_id', 'year_id', 'section_id', 'category_id', 'subject_ids', 'student_preview', 'service_mode', 'completed_at'];
}
