<?php

namespace App\Modules\admission\Services;

use App\Models\StudentEnrollmentModel;
use App\Models\StudentGuardianModel;
use App\Models\StudentModel;
use App\Modules\admission\Models\AdmissionApplicationModel;
use App\Modules\admission\Models\AdmissionConfirmationModel;
use App\Modules\admission\Models\AdmissionStatusHistoryModel;
use DomainException;
use Throwable;

final class AdmissionEnrollmentService
{
    public function complete(object $application, array $assignment, int $userId): object
    {
        if (!in_array($application->application_status, ['eligible','selected','admission_pending'], true)) {
            throw new DomainException('Only an eligible or selected applicant can be admitted.');
        }
        if (!empty($application->student_id)) {
            throw new DomainException('This applicant has already been converted to a student.');
        }
        $circular=db_connect()->table('admission_circulars')->where('id',$application->circular_id)->where('school_id',$application->school_id)->get()->getRow();
        if($circular&&(float)$circular->admission_fee>0&&($application->admission_fee_status??'unpaid')!=='paid') {
            throw new DomainException('The admission fee must be verified before completing admission.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $studentModel = new StudentModel();
            $enrollmentModel = new StudentEnrollmentModel();
            $guardianModel = new StudentGuardianModel();
            $applicationModel = new AdmissionApplicationModel();
            $confirmationModel = new AdmissionConfirmationModel();
            $historyModel = new AdmissionStatusHistoryModel();
            $token = bin2hex(random_bytes(24));
            $code = trim((string)($assignment['student_code'] ?? '')) ?: 'STU-'.strtoupper(substr(bin2hex(random_bytes(6)),0,10));
            $admissionNo = trim((string)($assignment['admission_no'] ?? '')) ?: 'ADM-STU-'.date('Y').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,6));

            $studentId = $studentModel->insert([
                'school_id'=>$application->school_id, 'school_owner_uid'=>$userId,
                'student_code'=>$code, 'registration_no'=>$admissionNo,
                'first_name'=>$application->student_name, 'alias'=>$application->student_name,
                'token'=>$token, 'gender'=>$this->studentGender($application->gender),
                'date_of_birth'=>$application->dob, 'blood_group'=>$application->blood_group,
                'religion'=>$application->religion, 'nationality'=>$application->nationality,
                'phone'=>$application->guardian_mobile, 'photo'=>$application->photo,
                'admission_date'=>date('Y-m-d'), 'student_status'=>'Active', 'admission_source'=>'Online',
                'student_qr_code'=>$token, 'status'=>1, 'created_by'=>$userId, 'updated_by'=>$userId,
            ], true);
            if (!$studentId) throw new DomainException('The student record could not be created.');

            $enrollmentId = $enrollmentModel->insert([
                'school_id'=>$application->school_id, 'school_owner_uid'=>$userId, 'student_id'=>$studentId,
                'session_id'=>$application->academic_year_id, 'class_id'=>$application->class_id,
                'section_id'=>$assignment['section_id'] ?: null, 'department_id'=>$application->department_id,
                'group_id'=>$application->group_id, 'shift_id'=>$application->shift_id,
                'roll_no'=>trim((string)($assignment['roll_no'] ?? '')) ?: null,
                'status'=>1, 'created_by'=>$userId, 'updated_by'=>$userId,
            ], true);
            if (!$enrollmentId) throw new DomainException('The student enrollment could not be created.');

            $guardians = [
                ['Father',$application->father_name,$application->father_mobile,$application->father_email,$application->father_occupation],
                ['Mother',$application->mother_name,$application->mother_mobile,$application->mother_email,$application->mother_occupation],
                ['Guardian',$application->guardian_name,$application->guardian_mobile,null,null],
            ];
            foreach ($guardians as [$relation,$name,$phone,$email,$occupation]) {
                if (trim((string)$name) !== '') $guardianModel->insert(['school_id'=>$application->school_id,'student_id'=>$studentId,'relation_type'=>$relation,'name'=>$name,'phone'=>$phone ?: null,'email'=>$email ?: null,'occupation'=>$occupation ?: null,'address'=>$application->present_address]);
            }

            $now = date('Y-m-d H:i:s');
            $confirmationModel->insert(['school_id'=>$application->school_id,'application_id'=>$application->id,'student_id'=>$studentId,'enrollment_id'=>$enrollmentId,'admission_no'=>$admissionNo,'roll_no'=>$assignment['roll_no'] ?: null,'confirmed_by'=>$userId,'confirmed_at'=>$now,'notes'=>$assignment['notes'] ?? null]);
            $applicationModel->update($application->id, ['application_status'=>'admitted','student_id'=>$studentId,'admitted_at'=>$now,'updated_by'=>$userId]);
            $historyModel->insert(['school_id'=>$application->school_id,'application_id'=>$application->id,'from_status'=>$application->application_status,'to_status'=>'admitted','note'=>'Converted to student #'.$studentId,'created_by'=>$userId]);
            $db->transCommit();
            return (object)['student_id'=>$studentId,'enrollment_id'=>$enrollmentId,'admission_no'=>$admissionNo];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function studentGender(?string $gender): ?string
    {
        $value = ucfirst(strtolower((string)$gender));
        return in_array($value, ['Male','Female','Other'], true) ? $value : null;
    }
}
