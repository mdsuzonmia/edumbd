<?php

namespace App\Modules\admission\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Modules\admission\Models\AdmissionPaymentModel;
use App\Modules\admission\Models\AdmissionStatusHistoryModel;
use App\Modules\admission\Services\AdmissionPaymentGatewayService;
use Throwable;

class PublicAdmissionController extends BaseController
{
    public function index(string $schoolSlug)
    {
        $school=$this->publicSchool($schoolSlug);
        if (!$school) return $this->response->setStatusCode(404)->setBody('School not found.');
        $now=date('Y-m-d H:i:s');
        $circulars=$this->circulars->select('admission_circulars.*,academic_classes.title class_name,academic_shift.title shift_name')
            ->join('academic_classes','academic_classes.id=admission_circulars.class_id','left')
            ->join('academic_shift','academic_shift.id=admission_circulars.shift_id','left')
            ->where('admission_circulars.school_id',$school->id)->where('admission_circulars.status','published')
            ->where('admission_circulars.application_deadline >=',$now)->orderBy('application_deadline','ASC')->findAll();
        return view('App\Modules\admission\Views\public\portal',compact('school','circulars'));
    }

    public function create(string $schoolSlug,string $circularToken)
    {
        [$school,$circular]=$this->publicCircular($schoolSlug,$circularToken);
        if (!$school||!$circular) return $this->response->setStatusCode(404)->setBody('Admission circular not found or applications are closed.');
        $fields=$this->db->table('admission_form_fields')->where('circular_id',$circular->id)->where('status',1)->orderBy('sort_order','ASC')->get()->getResult();
        return view('App\Modules\admission\Views\public\application_form',compact('school','circular','fields'));
    }

    public function store(string $schoolSlug,string $circularToken)
    {
        [$school,$circular]=$this->publicCircular($schoolSlug,$circularToken);
        if (!$school||!$circular) return $this->response->setStatusCode(404)->setBody('Applications are closed.');
        $rules=['student_name'=>'required|max_length[180]','dob'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Male,Female,Other,male,female,other]','guardian_mobile'=>'required|max_length[30]','father_email'=>'permit_empty|valid_email','mother_email'=>'permit_empty|valid_email','photo'=>'permit_empty|is_image[photo]|max_size[photo,2048]|mime_in[photo,image/jpeg,image/png,image/webp]'];
        $fields=$this->db->table('admission_form_fields')->where('circular_id',$circular->id)->where('status',1)->orderBy('sort_order','ASC')->get()->getResult();
        foreach ($fields as $field) {
            $name='custom_'.$field->id;
            if ($field->field_type==='file') $rules[$name]=($field->is_required?'uploaded['.$name.']|':'permit_empty|').'max_size['.$name.',5120]|ext_in['.$name.',pdf,jpg,jpeg,png,webp]';
            else {
                $fieldRules=trim((string)$field->validation_rules);
                if($field->is_required)$fieldRules='required'.($fieldRules!==''?'|'.$fieldRules:'');
                if($fieldRules!=='')$rules[$name]=$fieldRules;
            }
        }
        if (!$this->validate($rules)) return view('App\Modules\admission\Views\public\application_form',compact('school','circular','fields')+['validation'=>$this->validator]);
        $p=$this->request->getPost();
        if (!$this->ageAllowed($p['dob'],$circular)) return redirect()->back()->withInput()->with('error','The applicant does not meet this circular’s age requirement.');
        $photo=null; $upload=$this->request->getFile('photo');
        if ($upload&&$upload->isValid()&&!$upload->hasMoved()) {
            $directory=WRITEPATH.'uploads'.DIRECTORY_SEPARATOR.'admission'.DIRECTORY_SEPARATOR.$school->id;
            if (!is_dir($directory)) mkdir($directory,0755,true);
            $photo=$upload->getRandomName(); $upload->move($directory,$photo); $photo='admission/'.$school->id.'/'.$photo;
        }
        $session=$this->sessions->find($circular->admission_session_id);
        $status=(float)$circular->application_fee>0?'payment_pending':'submitted';
        $data=['school_id'=>$school->id,'admission_session_id'=>$circular->admission_session_id,'circular_id'=>$circular->id,'application_no'=>'PENDING-'.bin2hex(random_bytes(8)),'token'=>bin2hex(random_bytes(24)),'academic_year_id'=>$session->academic_year_id,'class_id'=>$circular->class_id,'shift_id'=>$circular->shift_id,'version_id'=>$circular->version_id,'department_id'=>$circular->department_id,'group_id'=>$circular->group_id,'student_name'=>trim($p['student_name']),'student_name_bn'=>trim($p['student_name_bn']??'')?:null,'dob'=>$p['dob'],'gender'=>ucfirst(strtolower($p['gender'])),'birth_registration_no'=>trim($p['birth_registration_no']??'')?:null,'nationality'=>trim($p['nationality']??'')?:null,'religion'=>trim($p['religion']??'')?:null,'blood_group'=>trim($p['blood_group']??'')?:null,'photo'=>$photo,'father_name'=>trim($p['father_name']??'')?:null,'father_occupation'=>trim($p['father_occupation']??'')?:null,'father_mobile'=>trim($p['father_mobile']??'')?:null,'father_email'=>trim($p['father_email']??'')?:null,'father_nid'=>trim($p['father_nid']??'')?:null,'mother_name'=>trim($p['mother_name']??'')?:null,'mother_occupation'=>trim($p['mother_occupation']??'')?:null,'mother_mobile'=>trim($p['mother_mobile']??'')?:null,'mother_email'=>trim($p['mother_email']??'')?:null,'mother_nid'=>trim($p['mother_nid']??'')?:null,'guardian_name'=>trim($p['guardian_name']??'')?:null,'guardian_mobile'=>trim($p['guardian_mobile']),'guardian_relation'=>trim($p['guardian_relation']??'')?:null,'present_address'=>trim($p['present_address']??'')?:null,'permanent_address'=>trim($p['permanent_address']??'')?:null,'previous_school'=>trim($p['previous_school']??'')?:null,'previous_class'=>trim($p['previous_class']??'')?:null,'previous_roll'=>trim($p['previous_roll']??'')?:null,'previous_result'=>trim($p['previous_result']??'')?:null,'board'=>trim($p['board']??'')?:null,'passing_year'=>trim($p['passing_year']??'')?:null,'application_status'=>$status,'payment_status'=>(float)$circular->application_fee>0?'unpaid':'not_required','application_fee_status'=>(float)$circular->application_fee>0?'unpaid':'not_required','admission_fee_status'=>(float)$circular->admission_fee>0?'unpaid':'not_required','submitted_at'=>date('Y-m-d H:i:s')];
        $this->db->transStart();
        $id=$this->applications->insert($data,true);
        $year=$this->applicationYear($session); $applicationNo=sprintf('ADM-%s-%06d',$year,$id);
        $this->applications->update($id,['application_no'=>$applicationNo]);
        foreach ($fields as $field) {
            if ($field->field_type==='file') {
                $file=$this->request->getFile('custom_'.$field->id);
                if ($file&&$file->isValid()&&!$file->hasMoved()) {
                    $directory=WRITEPATH.'private'.DIRECTORY_SEPARATOR.'admission'.DIRECTORY_SEPARATOR.$school->id.DIRECTORY_SEPARATOR.'documents';
                    if (!is_dir($directory)) mkdir($directory,0755,true);
                    $stored=$file->getRandomName(); $original=$file->getClientName(); $mime=$file->getClientMimeType(); $size=$file->getSize(); $file->move($directory,$stored);
                    $this->db->table('admission_documents')->insert(['school_id'=>$school->id,'application_id'=>$id,'document_type'=>$field->field_key,'original_name'=>$original,'stored_name'=>$school->id.'/documents/'.$stored,'mime_type'=>$mime,'file_size'=>$size,'verification_status'=>'pending','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
                }
                continue;
            }
            $value=$p['custom_'.$field->id]??null; if (is_array($value)) $value=json_encode(array_values($value));
            if ($value!==null&&$value!=='') $this->db->table('admission_application_values')->insert(['school_id'=>$school->id,'application_id'=>$id,'field_id'=>$field->id,'value'=>$value,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        }
        $this->db->table('admission_status_history')->insert(['school_id'=>$school->id,'application_id'=>$id,'from_status'=>null,'to_status'=>$status,'note'=>'Public application submitted','created_by'=>null,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $this->db->transComplete();
        if ($this->db->transStatus()===false) return redirect()->back()->withInput()->with('error','The application could not be saved. Please try again.');
        return redirect()->to('admission/application/'.$data['token'])->with('success','Application submitted successfully. Keep your application number safe.');
    }

    public function application(string $token)
    {
        $application=$this->applicationByToken($token);
        if (!$application) return $this->response->setStatusCode(404)->setBody('Application not found.');
        $school=$this->schools->find($application->school_id);
        $result=$this->lotteryResultFor((int)$application->id);
        $circular=$this->circulars->find($application->circular_id);$payments=(new AdmissionPaymentModel())->where('application_id',$application->id)->orderBy('id','DESC')->findAll();$admitCards=$this->db->table('admission_test_admit_cards')->where('application_id',$application->id)->where('status',1)->get()->getResult();
        return view('App\Modules\admission\Views\public\application',compact('application','school','result','circular','payments','admitCards'));
    }

    public function verify(string $token)
    {
        $application=$this->applicationByToken($token);
        if (!$application) return $this->response->setStatusCode(404)->setBody('Application not found.');
        $school=$this->schools->find($application->school_id); $result=$this->lotteryResultFor((int)$application->id);
        return view('App\Modules\admission\Views\public\verify',compact('application','school','result'));
    }

    public function statusForm() { return view('App\Modules\admission\Views\public\status'); }

    public function status()
    {
        $school=$this->publicSchool(trim((string)$this->request->getPost('school_slug')));
        $application=$school?$this->credentialApplication((int)$school->id):null;
        if (!$application) return redirect()->back()->withInput()->with('error','No matching application was found.');
        return redirect()->to('admission/application/'.$application->token);
    }

    public function lotteryResult(string $schoolSlug)
    {
        $school=$this->publicSchool($schoolSlug); $application=$school?$this->credentialApplication((int)$school->id):null;
        if (!$application) return redirect()->back()->withInput()->with('error','No matching application was found.');
        return redirect()->to('admission/application/'.$application->token);
    }

    public function pdf(string $token)
    {
        $application=$this->applicationByToken($token);
        if (!$application) return $this->response->setStatusCode(404)->setBody('Application not found.');
        $school=$this->schools->find($application->school_id); $qr=generate_qr_code(base_url('admission/verify/'.$application->token),130);
        $html=view('App\Modules\admission\Views\public\pdf',compact('application','school','qr'));
        $options=new Options(); $options->set('isRemoteEnabled',true); $pdf=new Dompdf($options); $pdf->loadHtml($html); $pdf->setPaper('A4'); $pdf->render();
        return $this->response->setHeader('Content-Type','application/pdf')->setHeader('Content-Disposition','attachment; filename="'.$application->application_no.'.pdf"')->setBody($pdf->output());
    }

    public function payment(string $token,string $type)
    {
        $context=$this->feeContext($token,$type);
        if($context instanceof \CodeIgniter\HTTP\ResponseInterface)return $context;
        ['application'=>$application,'circular'=>$circular,'school'=>$school,'amount'=>$amount,'currency'=>$currency,'paymentSettings'=>$paymentSettings]=$context;
        $gateways=(new AdmissionPaymentGatewayService())->availableGateways((int)$application->school_id);
        return view('App\Modules\admission\Views\public\payment',compact('application','circular','school','amount','currency','type','gateways','paymentSettings'));
    }

    public function submitPayment(string $token,string $type)
    {
        $context=$this->feeContext($token,$type);
        if($context instanceof \CodeIgniter\HTTP\ResponseInterface)return $context;
        ['application'=>$application,'school'=>$school,'amount'=>$amount,'currency'=>$currency]=$context;

        $gateway=(string)$this->request->getPost('gateway');
        $gatewayService=new AdmissionPaymentGatewayService();
        if(!isset($gatewayService->availableGateways((int)$application->school_id)[$gateway]))return redirect()->back()->withInput()->with('error','Select an available payment method.');

        if($gateway==='manual')return $this->submitManualPayment($application,$type,$amount,$currency);

        $payment=$this->pendingPayment($application,$type,$amount,$currency,$gateway);
        $description=ucwords(str_replace('_',' ',$type)).' - '.$school->name.' - '.$application->application_no;
        try{
            if($gateway==='stripe'){
                $session=$gatewayService->createStripeCheckout($payment,$description);
                (new AdmissionPaymentModel())->update($payment->id,['transaction_id'=>$session->id,'metadata'=>json_encode(['checkout_session_id'=>$session->id])]);
                return redirect()->to($session->url);
            }

            $order=$gatewayService->createPayPalCheckout($payment,$description);
            (new AdmissionPaymentModel())->update($payment->id,['transaction_id'=>$order['order_id'],'metadata'=>json_encode(['order_id'=>$order['order_id']])]);
            return redirect()->to($order['approve_url']);
        }catch(Throwable $e){
            log_message('error','Admission '.$gateway.' checkout error: '.$e->getMessage());
            (new AdmissionPaymentModel())->update($payment->id,['status'=>'failed','rejection_reason'=>'Unable to start provider checkout.']);
            $this->setFeeStatus($application,$type,'unpaid');
            return redirect()->back()->with('error','The payment provider could not start checkout. Please try again.');
        }
    }

    public function stripeSuccess(string $paymentToken)
    {
        $payment=$this->onlinePayment($paymentToken,'stripe');
        if(!$payment)return $this->response->setStatusCode(404)->setBody('Payment not found.');
        if($payment->status==='paid')return redirect()->to('admission/application/'.$this->applications->find($payment->application_id)->token)->with('success','Payment is already complete.');
        $returnedSession=(string)$this->request->getGet('session_id');
        if($returnedSession!==''&&!hash_equals((string)$payment->transaction_id,$returnedSession))return $this->response->setStatusCode(400)->setBody('Invalid Stripe session.');
        try{$verified=(new AdmissionPaymentGatewayService())->verifyStripeCheckout($payment);return $this->completeOnlinePayment($payment,$verified);}catch(Throwable $e){log_message('error','Admission Stripe verification error: '.$e->getMessage());return redirect()->to('admission/application/'.$this->applications->find($payment->application_id)->token)->with('error','Stripe has not confirmed this payment.');}
    }

    public function stripeCancel(string $paymentToken){return $this->cancelOnlinePayment($paymentToken,'stripe');}

    public function paypalSuccess(string $paymentToken)
    {
        $payment=$this->onlinePayment($paymentToken,'paypal');
        if(!$payment)return $this->response->setStatusCode(404)->setBody('Payment not found.');
        if($payment->status==='paid')return redirect()->to('admission/application/'.$this->applications->find($payment->application_id)->token)->with('success','Payment is already complete.');
        $orderId=(string)$this->request->getGet('token');
        try{$verified=(new AdmissionPaymentGatewayService())->capturePayPalCheckout($payment,$orderId);return $this->completeOnlinePayment($payment,$verified);}catch(Throwable $e){log_message('error','Admission PayPal verification error: '.$e->getMessage());return redirect()->to('admission/application/'.$this->applications->find($payment->application_id)->token)->with('error','PayPal has not confirmed this payment.');}
    }

    public function paypalCancel(string $paymentToken){return $this->cancelOnlinePayment($paymentToken,'paypal');}

    public function admitCard(string $token){$data=$this->admitCardData($token);if(!$data)return $this->response->setStatusCode(404)->setBody('Admit card not found.');return view('App\Modules\admission\Views\public\admit_card',$data);}
    public function verifyAdmitCard(string $token){$data=$this->admitCardData($token);return view('App\Modules\admission\Views\public\admit_card_verify',['valid'=>(bool)$data]+($data?:[]));}
    public function admitCardPdf(string $token){$data=$this->admitCardData($token);if(!$data)return $this->response->setStatusCode(404)->setBody('Admit card not found.');$data['is_pdf']=true;$options=new Options();$options->set('isRemoteEnabled',true);$pdf=new Dompdf($options);$pdf->loadHtml(view('App\Modules\admission\Views\public\admit_card',$data));$pdf->setPaper('A4');$pdf->render();return $this->response->setHeader('Content-Type','application/pdf')->setHeader('Content-Disposition','attachment; filename="'.$data['card']->card_no.'.pdf"')->setBody($pdf->output());}

    private function publicSchool(string $slug): ?object { return $this->schools->where('slug',$slug)->where('status',1)->first(); }

    private function publicCircular(string $slug,string $token): array
    {
        $school=$this->publicSchool($slug); if (!$school) return [null,null]; $now=date('Y-m-d H:i:s');
        $circular=$this->circulars->where('token',$token)->where('school_id',$school->id)->where('status','published')->where('application_start <=',$now)->where('application_deadline >=',$now)->first();
        return [$school,$circular];
    }

    private function applicationByToken(string $token): ?object
    {
        return $this->applications->select('admission_applications.*,academic_classes.title class_name,academic_shift.title shift_name')->join('academic_classes','academic_classes.id=admission_applications.class_id','left')->join('academic_shift','academic_shift.id=admission_applications.shift_id','left')->where('admission_applications.token',$token)->first();
    }

    private function credentialApplication(int $schoolId): ?object
    {
        $number=trim((string)$this->request->getPost('application_no')); $credential=trim((string)$this->request->getPost('credential'));
        return $this->applications->where('school_id',$schoolId)->where('application_no',$number)->groupStart()->where('dob',$credential)->orWhere('guardian_mobile',$credential)->groupEnd()->first();
    }

    private function lotteryResultFor(int $applicationId): ?object
    {
        return $this->db->table('admission_lottery_results')->select('admission_lottery_results.*,admission_lotteries.name lottery_name,admission_lotteries.status lottery_status')->join('admission_lotteries','admission_lotteries.id=admission_lottery_results.lottery_id')->where('application_id',$applicationId)->where('admission_lotteries.status','finalized')->orderBy('admission_lottery_results.id','DESC')->get()->getRow();
    }

    private function ageAllowed(string $dob,object $circular): bool
    {
        $age=(new \DateTimeImmutable($dob))->diff(new \DateTimeImmutable(substr($circular->application_deadline,0,10)))->days/365.2425;
        return ($circular->minimum_age===null||$age>=(float)$circular->minimum_age)&&($circular->maximum_age===null||$age<=(float)$circular->maximum_age);
    }

    private function applicationYear(object $session): string
    {
        if (preg_match('/\b(20\d{2})\b/',(string)$session->title,$match)) return $match[1];
        return date('Y',strtotime($session->application_start));
    }

    private function admitCardData(string $token): ?array
    {
        $card=$this->db->table('admission_test_admit_cards c')->select('c.*,admission_applications.application_no,admission_applications.student_name,admission_applications.dob,admission_applications.photo,admission_tests.title test_title,admission_tests.test_date,admission_tests.start_time,admission_tests.end_time,admission_tests.reporting_time,admission_tests.instructions,a.seat_no,r.room_name_snapshot room_name,r.room_no_snapshot room_no')
            ->join('admission_applications','admission_applications.id=c.application_id')->join('admission_tests','admission_tests.id=c.test_id')->join('admission_test_seat_allocations a','a.id=c.seat_allocation_id','left')->join('admission_test_seat_plan_rooms r','r.seat_plan_id=a.seat_plan_id AND r.room_id=a.room_id','left')->where('c.token',$token)->where('c.status',1)->get()->getRow();if(!$card)return null;$school=$this->schools->find($card->school_id);$qr=generate_qr_code(base_url('admission/admit-card/verify/'.$card->token),110);return compact('card','school','qr');
    }

    private function feeContext(string $token,string $type)
    {
        $application=$this->applicationByToken($token);
        if(!$application||!in_array($type,['application_fee','admission_fee'],true))return $this->response->setStatusCode(404)->setBody('Payment request not found.');
        $circular=$this->circulars->find($application->circular_id);$statusField=$type==='application_fee'?'application_fee_status':'admission_fee_status';$amount=$type==='application_fee'?(float)$circular->application_fee:(float)$circular->admission_fee;
        if(($application->{$statusField}??null)==='paid')return redirect()->to('admission/application/'.$token)->with('error','This fee has already been paid.');
        if($amount<=0)return redirect()->to('admission/application/'.$token)->with('error','This fee is not required.');
        if($type==='admission_fee'&&!in_array($application->application_status,['selected','admission_pending'],true))return redirect()->to('admission/application/'.$token)->with('error','Admission fee is available only to selected applicants.');
        $gatewayService=new AdmissionPaymentGatewayService();$paymentSettings=$gatewayService->settings((int)$application->school_id);$currency=$gatewayService->currency((int)$application->school_id);
        return compact('application','circular','amount','currency','paymentSettings')+['school'=>$this->schools->find($application->school_id)];
    }

    private function submitManualPayment(object $application,string $type,float $amount,string $currency)
    {
        $name='proof_file';$rules=['transaction_id'=>'required|max_length[120]',$name=>'permit_empty|max_size['.$name.',5120]|ext_in['.$name.',pdf,jpg,jpeg,png,webp]'];
        if(!$this->validate($rules))return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));
        $proof=null;$file=$this->request->getFile($name);
        if($file&&$file->isValid()&&!$file->hasMoved()){$dir=WRITEPATH.'private'.DIRECTORY_SEPARATOR.'admission'.DIRECTORY_SEPARATOR.$application->school_id.DIRECTORY_SEPARATOR.'payments';if(!is_dir($dir))mkdir($dir,0755,true);$stored=$file->getRandomName();$file->move($dir,$stored);$proof=$application->school_id.'/payments/'.$stored;}
        $model=new AdmissionPaymentModel();$existing=$model->where('application_id',$application->id)->where('payment_type',$type)->whereIn('status',['pending','rejected','failed'])->orderBy('id','DESC')->first();
        $data=['token'=>$existing->token??bin2hex(random_bytes(24)),'school_id'=>$application->school_id,'application_id'=>$application->id,'payment_type'=>$type,'amount'=>$amount,'currency'=>$currency,'gateway'=>'manual','transaction_id'=>trim($this->request->getPost('transaction_id')),'payer_reference'=>trim($this->request->getPost('payer_reference')??'')?:null,'proof_file'=>$proof?:($existing->proof_file??null),'status'=>'pending','paid_at'=>null,'reviewed_by'=>null,'reviewed_at'=>null,'rejection_reason'=>null,'metadata'=>json_encode(['submitted_ip'=>$this->request->getIPAddress()])];
        $existing?$model->update($existing->id,$data):$model->insert($data);$this->setFeeStatus($application,$type,'pending');
        return redirect()->to('admission/application/'.$application->token)->with('success','Payment receipt submitted for school verification.');
    }

    private function pendingPayment(object $application,string $type,float $amount,string $currency,string $gateway): object
    {
        $model=new AdmissionPaymentModel();$existing=$model->where('application_id',$application->id)->where('payment_type',$type)->whereIn('status',['pending','rejected','failed'])->orderBy('id','DESC')->first();
        $data=['token'=>$existing->token??bin2hex(random_bytes(24)),'school_id'=>$application->school_id,'application_id'=>$application->id,'payment_type'=>$type,'amount'=>$amount,'currency'=>$currency,'gateway'=>$gateway,'transaction_id'=>null,'payer_reference'=>null,'proof_file'=>null,'status'=>'pending','paid_at'=>null,'reviewed_by'=>null,'reviewed_at'=>null,'rejection_reason'=>null,'metadata'=>null];
        if($existing){$model->update($existing->id,$data);$id=$existing->id;}else{$id=$model->insert($data,true);}
        $this->setFeeStatus($application,$type,'pending');return $model->find($id);
    }

    private function onlinePayment(string $token,string $gateway): ?object{return (new AdmissionPaymentModel())->where('token',$token)->where('gateway',$gateway)->whereIn('status',['pending','failed','paid'])->first();}

    private function completeOnlinePayment(object $payment,array $verified)
    {
        $application=$this->applications->find($payment->application_id);if(!$application)return $this->response->setStatusCode(404)->setBody('Application not found.');
        $updates=['status'=>'paid','transaction_id'=>$verified['transaction_id'],'payer_reference'=>$verified['provider_reference'],'paid_at'=>date('Y-m-d H:i:s'),'reviewed_at'=>date('Y-m-d H:i:s'),'rejection_reason'=>null,'metadata'=>json_encode(['verified_at'=>date(DATE_ATOM),'provider_reference'=>$verified['provider_reference'],'payload'=>$verified['payload']],JSON_UNESCAPED_SLASHES)];
        $applicationUpdates=[];$newStatus=null;if($payment->payment_type==='application_fee'){$applicationUpdates=['application_fee_status'=>'paid','payment_status'=>'paid'];if($application->application_status==='payment_pending')$newStatus='submitted';}else{$applicationUpdates=['admission_fee_status'=>'paid'];if($application->application_status==='selected')$newStatus='admission_pending';}if($newStatus)$applicationUpdates['application_status']=$newStatus;
        $this->db->transStart();(new AdmissionPaymentModel())->update($payment->id,$updates);$this->applications->update($application->id,$applicationUpdates);if($newStatus)(new AdmissionStatusHistoryModel())->insert(['school_id'=>$application->school_id,'application_id'=>$application->id,'from_status'=>$application->application_status,'to_status'=>$newStatus,'note'=>ucfirst($payment->gateway).' payment verified automatically','created_by'=>null]);$this->db->transComplete();
        if($this->db->transStatus()===false)return redirect()->to('admission/application/'.$application->token)->with('error','Payment was verified but could not be recorded. Please contact the school.');
        return redirect()->to('admission/application/'.$application->token)->with('success',ucfirst($payment->gateway).' payment completed successfully.');
    }

    private function cancelOnlinePayment(string $paymentToken,string $gateway)
    {
        $payment=$this->onlinePayment($paymentToken,$gateway);if(!$payment)return $this->response->setStatusCode(404)->setBody('Payment not found.');$application=$this->applications->find($payment->application_id);if(!$application)return $this->response->setStatusCode(404)->setBody('Application not found.');if($payment->status!=='paid'){(new AdmissionPaymentModel())->update($payment->id,['status'=>'failed','rejection_reason'=>'Checkout cancelled by applicant.']);$this->setFeeStatus($application,$payment->payment_type,'unpaid');}return redirect()->to('admission/application/'.$application->token)->with('error','Payment checkout was cancelled.');
    }

    private function setFeeStatus(object $application,string $type,string $status): void
    {
        $field=$type==='application_fee'?'application_fee_status':'admission_fee_status';$updates=[$field=>$status];if($type==='application_fee')$updates['payment_status']=$status;$this->applications->update($application->id,$updates);
    }
}
