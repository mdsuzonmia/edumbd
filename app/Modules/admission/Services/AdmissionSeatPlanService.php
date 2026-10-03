<?php

namespace App\Modules\admission\Services;

use App\Modules\admission\Models\AdmissionApplicationModel;
use App\Modules\admission\Models\AdmissionTestAdmitCardModel;
use App\Modules\admission\Models\AdmissionTestModel;
use App\Modules\admission\Models\AdmissionTestRoomModel;
use App\Modules\admission\Models\AdmissionTestSeatPlanModel;
use DomainException;
use Throwable;

final class AdmissionSeatPlanService
{
    public function generate(int $testId,int $schoolId,array $roomIds,string $title,string $method,string $format,int $userId): object
    {
        if(!in_array($method,['sequential','random'],true)||!in_array($format,['numeric','alpha_numeric'],true)) throw new DomainException('Invalid allocation option.');
        $test=(new AdmissionTestModel())->where('id',$testId)->where('school_id',$schoolId)->first();
        if(!$test) throw new DomainException('Admission test not found.');
        if($roomIds===[]) throw new DomainException('Select at least one test room.');
        $statuses=array_values(array_filter(array_map('trim',explode(',',$test->candidate_statuses))));
        $applications=(new AdmissionApplicationModel())->where('school_id',$schoolId)->where('circular_id',$test->circular_id)->whereIn('application_status',$statuses?:['eligible'])->orderBy('application_no','ASC')->findAll();
        if($applications===[]) throw new DomainException('No eligible test candidates were found.');
        $rooms=(new AdmissionTestRoomModel())->where('school_id',$schoolId)->whereIn('id',array_map('intval',$roomIds))->where('status',1)->orderBy('room_no','ASC')->findAll();
        if(count($rooms)!==count(array_unique(array_map('intval',$roomIds)))) throw new DomainException('One or more rooms are invalid.');
        $seed=$method==='random'?bin2hex(random_bytes(24)):null;
        $allocations=self::allocate($applications,$rooms,$method,$format,$seed); $db=db_connect();$db->transBegin();
        try{
            $plans=new AdmissionTestSeatPlanModel();$planId=$plans->insert(['token'=>bin2hex(random_bytes(24)),'school_id'=>$schoolId,'test_id'=>$testId,'title'=>$title,'allocation_method'=>$method,'seat_number_format'=>$format,'random_seed'=>$seed,'candidate_count'=>count($applications),'status'=>'generated','is_locked'=>0,'created_by'=>$userId,'updated_by'=>$userId],true);
            foreach($rooms as $index=>$room)$db->table('admission_test_seat_plan_rooms')->insert(['school_id'=>$schoolId,'seat_plan_id'=>$planId,'room_id'=>$room->id,'room_name_snapshot'=>$room->room_name,'room_no_snapshot'=>$room->room_no,'capacity'=>$room->capacity,'rows_count'=>$room->rows_count,'columns_count'=>$room->columns_count,'sort_order'=>$index,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            foreach($allocations as $allocation){$db->table('admission_test_seat_allocations')->insert(['school_id'=>$schoolId,'seat_plan_id'=>$planId,'test_id'=>$testId]+$allocation+['created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);}
            $db->transCommit();return $plans->find($planId);
        }catch(Throwable $e){$db->transRollback();throw $e;}
    }

    public function generateAdmitCards(object $plan,int $userId): int
    {
        if(empty($plan->is_locked))throw new DomainException('Lock the seat plan before generating admit cards.');
        $db=db_connect();$allocations=$db->table('admission_test_seat_allocations')->where('seat_plan_id',$plan->id)->orderBy('id','ASC')->get()->getResult();if(!$allocations)throw new DomainException('Generate a seat plan before admit cards.');
        $cards=new AdmissionTestAdmitCardModel();$db->transBegin();$created=0;
        try{foreach($allocations as $allocation){$existing=$cards->where('test_id',$plan->test_id)->where('application_id',$allocation->application_id)->first();$data=['seat_allocation_id'=>$allocation->id,'status'=>1,'generated_at'=>date('Y-m-d H:i:s'),'generated_by'=>$userId];if($existing){$cards->update($existing->id,$data);continue;}$data+=['token'=>bin2hex(random_bytes(24)),'school_id'=>$plan->school_id,'test_id'=>$plan->test_id,'application_id'=>$allocation->application_id,'card_no'=>sprintf('ATC-%s-%04d-%06d',date('Y'),$plan->test_id,$allocation->application_id)];$cards->insert($data);$created++;}$db->transCommit();return $created;}catch(Throwable $e){$db->transRollback();throw $e;}
    }

    public static function allocate(array $applications,array $rooms,string $method='sequential',string $format='numeric',?string $seed=null): array
    {
        if(!in_array($method,['sequential','random'],true)||!in_array($format,['numeric','alpha_numeric'],true))throw new DomainException('Invalid allocation option.');
        $applications=array_map(static fn($a)=>(object)(array)$a,$applications);$rooms=array_map(static fn($r)=>(object)(array)$r,$rooms);
        usort($applications,static fn($a,$b)=>strnatcasecmp((string)$a->application_no,(string)$b->application_no));
        if($method==='random'){if(!$seed)throw new DomainException('A random seed is required.');usort($applications,static fn($a,$b)=>hash_hmac('sha256',(string)$a->id,$seed)<=>hash_hmac('sha256',(string)$b->id,$seed));}
        $positions=[];foreach($rooms as $room){$rows=(int)$room->rows_count;$columns=(int)$room->columns_count;$capacity=(int)$room->capacity;if($rows<1||$columns<1||$capacity<1||$capacity>$rows*$columns)throw new DomainException('Invalid room capacity or layout.');$used=0;for($row=1;$row<=$rows;$row++){for($column=1;$column<=$columns;$column++){if($used>=$capacity)break 2;$used++;$seat=$format==='alpha_numeric'?'A'.str_pad((string)$used,max(2,strlen((string)$capacity)),'0',STR_PAD_LEFT):(string)$used;$positions[]=['room_id'=>(int)$room->id,'seat_no'=>$seat,'row_no'=>$row,'column_no'=>$column];}}}
        if(count($applications)>count($positions))throw new DomainException(sprintf('Insufficient capacity: %d candidates for %d seats.',count($applications),count($positions)));
        $result=[];foreach($applications as $index=>$application)$result[]=['room_id'=>$positions[$index]['room_id'],'application_id'=>(int)$application->id,'application_no_snapshot'=>(string)$application->application_no,'seat_no'=>$positions[$index]['seat_no'],'row_no'=>$positions[$index]['row_no'],'column_no'=>$positions[$index]['column_no']];return $result;
    }
}
