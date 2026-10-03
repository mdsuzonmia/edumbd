<?php

namespace App\Modules\bulkstudent\Models;

use CodeIgniter\Model;

class BulkstudentModel extends Model
{
    protected $table = 'blogs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'content', 'author_id', 'status'];
}
