<?php

use App\Modules\admission\Services\AdmissionSeatPlanService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdmissionSeatPlanServiceTest extends CIUnitTestCase
{
    public function testSequentialAllocationUsesApplicationOrderAndRoomLayout(): void
    {
        $apps=[['id'=>2,'application_no'=>'ADM-002'],['id'=>1,'application_no'=>'ADM-001'],['id'=>3,'application_no'=>'ADM-003']];
        $rooms=[['id'=>10,'capacity'=>2,'rows_count'=>1,'columns_count'=>2],['id'=>11,'capacity'=>2,'rows_count'=>2,'columns_count'=>1]];
        $rows=AdmissionSeatPlanService::allocate($apps,$rooms,'sequential','alpha_numeric');
        $this->assertSame([1,2,3],array_column($rows,'application_id'));
        $this->assertSame([10,10,11],array_column($rows,'room_id'));
        $this->assertSame(['A01','A02','A01'],array_column($rows,'seat_no'));
    }

    public function testRandomAllocationIsReproducibleWithStoredSeed(): void
    {
        $apps=array_map(static fn($i)=>['id'=>$i,'application_no'=>'ADM-'.$i],range(1,20));$rooms=[['id'=>1,'capacity'=>20,'rows_count'=>4,'columns_count'=>5]];
        $first=AdmissionSeatPlanService::allocate($apps,$rooms,'random','numeric','0123456789abcdef0123456789abcdef');
        $second=AdmissionSeatPlanService::allocate($apps,$rooms,'random','numeric','0123456789abcdef0123456789abcdef');
        $this->assertSame($first,$second);
    }

    public function testAllocationRejectsInsufficientCapacity(): void
    {
        $this->expectException(\DomainException::class);
        AdmissionSeatPlanService::allocate([['id'=>1,'application_no'=>'A'],['id'=>2,'application_no'=>'B']],[['id'=>1,'capacity'=>1,'rows_count'=>1,'columns_count'=>1]]);
    }
}
