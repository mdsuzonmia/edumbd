<?php 
$csrf_token = csrf_hash();
$history_url = base_url('examination/promotion/history');
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-arrow-up"></i> <?= lang('Promotion.page_title_list') ?? 'Student Promotion' ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= $history_url ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-history"></i> <?= lang('Promotion.page_title_history') ?? 'Promotion History' ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="promotionForm" class="row g-3">
                    <div class="col-md-12">
                        <div class="row g-3">
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label for="result_source" class="form-label">Result Source <span class="required">*</span></label>
                                <select name="result_source" id="result_source" class="form-control">
                                    <option value="aggregate" <?= $result_source == 'aggregate' ? 'selected' : '' ?>>Final Aggregate Result</option>
                                    <option value="exam" <?= $result_source == 'exam' ? 'selected' : '' ?>>Specific Exam</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <strong>From</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="from_session_id" class="form-label">Session <span class="required">*</span></label>
                                            <select name="from_session_id" id="from_session_id" class="form-control" required disabled>
                                                <option value="">Select Session</option>
                                                <?php if (!empty($year_list)): ?>
                                                    <?php foreach ($year_list as $yid => $yval): ?>
                                                        <option value="<?= $yid ?>" <?= $yid == $from_session_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="from_class_id" class="form-label">Class <span class="required">*</span></label>
                                            <select name="from_class_id" id="from_class_id" class="form-control" required disabled>
                                                <option value="">Select Class</option>
                                                <?php if (!empty($class_list)): ?>
                                                    <?php foreach ($class_list as $cid => $cval): ?>
                                                        <option value="<?= $cid ?>" <?= $cid == $from_class_id ? 'selected' : '' ?>><?= esc($cval) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <strong>To</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="to_session_id" class="form-label">Session <span class="required">*</span></label>
                                            <select name="to_session_id" id="to_session_id" class="form-control" required disabled>
                                                <option value="">Select Session</option>
                                                <?php if (!empty($year_list)): ?>
                                                    <?php foreach ($year_list as $yid => $yval): ?>
                                                        <option value="<?= $yid ?>" <?= $yid == $to_session_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="to_class_id" class="form-label">Class <span class="required">*</span></label>
                                            <select name="to_class_id" id="to_class_id" class="form-control" required disabled>
                                                <option value="">Select Class</option>
                                                <?php if (!empty($class_list)): ?>
                                                    <?php foreach ($class_list as $cid => $cval): ?>
                                                        <option value="<?= $cid ?>" <?= $cid == $to_class_id ? 'selected' : '' ?>><?= esc($cval) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="row g-3">
                            <div class="col-md-6" id="exam_select_container" style="display:none;">
                                <label for="exam_id" class="form-label">Select Exam <span class="required">*</span></label>
                                <select name="exam_id" id="exam_id" class="form-control" disabled>
                                    <option value="">Select Exam</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" id="loadStudentsBtn" class="btn btn-primary w-100" disabled>
                                    <i class="fa fa-users"></i> Load Students
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="previewResult">
    <?php if (!empty($preview_html)): ?>
        <?= $preview_html ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    
    function addCsrfToken(data) { data[csrfName] = csrfHash; return data; }
    
    function checkAllFields() {
        const s = $('#school_id').val(), fs = $('#from_session_id').val(), ts = $('#to_session_id').val();
        const fc = $('#from_class_id').val(), tc = $('#to_class_id').val();
        const rs = $('#result_source').val();
        const exam = $('#exam_id').val();
        
        let valid = s && fs && ts && fc && tc;
        if (rs === 'exam') {
            valid = valid && exam;
        }
        
        $('#loadStudentsBtn').prop('disabled', !valid);
        return valid;
    }
    
    function populate(sel, data, txt) {
        sel.empty().append('<option value="">' + txt + '</option>');
        if (data && Object.keys(data).length > 0) {
            $.each(data, function(k, v) { sel.append('<option value="' + k + '">' + v + '</option>'); });
            sel.prop('disabled', false);
        } else { sel.prop('disabled', true); }
        checkAllFields();
    }
    
    $('#school_id').change(function() {
        const s = $(this).val();
        $('#from_session_id,#to_session_id,#from_class_id,#to_class_id').empty().prop('disabled', true);
        $('#exam_id').empty().prop('disabled', true);
        $('#loadStudentsBtn').prop('disabled', true);
        $('#previewResult').empty();
        if (s) {
            $.post(baseUrl + 'examination/promotion/ajax-get-years', addCsrfToken({school_id: s}), function(r) {
                if (r.success) {
                    populate($('#from_session_id'), r.data, 'Select From Session');
                    populate($('#to_session_id'), r.data, 'Select To Session');
                }
            }, 'json');
            $.post(baseUrl + 'examination/promotion/ajax-get-classes', addCsrfToken({school_id: s}), function(r) {
                if (r.success) {
                    populate($('#from_class_id'), r.data, 'Select From Class');
                    populate($('#to_class_id'), r.data, 'Select To Class');
                }
            }, 'json');
        }
    });
    
    $('#from_session_id, #to_session_id, #from_class_id, #to_class_id, #exam_id').change(function() { checkAllFields(); });
    
    $('#result_source').change(function() {
        const rs = $(this).val();
        if (rs === 'exam') {
            $('#exam_select_container').show();
            $('#exam_id').prop('disabled', true);
            $('#exam_id').empty().append('<option value="">Loading exams...</option>');
            // Load exams for the selected class
            const fromClassId = $('#from_class_id').val();
            if (fromClassId) {
                $.post(baseUrl + 'examination/promotion/ajax-get-exams', addCsrfToken({
                    school_id: $('#school_id').val(),
                    session_id: $('#from_session_id').val(),
                    class_id: fromClassId
                }), function(r) {
                    if (r.success && r.data && Object.keys(r.data).length > 0) {
                        populate($('#exam_id'), r.data, 'Select Exam');
                    } else {
                        $('#exam_id').empty().append('<option value="">No exams found</option>');
                        $('#exam_id').prop('disabled', true);
                    }
                }, 'json').fail(function(xhr, status, error) {
                    console.error('Error loading exams:', error, xhr.responseText);
                    $('#exam_id').empty().append('<option value="">Error loading exams</option>');
                    $('#exam_id').prop('disabled', true);
                });
            } else {
                $('#exam_id').empty().append('<option value="">Select From Class first</option>');
                $('#exam_id').prop('disabled', true);
            }
        } else {
            $('#exam_select_container').hide();
            $('#exam_id').prop('disabled', true);
        }
        checkAllFields();
    });
    
    // Also load exams when from_class_id changes if result_source is 'exam'
    $('#from_class_id').change(function() {
        if ($('#result_source').val() === 'exam') {
            $('#result_source').trigger('change');
        }
    });
    
    $('#loadStudentsBtn').click(function() {
        if (!checkAllFields()) return;
        $('#previewResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Loading students...</div></div></div>');
        
        const postData = {
            school_id: $('#school_id').val(),
            from_session_id: $('#from_session_id').val(),
            to_session_id: $('#to_session_id').val(),
            from_class_id: $('#from_class_id').val(),
            to_class_id: $('#to_class_id').val(),
            result_source: $('#result_source').val()
        };
        
        // Add exam_id if result_source is 'exam'
        if ($('#result_source').val() === 'exam') {
            postData.exam_id = $('#exam_id').val();
        }
        
        $.post(baseUrl + 'examination/promotion/preview', addCsrfToken(postData), function(r) {
            if (r.success) $('#previewResult').html(r.html);
            else $('#previewResult').html('<div class="alert alert-danger">' + (r.message || 'Error loading students') + '</div>');
        }, 'json').fail(function(xhr, status, error) { 
            $('#previewResult').html('<div class="alert alert-danger">Error: ' + status + ' - ' + error + '<br>Response: ' + xhr.responseText + '</div>'); 
        });
    });
});
</script>