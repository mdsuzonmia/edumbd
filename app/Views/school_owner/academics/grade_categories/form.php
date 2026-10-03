<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Gradingcat.page_title_edit');
}else{
    $header_title = lang('Gradingcat.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['id'])
    ? 'school-owner/academics/grade-categories/store'
    : 'school-owner/academics/grade-categories/store';

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'grade_category_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-tags"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/school-owner/academics/grade-categories') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Gradingcat.back_to') ?>
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

                    <!-- School -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">School <span class="text-danger">*</span></label>
                        <select name="school_id" class="form-select" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>" <?= (isset($post_data['school_id']) && $post_data['school_id'] == $sid) ? 'selected' : '' ?>>
                                        <?= esc($sname) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Gradingcat.field_title') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Mark -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Gradingcat.mark') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="mark"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['mark']) ? esc($post_data['mark']) : '' ?>">
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