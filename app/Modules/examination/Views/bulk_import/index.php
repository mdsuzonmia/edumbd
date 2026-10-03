<?php
$school_list = isset($school_list) ? $school_list : [];
$csrfToken = csrf_hash();
?>

<input type="hidden" id="csrf_token" value="<?= $csrfToken ?>" />
<div class="right_col" role="main">
    <div class="row">
        <div class="col-sm-3">
            <h3 class="text-secondary mb-0"><i class="bi bi-upload"></i> Bulk Mark Import</h3>
        </div>
        <div class="col-sm-9 text-end pt-0">
            <div class="row g-2 justify-content-end">
                <div class="col-auto">
                    <a href="<?= base_url('examination/marks/input') ?>" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Manual Entry
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <?= get_system_message(); ?>
                    <div id="result"></div>
                    <div id="lock_message" style="display: none;"></div>

                    <!-- Step 1: Filter Selection -->
                    <div class="row">
                        <div class="col-md-12">
                            <p>Before importing marks, please select the appropriate filters to ensure that the data is imported into the correct context. The filters include School, Academic Year, Exam, Class, and Subject. Once you have selected the necessary filters, you can proceed to download sample files or upload your data file for import.</p>
                            
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="col-form-label" for="school_id"><?= lang('Mark.field_school') ?> *</label>
                                                <select name="school_id" id="school_id" class="form-control" required>
                                                    <option value=""><?= lang('Mark.field_select_school') ?></option>
                                                    <?php if (!empty($school_list)): ?>
                                                        <?php foreach ($school_list as $sid => $sname): ?>
                                                            <?php 
                                                            $schoolName = is_object($sname) ? $sname->name : $sname;
                                                            ?>
                                                            <option value="<?= $sid ?>"><?= esc($schoolName) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="col-form-label" for="year_id"><?= lang('Mark.field_year') ?></label>
                                                <select name="year_id" id="year_id" class="form-control" disabled>
                                                    <option value=""><?= lang('Mark.field_select_year') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="col-form-label" for="exam_id"><?= lang('Mark.field_exam') ?> *</label>
                                                <select name="exam_id" id="exam_id" class="form-control" disabled required>
                                                    <option value=""><?= lang('Mark.field_select_exam') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="col-form-label" for="class_id"><?= lang('Mark.field_class') ?> *</label>
                                                <select name="class_id" id="class_id" class="form-control" disabled required>
                                                    <option value=""><?= lang('Mark.field_select_class') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="col-form-label" for="subject_id"><?= lang('Mark.field_subject') ?> *</label>
                                                <select name="subject_id" id="subject_id" class="form-control" disabled required>
                                                    <option value=""><?= lang('Mark.field_select_subject') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-9 text-end align-self-end">
                                            <button type="button" id="btn_lock_marks" class="btn btn-warning" style="display: none;">
                                                <i class="fa fa-lock"></i> Lock Marks
                                            </button>
                                            <button type="button" id="btn_unlock_marks" class="btn btn-success" style="display: none;">
                                                <i class="fa fa-unlock"></i> Unlock Marks
                                            </button>
                                        </div>
                                    </div>
                                
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5><i class="fa fa-download"></i> Download Files</h5>
                            <p>Download sample files containing placeholder data to help you prepare and format new records before import.</p>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-md-6">
                            <p>
                                <strong>Download Real Data</strong><br>
                                Download files containing actual records ready for import into the system. 
                                Use these files when you need to export and re-import existing data.
                            </p>

                            <div class="">
                                <a href="#" id="download_csv" class="btn btn-success">
                                    <i class="fa fa-file-csv"></i> Download Real Data CSV
                                </a>
                                <a href="#" id="download_json" class="btn btn-info">
                                    <i class="fa fa-file-code"></i> Download Real Data JSON
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <p>
                                <strong>Download Sample Data</strong><br>
                                Download sample files containing placeholder data to help you prepare and format new records before import.
                            </p>

                            <div class="">
                                <a href="#" id="download_template_csv" class="btn btn-success">
                                    <i class="fa fa-file-csv"></i> Download Sample CSV Template
                                </a>
                                <a href="#" id="download_template_json" class="btn btn-info">
                                    <i class="fa fa-file-code"></i> Download Sample JSON Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <small class="text-danger">
                                <i class="fa fa-exclamation-triangle"></i> <strong>Note:</strong> Download options are available only after selecting a School, Academic Year, Exam, Class, and Subject.
                            </small>
                        </div>
                    </div>

                    <!-- Step 2: File Upload & Download -->
                    <div class="row mt-5">
                        <div class="col-md-12">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fa fa-upload"></i> Upload File</h5>
                                </div>
                                <div class="card-body">
                                    <form id="upload_form" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label">Upload Data File <span class="text-danger">*</span></label>
                                            <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv,.json" required>
                                            <small class="form-text text-muted">Choose a CSV or JSON file containing the records you wish to import. The system will validate the file and display a preview before the import is completed. </small>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-upload"></i> Preview and validate the data
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                    </div>

                    <!-- Step 3: Preview and Import -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card" id="preview_card" style="display: none;">
                                <div class="card-header">
                                    <h5><i class="fa fa-eye"></i> Preview Data</h5>
                                </div>
                                <div class="card-body">
                                    <div id="preview_info" class="alert alert-info"></div>
                                    <div id="preview_errors" class="alert alert-danger" style="display: none;"></div>
                                    
                                    <div class="table-responsive" >
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student</th>
                                                    <th>Subject</th>
                                                    <th>Distribution</th>
                                                    <th class="text-center">Obtained</th>
                                                    <th class="text-center">Full</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="preview_table_body">
                                            </tbody>
                                        </table>
                                    </div>

                                    <form id="import_form" method="post" action="<?= base_url('examination/marks/bulk-import/import') ?>">
                                        <input type="hidden" name="import_data" id="import_data">
                                        <div class="d-grid gap-2 mt-3">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fa fa-check"></i> Confirm Import
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="cancelImport()">
                                                <i class="fa fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var csrfToken = '<?= $csrfToken ?>';
$(document).ready(function() {
    
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Check lock status on page load if filters are already selected
    setTimeout(function() {
        checkRequiredFields();
    }, 500);

    // When school changes, populate all dropdowns
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        if (schoolId) {
            populateAllDropdowns(schoolId);
        } else {
            clearAllDropdowns();
        }
    });

    function populateAllDropdowns(schoolId) {
        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/marks/bulk-import/getAcademicDataBySchool'); ?>',
            data: { school_id: schoolId},
            success: function(resp) {
                if (resp.status) {
                    populateDropdown('year_id', resp.year_list, true);
                    populateDropdown('exam_id', resp.exam_list, true);
                    populateDropdown('class_id', resp.class_list, true);
                    populateDropdown('subject_id', resp.subject_list);

                    // Enable exam, class, and subject
                    $('#exam_id').prop('disabled', false);
                    $('#class_id').prop('disabled', false);
                    $('#subject_id').prop('disabled', false);
                    
                    // Trigger change to update lock status
                    setTimeout(function() {
                        checkRequiredFields();
                    }, 100);
                }
            }
        });
    }

    function clearAllDropdowns() {
        $('#year_id').html('<option value=""><?= lang('Mark.field_select_year') ?></option>').prop('disabled', true);
        $('#exam_id').html('<option value=""><?= lang('Mark.field_select_exam') ?></option>').prop('disabled', true);
        $('#class_id').html('<option value=""><?= lang('Mark.field_select_class') ?></option>').prop('disabled', true);
        $('#subject_id').html('<option value=""><?= lang('Mark.field_select_subject') ?></option>').prop('disabled', true);
        
        // Hide lock/unlock buttons
        $('#btn_lock_marks').hide();
        $('#btn_unlock_marks').hide();
        $('#lock_message').hide();
        
        // Disable upload/download sections
        disableUploadDownload();
    }

    function checkRequiredFields() {
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();

        if (schoolId && examId && classId && subjectId) {
            checkLockStatus();
        } else {
            disableUploadDownload();
            $('#lock_message').hide();
            $('#btn_lock_marks').hide();
            $('#btn_unlock_marks').hide();
        }
    }

    function checkLockStatus() {
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();
        var yearId = $('#year_id').val();

        if (!schoolId || !examId || !classId || !subjectId || !yearId) {
            return;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/marks/check-lock-status') ?>',
            data: {
                school_id: schoolId,
                exam_id: examId,
                class_id: classId,
                session_id: yearId,
                subject_id: subjectId
            },
            success: function(response) {
                console.log('Lock check response:', response);
                if (response.status && response.is_locked) {
                    // Show lock message
                    var examTitle = response.exam_title || 'this exam';
                    var sessionName = response.session_name || 'this session';
                    var className = response.class_name || 'this class';
                    var lockedBy = response.locked_by_name || 'Unknown';
                    var lockedAt = response.locked_at || 'Unknown';
                    
                    $('#lock_message').html('<div class="alert alert-warning"><strong>Locked:</strong> Subject marks are locked for the exam <strong>' + examTitle + '</strong>, session <strong>' + sessionName + '</strong>, class <strong>' + className + '</strong>. Locked by: ' + lockedBy + ' at ' + lockedAt + '</div>').show();
                    
                    // Show unlock button, hide lock button
                    $('#btn_lock_marks').hide();
                    $('#btn_unlock_marks').show();
                    
                    // Disable upload and import
                    disableUploadDownload();
                    $('#import_form button[type="submit"]').prop('disabled', true);
                    $('#preview_card').hide();
                } else {
                    $('#lock_message').hide();
                    
                    // Show lock button, hide unlock button
                    $('#btn_lock_marks').show();
                    $('#btn_unlock_marks').hide();
                    
                    enableUploadDownload();
                }
            },
            error: function(xhr, status, error) {
                console.error('Lock check error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    function enableUploadDownload() {
        $('#import_file').prop('disabled', false);
        $('#upload_form button[type="submit"]').prop('disabled', false);
        $('#download_csv').removeClass('disabled').css('pointer-events', 'auto');
        $('#download_json').removeClass('disabled').css('pointer-events', 'auto');
    }

    function disableUploadDownload() {
        $('#import_file').prop('disabled', true);
        $('#upload_form button[type="submit"]').prop('disabled', true);
        $('#download_csv').addClass('disabled').css('pointer-events', 'none');
        $('#download_json').addClass('disabled').css('pointer-events', 'none');
    }

    function populateDropdown(elementId, data, enabled) {
        var select = $('#' + elementId);
        select.empty();
        var defaultText = select.find('option:first').text() || '-- Select --';
        select.append('<option value="">' + defaultText + '</option>');
        $.each(data, function(id, title) {
            select.append('<option value="' + id + '">' + title + '</option>');
        });
        if (enabled !== undefined) {
            select.prop('disabled', !enabled);
        }
    }

    // Lock marks
    $('#btn_lock_marks').on('click', function() {
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();
        var yearId = $('#year_id').val();

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
                school_id: schoolId,
                class_id: classId,
                exam_id: examId,
                subject_id: subjectId,
                session_id: yearId,
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
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();
        var yearId = $('#year_id').val();

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
                school_id: schoolId,
                class_id: classId,
                exam_id: examId,
                subject_id: subjectId,
                session_id: yearId,
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

    // Check required fields when any dropdown changes
    $('#school_id, #exam_id, #class_id, #subject_id, #year_id').on('change', function() {
        checkRequiredFields();
    });

    // Update all download links when filters change
    function updateDownloadLinks() {
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();
        var yearId = $('#year_id').val();
        var json_url = '<?= base_url('examination/marks/bulk-import/download-real-data-json') ?>';
        var csv_url = '<?= base_url('examination/marks/bulk-import/download-real-data-csv') ?>';
        var sample_json_url = '<?= base_url('examination/marks/bulk-import/download-sample-data-json') ?>';
        var sample_csv_url = '<?= base_url('examination/marks/bulk-import/download-sample-data-csv') ?>';
        
        if (schoolId && examId && classId && subjectId) {
            // Sample with real data
            $('#download_csv').attr('href', csv_url + '?school_id=' + schoolId + '&exam_id=' + examId + '&class_id=' + classId + '&subject_id=' + subjectId + '&year_id=' + yearId);
            $('#download_json').attr('href', json_url + '?school_id=' + schoolId + '&exam_id=' + examId + '&class_id=' + classId + '&subject_id=' + subjectId + '&year_id=' + yearId);
            
            // Template with dummy data
            $('#download_template_csv').attr('href', sample_csv_url + '?school_id=' + schoolId + '&subject_id=' + subjectId);
            $('#download_template_json').attr('href', sample_json_url + '?school_id=' + schoolId + '&subject_id=' + subjectId);
        } else {
            $('#download_csv').attr('href', '#');
            $('#download_json').attr('href', '#');
            $('#download_template_csv').attr('href', '#');
            $('#download_template_json').attr('href', '#');
        }
    }

    $('#school_id, #exam_id, #class_id, #subject_id, #year_id').on('change', function() {
        updateDownloadLinks();
    });

    // File upload form submission
    $('#upload_form').on('submit', function(e) {
        e.preventDefault();
        
        var schoolId = $('#school_id').val();
        var examId = $('#exam_id').val();
        var classId = $('#class_id').val();
        var subjectId = $('#subject_id').val();
        
        if (!schoolId || !examId || !classId || !subjectId) {
            alert('Please select all filters (school, exam, class, subject) before uploading');
            return;
        }

        var yearId = $('#year_id').val();
        
        var formData = new FormData(this);
        formData.append('school_id', schoolId);
        formData.append('year_id', yearId);
        formData.append('exam_id', examId);
        formData.append('class_id', classId);
        formData.append('subject_id', subjectId);

        $('#result').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');

        $.ajax({
            url: '<?= base_url('examination/marks/bulk-import/preview') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                $('#result').empty();
                
                if (response.status) {
                    displayPreview(response);
                } else {
                    $('#result').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#result').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        });
    });

    function displayPreview(data) {
        if (data.error_count > 0) {
            var errorHtml = '<strong>Validation Errors:</strong><ul>';
            data.errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul>';
            $('#preview_errors').html(errorHtml).show();
            
            // Disable import button when there are errors
            $('#import_form button[type="submit"]').prop('disabled', true);
        } else {
            $('#preview_errors').hide();
            
            // Enable import button when there are no errors
            $('#import_form button[type="submit"]').prop('disabled', false);
        }

        if (data.valid_count > 0) {
            var html = '';
            data.valid_rows.forEach(function(row, index) {
                // Display each distribution mark for this student
                if (row.distribution_marks && row.distribution_marks.length > 0) {
                    row.distribution_marks.forEach(function(dist, distIndex) {
                        html += '<tr>';
                        if (distIndex === 0) {
                            // First distribution row shows student info
                            html += '<td rowspan="' + row.distribution_marks.length + '">' + (index + 1) + '</td>';
                            html += '<td rowspan="' + row.distribution_marks.length + '">' + row.student_name + '<br><small class="text-muted">' + row.student_code + '</small></td>';
                            html += '<td rowspan="' + row.distribution_marks.length + '">' + (row.subject_name || 'Subject') + '</td>';
                        }
                        html += '<td>' + (dist.label || dist.code) + '</td>';
                        html += '<td class="text-center">' + dist.obtained + '</td>';
                        html += '<td class="text-center">' + dist.max + '</td>';
                        if (distIndex === 0) {
                            html += '<td rowspan="' + row.distribution_marks.length + '" class="text-center">' + (row.is_absent ? '<span class="badge bg-danger">Absent</span>' : '<span class="badge bg-success">Present</span>') + '</td>';
                        }
                        html += '</tr>';
                    });
                } else {
                    // No distribution marks, just show student info
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td>' + row.student_name + '<br><small class="text-muted">' + row.student_code + '</small></td>';
                    html += '<td>' + (row.subject_name || 'Subject') + '</td>';
                    html += '<td colspan="2" class="text-center">No marks</td>';
                    html += '<td class="text-center">' + (row.is_absent ? '<span class="badge bg-danger">Absent</span>' : '<span class="badge bg-success">Present</span>') + '</td>';
                    html += '</tr>';
                }
            });
            $('#preview_table_body').html(html);

            $('#preview_info').html(
                '<strong>Total Records:</strong> ' + data.valid_count + 
                ' | <strong>Errors:</strong> ' + data.error_count
            );

            $('#import_data').val(JSON.stringify(data.valid_rows));
            $('#preview_card').show();
        } else {
            $('#preview_info').html('No valid records found to import.');
            $('#preview_card').show();
        }
    }

    $('#import_form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to import ' + $('#preview_table_body tr').length + ' records?')) {
            return;
        }

        // Get the import data and parse it
        var importData = $('#import_data').val();
        var studentsData = JSON.parse(importData);
        
        // Create FormData and add all form fields
        var formData = new FormData(this);
        
        // Add the students data with the correct parameter name
        formData.append('students', JSON.stringify(studentsData));
        
        // Also add the filter fields
        formData.append('school_id', $('#school_id').val());
        formData.append('exam_id', $('#exam_id').val());
        formData.append('class_id', $('#class_id').val());
        formData.append('subject_id', $('#subject_id').val());
        formData.append('year_id', $('#year_id').val());

        $('#result').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                $('#result').empty();
                
                // Show success/error message
                if (response && response.status) {
                    $('#result').html('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + response.message + '</div>');
                } else {
                    var msg = (response && response.message) ? response.message : 'Import completed';
                    $('#result').html('<div class="alert alert-' + (response.success ? 'success' : 'danger') + '">' + msg + '</div>');
                }
                
                // Hide preview card
                $('#preview_card').hide();
                $('#preview_table_body').empty();
                $('#import_data').val('');
            }
        });
    });
});

function cancelImport() {
    $('#preview_card').hide();
    $('#preview_table_body').empty();
    $('#import_data').val('');
}
</script>