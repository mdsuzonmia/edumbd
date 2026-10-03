<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('SubjectDistribution.page_title_edit');
}else{
    $header_title = lang('SubjectDistribution.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$token = $token ?? ($post_data['token'] ?? '');

// Use token for edit action URL
$form_action = !empty($is_edit) && !empty($token)
    ? 'examination/subject-distributions/update/' . $token
    : 'examination/subject-distributions/store';

    
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'subject_distribution_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-diagram-3"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/examination/subject-distributions') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('SubjectDistribution.back_to') ?>
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

                    <!-- Subject -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="field_subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php if (!empty($subject_list)): ?>
                                <?php foreach ($subject_list as $subid => $subtitle): ?>
                                    <option value="<?= $subid ?>" <?= (isset($post_data['subject_id']) && $post_data['subject_id'] == $subid) ? 'selected' : '' ?>>
                                        <?= esc($subtitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Mark Distribution -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mark Distribution <span class="text-danger">*</span></label>
                        <select name="distribution_id" id="field_distribution_id" class="form-control" required>
                            <option value="">Select Mark Distribution</option>
                            <?php if (!empty($mark_distribution_list)): ?>
                                <?php foreach ($mark_distribution_list as $did => $dname): ?>
                                    <option value="<?= $did ?>" <?= (isset($post_data['distribution_id']) && $post_data['distribution_id'] == $did) ? 'selected' : '' ?>>
                                        <?= esc($dname) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Full Mark -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full Mark</label>
                        <input type="number"
                               name="full_mark"
                               id="field_full_mark"
                               class="form-control"
                               step="0.01"
                               min="0"
                               value="<?= isset($post_data['full_mark']) ? $post_data['full_mark'] : 0 ?>">
                    </div>

                    <!-- Pass Mark -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pass Mark</label>
                        <input type="number"
                               name="pass_mark"
                               id="field_pass_mark"
                               class="form-control"
                               step="0.01"
                               min="0"
                               value="<?= isset($post_data['pass_mark']) ? $post_data['pass_mark'] : 0 ?>">
                    </div>

                    <!-- Weight Percent -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Weight Percent</label>
                        <input type="number"
                               name="weight_percent"
                               id="field_weight_percent"
                               class="form-control"
                               step="0.01"
                               min="0"
                               max="100"
                               value="<?= isset($post_data['weight_percent']) ? $post_data['weight_percent'] : 0 ?>">
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
    var csrfToken = '<?= csrf_hash() ?>';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // When school changes, load subjects and mark distributions dynamically
    $('#field_school_id').on('change', function() {
        var schoolId = $(this).val();
        var $subjectSelect = $('#field_subject_id');
        var $distributionSelect = $('#field_distribution_id');

        // Reset dependent dropdowns
        $subjectSelect.html('<option value="">Select Subject</option>');
        $distributionSelect.html('<option value="">Select Mark Distribution</option>');

        if (!schoolId) {
            return;
        }

        // Load Subjects
        $.ajax({
            url: '<?= base_url('examination/subject-distributions/getSubjectsBySchool') ?>',
            type: 'post',
            dataType: 'json',
            data: { school_id: schoolId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);
                if (response.status && response.subjects) {
                    $.each(response.subjects, function(id, title) {
                        $subjectSelect.append('<option value="' + id + '">' + title + '</option>');
                    });
                }
            }
        });

        // Load Mark Distributions
        $.ajax({
            url: '<?= base_url('examination/subject-distributions/getMarkDistributionsBySchool') ?>',
            type: 'post',
            dataType: 'json',
            data: { school_id: schoolId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);
                if (response.status && response.mark_distributions) {
                    $.each(response.mark_distributions, function(id, name) {
                        $distributionSelect.append('<option value="' + id + '">' + name + '</option>');
                    });
                }
            }
        });
    });
});
</script>