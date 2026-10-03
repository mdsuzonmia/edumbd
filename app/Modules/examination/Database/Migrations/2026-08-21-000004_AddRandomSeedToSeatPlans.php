<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRandomSeedToSeatPlans extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('random_seed', 'examination_seat_plans')) {
            $this->forge->addColumn('examination_seat_plans', [
                'random_seed' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'seat_number_format',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('random_seed', 'examination_seat_plans')) {
            $this->forge->dropColumn('examination_seat_plans', 'random_seed');
        }
    }
}
