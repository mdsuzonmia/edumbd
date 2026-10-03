<?php
$csrf_token = csrf_hash();
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-check"></i> <?= lang('ResultGenerator.heading_generate'); ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/result-publish') ?>" class="btn btn-info">
            <i class="fa fa-globe"></i> Publish Results
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>
                <div id="result"></div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="field_school_id" class="form-label"><?= lang('Common.field_school'); ?> <span class="required">*</span></label>
                        <select name="school_id" id="field_school_id" class="form-control" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>"><?= esc($sname) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_year_id" class="form-label"><?= lang('Common.field_year'); ?> <span class="required">*</span></label>
                        <select name="year_id" id="field_year_id" class="form-control" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="field_exam_id" class="form-control" required>
                            <option value="">Select Exam</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_class_id" class="form-label"><?= lang('Common.field_class'); ?> <span class="required">*</span></label>
                        <select name="class_id" id="field_class_id" class="form-control" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12 text-end">
                        <button type="button" id="btn_generate" class="btn btn-success btn-lg me-2">
                            <i class="fa fa-cogs"></i> <?= lang('ResultGenerator.btn_generate'); ?>
                        </button>
                        <button type="button" id="btn_recalculate" class="btn btn-warning btn-lg">
                            <i class="fa fa-refresh"></i> <?= lang('ResultGenerator.btn_recalculate'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Steps -->
<div id="progress_section" class="row" style="display: none;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= lang('ResultGenerator.heading_generate'); ?> - Progress</h5>
                <div id="progress_message" class="mb-3"></div>

                <ul class="list-group" id="steps_list">
                    <li class="list-group-item" id="step_validate">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text"><?= lang('ResultGenerator.step_validate'); ?></span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_subject_results">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text"><?= lang('ResultGenerator.step_subject_results'); ?></span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_exam_results">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text"><?= lang('ResultGenerator.step_exam_results'); ?></span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_positions">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text"><?= lang('ResultGenerator.step_positions'); ?></span>
                        <span class="step-status float-end"></span>
                    </li>
                    
                    <li class="list-group-item" id="step_publish">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text"><?= lang('ResultGenerator.step_publish'); ?></span>
                        <span class="step-status float-end"></span>
                    </li>
                </ul>

                <div id="result_summary" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = '<?= $csrf_token ?>';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Load academic data when school changes
    $('#field_school_id').on('change', function() {
        var school_id = $(this).val();
        if (school_id) {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/results/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                        populateSelect('#field_exam_id', response.exam_list);
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id', '#field_exam_id'].forEach(function(sel) {
                $(sel).html('<option value="">Select...</option>');
            });
        }
    });

    function populateSelect(selector, data) {
        var $sel = $(selector);
        $sel.html('<option value="">Select...</option>');
        if (data) {
            $.each(data, function(key, value) {
                $sel.append('<option value="' + key + '">' + value + '</option>');
            });
        }
    }

    function resetSteps() {
        $('#steps_list .step-icon').removeClass('fa-check-circle fa-times-circle fa-spinner fa-spin').addClass('fa-circle-o text-muted');
        $('#steps_list .step-status').html('');
        $('#steps_list .list-group-item').removeClass('list-group-item-success list-group-item-danger list-group-item-warning');
        $('#result_summary').hide().html('');
        $('#progress_message').html('');
    }

    function markStepInProgress(stepId) {
        var $li = $('#' + stepId);
        $li.removeClass('list-group-item-success list-group-item-danger').addClass('list-group-item-warning');
        $li.find('.step-icon').removeClass('fa-circle-o fa-check-circle fa-times-circle').addClass('fa-spinner fa-spin text-warning');
        $li.find('.step-status').html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    }

    function markStepComplete(stepId, success, message) {
        var $li = $('#' + stepId);
        if (success) {
            $li.removeClass('list-group-item-warning list-group-item-danger').addClass('list-group-item-success');
            $li.find('.step-icon').removeClass('fa-circle-o fa-spinner fa-spin').addClass('fa-check-circle text-success');
            $li.find('.step-status').html('<span class="text-success"><i class="fa fa-check"></i> ' + (message || 'Done') + '</span>');
        } else {
            $li.removeClass('list-group-item-warning list-group-item-success').addClass('list-group-item-danger');
            $li.find('.step-icon').removeClass('fa-circle-o fa-spinner fa-spin').addClass('fa-times-circle text-danger');
            $li.find('.step-status').html('<span class="text-danger"><i class="fa fa-times"></i> ' + (message || 'Failed') + '</span>');
        }
    }

    function performGenerate(actionType) {
        var school_id  = $('#field_school_id').val();
        var exam_id    = $('#field_exam_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = 0;
        var year_id    = $('#field_year_id').val();

        if (!school_id || !exam_id || !class_id || !year_id) {
            alert('Please select all required fields (School, Year, Exam, Class).');
            return;
        }

        resetSteps();
        $('#progress_section').show();
        $('#result').html('');

        var btn = (actionType === 'generate') ? '#btn_generate' : '#btn_recalculate';
        var btnText = (actionType === 'generate')
            ? '<?= lang('ResultGenerator.generating'); ?>'
            : '<?= lang('ResultGenerator.recalculating'); ?>';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + btnText + '...');

        var url = (actionType === 'generate')
            ? '<?= base_url('examination/results/generate') ?>'
            : '<?= base_url('examination/results/recalculate') ?>';

        // Start showing progress steps
        if (actionType === 'generate') {
            markStepInProgress('step_validate');
        } else {
            markStepInProgress('step_subject_results');
        }

        $.ajax({
            type: "post", dataType: "json",
            url: url,
            data: {
                school_id: school_id,
                exam_id: exam_id,
                class_id: class_id,
                section_id: section_id,
                year_id: year_id
            },
            success: function(response) {
                if (response.status && response.steps_completed) {
                    var steps = response.steps_completed;
                    
                    if (steps.indexOf('validate') !== -1) {
                        markStepComplete('step_validate', true, 'Validated');
                    } else if (actionType === 'recalculate') {
                        markStepComplete('step_validate', true, 'Skipped');
                    }
                    
                    if (steps.indexOf('subject_results') !== -1) {
                        var msg = response.subject_results ? response.subject_results + ' records' : 'Done';
                        markStepComplete('step_subject_results', true, msg);
                    }
                    if (steps.indexOf('exam_results') !== -1) {
                        var msg = response.exam_results ? response.exam_results + ' students' : 'Done';
                        markStepComplete('step_exam_results', true, msg);
                    }
                    if (steps.indexOf('positions') !== -1) {
                        markStepComplete('step_positions', true, 'Ranks assigned');
                    }
                    
                    if (steps.indexOf('publish') !== -1) {
                        markStepComplete('step_publish', true, 'Ready to publish');
                    }

                    $('#progress_message').html('<div class="alert alert-success">' + response.message + '</div>');
                } else if (response.status) {
                    markStepComplete('step_subject_results', true, 'Done');
                    markStepComplete('step_exam_results', true, 'Done');
                    markStepComplete('step_positions', true, 'Done');
                    markStepComplete('step_publish', true, 'Ready');
                    $('#progress_message').html('<div class="alert alert-success">' + response.message + '</div>');
                } else {
                    if (actionType === 'generate') {
                        markStepComplete('step_validate', false, response.message || 'Validation failed');
                    } else {
                        markStepComplete('step_subject_results', false, response.message || 'Error');
                    }
                    $('#progress_message').html('<div class="alert alert-danger">' + (response.message || 'Unknown error') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                var errMsg = 'Error: ';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    errMsg += resp.message || error;
                } catch(e) {
                    errMsg += error || 'Unknown error';
                }
                if (actionType === 'generate') {
                    markStepComplete('step_validate', false, errMsg);
                } else {
                    markStepComplete('step_subject_results', false, errMsg);
                }
                $('#progress_message').html('<div class="alert alert-danger">' + errMsg + '</div>');
            },
            complete: function() {
                $(btn).prop('disabled', false).html(
                    (actionType === 'generate')
                        ? '<i class="fa fa-cogs"></i> <?= lang('ResultGenerator.btn_generate'); ?>'
                        : '<i class="fa fa-refresh"></i> <?= lang('ResultGenerator.btn_recalculate'); ?>'
                );
            }
        });
    }

    $('#btn_generate').on('click', function() {
        performGenerate('generate');
    });

    $('#btn_recalculate').on('click', function() {
        performGenerate('recalculate');
    });
});
</script>