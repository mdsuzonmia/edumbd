<?php 
use App\Models\SessionModel;
use App\Models\ExamModel;

// Get Session list
$session_model = new SessionModel();
$session_list  = get_academic_list(lang('Mark.field_session'), 'session', '', lang('Mark.field_select_session'), $session_model, '', true);

// Get exam list
$exam_model = new ExamModel();
$exam_list  = get_academic_list(lang('Mark.field_exam'), 'exam', '', lang('Mark.field_select_exam'), $exam_model, '', true);

$role        = session()->get('role_id');
$school_field = get_school_field(lang('School.select_school'), 'school_id', 'field_school', '', '', true);

?>

<!-- page content -->
<div class="right_col" role="main">

    <?= form_open('certificate', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'certificate', 
    'method'  => 'get', 
    'data-parsley-validate'=>'' 
    ]); 
    ?>
    <input type="hidden" name="task" value="certificate" />
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-md-6 col-sm-6 ">
            <h3><i class="fa fa-certificate"></i> <?= lang('Certificate.heading_form'); ?></h3>
        </div>

        <div class="col-md-6 col-sm-6 text-right pt-2">
            <button type="submit" class="btn btn-sm btn-success add"><i class="fa fa-file-text"></i> <?= lang('Certificate.btn_get_certificate') ?></button>
        </div>
    </div>

    <div class="clearfix"></div>
    
    
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_content">
                    <div class="col-sm-6">
                        <?= field_text(lang('Certificate.student_id'), 'student_id', 'student_id', '', '', false); ?>
                        <?= $exam_list ?>
                        <?= $session_list ?>

                        <?php if($role == 1): // Access Only Supper Admin ?>
                            <div class="row form-group">
                                <label class="col-form-label col-sm-4 control-label" for="field_school"><?= lang('Mark.field_school') ?> *</label>
                                <div class="col-sm-8">
                                <?= $school_field ?>
                                </div>
                            </div>
                        <?php else: $school_id    = session()->get('school_id'); ?>
                            <input type="hidden" id="field_school" name="school_id" value="<?= $school_id ?>">
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>
    <?= form_close() ?>
</div>
<!-- /page content -->
