<?php 
use App\Models\AcademicsModel;
use App\Models\ParentModel;
use App\Models\ParentStudentModel;
use App\Models\ResultModel;
use App\Models\SubjectModel;
use App\Models\SkillModel;
use App\Models\ModuleModel;

$parent_student_model = new ParentStudentModel();
$subject_model        = new SubjectModel();
$skill_model          = new SkillModel();

$academic_roll        = esc(get_school_value($school_id, 'academic_roll', true));
$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));

// Get academic data
$academmic_model = new AcademicsModel();
// Get check if session and school_id is set
if(!empty($session) && !empty($school_id)){
    
    $academmic_model->where('session_id', $session)->where('school_id', $school_id);

    // if class is set
    if(!empty($class)){
        $academmic_model->where('class_id', $class);
    }

    // if grade is set
    if(!empty($grade)){
        $academmic_model->where('grade_level_id', $grade);
    }

    // if group is set
    if(!empty($group)){
        $academmic_model->where('group_id', $group);
    }

    // if house is set
    if(!empty($house)){
        $academmic_model->where('house_id', $house);
    }

    // if version is set
    if(!empty($version)){
        $academmic_model->where('version_id', $version);
    }

    // if section is set    
    if(!empty($section)){
        $academmic_model->where('section_id', $section);
    }

    // if category is set
    if(!empty($category)){
        $academmic_model->where('category_id', $category);
    }

    // if shift is set
    if(!empty($shift)){
        $academmic_model->where('shift_id', $shift);
    }

    // if department is set
    if(!empty($department)){
        $academmic_model->where('department_id', $department);
    }

    $academmic_model->where('status', 1);

    // Get order by ID DESC
    if($academic_roll){
        $academmic_model->orderBy('roll', 'ASC');
    }else{
        $academmic_model->orderBy('id', 'ASC');
    }
    

    $academic_data = $academmic_model->findAll();
}else{
    $academic_data = '';
}




?>

<div class="right_col" role="main">

    <div class="row">
        <div class="col-md-12 ">
            <h3><?= lang('Sms.page_title') ?></h3>
        </div>

        <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-12   ">
            <div class="x_panel">

                
                <div class="x_content">
                    <div class="col-sm-3 mb-3">
                        <?php 
                        // Get exam
                        $exam_title = get_item('title', 'academic_exam', 'id', $exam);
                        ?>
                        <b><?= lang('Mark.field_exam').': '.$exam_title; ?></b>
                    </div>

                    <div class="col-sm-3 mb-3">
                        <?php
                        // Get session
                        $session_title = get_item('title', 'academic_sessions', 'id', $session);
                        ?>
                        <b><?= lang('Mark.field_session').': '.$session_title; ?></b>
                    </div>

                    <?php 
                    // Get class
                    if(!empty($class)){
                        $class_title = get_item('title', 'academic_class', 'id', $class);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_class').': '.$class_title.'</b>';
                        echo '</div>';
                    }
                    ?>

                    <div class="col-sm-3 mb-3">
                        <?php
                        // Get school
                        $school_title = get_item('name', 'schools', 'id', $school_id);
                        ?>
                        <b><?= lang('Mark.field_school').': '.$school_title; ?></b>
                    </div>

                    
                    <?php 
                    // Get category
                    if(!empty($category)){
                        $category_title = get_item('title', 'academic_category', 'id', $category);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_category').': '.$category_title.'</b>';
                        echo '</div>';
                    }

                    // Get shift
                    if(!empty($shift)){
                        $shift_title = get_item('title', 'academic_shift', 'id', $shift);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_shift').': '.$shift_title.'</b>';
                        echo '</div>';
                    }

                    // Get department
                    if(!empty($department)){
                        $department_title = get_item('title', 'academic_department', 'id', $department);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_department').': '.$department_title.'</b>';
                        echo '</div>';
                    }

                    // Get section
                    if(!empty($section)){
                        $section_title = get_item('title', 'academic_section', 'id', $section);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_section').': '.$section_title.'</b>';
                        echo '</div>';
                    }

                    // Get house
                    if(!empty($house)){
                        $house_title = get_item('title', 'academic_house', 'id', $house);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_house').': '.$house_title.'</b>';
                        echo '</div>';
                    }

                    // Get group
                    if(!empty($group)){
                        $group_title = get_item('title', 'academic_group', 'id', $group);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_group').': '.$group_title.'</b>';
                        echo '</div>';
                    }   

                    // Get version
                    if(!empty($version)){
                        $version_title = get_item('title', 'academic_version', 'id', $version);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_version').': '.$version_title.'</b>';
                        echo '</div>';
                    }

                    // Get Grade Label
                    if(!empty($grade)){
                        $grade_title = get_item('title', 'academic_grade', 'id', $grade);
                        echo '<div class="col-sm-3 mb-3">';
                        echo '<b>'.lang('Mark.field_grade').': '.$grade_title.'</b>';
                        echo '</div>';
                    }
                    ?>
                    

                </div>

            </div>
        </div>

    </div>

    <?php 
    $html = '';
    if($academic_data){
        
        foreach ($academic_data as $key => $academic) {
            $academic_id         = $academic->id;
            $student_id          = $academic->student_id;
            $student_first_name  = get_item('first_name', 'students', 'id', $student_id);
            $student_last_name   = get_item('last_name', 'students', 'id', $student_id);
            $student_full_name   = get_item('name', 'students', 'id', $student_id);
            $student_id_number   = get_item('student_id_number', 'students', 'id', $student_id);
            $academic_roll       = $academic->roll;
            $student_phone       = get_item('phone', 'students', 'id', $student_id);

            // Get result data
            $result_model         = new ResultModel();
            $result_model->where('exam_id', $exam)->where('school_id', $school_id)->where('session_id', $session);
            $result_model->where('student_id', $student_id);
            $result_model->where('academic_id', $academic_id);
            $result_data = $result_model->first();

            if($result_data){
                $percentage    = $result_data->percentage;
                $total_marks   = $result_data->total_marks;
                $grade_point   = $result_data->grade_point;
                $letter_grade  = $result_data->letter_grade;
                $subject_value = $result_data->subject_value;
                $skill_value   = $result_data->skill_value;

                $exam_id       = $result_data->exam_id;
                $student_exam_title = get_item('title', 'academic_exam', 'id', $exam_id);

                // Get subject from subject value json
                $subject_object = json_decode($subject_value, false);

                // Get Subject Data
                $subject_output = '(';
                $subject_data = [];
                foreach ($subject_object as $subject_id => $subject_value) {
                    $subject = $subject_model->where('id', $subject_id)->first();
                    if ($subject) {
                        $subject_data[] = $subject->short_title . ':' . $subject_value;
                    }
                }
                $subject_output .= implode(',', $subject_data) . ')';

                // Get skill from skill_value json
                $skill_object = json_decode($skill_value, false);
                $skill_output = '(';
                $skill_data = [];
                foreach ($skill_object as $skill_id =>$skill_value) {
                    $skill = $skill_model->where('id', $skill_id)->first();
                    if ($skill) {
                        $skill_data[] = $skill->short_title . ':' . $skill_value;
                    }
                }
                $skill_output .= implode(',', $skill_data) . ')';
            }else{
                $percentage     = '';
                $total_marks    = '';
                $grade_point    = '';
                $letter_grade   = '';
                $subject_output = '';
                $skill_output   = '';
            }

           

            // Get Student Parent ID
            $parent_model    = new ParentModel();
            $child_records = $parent_student_model->where('student_id', $student_id_number)->first();         
            if($child_records){
                $parent_id         = $child_records->parent_id;
                $parent_data       = $parent_model->where('id', $parent_id)->first();
                $parent_user_id    = $parent_data->user_id;
                $parent_first_name = $parent_data->first_name;
                $parent_last_name  = $parent_data->last_name;
                $parent_full_name  = $parent_first_name.' '.$parent_last_name;
                $parent_phone      = get_item('phone', 'users', 'id', $parent_user_id);
            }else{
                $parent_id         = '';
                $parent_phone      = '';
                $parent_first_name = '';
                $parent_last_name  = '';
                $parent_full_name  = '';
            }

            $module_model = new ModuleModel();
            $module_data  = $module_model->where('slug', 'sms_whatapp')->where('status', 1)->first();

            if ($module_data) {
                $module_param_data = json_decode($module_data->params);
                $sms_student_template = $module_param_data->sms_student_template;
                $sms_parent_template  = $module_param_data->sms_parent_template;
            }else{
                $sms_student_template = '';
                $sms_parent_template  = '';
            }

            // Student Message Template
            $student_message = $sms_student_template; 
            $student_placeholders = ['[FIRST_NAME]','[LAST_NAME]','[FULL_NAME]','[STUDENT_ID]','[CLASS_ROLL]','[EXAM]','[SCORE]','[TOTAL_MARK]','[GRADE_POINT]','[LETTER_GRADE]','[SUBJECTS_WITH_VALUE]','[SKILL_WITH_VALUE]'];
            $student_values = [$student_first_name,$student_last_name,$student_full_name,$student_id_number,$academic_roll,$student_exam_title,$percentage.'%',$total_marks,$grade_point,$letter_grade,$subject_output,$skill_output];
            $student_message_rendered = str_replace($student_placeholders, $student_values, $student_message);
            $student_message_total_chars = strlen($student_message_rendered);

            // Parent Message Template
            $parent_message = $sms_parent_template; 
            $parent_placeholders = ['[FIRST_NAME]','[LAST_NAME]','[FULL_NAME]','[STUDENT_ID]','[CLASS_ROLL]','[EXAM]','[SCORE]','[TOTAL_MARK]','[GRADE_POINT]','[LETTER_GRADE]','[SUBJECTS_WITH_VALUE]','[SKILL_WITH_VALUE]'];
            $parent_values = [$student_first_name,$student_last_name,$student_full_name,$student_id_number,$academic_roll,$student_exam_title,$percentage.'%',$total_marks,$grade_point,$letter_grade,$subject_output,$skill_output];
            $parent_message_rendered = str_replace($parent_placeholders, $parent_values, $parent_message);
            $parent_message_total_chars = strlen($parent_message_rendered);

            $html .='<div class="card p-2 mb-2"><div class="body">';
            $html .='<h5>'.$student_full_name.'<br><span class="mark-student-id">'.lang('Mark.field_student_id').': '.$student_id_number.'</span><span class="mark-roll">'.lang('Mark.field_class_roll').': '.$academic_roll.'</span></h5><hr>';
            
            // Message Section
            $html .='<div class="row">';
            $html .='<div id="system_message_'.$academic_id.'" class="col-md-12 ">';
            $html .='</div>'; 
            $html .='</div>';
            
            $html .='<div class="row">';

            if($send_to == 'student' || $send_to =='both'){
                $html .='<div class="col-md-6 ">';
                $html .='<p class="mb-0">'.lang('Sms.sms_student_phone').' : <b class="green">'.$student_phone.'</b><input type="hidden" name="student_phone_'.$academic_id.'" id="student_phone_'.$academic_id.'" value="'.$student_phone.'" /> </p>';
                $html .='<label class="col-form-label col-sm-12 control-label pl-0" for="sms_student_'.$academic_id.'">'.lang('Sms.sms_student_message').' :</label>';
                
                $html .= '<textarea name="sms_student_'.$academic_id.'" cols="50" rows="2" id="sms_student_'.$academic_id.'" class="form-control mb-1" placeholder="'.lang('Sms.sms_student_message').'">'.$student_message_rendered.'</textarea>';
                $html .= '<p class="text-right mb-0">'.lang('Sms.sms_character').': '.$student_message_total_chars.'</p>';
                $html .='</div>';
            }

            if($send_to == 'parent' || $send_to =='both'){
                $html .='<div class="col-md-6 ">';
                if($parent_phone){
                    $html .='<p class="mb-0">'.lang('Sms.sms_parent_phone').' : <b class="green">'.$parent_phone.'</b> <input type="hidden" id="parent_phone_'.$academic_id.'" name="parent_phone_'.$academic_id.'" value="'.$parent_phone.'" /> </p>';
                    $html .='<label class="col-form-label col-sm-12 control-label pl-0" for="sms_parent_'.$academic_id.'">'.lang('Sms.sms_parent_message').' :</label>';
                    
                    $html .= '<textarea name="sms_parent_'.$academic_id.'" cols="50" rows="2" id="sms_parent_'.$academic_id.'" class="form-control mb-1" placeholder="'.lang('Sms.sms_parent_message').'">'.$parent_message_rendered.'</textarea>';
                    $html .= '<p class="text-right mb-0">'.lang('Sms.sms_character').': '.$student_message_total_chars.'</p>';
                }
                $html .='</div>';
            }
           
            $html .='</div>';

            $html .='<div class="row">';
            $html .='<div class="col-md-12 ">';
            $html .='<input type="button" id="button_'.$academic_id.'" class="btn btn-success" value="'.lang('Sms.btn_send_sms').'">';
            $html .='</div>'; 
            $html .='</div>';

            $html .='</div>'; 
            $html .='</div>'; 

        } // End Academic
    }else{
        $html .='<div class="card p-2 mb-2"><div class="body text-center">';
        $html .='<h5 class="text-danger p-4">'.lang('Mark.student_not_found').'</h5>';
        $html .='</div>';
        $html .='</div>';
    }

    echo $html
    ?>

<div class="clearfix"></div>
    
</div>

    
<?php 
if($academic_data){
    $script = '<script type="text/javascript">';
    $script .= 'var csrfToken  = $("#csrf_token").val();';
    $script .= '$.ajaxSetup({ headers: {"X-CSRF-TOKEN": csrfToken}});';
    
    foreach ($academic_data as $key => $academic) {
        $academic_id         = $academic->id;
        $student_id          = $academic->student_id;
        
        $script .='$( "#button_'.$academic_id.'" ).click(function() {';
        $script .= 'var exam_id           = '.$exam.';';
        $script .= 'var session_id        = '.$session.';';
        $script .= 'var academic_id       = '.$academic_id.';';
        $script .= 'var school_id         = '.$school_id.';';
        $script .= 'var student_id        = '.$student_id.';';

        $script .= 'var student_phone     =  $("#student_phone_'.$academic_id.'").val();';
        $script .= 'var parent_phone      =  $("#parent_phone_'.$academic_id.'").val();';
        $script .= 'var sms_student       =  $("#sms_student_'.$academic_id.'").val();';
        $script .= 'var sms_parent        =  $("#sms_parent_'.$academic_id.'").val();';

        $script .= "$('#system_message_".$academic_id."').html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div> <span class=\"loading\"></span></div>');";
        
        $base_url = base_url('/sms_whatapp/send-sms');
        $script .= '
            $.ajax({
                type : "post",
                dataType : "json",
                url : "'.$base_url.'",
                data : {
                    exam_id:exam_id,
                    session_id:session_id,
                    academic_id:academic_id,
                    school_id:school_id,
                    student_id:student_id,
                    student_phone:student_phone,
                    parent_phone:parent_phone,
                    sms_student:sms_student,
                    sms_parent:sms_parent
                },
                headers: {
                    "X-CSRF-TOKEN": csrfToken 
                },
                success: function(response, status, xhr) {
                    csrfToken = xhr.getResponseHeader("X-CSRF-TOKEN");
                    $("#csrf_token").val(csrfToken);
                    if(response.status = true) {
                        $("#system_message_'.$academic_id.'").html(response.html);
                    }
                }
            }) 
        ';
        $script .= '});';
    }

    $script .= '</script>';
    echo $script;


}

?>