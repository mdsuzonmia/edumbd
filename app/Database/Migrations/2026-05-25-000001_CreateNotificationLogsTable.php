<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'recipient_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'total_recipients' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'sent_count' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'failed_count' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'failed_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sent_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('sent_at');
        $this->forge->createTable('notification_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('notification_logs', true);
    }
}
