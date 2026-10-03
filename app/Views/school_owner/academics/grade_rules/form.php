<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('GradeRule.page_title_edit');
}else{
    $header_title = lang('GradeRule.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['id'])
    ? 'school-owner/grade-rules/store'
    : 'school-owner/grade-rules/store';

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'grade_rule_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-list-check"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/school-owner/grade-rules/'.$grade_system_id) ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('GradeRule.back_to') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">

                <?php if(isset($validation)): ?>
                    <div class="alert alert-danger">
                        <?= $validation->listErrors(); ?>
                    </div>
                <?php endif; ?>

                <div class="row">

                    <!-- Grade System -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_grade_system') ?> <span class="text-danger">*</span></label>
                        <select name="grade_system_id" id="field_grade_system_id" class="form-select" required>
                            <option value="">Select Grade System</option>
                            <?php if (!empty($system_list)): ?>
                                <?php foreach ($system_list as $stoken => $stitle): ?>
                                    <option value="<?= $stoken ?>" <?= (isset($grade_system_id) && $grade_system_id == $stoken) ? 'selected' : '' ?>>
                                        <?= esc($stitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_title') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Field Order -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_order') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="field_order"
                               class="form-control"
                               required
                               value="<?= isset($post_data['field_order']) ? esc($post_data['field_order']) : '1' ?>">
                    </div>

                    <!-- Grade Point -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_grade_point') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="grade_point"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['grade_point']) ? esc($post_data['grade_point']) : '' ?>">
                    </div>

                    <!-- Mark From -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_mark_from') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="mark_from"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['mark_from']) ? esc($post_data['mark_from']) : '' ?>">
                    </div>

                    <!-- Mark To -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_mark_to') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="mark_to"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['mark_to']) ? esc($post_data['mark_to']) : '' ?>">
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('GradeRule.field_remarks') ?></label>
                        <textarea name="remarks"
                                  class="form-control"
                                  rows="3"><?= isset($post_data['remarks']) ? esc($post_data['remarks']) : '' ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <?= $status_list ?>
                    </div>

                </div>

            </div>

            <input type="hidden"
                   name="id"
                   value="<?= isset($post_data['id']) ? esc($post_data['id']) : '' ?>">

        </div>

    </div>
</div>

<?= form_close() ?>
