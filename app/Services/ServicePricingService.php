<?php
namespace App\Services;
use App\Models\ServiceBillingSettingModel;
use App\Models\ServiceOrderModel;
use App\Models\ServicePricingRuleModel;
use InvalidArgumentException;
class ServicePricingService
{
    public const RESULT='RESULT', SEAT_PLAN='SEAT_PLAN', ADMIT_CARD='ADMIT_CARD';
    public const SELF_SERVICE='SELF_SERVICE', MANAGED_SERVICE='MANAGED_SERVICE';
    public function __construct(private ?ServicePricingRuleModel $rules=null,private ?ServiceBillingSettingModel $settings=null,private ?ServiceOrderModel $orders=null){$this->rules??=new ServicePricingRuleModel();$this->settings??=new ServiceBillingSettingModel();$this->orders??=new ServiceOrderModel();}
    public function quote(string $service,int $students,string $serviceMode=self::SELF_SERVICE):array
    {
        $service=strtoupper($service);$serviceMode=$service===self::RESULT?$this->normalizeMode($serviceMode):null;
        $query=$this->rules->where(['service'=>$service,'status'=>1]);
        if($service===self::RESULT)$query->groupStart()->where('service_mode',$serviceMode)->orWhere('service_mode',null)->groupEnd();
        $rules=$query->orderBy('service_mode','DESC')->orderBy('sort_order')->findAll();$rule=null;
        foreach($rules as $r)if(($r->min_students===null||$students>=(int)$r->min_students)&&($r->max_students===null||$students<=(int)$r->max_students)){$rule=$r;break;}
        // Counts below the first configured tier use that tier. This keeps
        // small schools billable without requiring a duplicate 1-N rule.
        if(!$rule&&$rules)$rule=$rules[0];
        if(!$rule)throw new InvalidArgumentException('এই শিক্ষার্থী সংখ্যার জন্য মূল্য এখনো নির্ধারণ করা হয়নি।');
        $rate=(float)$rule->amount;$minimum=0;$minimumEnabled=$service===self::RESULT&&(int)$this->settings->value('result_minimum_enabled',1)===1;if($minimumEnabled)$minimum=(float)$this->settings->value('result_minimum_charge',0);
        $subtotal=$this->calculateAmount($rule->pricing_type,$rate,$students,$minimumEnabled,$minimum);
        return ['service'=>$service,'service_mode'=>$serviceMode,'student_count'=>$students,'pricing_type'=>$rule->pricing_type,'rule_id'=>(int)$rule->id,'applied_rate'=>$rate,'subtotal'=>$subtotal,'minimum_charge'=>$minimum,'currency'=>'BDT','details'=>['range'=>[$rule->min_students,$rule->max_students],'rule_amount'=>$rate,'minimum_applied'=>$minimum>0&&$subtotal===$minimum]];
    }
    public function demoLimit():int{return (int)$this->settings->value('demo_student_limit',20);}
    public function calculateAmount(string $pricingType,float $rate,int $students,bool $minimumEnabled=false,float $minimum=0):float
    {
        $amount=match($pricingType){'PER_STUDENT_TIER','PER_STUDENT'=>$students*$rate,'FIXED_TIER','FIXED'=>$rate,'FREE'=>0,default=>throw new InvalidArgumentException('Pricing type invalid.')};
        return $minimumEnabled?max($amount,$minimum):$amount;
    }
    public function createOrUpdateOrder(int $schoolId,int $userId,string $service,int $students,?int $examId=null,?string $referenceType=null,?int $referenceId=null,string $serviceMode=self::SELF_SERVICE):object
    {
        $service=strtoupper($service);$serviceMode=$service===self::RESULT?$this->normalizeMode($serviceMode):null;$identity=['school_id'=>$schoolId,'service'=>$service,'exam_id'=>$examId];if($service===self::RESULT)$identity['service_mode']=$serviceMode;
        $final=$this->orders->where($identity)->whereIn('status',['paid','completed'])->orderBy('id','DESC')->first();if($final)return $final;
        $q=$this->quote($service,$students,$serviceMode??self::SELF_SERVICE);$existing=$this->orders->where($identity)->whereIn('status',['draft','payment_pending'])->orderBy('id','DESC')->first();
        $data=['school_id'=>$schoolId,'user_id'=>$userId,'service'=>$service,'service_mode'=>$serviceMode,'exam_id'=>$examId,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'student_count'=>$students,'pricing_type'=>$q['pricing_type'],'applied_rate'=>$q['applied_rate'],'subtotal'=>$q['subtotal'],'discount'=>0,'total'=>$q['subtotal'],'currency'=>'BDT','status'=>'draft','pricing_snapshot'=>json_encode($q,JSON_UNESCAPED_UNICODE)];
        if($existing){
            // Reopening a wizard must never discard payment evidence or move a
            // submitted payment back to draft. Preserve admin adjustments too.
            $discount=min((float)$existing->discount,(float)$q['subtotal']);
            $data['discount']=$discount;$data['total']=(float)$q['subtotal']-$discount;
            if($existing->status==='payment_pending')unset($data['status']);
            $this->orders->update($existing->id,$data);return $this->orders->find($existing->id);
        }
        $data['token']=bin2hex(random_bytes(24));$data['invoice_no']='SRV-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));$id=$this->orders->insert($data);return $this->orders->find($id);
    }
    public function isPaid(int $schoolId,string $service,?int $examId=null):bool{return (bool)$this->orders->where(['school_id'=>$schoolId,'service'=>$service,'exam_id'=>$examId])->whereIn('status',['paid','completed'])->first();}
    private function normalizeMode(string $mode):string{$mode=strtoupper($mode);if(!in_array($mode,[self::SELF_SERVICE,self::MANAGED_SERVICE],true))throw new InvalidArgumentException('Invalid result service mode.');return $mode;}
}
