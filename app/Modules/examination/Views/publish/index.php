<?php
$csrf_token = csrf_hash();
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-globe"></i> <?= lang('ResultPublish.heading_publish'); ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/results/generate') ?>" class="btn btn-success">
            <i class="fa fa-cogs"></i> <?= lang('ResultGenerator.btn_generate'); ?>
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
                    <div class="col-md-2">
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

                    <div class="col-md-2">
                        <label for="field_year_id" class="form-label"><?= lang('Common.field_year'); ?> <span class="required">*</span></label>
                        <select name="year_id" id="field_year_id" class="form-control" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="field_exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="field_exam_id" class="form-control" required>
                            <option value="">Select Exam</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="field_class_id" class="form-label"><?= lang('Common.field_class'); ?> <span class="required">*</span></label>
                        <select name="class_id" id="field_class_id" class="form-control" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="field_section_id" class="form-label"><?= lang('Common.field_section'); ?></label>
                        <select name="section_id" id="field_section_id" class="form-control">
                            <option value="">Select Section</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="btn_check_status" class="btn btn-primary w-100">
                            <i class="fa fa-search"></i> Check Status
                        </button>
                    </div>
                </div>

                <!-- Status Display -->
                <div id="status_section" class="row mt-4" style="display: none;">
                    <div class="col-md-12">
                        <div class="card <?= session('status_color') ?? 'card-secondary' ?>">
                            <div class="card-body">
                                <div id="status_content">
                                    <h5 id="status_title" class="card-title">Status</h5>
                                    <p id="status_details"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-4" id="action_section" style="display: none;">
                    <div class="col-md-12 text-end">
                        <button type="button" id="btn_publish" class="btn btn-success btn-lg me-2">
                            <i class="fa fa-check-circle"></i> <?= lang('ResultPublish.btn_publish'); ?>
                        </button>
                        <button type="button" id="btn_unpublish" class="btn btn-danger btn-lg">
                            <i class="fa fa-times-circle"></i> <?= lang('ResultPublish.btn_unpublish'); ?>
                        </button>
                    </div>
                </div>
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
                url: '<?= base_url('examination/result-publish/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                        populateSelect('#field_section_id', response.section_list);
                        populateSelect('#field_exam_id', response.exam_list);

                        if (!response.academic_section_enabled) {
                            $('#field_section_id').closest('.col-md-2').hide();
                        } else {
                            $('#field_section_id').closest('.col-md-2').show();
                        }
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id', '#field_section_id', '#field_exam_id'].forEach(function(sel) {
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

    function resetDisplay() {
        $('#status_section').hide();
        $('#action_section').hide();
        $('#result').html('');
    }

    // Check status
    $('#btn_check_status').on('click', function() {
        var school_id  = $('#field_school_id').val();
        var exam_id    = $('#field_exam_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val() || 0;
        var year_id    = $('#field_year_id').val();

        if (!school_id || !exam_id || !class_id || !year_id) {
            alert('Please select all required fields (School, Year, Exam, Class).');
            return;
        }

        resetDisplay();
        $('#btn_check_status').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Checking...');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/result-publish/checkStatus') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                class_id: class_id,
                section_id: section_id
            },
            success: function(response) {
                if (response.status && response.has_results) {
                    $('#status_section').show();
                    $('#action_section').show();

                    var totalStudents = response.total_students || 0;
                    
                    if (response.is_published) {
                        var publishedDate = response.published_at ? new Date(response.published_at).toLocaleString() : 'Unknown';
                        $('#status_section .card').removeClass('card-secondary card-danger').addClass('card-success');
                        $('#status_title').html('<i class="fa fa-check-circle text-success"></i> Published');
                        $('#status_details').html(
                            '<strong>Status:</strong> Published<br>' +
                            '<strong>Total Students:</strong> ' + totalStudents + '<br>' +
                            '<strong>Published At:</strong> ' + publishedDate
                        );
                        $('#btn_publish').hide();
                        $('#btn_unpublish').show();
                    } else {
                        $('#status_section .card').removeClass('card-success card-danger').addClass('card-warning');
                        $('#status_title').html('<i class="fa fa-clock-o text-warning"></i> Not Published');
                        $('#status_details').html(
                            '<strong>Status:</strong> Generated but not published<br>' +
                            '<strong>Total Students:</strong> ' + totalStudents
                        );
                        $('#btn_publish').show();
                        $('#btn_unpublish').hide();
                    }
                } else {
                    $('#status_section').show();
                    $('#action_section').hide();
                    $('#status_section .card').removeClass('card-success card-warning').addClass('card-danger');
                    $('#status_title').html('<i class="fa fa-exclamation-triangle text-danger"></i> No Results');
                    $('#status_details').html(response.message || 'No results found for the selected criteria.');
                }
            },
            error: function(xhr, status, error) {
                var errMsg = 'Error: ' + (xhr.responseJSON?.message || error || 'Unknown error');
                $('#result').html('<div class="alert alert-danger">' + errMsg + '</div>');
            },
            complete: function() {
                $('#btn_check_status').prop('disabled', false).html('<i class="fa fa-search"></i> Check Status');
            }
        });
    });

    // Publish
    $('#btn_publish').on('click', function() {
        if (!confirm('<?= lang('ResultPublish.confirm_publish') ?>')) {
            return;
        }

        var school_id  = $('#field_school_id').val();
        var exam_id    = $('#field_exam_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val() || 0;

        $('#btn_publish').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Publishing...');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/result-publish/publish') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                class_id: class_id,
                section_id: section_id
            },
            success: function(response) {
                if (response.status) {
                    $('#result').html('<div class="alert alert-success">' + response.message + '</div>');
                    // Refresh status
                    $('#btn_check_status').click();
                } else {
                    $('#result').html('<div class="alert alert-danger">' + (response.message || 'Publish failed') + '</div>');
                }
            },
            error: function() {
                $('#result').html('<div class="alert alert-danger">Error publishing results. Please try again.</div>');
            },
            complete: function() {
                $('#btn_publish').prop('disabled', false).html('<i class="fa fa-check-circle"></i> <?= lang('ResultPublish.btn_publish'); ?>');
            }
        });
    });

    // Unpublish
    $('#btn_unpublish').on('click', function() {
        if (!confirm('<?= lang('ResultPublish.confirm_unpublish') ?>')) {
            return;
        }

        var school_id  = $('#field_school_id').val();
        var exam_id    = $('#field_exam_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val() || 0;

        $('#btn_unpublish').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Unpublishing...');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/result-publish/unpublish') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                class_id: class_id,
                section_id: section_id
            },
            success: function(response) {
                if (response.status) {
                    $('#result').html('<div class="alert alert-success">' + response.message + '</div>');
                    // Refresh status
                    $('#btn_check_status').click();
                } else {
                    $('#result').html('<div class="alert alert-danger">' + (response.message || 'Unpublish failed') + '</div>');
                }
            },
            error: function() {
                $('#result').html('<div class="alert alert-danger">Error unpublishing results. Please try again.</div>');
            },
            complete: function() {
                $('#btn_unpublish').prop('disabled', false).html('<i class="fa fa-times-circle"></i> <?= lang('ResultPublish.btn_unpublish'); ?>');
            }
        });
    });
});
</script>