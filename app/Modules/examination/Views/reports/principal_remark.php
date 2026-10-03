<?php 
$school_list = $school_list ?? [];
$school_id = $school_id ?? 0;
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-chat-left-text"></i> Principal Remarks</h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="school_id" class="form-label">School <span class="required">*</span></label>
                        <select name="school_id" id="school_id" class="form-control" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>" <?= $sid == $school_id ? 'selected' : '' ?>>
                                        <?= esc($sname) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="year_id" class="form-label">Year/Session <span class="required">*</span></label>
                        <select name="year_id" id="year_id" class="form-control" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="exam_id" class="form-control" required disabled>
                            <option value="">Select Exam</option>
                        </select>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="button" id="loadResultsBtn" class="btn btn-primary w-100" disabled>
                            <i class="bi bi-search"></i> Load Results
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="resultsContainer"></div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    
    function addCsrfToken(data) { data[csrfName] = csrfHash; return data; }
    
    function checkLoadButton() {
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const examId = $('#exam_id').val();
        const classId = $('#class_id').val();
        $('#loadResultsBtn').prop('disabled', !(schoolId && yearId && examId && classId));
    }
    
    function populateSelect(sel, data, placeholder) {
        sel.empty().append('<option value="">' + placeholder + '</option>');
        if (data && Object.keys(data).length > 0) {
            $.each(data, function(k, v) { sel.append('<option value="' + k + '">' + v + '</option>'); });
            sel.prop('disabled', false);
        } else {
            sel.prop('disabled', true);
        }
        checkLoadButton();
    }
    
    // School change -> load years and classes via AJAX
    $('#school_id').change(function() {
        const schoolId = $(this).val();
        $('#year_id, #class_id, #exam_id').empty().prop('disabled', true);
        $('#resultsContainer').empty();
        $('#loadResultsBtn').prop('disabled', true);
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        
        if (schoolId) {
            $.post(baseUrl + 'examination/aggregate/getAcademicDataBySchool', addCsrfToken({school_id: schoolId}), function(r) {
                if (r.status) {
                    populateSelect($('#year_id'), r.year_list, 'Select Year');
                    populateSelect($('#class_id'), r.class_list, 'Select Class');
                }
            }, 'json');
        }
    });
    
    // Year change -> load exams for this school+year
    $('#year_id').change(function() {
        const schoolId = $('#school_id').val();
        const yearId = $(this).val();
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        
        if (schoolId && yearId) {
            $.post(baseUrl + 'examination/exam-setup/get-exams-by-school-and-year', addCsrfToken({school_id: schoolId, year_id: yearId}), function(examR) {
                console.log('Exam response:', examR);
                if (examR.status && examR.exams && Object.keys(examR.exams).length > 0) {
                    populateSelect($('#exam_id'), examR.exams, 'Select Exam');
                } else {
                    $('#exam_id').empty().append('<option value="">No exams found</option>').prop('disabled', true);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error loading exams:', error, xhr.responseText);
                $('#exam_id').empty().append('<option value="">Error loading exams</option>').prop('disabled', true);
            });
        }
        
        checkLoadButton();
    });
    
    $('#class_id, #exam_id').change(function() { checkLoadButton(); });
    
    // Load results button
    $('#loadResultsBtn').click(function() {
        var schoolId = $('#school_id').val();
        var yearId = $('#year_id').val();
        var classId = $('#class_id').val();
        var examId = $('#exam_id').val();
        
        if (!schoolId || !yearId || !examId || !classId) {
            alert('Please select School, Year, Exam, and Class');
            return;
        }
        
        $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Loading results...</div></div></div>');
        
        $.post(baseUrl + 'examination/remarks/exam/results', addCsrfToken({
            school_id: schoolId,
            year_id: yearId,
            class_id: classId,
            exam_id: examId
        }), function(r) {
            if (r.status) {
                if (r.html) {
                    $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="card"><div class="card-body p-0">' + r.html + '</div></div></div></div>');
                } else {
                    $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="bi bi-info-circle"></i> No results found.</div></div></div>');
                }
            } else {
                $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger">' + (r.message || 'Error loading results') + '</div></div></div>');
            }
        }, 'json').fail(function(xhr, status, error) {
            $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger">Error: ' + status + ' - ' + error + '</div></div></div>');
        });
    });
    
    // Auto-save remarks on blur
    var csrfToken = '<?= csrf_hash() ?>';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });
    
    $(document).on('blur', '.principal-remark-textarea, .teacher-remark-textarea', function() {
        var textarea = $(this);
        var resultId = textarea.data('result-id');
        var remarks = textarea.val();
        var remarkType = textarea.hasClass('principal-remark-textarea') ? 'principal' : 'teacher';

        $.ajax({
            type: "POST",
            dataType: "json",
            url: baseUrl + 'examination/remarks/exam/save',
            data: {
                result_id: resultId,
                remark_type: remarkType,
                principal_remarks: remarkType === 'principal' ? remarks : '',
                teacher_remarks: remarkType === 'teacher' ? remarks : ''
            },
            success: function(response, textStatus, xhr) {
                var newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newToken) {
                    csrfToken = newToken;
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': newToken } });
                }
                if (response.status) {
                    textarea.removeClass('is-invalid').addClass('is-valid');
                    setTimeout(function() { textarea.removeClass('is-valid'); }, 2000);
                } else {
                    textarea.removeClass('is-valid').addClass('is-invalid');
                    alert(response.message || 'Failed to save remark.');
                }
            },
            error: function() {
                textarea.removeClass('is-valid').addClass('is-invalid');
                alert('An error occurred while saving remark.');
            }
        });
    });
    
    // Validate attendance inputs on blur (no auto-save, just validation)
    $(document).on('blur', '.attendance-input', function() {
        var input = $(this);
        var $card = input.closest('.card');
        
        var workingDays = parseInt($card.find('.working-days').val()) || 0;
        var presentDays = parseInt($card.find('.present-days').val()) || 0;
        var absentDays = parseInt($card.find('.absent-days').val()) || 0;
        
        // Validation: present + absent should not exceed working days
        if (workingDays > 0 && (presentDays + absentDays) > workingDays) {
            input.addClass('is-invalid');
            alert('Present days + Absent days cannot exceed Working days (' + workingDays + ')');
            input.val('');
            return;
        }
        
        // Validation: individual fields should not be negative
        if (presentDays < 0 || absentDays < 0 || workingDays < 0) {
            input.addClass('is-invalid');
            alert('Days cannot be negative');
            input.val('');
            return;
        }
        
        // Validation: present days cannot exceed working days
        if (presentDays > workingDays && workingDays > 0) {
            input.addClass('is-invalid');
            alert('Present days cannot exceed Working days');
            input.val('');
            return;
        }
        
        // Validation: absent days cannot exceed working days
        if (absentDays > workingDays && workingDays > 0) {
            input.addClass('is-invalid');
            alert('Absent days cannot exceed Working days');
            input.val('');
            return;
        }
        
        // If valid, remove error state
        input.removeClass('is-invalid');
    });
    
    // Individual student save button
    $(document).on('click', '.save-student-btn', function() {
        var btn = $(this);
        var resultId = btn.data('result-id');
        var $card = btn.closest('.card');
        
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');
        
        // Get attendance data
        var workingDaysVal = $card.find('.working-days').val();
        var presentDaysVal = $card.find('.present-days').val();
        var absentDaysVal = $card.find('.absent-days').val();
        
        var workingDays = workingDaysVal !== '' ? parseInt(workingDaysVal) : null;
        var presentDays = presentDaysVal !== '' ? parseInt(presentDaysVal) : null;
        var absentDays = absentDaysVal !== '' ? parseInt(absentDaysVal) : null;
        
        // Get remarks
        var teacherRemark = $card.find('.teacher-remark-textarea').val();
        var principalRemark = $card.find('.principal-remark-textarea').val();
        
        // Prepare data
        var saveData = {
            result_id: resultId,
            remark_type: 'all',
            teacher_remarks: teacherRemark,
            principal_remarks: principalRemark
        };
        
        // Log what we're sending
        console.log('Saving data for result ID:', resultId);
        console.log('Attendance - Working:', workingDays, 'Present:', presentDays, 'Absent:', absentDays);
        
        // Only add attendance data if at least one field has a value
        if (workingDays !== null || presentDays !== null || absentDays !== null) {
            saveData.working_days = workingDays !== null ? workingDays : 0;
            saveData.present_days = presentDays !== null ? presentDays : 0;
            saveData.absent_days = absentDays !== null ? absentDays : 0;
            console.log('Sending attendance data:', saveData);
        } else {
            console.log('No attendance data to send');
        }
        
        $.ajax({
            type: "POST",
            dataType: "json",
            url: baseUrl + 'examination/remarks/exam/save',
            data: saveData,
            success: function(response, textStatus, xhr) {
                console.log('Save response:', response);
                var newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newToken) {
                    csrfToken = newToken;
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': newToken } });
                }
                
                if (response.status) {
                    btn.removeClass('btn-primary').addClass('btn-success');
                    btn.html('<i class="bi bi-check"></i>');
                    
                    // Update attendance percentage display
                    if (response.attendance_percentage > 0) {
                        var attPct = response.attendance_percentage;
                        var attClass = attPct >= 75 ? 'text-success' : (attPct >= 60 ? 'text-warning' : 'text-danger');
                        $card.find('.attendance-percentage span')
                            .removeClass('text-success text-warning text-danger')
                            .addClass(attClass)
                            .html('<i class="bi bi-calendar-check"></i> ' + attPct.toFixed(1) + '%');
                    }
                    
                    setTimeout(function() { 
                        btn.removeClass('btn-success').addClass('btn-primary');
                        btn.html('<i class="bi bi-save"></i> Save');
                        btn.prop('disabled', false);
                    }, 2000);
                } else {
                    btn.removeClass('btn-primary').addClass('btn-danger');
                    btn.html('<i class="bi bi-x"></i>');
                    alert('Failed to save: ' + (response.message || 'Unknown error'));
                    
                    setTimeout(function() { 
                        btn.removeClass('btn-danger').addClass('btn-primary');
                        btn.html('<i class="bi bi-save"></i> Save');
                        btn.prop('disabled', false);
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                btn.removeClass('btn-primary').addClass('btn-danger');
                btn.html('<i class="bi bi-x"></i>');
                alert('Error: ' + status + ' - ' + error);
                
                setTimeout(function() { 
                    btn.removeClass('btn-danger').addClass('btn-primary');
                    btn.html('<i class="bi bi-save"></i> Save');
                    btn.prop('disabled', false);
                }, 2000);
            }
        });
    });

});
</script>