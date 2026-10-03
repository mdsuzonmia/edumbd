<?php

namespace App\Modules\admission\Controllers;

use App\Modules\admission\Models\AdmissionApplicationModel;
use App\Modules\admission\Models\AdmissionLotteryResultModel;
use App\Modules\admission\Models\AdmissionStatusHistoryModel;
use App\Modules\admission\Services\AdmissionEnrollmentService;
use App\Modules\admission\Services\LotteryService;
use Throwable;

class AdmissionController extends BaseController
{
    public function academicDataJson(int $schoolId)
    {
        if (!$this->ownsSchool($schoolId)) return $this->response->setStatusCode(403)->setJSON(['error'=>'Access denied.']);
        $data=$this->academicData($schoolId);
        $data['sessions']=$this->sessions->where('school_id',$schoolId)->whereIn('status',['draft','published'])->orderBy('title','ASC')->findAll();
        $data['circulars']=$this->circulars->where('school_id',$schoolId)->where('lottery_required',1)->where('status','published')->orderBy('title','ASC')->findAll();
        return $this->response->setJSON($data);
    }

    public function dashboard()
    {
        $ids=$this->schoolIds();
        $stats=['applications'=>0,'pending'=>0,'eligible'=>0,'selected'=>0,'waiting'=>0,'admitted'=>0];
        if ($ids) {
            $stats['applications']=$this->applications->whereIn('school_id',$ids)->countAllResults();
            foreach (['under_review'=>'pending','eligible'=>'eligible','selected'=>'selected','waiting'=>'waiting','admitted'=>'admitted'] as $status=>$key) {
                $stats[$key]=$this->applications->whereIn('school_id',$ids)->where('application_status',$status)->countAllResults();
            }
        }
        return $this->render('dashboard',['stats'=>$stats],'Admission Dashboard');
    }

    public function sessions()
    {
        $ids=$this->schoolIds();
        $items=$ids ? $this->sessions->select('admission_sessions.*,schools.name school_name')->join('schools','schools.id=admission_sessions.school_id','left')->whereIn('admission_sessions.school_id',$ids)->orderBy('admission_sessions.id','DESC')->findAll() : [];
        return $this->render('sessions/index',['items'=>$items],'Admission Sessions');
    }

    public function sessionForm(?string $token=null)
    {
        $item=$token ? $this->findOwned($this->sessions,$token) : null;
        if ($token && !$item) return redirect()->to('school/admission/sessions')->with('error','Admission session not found.');
        $schools=$this->userSchools(); $schoolId=(int)($item->school_id ?? ($schools[0]->id ?? 0));
        return $this->render('sessions/form',['item'=>$item,'schools'=>$schools,'years'=>$schoolId?$this->academicData($schoolId)['years']:[],'validation'=>null],'Admission Session');
    }

    public function saveSession()
    {
        $rules=['school_id'=>'required|is_natural_no_zero','title'=>'required|max_length[180]','academic_year_id'=>'required|is_natural_no_zero','application_start'=>'required|valid_date[Y-m-d\TH:i]','application_end'=>'required|valid_date[Y-m-d\TH:i]','status'=>'required|in_list[draft,published,closed]'];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('error',implode(' ', $this->validator->getErrors()));
        $p=$this->request->getPost(); $schoolId=(int)$p['school_id'];
        if (!$this->ownsSchool($schoolId)) return redirect()->to('school/admission/sessions')->with('error','Access denied.');
        if (!$this->belongsToSchool('academic_years',(int)$p['academic_year_id'],$schoolId)) return redirect()->back()->withInput()->with('error','The academic session does not belong to the selected school.');
        if (strtotime($p['application_end']) <= strtotime($p['application_start'])) return redirect()->back()->withInput()->with('error','Application end must be after its start.');
        $item=!empty($p['token']) ? $this->findOwned($this->sessions,$p['token']) : null;
        $data=['school_id'=>$schoolId,'school_owner_uid'=>$this->userId(),'title'=>trim($p['title']),'academic_year_id'=>(int)$p['academic_year_id'],'application_start'=>str_replace('T',' ',$p['application_start']).':00','application_end'=>str_replace('T',' ',$p['application_end']).':00','admission_start'=>$this->dateTime($p['admission_start']??null),'admission_end'=>$this->dateTime($p['admission_end']??null),'status'=>$p['status'],'updated_by'=>$this->userId()];
        if ($item) $this->sessions->update($item->id,$data); else { $data['token']=bin2hex(random_bytes(20)); $data['created_by']=$this->userId(); $this->sessions->insert($data); }
        return redirect()->to('school/admission/sessions')->with('success','Admission session saved.');
    }

    public function circulars()
    {
        $ids=$this->schoolIds();
        $items=$ids ? $this->circulars->select('admission_circulars.*,schools.name school_name,academic_classes.title class_name')->join('schools','schools.id=admission_circulars.school_id','left')->join('academic_classes','academic_classes.id=admission_circulars.class_id','left')->whereIn('admission_circulars.school_id',$ids)->orderBy('admission_circulars.id','DESC')->findAll() : [];
        return $this->render('circulars/index',['items'=>$items],'Admission Circulars');
    }

    public function circularForm(?string $token=null)
    {
        $item=$token?$this->findOwned($this->circulars,$token):null;
        if ($token&&!$item) return redirect()->to('school/admission/circulars')->with('error','Circular not found.');
        $schools=$this->userSchools(); $schoolId=(int)($item->school_id??($schools[0]->id??0));
        $academic=$schoolId?$this->academicData($schoolId):['years'=>[],'classes'=>[],'sections'=>[],'shifts'=>[]];
        $sessionItems=$schoolId?$this->sessions->where('school_id',$schoolId)->whereIn('status',['draft','published'])->findAll():[];
        return $this->render('circulars/form',compact('item','schools','academic','sessionItems'),'Admission Circular');
    }

    public function saveCircular()
    {
        $rules=['school_id'=>'required|is_natural_no_zero','admission_session_id'=>'required|is_natural_no_zero','title'=>'required|max_length[200]','class_id'=>'required|is_natural_no_zero','available_seats'=>'required|is_natural','application_start'=>'required','application_deadline'=>'required','lottery_required'=>'required|in_list[0,1]','status'=>'required|in_list[draft,published,closed]'];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));
        $p=$this->request->getPost(); $schoolId=(int)$p['school_id'];
        if (!$this->ownsSchool($schoolId)) return redirect()->to('school/admission/circulars')->with('error','Access denied.');
        $session=$this->sessions->where('id',(int)$p['admission_session_id'])->where('school_id',$schoolId)->first();
        if (!$session) return redirect()->back()->withInput()->with('error','Invalid admission session.');
        if (!$this->belongsToSchool('academic_classes',(int)$p['class_id'],$schoolId)) return redirect()->back()->withInput()->with('error','The class does not belong to the selected school.');
        if (!empty($p['shift_id'])&&!$this->belongsToSchool('academic_shift',(int)$p['shift_id'],$schoolId)) return redirect()->back()->withInput()->with('error','The shift does not belong to the selected school.');
        $item=!empty($p['token'])?$this->findOwned($this->circulars,$p['token']):null;
        $data=['school_id'=>$schoolId,'admission_session_id'=>$session->id,'title'=>trim($p['title']),'description'=>trim($p['description']??''),'class_id'=>(int)$p['class_id'],'shift_id'=>!empty($p['shift_id'])?(int)$p['shift_id']:null,'available_seats'=>(int)$p['available_seats'],'minimum_age'=>$p['minimum_age']!==''?$p['minimum_age']:null,'maximum_age'=>$p['maximum_age']!==''?$p['maximum_age']:null,'application_start'=>$this->dateTime($p['application_start']),'application_deadline'=>$this->dateTime($p['application_deadline']),'application_fee'=>(float)($p['application_fee']??0),'admission_fee'=>(float)($p['admission_fee']??0),'lottery_required'=>(int)$p['lottery_required'],'instructions'=>trim($p['instructions']??''),'required_documents'=>trim($p['required_documents']??''),'status'=>$p['status'],'updated_by'=>$this->userId()];
        if ($item) $this->circulars->update($item->id,$data); else { $data['token']=bin2hex(random_bytes(20)); $data['created_by']=$this->userId(); $this->circulars->insert($data); }
        return redirect()->to('school/admission/circulars')->with('success','Admission circular saved.');
    }

    public function applications()
    {
        $ids=$this->schoolIds(); $status=(string)$this->request->getGet('status');
        $builder=$this->applications->select('admission_applications.*,schools.name school_name,academic_classes.title class_name')->join('schools','schools.id=admission_applications.school_id','left')->join('academic_classes','academic_classes.id=admission_applications.class_id','left');
        if ($ids) $builder->whereIn('admission_applications.school_id',$ids); else $builder->where('admission_applications.id',0);
        if (in_array($status,AdmissionApplicationModel::STATUSES,true)) $builder->where('application_status',$status);
        return $this->render('applications/index',['items'=>$builder->orderBy('admission_applications.id','DESC')->findAll(),'status'=>$status],'Admission Applications');
    }

    public function showApplication(string $token)
    {
        $item=$this->findOwned($this->applications,$token);
        if (!$item) return redirect()->to('school/admission/applications')->with('error','Application not found.');
        $history=(new AdmissionStatusHistoryModel())->where('application_id',$item->id)->orderBy('id','DESC')->findAll();
        $customValues=$this->db->table('admission_application_values v')->select('v.value,f.label,f.field_type')->join('admission_form_fields f','f.id=v.field_id')->where('v.application_id',$item->id)->orderBy('f.sort_order','ASC')->get()->getResult();
        $documents=$this->db->table('admission_documents')->where('application_id',$item->id)->orderBy('id','ASC')->get()->getResult();
        $payments=$this->db->table('admission_payments')->where('application_id',$item->id)->orderBy('id','DESC')->get()->getResult();
        return $this->render('applications/show',['item'=>$item,'history'=>$history,'customValues'=>$customValues,'documents'=>$documents,'payments'=>$payments,'sections'=>$this->academicData((int)$item->school_id)['sections']],'Application '.$item->application_no);
    }

    public function applicationsCsv()
    {
        $ids=$this->schoolIds(); $status=(string)$this->request->getGet('status');
        $builder=$this->applications->select('admission_applications.application_no,admission_applications.student_name,admission_applications.dob,admission_applications.gender,admission_applications.guardian_mobile,schools.name school_name,academic_classes.title class_name,admission_applications.application_fee_status,admission_applications.admission_fee_status,admission_applications.application_status,admission_applications.submitted_at')
            ->join('schools','schools.id=admission_applications.school_id','left')->join('academic_classes','academic_classes.id=admission_applications.class_id','left');
        if ($ids) $builder->whereIn('admission_applications.school_id',$ids); else $builder->where('admission_applications.id',0);
        if (in_array($status,AdmissionApplicationModel::STATUSES,true)) $builder->where('application_status',$status);
        $rows=$builder->orderBy('admission_applications.id','ASC')->findAll();
        $stream=fopen('php://temp','w+');
        fputcsv($stream,['Application','Student','Date of Birth','Gender','Guardian Mobile','School','Class','Application Fee','Admission Fee','Status','Submitted']);
        foreach ($rows as $row) fputcsv($stream,[$row->application_no,$row->student_name,$row->dob,$row->gender,$row->guardian_mobile,$row->school_name,$row->class_name,$row->application_fee_status,$row->admission_fee_status,$row->application_status,$row->submitted_at]);
        rewind($stream); $csv=stream_get_contents($stream); fclose($stream);
        $suffix=$status?:'all';
        return $this->response->setHeader('Content-Type','text/csv; charset=UTF-8')->setHeader('Content-Disposition','attachment; filename="admission-applications-'.$suffix.'-'.date('Ymd').'.csv"')->setBody("\xEF\xBB\xBF".$csv);
    }

    public function updateApplicationStatus(string $token)
    {
        $item=$this->findOwned($this->applications,$token); $to=(string)$this->request->getPost('status');
        $allowed=['under_review','correction_required','eligible','ineligible','rejected','admission_pending','cancelled'];
        if (!$item||!in_array($to,$allowed,true)) return redirect()->back()->with('error','Invalid status change.');
        if (in_array($item->application_status,['admitted','cancelled'],true)) return redirect()->back()->with('error','A final application cannot be changed.');
        if($to==='eligible') {
            $circular=$this->circulars->find($item->circular_id);
            if($circular&&(float)$circular->application_fee>0&&($item->application_fee_status??$item->payment_status)!=='paid') return redirect()->back()->with('error','Verify the application fee before marking this applicant eligible.');
            $requiredFiles=$this->db->table('admission_form_fields')->select('field_key')->where('circular_id',$item->circular_id)->where('field_type','file')->where('is_required',1)->where('status',1)->get()->getResult();
            foreach($requiredFiles as $field) if(!$this->db->table('admission_documents')->where('application_id',$item->id)->where('document_type',$field->field_key)->where('verification_status','verified')->countAllResults()) return redirect()->back()->with('error','Verify every required document before marking this applicant eligible.');
        }
        $note=trim((string)$this->request->getPost('note'));
        if ($to==='correction_required'&&$note==='') return redirect()->back()->with('error','Describe the correction that is required.');
        $this->db->transStart();
        $this->applications->update($item->id,['application_status'=>$to,'correction_message'=>$to==='correction_required'?$note:null,'reviewed_at'=>date('Y-m-d H:i:s'),'updated_by'=>$this->userId()]);
        (new AdmissionStatusHistoryModel())->insert(['school_id'=>$item->school_id,'application_id'=>$item->id,'from_status'=>$item->application_status,'to_status'=>$to,'note'=>$note?:null,'created_by'=>$this->userId()]);
        $this->db->table('admission_reviews')->insert(['school_id'=>$item->school_id,'application_id'=>$item->id,'decision'=>$to,'notes'=>$note?:null,'reviewed_by'=>$this->userId(),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $this->db->transComplete();
        return redirect()->back()->with('success','Application status updated.');
    }

    public function admit(string $token)
    {
        $item=$this->findOwned($this->applications,$token);
        if (!$item) return redirect()->to('school/admission/applications')->with('error','Application not found.');
        $sectionId=(int)$this->request->getPost('section_id');
        if ($sectionId&&!$this->belongsToSchool('academic_sections',$sectionId,(int)$item->school_id)) return redirect()->back()->withInput()->with('error','The section does not belong to this school.');
        try {
            $result=(new AdmissionEnrollmentService())->complete($item,['section_id'=>$sectionId,'roll_no'=>$this->request->getPost('roll_no'),'student_code'=>$this->request->getPost('student_code'),'admission_no'=>$this->request->getPost('admission_no'),'notes'=>$this->request->getPost('notes')],$this->userId());
            return redirect()->to('school/admission/applications/'.$token)->with('success','Admission completed. Student #'.$result->student_id.' was created.');
        } catch (Throwable $e) { return redirect()->back()->withInput()->with('error',$e->getMessage()); }
    }

    public function lotteries()
    {
        $ids=$this->schoolIds(); $items=$ids?$this->lotteries->whereIn('school_id',$ids)->orderBy('id','DESC')->findAll():[];
        return $this->render('lotteries/index',['items'=>$items],'Admission Lotteries');
    }

    public function lotteryForm()
    {
        $schools=$this->userSchools(); $schoolId=(int)($schools[0]->id??0);
        $circularItems=$schoolId?$this->circulars->where('school_id',$schoolId)->where('lottery_required',1)->where('status','published')->findAll():[];
        return $this->render('lotteries/form',['schools'=>$schools,'circularItems'=>$circularItems],'Create Lottery');
    }

    public function saveLottery()
    {
        if (!$this->validate(['school_id'=>'required|is_natural_no_zero','circular_id'=>'required|is_natural_no_zero','name'=>'required|max_length[180]','seat_count'=>'required|is_natural_no_zero','waiting_count'=>'required|is_natural'])) return redirect()->back()->withInput()->with('error',implode(' ',$this->validator->getErrors()));
        $p=$this->request->getPost(); $schoolId=(int)$p['school_id'];
        if (!$this->ownsSchool($schoolId)) return redirect()->back()->with('error','Access denied.');
        $circular=$this->circulars->where('id',(int)$p['circular_id'])->where('school_id',$schoolId)->where('lottery_required',1)->first();
        if (!$circular) return redirect()->back()->withInput()->with('error','Invalid lottery circular.');
        $revision=$this->lotteries->where('circular_id',$circular->id)->selectMax('revision')->first();
        $this->lotteries->insert(['token'=>bin2hex(random_bytes(20)),'school_id'=>$schoolId,'admission_session_id'=>$circular->admission_session_id,'circular_id'=>$circular->id,'name'=>trim($p['name']),'academic_year_id'=>$this->sessions->find($circular->admission_session_id)->academic_year_id,'class_id'=>$circular->class_id,'shift_id'=>$circular->shift_id,'seat_count'=>(int)$p['seat_count'],'waiting_count'=>(int)$p['waiting_count'],'revision'=>(int)($revision->revision??0)+1,'status'=>'draft','algorithm_version'=>LotteryService::ALGORITHM_VERSION,'created_by'=>$this->userId(),'updated_by'=>$this->userId()]);
        return redirect()->to('school/admission/lotteries')->with('success','Lottery created. Review it before locking candidates.');
    }

    public function showLottery(string $token)
    {
        $item=$this->findOwned($this->lotteries,$token);
        if (!$item) return redirect()->to('school/admission/lotteries')->with('error','Lottery not found.');
        $results=(new AdmissionLotteryResultModel())->select('admission_lottery_results.*,admission_applications.application_no,admission_applications.student_name')->join('admission_applications','admission_applications.id=admission_lottery_results.application_id')->where('lottery_id',$item->id)->orderBy('draw_position','ASC')->findAll();
        $audit=(new LotteryService())->verifyAudit($item);
        return $this->render('lotteries/show',['item'=>$item,'results'=>$results,'audit'=>$audit],'Lottery '.$item->name);
    }

    public function lockLottery(string $token) { return $this->lotteryAction($token,fn($i)=>(new LotteryService())->lock($i->id,$i->school_id,$this->userId()),'Candidate list locked.'); }
    public function runLottery(string $token) { return $this->lotteryAction($token,fn($i)=>(new LotteryService())->draw($i->id,$i->school_id,$this->userId()),'Lottery draw completed. Review and finalize the result.'); }

    public function finalizeLottery(string $token)
    {
        $item=$this->findOwned($this->lotteries,$token);
        if (!$item||$item->status!=='drawn') return redirect()->back()->with('error','Only a drawn lottery can be finalized.');
        $this->lotteries->update($item->id,['status'=>'finalized','finalized_at'=>date('Y-m-d H:i:s'),'updated_by'=>$this->userId()]);
        return redirect()->back()->with('success','Lottery finalized. It cannot be re-run.');
    }

    public function cancelLottery(string $token)
    {
        $item=$this->findOwned($this->lotteries,$token); $reason=trim((string)$this->request->getPost('reason'));
        if (!$item||!in_array($item->status,['locked','drawn','finalized'],true)||$reason==='') return redirect()->back()->with('error','A cancellation reason is required.');
        $this->db->transStart();
        if (in_array($item->status,['drawn','finalized'],true)) {
            $affected=$this->db->table('admission_lottery_results')->select('application_id')->where('lottery_id',$item->id)->get()->getResult();
            foreach ($affected as $row) {
                $application=$this->applications->find($row->application_id);
                if ($application&&in_array($application->application_status,['selected','waiting','not_selected'],true)) {
                    $this->applications->update($application->id,['application_status'=>'eligible','updated_by'=>$this->userId()]);
                    (new AdmissionStatusHistoryModel())->insert(['school_id'=>$item->school_id,'application_id'=>$application->id,'from_status'=>$application->application_status,'to_status'=>'eligible','note'=>'Lottery revision cancelled: '.$reason,'created_by'=>$this->userId()]);
                }
            }
        }
        $this->lotteries->update($item->id,['status'=>'cancelled','cancelled_at'=>date('Y-m-d H:i:s'),'cancelled_by'=>$this->userId(),'cancellation_reason'=>$reason,'updated_by'=>$this->userId()]);
        $this->db->transComplete();
        return redirect()->back()->with('success','Lottery cancelled. Its audit records have been retained; create a new revision to draw again.');
    }

    private function lotteryAction(string $token, callable $action, string $success)
    {
        $item=$this->findOwned($this->lotteries,$token); if (!$item) return redirect()->to('school/admission/lotteries')->with('error','Lottery not found.');
        try { $action($item); return redirect()->back()->with('success',$success); } catch (Throwable $e) { return redirect()->back()->with('error',$e->getMessage()); }
    }

    private function dateTime(?string $value): ?string { return $value ? str_replace('T',' ',$value).(strlen($value)===16?':00':'') : null; }
    private function belongsToSchool(string $table,int $id,int $schoolId): bool { return $id>0&&$this->db->table($table)->where('id',$id)->where('school_id',$schoolId)->countAllResults()===1; }
}
