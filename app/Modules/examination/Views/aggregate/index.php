<?php
$csrf_token = csrf_hash();
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-calculator"></i> Aggregate Results</h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/results/generate') ?>" class="btn btn-success">
            <i class="fa fa-cogs"></i> Generate Results
        </a>
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
                            <option value=""><?= lang('Mark.field_select_school'); ?></option>
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
                            <option value=""><?= lang('Mark.field_select_year'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_class_id" class="form-label"><?= lang('Common.field_class'); ?> <span class="required">*</span></label>
                        <select name="class_id" id="field_class_id" class="form-control" required>
                            <option value=""><?= lang('Mark.field_select_class'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_section_id" class="form-label"><?= lang('Common.field_section'); ?></label>
                        <select name="section_id" id="field_section_id" class="form-control">
                            <option value=""><?= lang('Mark.field_select_section'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <strong>How Aggregate Works:</strong> The system calculates weighted aggregate marks using exams that have <strong>weight_percentage</strong> set in Exam Setup.
                            Each exam's subject result is multiplied by its weight percentage, then summed to produce the final aggregate.
                            <br><br>
                            <strong>Formula:</strong> Aggregate = (Exam1 × W1%) + (Exam2 × W2%) + (Exam3 × W3%) ... where W1 + W2 + W3 = 100%
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12 text-end">
                        <button type="button" id="btn_generate_aggregate" class="btn btn-primary btn-lg">
                            <i class="fa fa-calculator"></i> Generate Aggregate Results
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Section -->
<div id="progress_section" class="row" style="display: none;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Aggregate Generation Progress</h5>
                <div id="progress_message" class="mb-3"></div>

                <ul class="list-group" id="steps_list">
                    <li class="list-group-item" id="step_validate">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text">Step 1: Validate exams & weights</span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_subject_aggregate">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text">Step 2: Calculate subject-wise weighted aggregates</span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_positions">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text">Step 3: Assign positions (ranks)</span>
                        <span class="step-status float-end"></span>
                    </li>
                    <li class="list-group-item" id="step_final">
                        <i class="fa fa-circle-o text-muted step-icon"></i>
                        <span class="step-text">Step 4: Save final aggregate results</span>
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
                url: '<?= base_url('examination/aggregate/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                        populateSelect('#field_section_id', response.section_list);

                        if (!response.academic_section_enabled) {
                            $('#field_section_id').closest('.col-md-3').hide();
                        } else {
                            $('#field_section_id').closest('.col-md-3').show();
                        }
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id', '#field_section_id'].forEach(function(sel) {
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

    // Generate Aggregate
    $('#btn_generate_aggregate').on('click', function() {
        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val() || 0;
        var year_id    = $('#field_year_id').val();

        if (!school_id || !class_id || !year_id) {
            alert('Please select all required fields (School, Year, Class).');
            return;
        }

        resetSteps();
        $('#progress_section').show();
        $('#result').html('');

        $('#btn_generate_aggregate').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

        markStepInProgress('step_validate');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/aggregate/generate') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                section_id: section_id,
                year_id: year_id
            },
            success: function(response) {
                if (response.status) {
                    markStepComplete('step_validate', true, 'Exams validated (' + response.exams.length + ' exams, ' + response.total_weight + '%)');
                    markStepComplete('step_subject_aggregate', true, response.processed + ' students processed');
                    markStepComplete('step_positions', true, 'Ranks assigned');
                    markStepComplete('step_final', true, 'Saved to database');

                    // Build exam info
                    var examHtml = '<div class="alert alert-success mt-3">' + response.message + '</div>';
                    examHtml += '<h6>Exams Used:</h6><ul>';
                    $.each(response.exams, function(i, exam) {
                        examHtml += '<li><strong>' + exam.title + '</strong> - Weight: ' + exam.weight + '%</li>';
                    });
                    examHtml += '</ul>';
                    examHtml += '<p><strong>Formula:</strong> Aggregate = ';
                    var formulaParts = [];
                    $.each(response.exams, function(i, exam) {
                        formulaParts.push(exam.title + ' × ' + exam.weight + '%');
                    });
                    examHtml += formulaParts.join(' + ');
                    examHtml += '</p>';

                    $('#progress_message').html(examHtml);
                } else {
                    markStepComplete('step_validate', false, response.message || 'Validation failed');
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
                markStepComplete('step_validate', false, errMsg);
                $('#progress_message').html('<div class="alert alert-danger">' + errMsg + '</div>');
            },
            complete: function() {
                $('#btn_generate_aggregate').prop('disabled', false).html('<i class="fa fa-calculator"></i> Generate Aggregate Results');
            }
        });
    });
});
</script>