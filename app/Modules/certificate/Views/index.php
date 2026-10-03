<?php 
use App\Models\SessionModel;
use App\Models\ExamModel;
use App\Models\CategoryModel;
use App\Models\ShiftModel;
use App\Models\DepartmentModel;
use App\Models\ClassModel;
use App\Models\GradeModel;
use App\Models\GroupModel;
use App\Models\SectionModel;
use App\Models\HouseModel;
use App\Models\VersionModel;

// Get school id from session
$school_id      = session()->get('school_id');

// Get Setting Value from school
$student_account      = esc(get_school_value($school_id, 'student_account', true));
$academic_category    = esc(get_school_value($school_id, 'academic_category', true));
$academic_group       = esc(get_school_value($school_id, 'academic_group', true));
$academic_section     = esc(get_school_value($school_id, 'academic_section', true));
$academic_grade       = esc(get_school_value($school_id, 'academic_grade', true));
$academic_shift       = esc(get_school_value($school_id, 'academic_shift', true));
$academic_department  = esc(get_school_value($school_id, 'academic_department', true));
$academic_house       = esc(get_school_value($school_id, 'academic_house', true));
$academic_version     = esc(get_school_value($school_id, 'academic_version', true));

// Get Session list
$session_model = new SessionModel();
$session_list  = get_academic_list(lang('Mark.field_session'), 'session', '', lang('Mark.field_select_session'), $session_model, '', 'required');

// Get Category list
$category_model = new CategoryModel();
$category_list  = get_academic_list(lang('Student.field_category'),'category', '', lang('Student.field_select_category'), $category_model, '', '');

// Get shift list
$shift_model = new ShiftModel();
$shift_list  = get_academic_list(lang('Student.field_shift'), 'shift', '', lang('Student.field_select_shift'), $shift_model, '', '');

// Get department list
$department_model = new DepartmentModel();
$department_list  = get_academic_list(lang('Student.field_department'), 'department', '', lang('Student.field_select_department'), $department_model, '', '');

// Get class list
$class_model = new ClassModel();
$class_list  = get_academic_list(lang('Student.field_class'), 'class', '', lang('Student.field_select_class'), $class_model, '', '');

// Get Grade
$grade_model = new GradeModel();
$grade_list  = get_academic_list(lang('Student.field_grade'), 'grade', '', lang('Student.field_select_grade'), $grade_model, '', '');

// Get group
$group_model = new GroupModel();
$group_list  = get_academic_list(lang('Student.field_group'), 'group', '', lang('Student.field_select_group'), $group_model, '', '');

// Get section
$section_model = new SectionModel();
$section_list  = get_academic_list(lang('Student.field_section'), 'section', '', lang('Student.field_select_section'), $section_model, '', '');

// Get house
$house_model = new HouseModel();
$house_list  = get_academic_list(lang('Student.field_house'), 'house', '', lang('Student.field_select_house'), $house_model, '', '');

// Get version
$version_model = new VersionModel();
$version_list  = get_academic_list(lang('Student.field_version'), 'version', '', lang('Student.field_select_version'), $version_model, '', '');


// Get exam list
$exam_model = new ExamModel();
$exam_list  = get_academic_list(lang('Mark.field_exam'), 'exam', '', lang('Mark.field_select_exam'), $exam_model, '', 'required');

// Get receiver list
$receiver_array = array(
    'student' => lang('Sms.send_to_student'),
    'parent' => lang('Sms.send_to_parent'),
    'both' => lang('Sms.send_to_both')
);
$receiver_list = field_dropdown(lang('Sms.sms_send_to'), 'send_to', 'send_to', '', $receiver_array, '', true);

$role        = session()->get('role_id');
$school_field = get_school_field(lang('School.select_school'), 'school_id', 'field_school', '', '', true);
?>

<div class="right_col" role="main">

    <?= form_open('sms_whatapp', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'search', 
    'method'  => 'get', 
    'data-parsley-validate'=>'' 
    ]); 
    ?>
    <input type="hidden" name="task" value="send-sms" />
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-md-6 col-sm-6 ">
            <h3><?= lang('Sms.page_title'); ?></h3>
        </div>

        <div class="col-md-6 col-sm-6 text-right pt-2">
            <button type="submit" class="btn btn-sm btn-success add"><?= lang('Sms.btn_get_send_sms') ?></button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_content">
                    <div class="col-sm-6">
                        <?= $receiver_list ?>
                        <?= $exam_list ?>
                        <?= $session_list ?>
                        <?= $class_list ?>

                        <div class="row form-group">
                            <label class="col-form-label col-sm-4 control-label" for="field_school"><?= lang('Mark.field_school') ?> *</label>
                            <div class="col-sm-8">
                            <?= $school_field ?>
                            </div>
                        </div>
                       
                    </div>

                    <div class="col-sm-6">
                        <?php 
                        
                        // Display Grade
                        if($academic_grade){
                            echo $grade_list;
                        }

                        // Display Group
                        if($academic_group){
                            echo $group_list;
                        }

                        // Display House
                        if($academic_house){
                            echo $house_list;
                        }

                        // Display Version
                        if($academic_version){
                            echo $version_list;
                        }
                        
                        // Display Section
                        if($academic_section){
                            echo $section_list;
                        }

                        // Display Category
                        if($academic_category){
                            echo $category_list;
                        }

                        // Display Shift
                        if($academic_shift){
                            echo $shift_list;
                        }

                        // Display Department
                        if($academic_department){
                            echo $department_list;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>
    <?= form_close() ?>

</div>

    
    