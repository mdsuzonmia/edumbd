<?php
$csrf_token = csrf_hash();
$postData = isset($post_data) ? $post_data : [];
?>

<?= form_open('examination/marks/store', [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'marks_input_form',
    'method'  => 'post',
]); ?>
<input type="hidden" id="csrf_token" value="<?= $csrf_token ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-pencil-square"></i> <?= lang('Mark.heading_form'); ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/marks') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Mark.back_to'); ?>
        </a>
        <a href="<?= base_url('examination/marks/bulk-import') ?>" class="btn btn-info">
            <i class="fa fa-upload"></i> <?= lang('Mark.btn_bulk_import'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= lang('Mark.heading_select_filters'); ?></h5>
                <?= get_system_message(); ?>
                <div id="result"></div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="field_school_id" class="form-label"><?= lang('Mark.field_school'); ?> <span class="required">*</span></label>
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
                        <label for="field_year_id" class="form-label"><?= lang('Mark.field_year'); ?> <span class="required">*</span></label>
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
                        <label for="field_exam_id" class="form-label"><?= lang('Mark.field_exam'); ?> <span class="required">*</span></label>
                        <select name="exam_id" id="field_exam_id" class="form-control" required>
                            <option value=""><?= lang('Mark.field_select_exam'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="field_subject_id" class="form-label"><?= lang('Mark.field_subject'); ?> <span class="required">*</span></label>
                        <select name="subject_id" id="field_subject_id" class="form-control" required>
                            <option value=""><?= lang('Mark.field_select_subject'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-12 text-end">
                        <button type="button" id="btn_lock_marks" class="btn btn-warning" style="display: none;">
                            <i class="fa fa-lock"></i> Lock Marks
                        </button>
                        <button type="button" id="btn_unlock_marks" class="btn btn-success" style="display: none;">
                            <i class="fa fa-unlock"></i> Unlock Marks
                        </button>
                        <button type="button" id="btn_load_students" class="btn btn-primary">
                            <i class="fa fa-users"></i> <?= lang('Mark.btn_load_students'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="lock_message" style="display: none;"></div>

<div id="marks_input_section" style="display: none;">
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= lang('Mark.heading_marks_input'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="marks_table">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    <th><?= lang('Mark.field_student_code'); ?></th>
                                    <th><?= lang('Mark.field_student_name'); ?></th>
                                    <th><?= lang('Mark.field_roll_no'); ?></th>
                                    <th id="th_distribution_headers"></th>
                                    <th><?= lang('Mark.field_total_obtained'); ?></th>
                                    <th><?= lang('Mark.field_percentage'); ?></th>
                                    <th><?= lang('Mark.field_grade'); ?></th>
                                    <th><?= lang('Mark.field_absent'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="marks_table_body"></tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <button type="submit" id="btn_save_marks" class="btn btn-success">
                                <i class="fa fa-save"></i> <?= lang('Mark.btn_save_mark'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

<script type="text/javascript">

$(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Load academic data when school changes
    $('#field_school_id').on('change', function() {
        var school_id = $(this).val();
        if (school_id) {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/marks/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                        populateSelect('#field_section_id', response.section_list);
                        populateSelect('#field_exam_id', response.exam_list);
                        populateSelect('#field_subject_id', response.subject_list);

                        if (!response.academic_section_enabled) {
                            $('#field_section_id').closest('.col-md-3').hide();
                        } else {
                            $('#field_section_id').closest('.col-md-3').show();
                        }
                        
                        // Auto check lock status after dropdowns populate
                        setTimeout(checkLockStatus, 400);
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id', '#field_section_id', '#field_exam_id', '#field_subject_id'].forEach(function(sel) {
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

    // Check lock status when filters change
    function checkLockStatus() {
        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var exam_id    = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();
        var year_id    = $('#field_year_id').val();

        if (!school_id || !class_id || !exam_id || !subject_id || !year_id) {
            $('#btn_lock_marks').hide();
            $('#btn_unlock_marks').hide();
            $('#lock_message').hide();
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/check-lock-status') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                exam_id: exam_id,
                subject_id: subject_id,
                session_id: year_id
            },
            success: function(response) {
                console.log('Lock check response:', response);
                if (response.status) {
                    if (response.is_locked) {
                        $('#btn_lock_marks').hide();
                        $('#btn_unlock_marks').show();
                        $('#btn_unlock_marks').data('lock-info', response);
                        
                        // Show lock message
                        var examTitle = response.exam_title || 'this exam';
                        var sessionName = response.session_name || 'this session';
                        var className = response.class_name || 'this class';
                        var lockedBy = response.locked_by_name || 'Unknown';
                        var lockedAt = response.locked_at || 'Unknown';
                        
                        $('#lock_message').html('<div class="alert alert-warning"><strong>Locked:</strong> Subject marks are locked for the exam <strong>' + examTitle + '</strong>, session <strong>' + sessionName + '</strong>, class <strong>' + className + '</strong>. Locked by: ' + lockedBy + ' at ' + lockedAt + '</div>').show();
                        
                        // Disable only input fields and save button, but allow loading students to view
                        $('.dist-mark-input').prop('disabled', true);
                        $('.absent-checkbox').prop('disabled', true);
                        $('#btn_save_marks').prop('disabled', true);
                    } else {
                        $('#btn_lock_marks').show();
                        $('#btn_unlock_marks').hide();
                        $('#lock_message').hide();
                        $('.dist-mark-input').prop('disabled', false);
                        $('.absent-checkbox').prop('disabled', false);
                        $('#btn_save_marks').prop('disabled', false);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Lock check error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    // Lock marks
    $('#btn_lock_marks').on('click', function() {
        
        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val();
        var exam_id    = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();
        var year_id    = $('#field_year_id').val();

        var lockReason = prompt('Please enter a reason for locking these marks:');
        
        // If user clicks Cancel (null), show message and stop
        if (lockReason === null) {
            alert('You cancelled the prompt. Please enter a reason to lock marks.');
            return;
        }
        
        // If user leaves it empty, show message and stop
        if (!lockReason || lockReason.trim() === '') {
            alert('Please enter a valid reason for locking these marks.');
            return;
        }

        var confirmed = confirm('Are you sure you want to lock these marks? This will prevent any further edits.');
        if (!confirmed) {
            alert('Lock cancelled by user.');
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/lock') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                section_id: section_id,
                exam_id: exam_id,
                subject_id: subject_id,
                session_id: year_id,
                lock_reason: lockReason
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    checkLockStatus();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error locking marks. Please try again.');
            }
        });
    });

    // Unlock marks
    $('#btn_unlock_marks').on('click', function() {
        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val();
        var exam_id    = $('#field_exam_id').val();
        var year_id    = $('#field_year_id').val();
        var subject_id = $('#field_subject_id').val();

        var unlockReason = prompt('Please enter a reason for unlocking these marks:');
        if (!unlockReason) {
            return;
        }

        if (!confirm('Are you sure you want to unlock these marks? This will allow edits again.')) {
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/unlock') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                section_id: section_id,
                exam_id: exam_id,
                subject_id: subject_id,
                session_id: year_id,
                unlock_reason: unlockReason
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    checkLockStatus();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error unlocking marks. Please try again.');
            }
        });
    });

    // Check lock status on filter change
    $('#field_school_id, #field_year_id, #field_class_id, #field_section_id, #field_exam_id, #field_subject_id').on('change', function() {
        checkLockStatus();
    });

    // Load students
    $('#btn_load_students').on('click', function() {
        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val();
        var exam_id    = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();
        var year_id    = $('#field_year_id').val();

        if (!school_id || !class_id || !exam_id || !subject_id) {
            alert('<?= lang('Mark.alert_select_filters') ?>');
            return;
        }

        $('#btn_load_students').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/getStudentsByFilter') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                section_id: section_id,
                exam_id: exam_id,
                subject_id: subject_id,
                year_id: year_id
            },
            success: function(response) {
                if (response.status && response.students && response.students.length > 0) {
                    renderMarksTable(response);
                } else {
                    alert(response.message || '<?= lang('Mark.alert_no_students') ?>');
                }
            },
            error: function() {
                alert('Error loading students. Please try again.');
            },
            complete: function() {
                $('#btn_load_students').prop('disabled', false).html('<i class="fa fa-users"></i> <?= lang('Mark.btn_load_students') ?>');
            }
        });
    });

    function renderMarksTable(data) {
        var students = data.students;
        var distributions = data.mark_distribution || [];
        window.gradeRules = data.grade_rules || []; // Store grade rules globally

        // Build distribution header
        var distHeaderHtml = '';
        if (distributions.length > 0) {
            $.each(distributions, function(i, dist) {
                distHeaderHtml += '<div class="dist-header" data-id="' + dist.id + '" data-code="' + (dist.code || '') + '">' +
                    dist.field + '<br><small>Max: ' + dist.max + '</small>' +
                    '</div>';
            });
        }
        $('#th_distribution_headers').html(distHeaderHtml);

        // Build rows
        var tbody = $('#marks_table_body');
        tbody.empty();

        $.each(students, function(i, student) {
            var row = '<tr>';
            row += '<td>' + (i + 1) + '</td>';
            row += '<td>' + (student.student_code || '') + '</td>';
            row += '<td>' + student.student_name + '</td>';
            row += '<td>' + (student.roll_no || '') + '</td>';
            row += '<input type="hidden" name="students[' + i + '][roll_no]" value="' + (student.roll_no || '') + '">';
            row += '<td class="dist-cell">';
            row += '<input type="hidden" name="students[' + i + '][student_id]" value="' + student.student_id + '">';
            row += '<input type="hidden" name="students[' + i + '][enrollment_id]" value="' + student.enrollment_id + '">';

            if (distributions.length > 0) {
                var distRowHtml = '<div class="d-flex gap-1 flex-wrap">';
                $.each(distributions, function(j, dist) {
                    var existingVal = '';
                    if (student.existing_distributions && student.existing_distributions[dist.id]) {
                        existingVal = student.existing_distributions[dist.id].obtained;
                    }
                    distRowHtml += '<div class="dist-input-wrap">';
                    distRowHtml += '<input type="number" step="any" min="0" max="' + dist.max + '" ';
                    distRowHtml += 'name="students[' + i + '][distributions][' + j + '][distribution_id]" ';
                    distRowHtml += 'value="' + dist.id + '" type="hidden" style="display:none">';
                    distRowHtml += '<input type="number" step="any" min="0" max="' + dist.max + '" ';
                    distRowHtml += 'name="students[' + i + '][distributions][' + j + '][max]" ';
                    distRowHtml += 'value="' + dist.max + '" type="hidden" style="display:none">';
                    distRowHtml += '<input type="number" step="any" min="0" max="' + dist.max + '" ';
                    distRowHtml += 'name="students[' + i + '][distributions][' + j + '][obtained]" ';
                    distRowHtml += 'value="' + existingVal + '" class="form-control form-control-sm dist-mark-input" ';
                    distRowHtml += 'data-dist-id="' + dist.id + '" data-max="' + dist.max + '" ';
                    distRowHtml += 'data-student="' + i + '" style="width:70px" placeholder="' + dist.field + '">';
                    distRowHtml += '</div>';
                });
                distRowHtml += '</div>';
                row += distRowHtml;
            }

            row += '</td>';
            row += '<td class="total-obtained">' + (student.total_obtained || '0') + '</td>';
            row += '<td class="percentage">' + (student.percentage || '0') + '%</td>';
            row += '<td class="grade">' + (student.grade || '') + '</td>';
            row += '<td><input type="checkbox" name="students[' + i + '][is_absent]" value="1" ' +
                (student.is_absent ? 'checked' : '') + ' class="absent-checkbox"></td>';
            row += '</tr>';

            tbody.append(row);
        });

        // Recalculate totals on input change
        $(document).on('input', '.dist-mark-input', function() {
            calculateStudentTotals();
        });

        $(document).on('change', '.absent-checkbox', function() {
            var row = $(this).closest('tr');
            var inputs = row.find('.dist-mark-input');
            if ($(this).is(':checked')) {
                inputs.prop('disabled', true).val(0);
            } else {
                inputs.prop('disabled', false);
            }
            calculateStudentTotals();
        });

        function calculateStudentTotals() {
            $('#marks_table_body tr').each(function() {
                var totalObtained = 0;
                var totalMax = 0;
                var grade = '';
                
                $(this).find('.dist-mark-input').each(function() {
                    var val = parseFloat($(this).val()) || 0;
                    var max = parseFloat($(this).data('max')) || 0;
                    totalObtained += val;
                    totalMax += max;
                });
                
                $(this).find('.total-obtained').text(totalObtained.toFixed(2));
                var pct = totalMax > 0 ? ((totalObtained / totalMax) * 100).toFixed(2) : '0.00';
                $(this).find('.percentage').text(pct + '%');
                
                // Calculate grade based on percentage
                if (window.gradeRules && window.gradeRules.length > 0) {
                    for (var i = 0; i < window.gradeRules.length; i++) {
                        var rule = window.gradeRules[i];
                        if (pct >= rule.mark_from && pct <= rule.mark_to) {
                            grade = rule.grade;
                            break;
                        }
                    }
                }
                $(this).find('.grade').text(grade);
            });
        }

        $('#marks_input_section').show();
        $('html, body').animate({ scrollTop: $('#marks_input_section').offset().top - 100 }, 1000);
        
        // Re-check lock status to disable inputs if locked
        checkLockStatus();
    }

    // Form submission
    $('#marks_input_form').on('submit', function(e) {
        e.preventDefault();

        var school_id  = $('#field_school_id').val();
        var class_id   = $('#field_class_id').val();
        var section_id = $('#field_section_id').val();
        var exam_id    = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();
        var year_id    = $('#field_year_id').val();

        if (!school_id || !class_id || !exam_id || !subject_id) {
            alert('<?= lang('Mark.alert_select_filters') ?>');
            return;
        }

        // Check if there are any marks entered
        var hasData = false;
        $('#marks_table_body .dist-mark-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            if (val > 0) { hasData = true; }
        });

        if (!hasData) {
            alert('<?= lang('Mark.alert_no_data_to_save') ?>');
            return;
        }

        $('#btn_save_marks').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        var formData = $(this).serializeArray();
        formData.push({ name: 'school_id', value: school_id });
        formData.push({ name: 'class_id', value: class_id });
        formData.push({ name: 'section_id', value: section_id });
        formData.push({ name: 'exam_id', value: exam_id });
        formData.push({ name: 'subject_id', value: subject_id });
        formData.push({ name: 'year_id', value: year_id });

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/store') ?>',
            data: formData,
            success: function(response) {
                if (response.status) {
                    $('#result').html('<div class="alert alert-success">' + response.message + '</div>');
                } else {
                    $('#result').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#result').html('<div class="alert alert-danger">Error saving marks. Please try again.</div>');
            },
            complete: function() {
                $('#btn_save_marks').prop('disabled', false).html('<i class="fa fa-save"></i> <?= lang('Mark.btn_save_mark') ?>');
                $('html, body').animate({ scrollTop: $('#result').offset().top - 100 }, 500);
            }
        });
    });
});
</script>

<style>
.dist-header {
    font-size: 11px;
    text-align: center;
    display: inline-block;
    margin: 0 2px;
    padding: 2px 4px;
    background: #f8f9fa;
    border-radius: 3px;
    min-width: 70px;
}
.dist-input-wrap {
    display: inline-block;
    margin: 2px;
}
#marks_table .form-control-sm {
    font-size: 12px;
    padding: 2px 4px;
}
</style>