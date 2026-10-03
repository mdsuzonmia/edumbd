<?php 
use App\Models\ExamModel;
use App\Models\MarkModel;
use App\Models\SubjectModel;
use App\Models\SkillModel;
use App\Models\MdistributionModel;
use App\Models\RemarkModel;
use App\Models\ResultModel;

$remark_model            = new RemarkModel();
$mark_model              = new MarkModel();
$exam_model              = new ExamModel();
$subject_model           = new SubjectModel();
$mark_distribution_model = new MdistributionModel();
$skill_model             = new SkillModel();
$result_model            = new ResultModel();

// Get school id from session
$school_id      = session()->get('school_id');
$school_name    = esc(get_school_value($school_id, 'name'));
$school_address = esc(get_school_value($school_id, 'address'));
$school_phone   = esc(get_school_value($school_id, 'phone'));
$school_email   = esc(get_school_value($school_id, 'email'));
$school_logo    = esc(get_school_value($school_id, 'logo'));
$school_status  = esc(get_school_value($school_id, 'status'));

// Get Setting Value from school
$student_account      = esc(get_school_value($school_id, 'student_account', true));
$academic_roll        = esc(get_school_value($school_id, 'academic_roll', true));
$student_phone        = esc(get_school_value($school_id, 'student_phone', true));
$parent_account       = esc(get_school_value($school_id, 'parent_account', true));
$academic_category    = esc(get_school_value($school_id, 'academic_category', true));
$academic_group       = esc(get_school_value($school_id, 'academic_group', true));
$academic_section     = esc(get_school_value($school_id, 'academic_section', true));
$academic_grade       = esc(get_school_value($school_id, 'academic_grade', true));
$academic_shift       = esc(get_school_value($school_id, 'academic_shift', true));
$academic_department  = esc(get_school_value($school_id, 'academic_department', true));
$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));
$academic_house       = esc(get_school_value($school_id, 'academic_house', true));
$academic_version     = esc(get_school_value($school_id, 'academic_version', true));

$certificate_title        = esc(get_school_value($school_id, 'certificate_title', true));
$show_certificate_principal_signature = esc(get_school_value($school_id, 'show_certificate_principal_signature', true));
$certificate_template     = esc(get_school_value($school_id, 'certificate_template', true));
$certificate_bg           = esc(get_school_value($school_id, 'certificate_bg', true));
$principal_signature      = esc(get_school_value($school_id, 'principal_signature', true));


// Get Student data
if($student_data){
    $academic_id       = $student_data->id;
    $student_id_number = $student_data->student_id_number;
    $student_id        = $student_data->student_id;
    $first_name        = $student_data->first_name;
    $last_name         = $student_data->last_name;  
    $student_name      = $student_data->name;
    $photo             = $student_data->photo;
    $session_id        = $student_data->session_id;
    $class_id          = $student_data->class_id;
    $roll              = $student_data->roll;
    $grade_id          = $student_data->grade_level_id;
    $section_id        = $student_data->section_id;
    $department_id     = $student_data->department_id;
    $group_id          = $student_data->group_id;
    $shift_id          = $student_data->shift_id;
    $category_id       = $student_data->category_id;
    $house_id          = $student_data->house_id;
    $version_id        = $student_data->version_id;
    
    
    // Get Exam Data
    $exam_title         = get_item('title', 'academic_exam', 'id', $exam_id);

    // Get Session Title
    $session_title      = get_item('title', 'academic_sessions', 'id', $student_data->session_id);
    $class_title        = get_item('title', 'academic_class', 'id', $student_data->class_id);

    $grade_title       = get_item('title', 'academic_grade', 'id', $student_data->grade_level_id);
    $group_title       = get_item('title', 'academic_group', 'id', $student_data->group_id);
    $section_title     = get_item('title', 'academic_section', 'id', $student_data->section_id);
    $house_title       = get_item('title', 'academic_house', 'id', $student_data->house_id);
    $version_title     = get_item('title', 'academic_version', 'id', $student_data->version_id);
    $category_title    = get_item('title', 'academic_category', 'id', $student_data->category_id);
    $shift_title       = get_item('title', 'academic_shift', 'id', $student_data->shift_id);
    $department_title  = get_item('title', 'academic_department', 'id', $student_data->department_id);

    $result_exist = $result_model->where('student_id', $student_data->student_id)
                                    ->where('exam_id', $exam_id)
                                    ->where('school_id', $school_id)
                                    ->where('session_id', $session_id)
                                    ->first();

    if($result_exist){
        $total_marks = $result_exist->total_marks;
        $percentage  = $result_exist->percentage;
        $gpa         = $result_exist->grade_point;
        $letter_grade = $result_exist->letter_grade;
    }else{
        $total_marks = '';
        $percentage  = '';
        $gpa         = '';
        $letter_grade = '';
    }
    

    $exam_rank       = get_exam_rank($exam_id, $school_id, $session_id, $student_data->student_id, $academic_id, '','');

}else{
    $academic_id       = '';
    $student_id_number = '';
    $student_id        = '';
    $first_name        = '';
    $last_name         = '';
    $student_name      = '';
    $photo             = '';
    $class_id          = '';
    $roll              = '';
    $grade_id          = '';
    $section_id        = '';
    $department_id     = '';
    $group_id          = '';
    $shift_id          = '';
    $category_id       = '';
    $house_id          = '';
    $version_id        = '';
    $session_id        = '';
   
    
   
        $session_title     = '';
        $class_title       = '';
        $grade_title       = '';
        $group_title       = '';
        $section_title     = '';
        $house_title       = '';
        $version_title     = '';
        $category_title    = '';
        $shift_title       = '';
        $department_title  = '';

        $total_marks = '';
        $percentage  = '';
        $gpa         = '';
        $letter_grade = '';
        $exam_rank       = '';
        $exam_title        = '';
}

// Certificate Template
$templateContent = $certificate_template; 
$placeHolders = [
    '[STUDENT_NAME]',
    '[STUDENT_ID]',
    '[CLASS_ROLL]',
    '[EXAM]',
    '[SESSION]',
    '[CLASS]',
    '[GRADE]',
    '[GROUP]',
    '[SECTION]',
    '[HOUSE]',
    '[VERSION]',
    '[CATEGORY]',
    '[SHIFT]',
    '[DEPARTMENT]',
    '[TOTAL_MARK]',
    '[PERCETTAGE]',
    '[GPA]',
    '[LETTER_GRADE]',
    '[RANK]',
];

$values = [
    '<b>'.$student_name.'</b>',
    '<b>'.$student_id_number.'</b>',
    '<b>'.$roll.'</b>',
    '<b>'.$exam_title.'</b>',
    '<b>'.$session_title.'</b>',
    '<b>'.$class_title.'</b>',
    '<b>'.$grade_title.'</b>',
    '<b>'.$group_title.'</b>',

    '<b>'.$section_title.'</b>',
    '<b>'.$house_title.'</b>',
    '<b>'.$version_title.'</b>',
    '<b>'.$category_title.'</b>',

    '<b>'.$shift_title.'</b>',
    '<b>'.$department_title.'</b>',

    '<b>'.$total_marks.'</b>',
    '<b>'.$percentage.'</b>',
    '<b>'.$gpa.'</b>',
    '<b>'.$letter_grade.'</b>',
    '<b>'.$exam_rank.'</b>',
];
$rendered = str_replace($placeHolders, $values, $templateContent);

?>

<?php if($student_data){ ?>
    <div class="certificate-area">

        <?php $print_header_data['caption']  = $certificate_title; ?>
        <?= view('print/print_header', $print_header_data) ?>

        <!-- ### CERTIFICATE TEMPLATE ### -->
        <div class="text"><?= $rendered ?></div>

        <!-- ### CERTIFICATE SIGNATURE ### -->
        <div class="signature text-center mt-3">
            <?php if(!empty($show_certificate_principal_signature)): 
                $principal_signature_path = base_url('uploads/' . esc($principal_signature));
                    echo '<img src="'.$principal_signature_path.'" class="img-responsive" alt="'.lang('School.principal_signature').'" height="100px" />';
                    echo '<p>'.lang('School.principal_signature').'</p>';
                endif; ?>  
        </div>

    </div>
        
<?php }else{ ?>
<div class="row">
    <div class="col-sm-12 card">
        <div class="body p-3">
        <h3 class="text-center red"><i class="fa fa-meh-o"></i> <?= lang('Result.student_not_found'); ?></h3>
        </div>
    </div>
</div>
<?php } ?>
