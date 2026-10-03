<?php
$csrf_token = csrf_hash();
$postData = isset($post_data) ? $post_data : [];
$isEdit = isset($is_edit) ? $is_edit : false;
$record = isset($record) ? $record : null;
?>

<?= form_open('examination/marks/update/' . ($postData['token'] ?? ''), [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'marks_edit_form',
    'method'  => 'post',
]); ?>

<input type="hidden" name="token" value="<?= $postData['token'] ?? '' ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-pencil-square"></i> <?= lang('Mark.page_title_edit'); ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/marks') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Mark.back_to'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>
                <div id="result"></div>
                <div id="lock_message" style="display: none;"></div>

                <?php if (isset($is_locked) && $is_locked && isset($lock_info)): ?>
                <div class="alert alert-warning">
                    <strong>Locked:</strong> Subject marks are locked for the exam 
                    <strong><?= esc($lock_info->exam_title ?? 'this exam') ?></strong>, 
                    session <strong><?= esc($lock_info->session_name ?? 'this session') ?></strong>, 
                    class <strong><?= esc($lock_info->class_name ?? 'this class') ?></strong>. 
                    Locked by: <?= esc($lock_info->locked_by_name ?? 'Unknown') ?> at 
                    <?= esc($lock_info->locked_at ?? 'Unknown') ?>
                </div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Mark.field_student_name'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['student_name'] ?? '') ?></strong></p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Mark.field_school'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['school_name'] ?? 'N/A') ?></strong></p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Common.field_class'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['class_name'] ?? 'N/A') ?></strong></p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Mark.field_exam'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['exam_title'] ?? 'N/A') ?></strong></p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Mark.field_subject'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['subject_title'] ?? 'N/A') ?></strong></p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?= lang('Mark.field_year'); ?></label>
                        <p class="form-control-static"><strong><?= esc($postData['session_name'] ?? 'N/A') ?></strong></p>
                    </div>

                    <!-- Hidden fields to preserve data -->
                    <input type="hidden" name="exam_id" value="<?= $postData['exam_id'] ?? '' ?>">
                    <input type="hidden" name="subject_id" value="<?= $postData['subject_id'] ?? '' ?>">
                    <input type="hidden" name="full_mark" value="<?= $postData['full_mark'] ?? 0 ?>">

                    <div class="col-md-3">
                        <label for="field_obtained_mark" class="form-label"><?= lang('Mark.field_marks'); ?> (Obtained) <span class="required">*</span></label>
                        <input type="number" step="any" min="0" name="obtained_mark" id="field_obtained_mark" 
                               class="form-control" required
                               value="<?= $postData['obtained_mark'] ?? 0 ?>"
                               <?= (isset($is_locked) && $is_locked) ? 'disabled' : '' ?>>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label"><?= lang('Common.field_full_mark'); ?></label>
                        <p class="form-control-static"><strong><?= $postData['full_mark'] ?? 0 ?></strong></p>
                    </div>

                    <div class="col-md-3">
                        <label for="field_is_absent" class="form-label"><?= lang('Mark.field_absent'); ?></label>
                        <div class="form-check mt-2">
                            <input type="checkbox" name="is_absent" id="field_is_absent" value="1" 
                                   class="form-check-input"
                                   <?= !empty($postData['is_absent']) ? 'checked' : '' ?>
                                   <?= (isset($is_locked) && $is_locked) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="field_is_absent"><?= lang('Mark.field_absent'); ?></label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="field_remarks" class="form-label"><?= lang('Common.field_remarks'); ?></label>
                        <textarea name="remarks" id="field_remarks" class="form-control" rows="2"
                                  <?= (isset($is_locked) && $is_locked) ? 'disabled' : '' ?>><?= $postData['remarks'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12 ">
                        <button type="submit" id="submit_btn" class="btn btn-success"
                                <?= (isset($is_locked) && $is_locked) ? 'disabled' : '' ?>>
                            <i class="fa fa-save"></i> <?= lang('Common.btn_update'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

