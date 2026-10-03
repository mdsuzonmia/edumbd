<?php

if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Shift.page_title_edit');
}else{
    $header_title = lang('Shift.page_title_new');
}

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['id'])
    ? 'school-owner/academics/shifts/store'
    : 'school-owner/academics/shifts/store';

$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$token = isset($token) ? $token : '';

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'shift_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>



<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-clock"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>
        <a href="<?= base_url('/school-owner/academics/shifts') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Shift.back_to_shift') ?>
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
                    <!-- Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Shift.shift_field_label') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- School -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Shift.school_field_label') ?> <span class="text-danger">*</span></label>
                        <select name="school_id" class="form-select" required>
                            <option value=""><?= lang('Shift.select_school') ?></option>
                            <?php foreach ($school_list as $sid => $sname): ?>
                                <option value="<?= $sid ?>" <?= (isset($post_data['school_id']) && $post_data['school_id'] == $sid) ? 'selected' : '' ?>>
                                    <?= esc($sname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <?= $status_list ?>
                    </div>
                </div>

            </div>

            <input type="hidden"
                   name="token"
                   value="<?= esc($token) ?>">

        </div>
    </div>
</div>

<?= form_close() ?>