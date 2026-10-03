<?php

namespace App\Modules\admission\Models;

use CodeIgniter\Model;

abstract class BaseAdmissionModel extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $protectFields = true;
}
