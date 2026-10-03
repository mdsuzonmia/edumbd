<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Exam.page_title_edit');
}else{
    $header_title = lang('Exam.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['token'])
    ? 'examination/exam-setup/update/'.$post_data['token']
    : 'examination/exam-setup/store';

$year_list = $year_list ?? [];

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'exam_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-journal-text"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/examination/exam-setup') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Exam.back_to') ?>
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
                        <p class="mt-2 text-muted">When you choose a school, Academic Year will be available based on that school.</p>
                    </div>

                    <!-- Academic Year -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="year_id" id="year_id" class="form-select" required>
                            <option value="">Select Academic Year</option>
                            <?php if (!empty($year_list)): ?>
                                <?php foreach ($year_list as $yid => $ytitle): ?>
                                    <option value="<?= $yid ?>" <?= (isset($post_data['year_id']) && $post_data['year_id'] == $yid) ? 'selected' : '' ?>>
                                        <?= esc($ytitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="mt-2 text-muted">If it is not available after selecting a school, you can create it from <a href="<?= base_url('/school-owner/academics/years') ?>">Academic Year</a></p>
                    </div>

                    <!-- Exam Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Exam.exam_field_label') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Exam Short Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Exam Short Name</label>
                        <input type="text"
                               name="exam_short_name"
                               class="form-control"
                               placeholder="e.g. FT, MT, Annual"
                               value="<?= isset($post_data['exam_short_name']) ? esc($post_data['exam_short_name']) : '' ?>">
                        <p class="mt-1 text-muted small">Short name for display in reports (e.g. First Term, Mid Term, Final)</p>
                    </div>

                    <!-- Exam Date -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Exam.exam_date') ?></label>
                        <input type="date"
                               name="exam_date"
                               class="form-control"
                               value="<?= isset($post_data['exam_date']) ? esc($post_data['exam_date']) : '' ?>">
                    </div>

                    <!-- Duration -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Exam.exam_field_duration') ?></label>
                        <div class="input-group">
                            <input type="number"
                                   name="duration"
                                   class="form-control"
                                   min="0"
                                   value="<?= isset($post_data['duration']) ? esc($post_data['duration']) : '' ?>">
                            <span class="input-group-text">minutes</span>
                        </div>
                    </div>

                    <!-- Start Time -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Exam.exam_start_time') ?></label>
                        <?php
                            $start_val = '';
                            if (!empty($post_data['start_time'])) {
                                $start_val = date('H:i', strtotime($post_data['start_time']));
                            }
                        ?>
                        <input type="time"
                               name="start_time"
                               class="form-control"
                               value="<?= $start_val ?>">
                    </div>

                    <!-- End Time -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Exam.exam_end_time') ?></label>
                        <?php
                            $end_val = '';
                            if (!empty($post_data['end_time'])) {
                                $end_val = date('H:i', strtotime($post_data['end_time']));
                            }
                        ?>
                        <input type="time"
                               name="end_time"
                               class="form-control"
                               value="<?= $end_val ?>">
                    </div>

                    <!-- Is Aggregate Result -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Enabled Aggregate Result</label>
                        <select name="is_aggregate_result" id="is_aggregate_result" class="form-select">
                            <option value="1" <?= (isset($post_data['is_aggregate_result']) && $post_data['is_aggregate_result'] == 1) ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (isset($post_data['is_aggregate_result']) && $post_data['is_aggregate_result'] == 0) ? 'selected' : '' ?>>No</option>
                        </select>
                        <p class="mt-1 text-muted small">Enable this exam to be included in Aggregate Result calculation.</p>
                    </div>

                    <!-- Weight Percentage -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Weight Percentage (%)</label>
                        <div class="input-group">
                            <input type="number"
                                   name="weight_percentage"
                                   class="form-control"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   placeholder="e.g. 30, 40, 100"
                                   value="<?= isset($post_data['weight_percentage']) ? esc($post_data['weight_percentage']) : '' ?>">
                            <span class="input-group-text">%</span>
                        </div>
                        <p class="mt-1 text-muted small">
                            Used for <strong>Aggregate Result</strong> calculation. 
                            All exams for a class/session must total 100%. 
                            Example: First Term=30%, Mid Term=30%, Final=40%
                        </p>
                    </div>

                    <!-- Exam Order -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Exam Order</label>
                        <input type="number"
                               name="exam_order"
                               class="form-control"
                               min="0"
                               step="1"
                               placeholder="e.g. 1, 2, 3"
                               value="<?= isset($post_data['exam_order']) ? esc($post_data['exam_order']) : '' ?>">
                        <p class="mt-1 text-muted small">Order in which this exam appears in reports and lists (lower numbers appear first).</p>
                    </div>

                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?= lang('Exam.field_description') ?></label>
                        <textarea name="description"
                                  class="form-control"
                                  rows="3"><?= isset($post_data['description']) ? esc($post_data['description']) : '' ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <?= $status_list ?>
                    </div>

                </div>

            </div>

            <input type="hidden"
                   name="token"
                   value="<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>">

        </div>

    </div>
</div>

<?= form_close() ?>

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = '<?= csrf_hash() ?>';

    // Load academic years when school changes
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        var $yearSelect = $('#year_id');

        if (!schoolId) {
            $yearSelect.html('<option value="">Select Academic Year</option>');
            return;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/exam-setup/get-years-by-school') ?>',
            data: { school_id: schoolId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);

                $yearSelect.html('<option value="">Select Academic Year</option>');
                if (response.status && response.years) {
                    var selectedYear = '<?= isset($post_data['year_id']) ? $post_data['year_id'] : '' ?>';
                    $.each(response.years, function(id, title) {
                        var selected = (id == selectedYear) ? 'selected' : '';
                        $yearSelect.append('<option value="' + id + '" ' + selected + '>' + title + '</option>');
                    });
                }
            }
        });
    });
});
</script>