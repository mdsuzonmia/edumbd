<?php

namespace App\Modules\Backup\Models;

use CodeIgniter\Model;

class BackupModel extends Model
{
    protected $table = 'blogs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'content', 'author_id', 'status'];
}
