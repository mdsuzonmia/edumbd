<?php 
$class_roll             = esc(get_setting_value('class_roll'));
$academic_shift         = esc(get_setting_value('academic_shift'));
$academic_department    = esc(get_setting_value('academic_department'));
$academic_category      = esc(get_setting_value('academic_category'));
$extra_skill            = esc(get_setting_value('extra_skill'));
$setting_year_id        = esc(get_setting_value('year'));
$student_account        = esc(get_setting_value('student_account'));


// Academic Data
if($academic_data){
    $academic_id    = $academic_data->id;
    $class_id       = $academic_data->class_id;
    $department_id  = $academic_data->department_id;
    $category_id    = $academic_data->category_id;
    $shift_id       = $academic_data->shift_id;
    $roll           = $academic_data->roll;
    $year_id        = $academic_data->year_id;
    $subject_value    = explode(',', $academic_data->subject_ids);
    $skill_value      = explode(',', $academic_data->skill_ids);
}else{
    $academic_id    = '';
    $class_id       = '';
    $department_id  = '';
    $category_id    = '';
    $shift_id       = '';
    $roll           = '';
    $year_id        = '';
    $subject_value    = [];
    $skill_value      = [];
}


// Get year list
use App\Models\YearModel;
$year_model = new YearModel();
$year_value = isset($post_data['year']) ? $post_data['year']: $year_id;
$year_list = get_academic_list(lang('Student.field_year'), 'year', $year_value, lang('Student.field_select_year'), $year_model, '', 'required');

// Get Category list
use App\Models\CategoryModel;
$category_model = new CategoryModel();
$category_value = isset($post_data['category']) ? $post_data['category']: $category_id;
$category_list = get_academic_list(lang('Student.field_category'),'category', $category_value, lang('Student.field_select_category'), $category_model, '', 'required');

// Get shift list
use App\Models\ShiftModel;
$shift_model = new ShiftModel();
$shift_value = isset($post_data['shift']) ? $post_data['shift']: $shift_id;
$shift_list = get_academic_list(lang('Student.field_shift'), 'shift', $shift_value, lang('Student.field_select_shift'), $shift_model, '', 'required');

// Get department list
use App\Models\DepartmentModel;
$department_model = new DepartmentModel();
$department_value = isset($post_data['department']) ? $post_data['department']: $department_id;
$department_list = get_academic_list(lang('Student.field_department'), 'department', $department_value, lang('Student.field_select_department'), $department_model, '', 'required');

// Get class list
use App\Models\ClassModel;
$class_model = new ClassModel();
$class_value = isset($post_data['class']) ? $post_data['class']: $class_id;
$class_list = get_academic_list(lang('Student.field_class'), 'class', $class_value, lang('Student.field_select_class'), $class_model, '', 'required');

// Get subject
use App\Models\SubjectModel;
$subject_model = new SubjectModel();
$subject_value = isset($post_data['subject']) ? $post_data['subject']: $subject_value;
$subject_field = get_academic_list(lang('Student.field_subject'), 'subject', $subject_value, '', $subject_model, true, 'required');

// Get Skill
use App\Models\SkillModel;
$skill_model = new SkillModel();
$skill_value = isset($post_data['skill']) ? $post_data['skill']: $skill_value;
$skill_field = get_academic_list(lang('Student.field_skill'), 'skill', $skill_value, '', $skill_model, true);


?>
<input type="hidden" name="academic_id" value="<?= $academic_id ?>" />




<div id="academic_preview" class="row " style="display: none;">
    <div class="col-sm-6">
        <p><label><?= lang('Student.field_year') ?></label>: <b id="academic_preview_year"></b></p>
        <p><label><?= lang('Student.field_class') ?></label>: <b id="academic_preview_class"></b></p>
        <p><label><?= lang('Student.field_subject') ?></label>: <b id="academic_preview_subject"></b></p>
        <?php 
        // Display Category
        if($academic_category){
            echo '<p><label>'.lang('Student.field_category').'</label>: <b id="profile_preview_category"></b></p>';
        }

        // Display Shift
        if($academic_shift){
            echo '<p><label>'.lang('Student.field_shift').'</label>: <b id="profile_preview_shift"></b></p>';
        }

        // Display Department
        if($academic_department){
            echo '<p><label>'.lang('Student.field_department').'</label>: <b id="profile_preview_department"></b></p>';
        }

        // Display Extra Skill
        if($extra_skill){
            echo '<p><label>'.lang('Student.field_skill').'</label>: <b id="profile_preview_skill"></b></p>';
        }
        
        ?>
    </div>

    <div class="col-sm-6">
        <?php 
        if($student_account):
        ?>
        <h2><?= lang('Student.cap_accounting'); ?></h2>
        <p><label><?= lang('Student.username') ?></label>: <b id="academic_preview_username"></b></p>
        <p><label><?= lang('Student.field_email') ?></label>: <b id="academic_preview_email"></b></p>
        <?php endif; ?>
    </div>
</div>

<div id="academic_form" class="row ">
    <div class="col-sm-6">
        <?php

        // Academic Data
        $academic_html = '';
        if(empty($setting_year_id)){
            $academic_html .= '<p style="color: red;">'.lang('Student.not_set_default_year').'</p>';
        }
        
        $academic_html .= $year_list;
        $academic_html .= $class_list;
        $academic_html .= $subject_field;

        // Display Category
        if($academic_category){
            $academic_html .= $category_list;
        }

        // Display Shift
        if($academic_shift){
            $academic_html .= $shift_list;
        }

        // Display Department
        if($academic_department){
            $academic_html .= $department_list;
        }

        // Display Extra Skill
        if($extra_skill){
            $academic_html .= $skill_field;
        }

        echo $academic_html;
        ?>
        
    </div>
    <div class="col-sm-6">
        <?php 
        if($student_account):
        ?>
            <h2><?= lang('Student.cap_accounting'); ?></h2>
            <?= view('student/form/account') ?>
        <?php endif; ?>
    </div>
</div>