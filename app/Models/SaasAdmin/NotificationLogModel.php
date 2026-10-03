<?php

namespace App\Models\SaasAdmin;

use CodeIgniter\Model;

class NotificationLogModel extends Model
{
    protected $table = 'notification_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'recipient_type',
        'subject',
        'message',
        'total_recipients',
        'sent_count',
        'failed_count',
        'status',
        'recipients',
        'failed_recipients',
        'sent_by',
        'sent_at',
        'created_at',
        'updated_at',
    ];
}
