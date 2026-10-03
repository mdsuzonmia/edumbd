<?php 
$school_list = $school_list ?? [];
$school_id = $school_id ?? 0;
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-chat-square-text"></i> Final Result Remarks</h3>
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
                                    <option value="<?= $sid ?>" <?= $sid == $school_id ? 'selected' : '' ?>><?= esc($sname) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year_id" class="form-label">Academic Year <span class="required">*</span></label>
                        <select name="year_id" id="year_id" class="form-control" required disabled>
                            <option value="">Select Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required disabled>
                            <option value="">Select Class</option>
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
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

<!-- Remarks Edit Modal -->
<div class="modal fade" id="remarksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Final Result Remarks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="remarksForm">
                    <input type="hidden" id="final_result_id" name="final_result_id">
                    
                    <div class="mb-3">
                        <label for="principal_remark" class="form-label">Principal Remark</label>
                        <textarea class="form-control" id="principal_remark" name="principal_remark" rows="3" placeholder="Enter principal remark..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacher_remark" class="form-label">Teacher Remark</label>
                        <textarea class="form-control" id="teacher_remark" name="teacher_remark" rows="3" placeholder="Enter teacher remark..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="next_session_id" class="form-label">Next Session</label>
                            <select class="form-control" id="next_session_id" name="next_session_id">
                                <option value="">Select Session</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="next_class_id" class="form-label">Next Class</label>
                            <select class="form-control" id="next_class_id" name="next_class_id">
                                <option value="">Select Class</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="next_section_id" class="form-label">Next Section</label>
                            <select class="form-control" id="next_section_id" name="next_section_id">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="next_roll" class="form-label">Next Roll</label>
                            <input type="text" class="form-control" id="next_roll" name="next_roll" placeholder="Enter roll number">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRemarksBtn">Save Remarks</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    
    function addCsrfToken(data) { data[csrfName] = csrfHash; return data; }
    
    function checkLoadButton() {
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const classId = $('#class_id').val();
        $('#loadResultsBtn').prop('disabled', !(schoolId && yearId && classId));
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
        $('#year_id, #class_id').empty().prop('disabled', true);
        $('#resultsContainer').empty();
        $('#loadResultsBtn').prop('disabled', true);
        
        if (schoolId) {
            $.post(baseUrl + 'examination/aggregate/getAcademicDataBySchool', addCsrfToken({school_id: schoolId}), function(r) {
                if (r.status) {
                    populateSelect($('#year_id'), r.year_list, 'Select Year');
                    populateSelect($('#class_id'), r.class_list, 'Select Class');
                    
                    // Also populate modal dropdowns
                    if (r.year_list) {
                        var sessionOpts = '<option value="">Select Session</option>';
                        $.each(r.year_list, function(k, v) { sessionOpts += '<option value="' + k + '">' + v + '</option>'; });
                        $('#next_session_id').html(sessionOpts).prop('disabled', false);
                    }
                    if (r.class_list) {
                        var classOpts = '<option value="">Select Class</option>';
                        $.each(r.class_list, function(k, v) { classOpts += '<option value="' + k + '">' + v + '</option>'; });
                        $('#next_class_id').html(classOpts).prop('disabled', false);
                    }
                    if (r.section_list) {
                        var sectionOpts = '<option value="">Select Section</option>';
                        $.each(r.section_list, function(k, v) { sectionOpts += '<option value="' + k + '">' + v + '</option>'; });
                        $('#next_section_id').html(sectionOpts).prop('disabled', false);
                    }
                }
            }, 'json');
        }
    });
    
    $('#year_id, #class_id').change(function() { checkLoadButton(); });
    
    // Load results button
    $('#loadResultsBtn').click(function() {
        var schoolId = $('#school_id').val();
        var yearId = $('#year_id').val();
        var classId = $('#class_id').val();
        
        if (!schoolId || !yearId || !classId) {
            alert('Please select School, Year, and Class');
            return;
        }
        
        $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Loading results...</div></div></div>');
        
        $.post(baseUrl + 'examination/remarks/aggregate/results', addCsrfToken({
            school_id: schoolId,
            year_id: yearId,
            class_id: classId
        }), function(r) {
            if (r.status) {
                if (r.html) {
                    $('#resultsContainer').html('<div class="row mt-3"><div class="col-md-12"><div class="card"><div class="card-header"><h5 class="mb-0"><i class="bi bi-people"></i> Final Results (' + r.count + ' students)</h5></div><div class="card-body p-0">' + r.html + '</div></div></div></div>');
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
    
    // Remarks Modal
    var modal = new bootstrap.Modal(document.getElementById('remarksModal'));
    
    // Use event delegation for edit buttons (they're loaded dynamically)
    $(document).on('click', '.edit-remarks-btn', function() {
        $('#final_result_id').val($(this).data('id'));
        $('#principal_remark').val($(this).data('principal'));
        $('#teacher_remark').val($(this).data('teacher'));
        $('#next_session_id').val($(this).data('next-session'));
        $('#next_class_id').val($(this).data('next-class'));
        $('#next_section_id').val($(this).data('next-section'));
        $('#next_roll').val($(this).data('next-roll'));
        modal.show();
    });
    
    // Save remarks
    $('#saveRemarksBtn').click(function() {
        var formData = new FormData();
        formData.append('final_result_id', $('#final_result_id').val());
        formData.append('principal_remark', $('#principal_remark').val());
        formData.append('teacher_remark', $('#teacher_remark').val());
        formData.append('next_session_id', $('#next_session_id').val());
        formData.append('next_class_id', $('#next_class_id').val());
        formData.append('next_section_id', $('#next_section_id').val());
        formData.append('next_roll', $('#next_roll').val());
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        
        $.ajax({
            url: baseUrl + 'examination/remarks/aggregate/save',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) {
                if (data.status) {
                    alert('Remarks saved successfully!');
                    modal.hide();
                    $('#loadResultsBtn').click();
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error saving remarks: ' + error);
            }
        });
    });
});
</script>