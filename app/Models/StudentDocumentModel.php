<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentDocumentModel extends Model
{
    protected $table      = 'student_documents';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'student_id',
        'document_type',
        'document_title',
        'file_name',
        'file_size',
        'remarks',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}