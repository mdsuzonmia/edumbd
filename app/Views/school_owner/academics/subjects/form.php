<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Subject.page_title_edit');
}else{
    $header_title = lang('Subject.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['id'])
    ? 'school-owner/academics/subjects/store'
    : 'school-owner/academics/subjects/store';

$mark_distribution_list = $mark_distribution_list ?? [];
$grading_list           = $grading_list ?? [];

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'subject_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-book"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/school-owner/academics/subjects') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Subject.back_to') ?>
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
                        <select name="school_id" id="school_id" class="form-select" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>" <?= (isset($post_data['school_id']) && $post_data['school_id'] == $sid) ? 'selected' : '' ?>>
                                        <?= esc($sname) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <!-- Note -->
                        <p class="mt-2 text-muted"> When you choose a school, Grading and Mark Distribution will be available based on that school. </p>
                    </div>

                    <!-- Subject Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_subject') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Short Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_short_title') ?></label>
                        <input type="text"
                               name="short_title"
                               class="form-control"
                               value="<?= isset($post_data['short_title']) ? esc($post_data['short_title']) : '' ?>">
                    </div>

                    <!-- Subject Code -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_subject_code') ?></label>
                        <input type="text"
                               name="subject_code"
                               class="form-control"
                               value="<?= isset($post_data['subject_code']) ? esc($post_data['subject_code']) : '' ?>">
                    </div>

                    <!-- Mark Distribution (multi-select) -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_mark_distribution') ?></label>
                        <?php
                            $selected_md = [];
                            if (!empty($post_data['mark_distribution'])) {
                                $raw = $post_data['mark_distribution'];
                                $selected_md = is_string($raw) ? (json_decode($raw, true) ?? [$raw]) : (array) $raw;
                            }
                        ?>
                        <select name="mark_distribution[]" id="mark_distribution" class="form-select" multiple size="5">
                            <?php if (!empty($mark_distribution_list)): ?>
                                <?php foreach ($mark_distribution_list as $mdid => $mdtitle): ?>
                                    <option value="<?= $mdid ?>" <?= in_array((string) $mdid, array_map('strval', $selected_md), true) ? 'selected' : '' ?>>
                                        <?= esc($mdtitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple. </small>
                        <p class="mt-1 text-muted">If it is not available after selecting a school, you can create it from <a href="<?= base_url('/school-owner/academics/marks-distribution') ?>">Mark Distribution</a></p>
                    </div>

                    <!-- Grade System -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_grade_system') ?> <span class="text-danger">*</span></label>
                        <select name="grade_system" id="grade_system" class="form-select" required>
                            <option value=""><?= lang('Subject.sys_select_grade_system') ?></option>
                            <?php if (!empty($grading_list)): ?>
                                <?php foreach ($grading_list as $gid => $gtitle): ?>
                                    <option value="<?= $gid ?>" <?= (isset($post_data['grade_system']) && $post_data['grade_system'] == $gid) ? 'selected' : '' ?>>
                                        <?= esc($gtitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="mt-2 text-muted"> if it is not available after selecting a school, you can create it from <a href="<?= base_url('/school-owner/academics/grade-categories') ?>">Grading System</a> </p>
                    </div>

                    <!-- Status -->
                    <div class="col-md-4 mb-3">
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

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = '<?= csrf_hash() ?>';

    // Load mark distributions and grading systems when school changes
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        var $markDistSelect = $('#mark_distribution');
        var $gradeSystemSelect = $('#grade_system');

        if (!schoolId) {
            $markDistSelect.html('<option value="">Select Mark Distribution</option>');
            $gradeSystemSelect.html('<option value=""><?= lang('Subject.sys_select_grade_system') ?></option>');
            return;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('school-owner/academics/subjects/getDropdownsBySchool') ?>',
            data: { school_id: schoolId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);

                $markDistSelect.empty();
                $gradeSystemSelect.html('<option value=""><?= lang('Subject.sys_select_grade_system') ?></option>');

                if (response.status) {
                    if (response.mark_distributions) {
                        var selectedMd = <?= json_encode($selected_md ?? []) ?>;
                        $.each(response.mark_distributions, function(id, title) {
                            var isSelected = (selectedMd.indexOf(String(id)) !== -1) ? 'selected' : '';
                            $markDistSelect.append('<option value="' + id + '" ' + isSelected + '>' + title + '</option>');
                        });
                    }
                    if (response.grading_systems) {
                        var selectedGs = '<?= isset($post_data['grade_system']) ? $post_data['grade_system'] : '' ?>';
                        $.each(response.grading_systems, function(id, title) {
                            var selected = (id == selectedGs) ? 'selected' : '';
                            $gradeSystemSelect.append('<option value="' + id + '" ' + selected + '>' + title + '</option>');
                        });
                    }
                }
            }
        });
    });
});
</script>