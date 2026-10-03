<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('MarkDistribution.page_title_edit');
}else{
    $header_title = lang('MarkDistribution.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$token = $token ?? ($post_data['token'] ?? '');

// Use token for edit action URL
$form_action = !empty($is_edit) && !empty($token)
    ? 'examination/mark-distributions/update/' . $token
    : 'examination/mark-distributions/store';

    
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'mark_distribution_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-bar-chart"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/examination/mark-distributions') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('MarkDistribution.back_to') ?>
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
                        <select name="school_id" id="field_school_id" class="form-control" required>
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

                    <!-- Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('MarkDistribution.field_label_name') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="field_name"
                               class="form-control"
                               required
                               maxlength="100"
                               value="<?= isset($post_data['name']) ? esc($post_data['name']) : '' ?>">
                    </div>

                    <!-- Code -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('MarkDistribution.field_label_code') ?></label>
                        <input type="text"
                               name="code"
                               id="field_code"
                               class="form-control"
                               maxlength="50"
                               value="<?= isset($post_data['code']) ? esc($post_data['code']) : '' ?>">
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number"
                               name="sort_order"
                               id="field_sort_order"
                               class="form-control"
                               min="0"
                               value="<?= isset($post_data['sort_order']) ? $post_data['sort_order'] : 0 ?>">
                    </div>

                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?= lang('MarkDistribution.field_label_description') ?></label>
                        <textarea name="description"
                                  id="field_description"
                                  class="form-control"
                                  maxlength="255"><?= isset($post_data['description']) ? esc($post_data['description']) : '' ?></textarea>
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


<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });
});
</script>