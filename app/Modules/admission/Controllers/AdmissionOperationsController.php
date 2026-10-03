<?php

namespace App\Modules\admission\Controllers;

use App\Modules\admission\Models\AdmissionDocumentModel;
use App\Modules\admission\Models\AdmissionFormFieldModel;
use App\Modules\admission\Models\AdmissionPaymentModel;
use App\Modules\admission\Models\AdmissionStatusHistoryModel;
use App\Modules\admission\Models\AdmissionTestAdmitCardModel;
use App\Modules\admission\Models\AdmissionTestModel;
use App\Modules\admission\Models\AdmissionTestRoomModel;
use App\Modules\admission\Models\AdmissionTestSeatPlanModel;
use App\Modules\admission\Services\AdmissionPaymentGatewayService;
use App\Modules\admission\Services\AdmissionSeatPlanService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class AdmissionOperationsController extends BaseController
{
    public function formBuilder()
    {
        $ids=$this->schoolIds();$circularItems=$ids?$this->circulars->whereIn('school_id',$ids)->orderBy('id','DESC')->findAll():[];$circularId=(int)$this->request->getGet('circular_id');
        if(!$circularId&&$circularItems)$circularId=(int)$circularItems[0]->id;$circular=$circularId?$this->ownedById($this->circulars,$circularId):null;
        $fields=$circular?(new AdmissionFormFieldModel())->where('circular_id',$circular->id)->orderBy('sort_order','ASC')->findAll():[];
        return $this->render('form_builder/index',compact('circularItems','circular','fields'),'Application Form Builder');
    }

    public function saveField()
    {
        if(!$this->validate(['circular_id'=>'required|is_natural_no_zero','label'=>'required|max_length[150]','field_type'=>'required|in_list[text,textarea,number,date,dropdown,radio,checkbox,file]','is_required'=>'required|in_list[0,1]']))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));
        $p=$this->request->getPost();$circular=$this->ownedById($this->circulars,(int)$p['circular_id']);if(!$circular)return redirect()->back()->with('error','Circular not found.');
        $model=new AdmissionFormFieldModel();$id=(int)($p['field_id']??0);$field=$id?$model->where('id',$id)->where('circular_id',$circular->id)->first():null;
        $key=trim((string)($p['field_key']??''));if($key==='')$key=url_title($p['label'],'_',true);if(!preg_match('/^[a-z][a-z0-9_]{1,79}$/',$key))return redirect()->back()->withInput()->with('error','Field key must start with a letter and contain only lowercase letters, numbers, and underscores.');
        $duplicate=$model->where('circular_id',$circular->id)->where('field_key',$key);if($field)$duplicate->where('id !=',$field->id);if($duplicate->countAllResults())return redirect()->back()->withInput()->with('error','That field key already exists.');
        $options=array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)($p['options']??'')))));
        $data=['school_id'=>$circular->school_id,'circular_id'=>$circular->id,'field_key'=>$key,'label'=>trim($p['label']),'field_type'=>$p['field_type'],'options_json'=>$options?json_encode($options,JSON_UNESCAPED_UNICODE):null,'validation_rules'=>trim($p['validation_rules']??'')?:null,'placeholder'=>trim($p['placeholder']??'')?:null,'is_required'=>(int)$p['is_required'],'sort_order'=>(int)($p['sort_order']??0),'status'=>(int)($p['status']??1)];
        $field?$model->update($field->id,$data):$model->insert($data);return redirect()->to('school/admission/form-builder?circular_id='.$circular->id)->with('success','Form field saved.');
    }

    public function deleteField(int $id)
    {
        $model=new AdmissionFormFieldModel();$field=$model->find($id);if(!$field||!$this->ownsSchool((int)$field->school_id))return redirect()->back()->with('error','Field not found.');
        if($this->db->table('admission_application_values')->where('field_id',$id)->countAllResults())return redirect()->back()->with('error','This field already has application data and cannot be deleted. Disable it instead.');
        $model->delete($id);return redirect()->back()->with('success','Form field deleted.');
    }

    public function payments()
    {
        $ids=$this->schoolIds();$status=(string)$this->request->getGet('status');$builder=(new AdmissionPaymentModel())->select('admission_payments.*,admission_applications.application_no,admission_applications.student_name,schools.name school_name')->join('admission_applications','admission_applications.id=admission_payments.application_id')->join('schools','schools.id=admission_payments.school_id');
        $ids?$builder->whereIn('admission_payments.school_id',$ids):$builder->where('admission_payments.id',0);if(in_array($status,['pending','paid','rejected','failed','refunded'],true))$builder->where('admission_payments.status',$status);
        return $this->render('payments/index',['items'=>$builder->orderBy('admission_payments.id','DESC')->findAll(),'status'=>$status],'Admission Payments');
    }

    public function paymentSettings()
    {
        $schools=$this->userSchools();$schoolId=(int)$this->request->getGet('school_id');if(!$schoolId&&$schools)$schoolId=(int)$schools[0]->id;if(!$schoolId||!$this->ownsSchool($schoolId))return redirect()->to('school/admission')->with('error','School not found.');
        $settings=(new AdmissionPaymentGatewayService())->settings($schoolId);$credentialStatus=['stripe'=>!empty($settings->stripe_secret_key),'paypal'=>!empty($settings->paypal_client_id)&&!empty($settings->paypal_client_secret)];
        return $this->render('payment_settings/index',compact('schools','schoolId','settings','credentialStatus'),'Admission Payment Settings');
    }

    public function savePaymentSettings()
    {
        if(!$this->validate(['school_id'=>'required|is_natural_no_zero','currency'=>'required|regex_match[/^[A-Za-z]{3}$/]','manual_enabled'=>'required|in_list[0,1]','stripe_enabled'=>'required|in_list[0,1]','paypal_enabled'=>'required|in_list[0,1]','paypal_sandbox'=>'required|in_list[0,1]']))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));
        $schoolId=(int)$this->request->getPost('school_id');if(!$this->ownsSchool($schoolId))return redirect()->back()->with('error','Access denied.');$service=new AdmissionPaymentGatewayService();$existing=$service->settings($schoolId);$p=$this->request->getPost();
        if((int)$p['stripe_enabled']===1&&(trim((string)($p['stripe_secret_key']??''))===''&&empty($existing->stripe_secret_key)))return redirect()->back()->withInput()->with('error','Enter the Stripe secret key before enabling Stripe.');
        if((int)$p['paypal_enabled']===1&&((trim((string)($p['paypal_client_id']??''))===''&&empty($existing->paypal_client_id))||(trim((string)($p['paypal_client_secret']??''))===''&&empty($existing->paypal_client_secret))))return redirect()->back()->withInput()->with('error','Enter the PayPal client ID and secret before enabling PayPal.');
        try{$service->saveSettings($schoolId,['currency'=>strtoupper($p['currency']),'manual_enabled'=>(int)$p['manual_enabled'],'manual_instructions'=>trim((string)($p['manual_instructions']??''))?:null,'stripe_enabled'=>(int)$p['stripe_enabled'],'stripe_publishable_key'=>trim((string)($p['stripe_publishable_key']??''))?:($existing->stripe_publishable_key??null),'stripe_secret_key'=>$p['stripe_secret_key']??'','paypal_enabled'=>(int)$p['paypal_enabled'],'paypal_client_id'=>$p['paypal_client_id']??'','paypal_client_secret'=>$p['paypal_client_secret']??'','paypal_sandbox'=>(int)$p['paypal_sandbox']],$this->userId());}catch(Throwable $e){log_message('error','Admission payment settings error: '.$e->getMessage());return redirect()->back()->withInput()->with('error','Payment settings could not be securely saved.');}
        return redirect()->to('school/admission/payment-settings?school_id='.$schoolId)->with('success','Admission payment methods updated.');
    }

    public function reviewPayment(string $token)
    {
        $payment=(new AdmissionPaymentModel())->where('token',$token)->first();$decision=(string)$this->request->getPost('decision');if(!$payment||!$this->ownsSchool((int)$payment->school_id)||($payment->gateway?:'manual')!=='manual'||$payment->status!=='pending'||!in_array($decision,['paid','rejected'],true))return redirect()->back()->with('error','Invalid or already reviewed manual payment.');
        $application=$this->applications->find($payment->application_id);if(!$application)return redirect()->back()->with('error','Application not found.');$reason=trim((string)$this->request->getPost('reason'));if($decision==='rejected'&&$reason==='')return redirect()->back()->with('error','A rejection reason is required.');
        $this->db->transStart();(new AdmissionPaymentModel())->update($payment->id,['status'=>$decision,'paid_at'=>$decision==='paid'?date('Y-m-d H:i:s'):null,'reviewed_by'=>$this->userId(),'reviewed_at'=>date('Y-m-d H:i:s'),'rejection_reason'=>$decision==='rejected'?$reason:null]);
        $updates=['updated_by'=>$this->userId()];if($payment->payment_type==='application_fee'){$updates['application_fee_status']=$decision;$updates['payment_status']=$decision;if($decision==='paid'&&$application->application_status==='payment_pending')$updates['application_status']='submitted';}else{$updates['admission_fee_status']=$decision;if($decision==='paid'&&$application->application_status==='selected')$updates['application_status']='admission_pending';}$this->applications->update($application->id,$updates);
        if(isset($updates['application_status']))(new AdmissionStatusHistoryModel())->insert(['school_id'=>$application->school_id,'application_id'=>$application->id,'from_status'=>$application->application_status,'to_status'=>$updates['application_status'],'note'=>ucwords(str_replace('_',' ',$payment->payment_type)).' verified','created_by'=>$this->userId()]);$this->db->transComplete();return redirect()->back()->with('success','Payment review saved.');
    }

    public function paymentProof(string $token)
    {
        $payment=(new AdmissionPaymentModel())->where('token',$token)->first();
        if(!$payment||!$this->ownsSchool((int)$payment->school_id)||!$payment->proof_file)return $this->response->setStatusCode(404)->setBody('Payment proof not found.');
        return $this->privateFile($payment->proof_file,'payment-proof-'.basename($payment->proof_file));
    }

    public function documents()
    {
        $ids=$this->schoolIds();$status=(string)$this->request->getGet('status');$builder=(new AdmissionDocumentModel())->select('admission_documents.*,admission_applications.application_no,admission_applications.student_name,schools.name school_name')->join('admission_applications','admission_applications.id=admission_documents.application_id')->join('schools','schools.id=admission_documents.school_id');$ids?$builder->whereIn('admission_documents.school_id',$ids):$builder->where('admission_documents.id',0);if(in_array($status,['pending','verified','rejected'],true))$builder->where('verification_status',$status);return $this->render('documents/index',['items'=>$builder->orderBy('admission_documents.id','DESC')->findAll(),'status'=>$status],'Document Verification');
    }

    public function reviewDocument(int $id)
    {
        $model=new AdmissionDocumentModel();$document=$model->find($id);$decision=(string)$this->request->getPost('decision');if(!$document||!$this->ownsSchool((int)$document->school_id)||!in_array($decision,['verified','rejected'],true))return redirect()->back()->with('error','Invalid document review.');$note=trim((string)$this->request->getPost('note'));if($decision==='rejected'&&$note==='')return redirect()->back()->with('error','Explain why the document was rejected.');$model->update($id,['verification_status'=>$decision,'verification_note'=>$note?:null,'verified_by'=>$this->userId(),'verified_at'=>date('Y-m-d H:i:s')]);return redirect()->back()->with('success','Document verification saved.');
    }

    public function documentFile(int $id)
    {
        $document=(new AdmissionDocumentModel())->find($id);
        if(!$document||!$this->ownsSchool((int)$document->school_id))return $this->response->setStatusCode(404)->setBody('Document not found.');
        return $this->privateFile($document->stored_name,$document->original_name);
    }

    public function tests()
    {
        $ids=$this->schoolIds();$items=$ids?(new AdmissionTestModel())->select('admission_tests.*,admission_circulars.title circular_title,schools.name school_name')->join('admission_circulars','admission_circulars.id=admission_tests.circular_id')->join('schools','schools.id=admission_tests.school_id')->whereIn('admission_tests.school_id',$ids)->orderBy('admission_tests.id','DESC')->findAll():[];return $this->render('tests/index',compact('items'),'Admission Tests');
    }

    public function testForm(?string $token=null)
    {
        $model=new AdmissionTestModel();$item=$token?$this->findOwned($model,$token):null;if($token&&!$item)return redirect()->to('school/admission/tests')->with('error','Test not found.');$ids=$this->schoolIds();$circularItems=$ids?$this->circulars->whereIn('school_id',$ids)->orderBy('title','ASC')->findAll():[];return $this->render('tests/form',compact('item','circularItems'),'Admission Test');
    }

    public function saveTest()
    {
        if(!$this->validate(['circular_id'=>'required|is_natural_no_zero','title'=>'required|max_length[180]','test_date'=>'required|valid_date[Y-m-d]','start_time'=>'required','total_marks'=>'required|decimal','pass_marks'=>'required|decimal','status'=>'required|in_list[draft,published,completed,cancelled]']))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));$p=$this->request->getPost();$circular=$this->ownedById($this->circulars,(int)$p['circular_id']);if(!$circular)return redirect()->back()->withInput()->with('error','Circular not found.');$model=new AdmissionTestModel();$item=!empty($p['token'])?$this->findOwned($model,$p['token']):null;$statuses=array_values(array_intersect((array)($p['candidate_statuses']??[]),['submitted','under_review','eligible','selected','admission_pending']));if(!$statuses)$statuses=['eligible'];$data=['school_id'=>$circular->school_id,'circular_id'=>$circular->id,'title'=>trim($p['title']),'test_date'=>$p['test_date'],'start_time'=>$p['start_time'],'end_time'=>$p['end_time']?:null,'reporting_time'=>$p['reporting_time']?:null,'total_marks'=>(float)$p['total_marks'],'pass_marks'=>(float)$p['pass_marks'],'instructions'=>trim($p['instructions']??'')?:null,'candidate_statuses'=>implode(',',$statuses),'status'=>$p['status'],'updated_by'=>$this->userId()];if($item)$model->update($item->id,$data);else{$data['token']=bin2hex(random_bytes(24));$data['created_by']=$this->userId();$model->insert($data);}return redirect()->to('school/admission/tests')->with('success','Admission test saved.');
    }

    public function rooms(){ $ids=$this->schoolIds();$items=$ids?(new AdmissionTestRoomModel())->select('admission_test_rooms.*,schools.name school_name')->join('schools','schools.id=admission_test_rooms.school_id')->whereIn('admission_test_rooms.school_id',$ids)->orderBy('room_no','ASC')->findAll():[];return $this->render('rooms/index',compact('items'),'Admission Test Rooms'); }
    public function roomForm(?string $token=null){$model=new AdmissionTestRoomModel();$item=$token?$this->findOwned($model,$token):null;if($token&&!$item)return redirect()->to('school/admission/test-rooms')->with('error','Room not found.');$schools=$this->userSchools();return $this->render('rooms/form',compact('item','schools'),'Admission Test Room');}
    public function saveRoom(){if(!$this->validate(['school_id'=>'required|is_natural_no_zero','room_name'=>'required|max_length[150]','room_no'=>'required|max_length[50]','capacity'=>'required|is_natural_no_zero','rows_count'=>'required|is_natural_no_zero','columns_count'=>'required|is_natural_no_zero']))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));$p=$this->request->getPost();$schoolId=(int)$p['school_id'];if(!$this->ownsSchool($schoolId))return redirect()->back()->with('error','Access denied.');if((int)$p['capacity']>(int)$p['rows_count']*(int)$p['columns_count'])return redirect()->back()->withInput()->with('error','Capacity cannot exceed rows × columns.');$model=new AdmissionTestRoomModel();$item=!empty($p['token'])?$this->findOwned($model,$p['token']):null;$duplicate=$model->where('school_id',$schoolId)->where('room_no',trim($p['room_no']));if($item)$duplicate->where('id !=',$item->id);if($duplicate->countAllResults())return redirect()->back()->withInput()->with('error','Room number already exists.');$data=['school_id'=>$schoolId,'room_name'=>trim($p['room_name']),'room_no'=>trim($p['room_no']),'building'=>trim($p['building']??'')?:null,'floor'=>trim($p['floor']??'')?:null,'capacity'=>(int)$p['capacity'],'rows_count'=>(int)$p['rows_count'],'columns_count'=>(int)$p['columns_count'],'status'=>(int)($p['status']??1),'updated_by'=>$this->userId()];if($item)$model->update($item->id,$data);else{$data['token']=bin2hex(random_bytes(24));$data['created_by']=$this->userId();$model->insert($data);}return redirect()->to('school/admission/test-rooms')->with('success','Test room saved.');}

    public function seatPlans(){ $ids=$this->schoolIds();$items=$ids?(new AdmissionTestSeatPlanModel())->select('admission_test_seat_plans.*,admission_tests.title test_title,schools.name school_name')->join('admission_tests','admission_tests.id=admission_test_seat_plans.test_id')->join('schools','schools.id=admission_test_seat_plans.school_id')->whereIn('admission_test_seat_plans.school_id',$ids)->orderBy('admission_test_seat_plans.id','DESC')->findAll():[];return $this->render('seat_plans/index',compact('items'),'Admission Test Seat Plans');}
    public function seatPlanForm(){ $ids=$this->schoolIds();$tests=$ids?(new AdmissionTestModel())->whereIn('school_id',$ids)->whereIn('status',['draft','published'])->findAll():[];$rooms=$ids?(new AdmissionTestRoomModel())->whereIn('school_id',$ids)->where('status',1)->findAll():[];return $this->render('seat_plans/form',compact('tests','rooms'),'Generate Seat Plan');}
    public function generateSeatPlan(){if(!$this->validate(['test_id'=>'required|is_natural_no_zero','title'=>'required|max_length[180]','rooms'=>'required','allocation_method'=>'required|in_list[sequential,random]','seat_number_format'=>'required|in_list[numeric,alpha_numeric]']))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));$test=$this->ownedById(new AdmissionTestModel(),(int)$this->request->getPost('test_id'));if(!$test)return redirect()->back()->with('error','Test not found.');try{$plan=(new AdmissionSeatPlanService())->generate($test->id,$test->school_id,(array)$this->request->getPost('rooms'),trim($this->request->getPost('title')),$this->request->getPost('allocation_method'),$this->request->getPost('seat_number_format'),$this->userId());return redirect()->to('school/admission/seat-plans/'.$plan->token)->with('success','Seat plan generated.');}catch(Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}

    public function showSeatPlan(string $token){$plan=$this->findOwned(new AdmissionTestSeatPlanModel(),$token);if(!$plan)return redirect()->to('school/admission/seat-plans')->with('error','Seat plan not found.');$data=$this->seatPlanData($plan);return $this->render('seat_plans/show',$data,'Admission Seat Plan');}
    public function lockSeatPlan(string $token){$model=new AdmissionTestSeatPlanModel();$plan=$this->findOwned($model,$token);if(!$plan||$plan->is_locked)return redirect()->back()->with('error','Seat plan not found or already locked.');$model->update($plan->id,['is_locked'=>1,'status'=>'locked','locked_at'=>date('Y-m-d H:i:s'),'updated_by'=>$this->userId()]);return redirect()->back()->with('success','Seat plan locked.');}
    public function seatPlanPdf(string $token){$plan=$this->findOwned(new AdmissionTestSeatPlanModel(),$token);if(!$plan)return $this->response->setStatusCode(404)->setBody('Seat plan not found.');$data=$this->seatPlanData($plan)+['is_pdf'=>true];$options=new Options();$options->set('isRemoteEnabled',true);$pdf=new Dompdf($options);$pdf->loadHtml(view('App\Modules\admission\Views\seat_plans\report',$data));$pdf->setPaper('A4');$pdf->render();return $this->response->setHeader('Content-Type','application/pdf')->setHeader('Content-Disposition','attachment; filename="admission-seat-plan.pdf"')->setBody($pdf->output());}

    public function admitCards(){ $ids=$this->schoolIds();$items=$ids?(new AdmissionTestAdmitCardModel())->select('admission_test_admit_cards.*,admission_applications.application_no,admission_applications.student_name,admission_tests.title test_title')->join('admission_applications','admission_applications.id=admission_test_admit_cards.application_id')->join('admission_tests','admission_tests.id=admission_test_admit_cards.test_id')->whereIn('admission_test_admit_cards.school_id',$ids)->orderBy('admission_test_admit_cards.id','DESC')->findAll():[];$plans=$ids?(new AdmissionTestSeatPlanModel())->whereIn('school_id',$ids)->whereIn('status',['generated','locked'])->findAll():[];return $this->render('admit_cards/index',compact('items','plans'),'Admission Test Admit Cards');}
    public function generateAdmitCards(string $token){$plan=$this->findOwned(new AdmissionTestSeatPlanModel(),$token);if(!$plan)return redirect()->back()->with('error','Seat plan not found.');try{$count=(new AdmissionSeatPlanService())->generateAdmitCards($plan,$this->userId());return redirect()->back()->with('success',$count.' new admit cards generated. Existing cards were refreshed.');}catch(Throwable $e){return redirect()->back()->with('error',$e->getMessage());}}

    private function ownedById($model,int $id): ?object {$ids=$this->schoolIds();return $ids?$model->where('id',$id)->whereIn('school_id',$ids)->first():null;}
    private function privateFile(string $relativePath,string $downloadName)
    {
        $relativePath=str_replace(['../','..\\'], '', $relativePath);
        $root=realpath(WRITEPATH.'private'.DIRECTORY_SEPARATOR.'admission');
        $path=realpath(WRITEPATH.'private'.DIRECTORY_SEPARATOR.'admission'.DIRECTORY_SEPARATOR.$relativePath);
        if($root===false||$path===false||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path))return $this->response->setStatusCode(404)->setBody('File not found.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path)?:'application/octet-stream';
        $safeName=str_replace(['"',"\r","\n"],'',basename($downloadName));
        return $this->response->setHeader('Content-Type',$mime)->setHeader('Content-Disposition','inline; filename="'.$safeName.'"')->setBody((string)file_get_contents($path));
    }
    private function seatPlanData(object $plan): array {$test=(new AdmissionTestModel())->find($plan->test_id);$school=$this->schools->find($plan->school_id);$rooms=$this->db->table('admission_test_seat_plan_rooms')->where('seat_plan_id',$plan->id)->orderBy('sort_order','ASC')->get()->getResult();$allocations=$this->db->table('admission_test_seat_allocations a')->select('a.*,admission_applications.student_name,admission_applications.dob')->join('admission_applications','admission_applications.id=a.application_id')->where('a.seat_plan_id',$plan->id)->orderBy('a.room_id')->orderBy('a.row_no')->orderBy('a.column_no')->get()->getResult();$byRoom=[];foreach($allocations as $a)$byRoom[$a->room_id][]=$a;return compact('plan','test','school','rooms','allocations','byRoom');}
}
