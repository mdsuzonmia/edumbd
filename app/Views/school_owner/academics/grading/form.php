<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Grading.page_title_edit');
}else{
    $header_title = lang('Grading.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['id'])
    ? 'school-owner/academics/grades/store'
    : 'school-owner/academics/grades/store';

$category_list = $category_list ?? [];

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'grade_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-award"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/school-owner/academics/grades') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Grading.back_to') ?>
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
                    </div>

                    <!-- Category -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Grading.field_category') ?> <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php if (!empty($category_list)): ?>
                                <?php foreach ($category_list as $cid => $ctitle): ?>
                                    <option value="<?= $cid ?>" <?= (isset($post_data['category_id']) && $post_data['category_id'] == $cid) ? 'selected' : '' ?>>
                                        <?= esc($ctitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Letter Grade -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Grading.field_title') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Grade Point -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><?= lang('Grading.field_grade_point') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="grade_point"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['grade_point']) ? esc($post_data['grade_point']) : '' ?>">
                    </div>

                    <!-- Point From -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?= lang('Grading.field_point_from') ?></label>
                        <input type="number"
                               name="point_from"
                               class="form-control"
                               step="0.01"
                               value="<?= isset($post_data['point_from']) ? esc($post_data['point_from']) : '' ?>">
                    </div>

                    <!-- Point To -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?= lang('Grading.field_point_to') ?></label>
                        <input type="number"
                               name="point_to"
                               class="form-control"
                               step="0.01"
                               value="<?= isset($post_data['point_to']) ? esc($post_data['point_to']) : '' ?>">
                    </div>

                    <!-- Mark From -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?= lang('Grading.field_mark_from') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="mark_from"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['mark_from']) ? esc($post_data['mark_from']) : '' ?>">
                    </div>

                    <!-- Mark To -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?= lang('Grading.field_mark_to') ?> <span class="text-danger">*</span></label>
                        <input type="number"
                               name="mark_upto"
                               class="form-control"
                               step="0.01"
                               required
                               value="<?= isset($post_data['mark_upto']) ? esc($post_data['mark_upto']) : '' ?>">
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Grading.field_remarks') ?></label>
                        <textarea name="remarks"
                                  class="form-control"
                                  rows="2"><?= isset($post_data['remarks']) ? esc($post_data['remarks']) : '' ?></textarea>
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

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = '<?= csrf_hash() ?>';

    // Load categories when school changes
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        var $categorySelect = $('#category_id');

        if (!schoolId) {
            $categorySelect.html('<option value="">Select Category</option>');
            return;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('school-owner/academics/grades/getCategoriesBySchool') ?>',
            data: { school_id: schoolId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);

                $categorySelect.html('<option value="">Select Category</option>');
                if (response.status && response.categories) {
                    var selectedCat = '<?= isset($post_data['category_id']) ? $post_data['category_id'] : '' ?>';
                    $.each(response.categories, function(id, title) {
                        var selected = (id == selectedCat) ? 'selected' : '';
                        $categorySelect.append('<option value="' + id + '" ' + selected + '>' + title + '</option>');
                    });
                }
            }
        });
    });
});
</script>
