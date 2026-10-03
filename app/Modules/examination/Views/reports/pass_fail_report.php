<?php 
$school_list = $school_list ?? [];
$year_list = $year_list ?? [];
$exam_list = $exam_list ?? [];
$class_list = $class_list ?? [];
$section_list = $section_list ?? [];
$school_id = $school_id ?? 0;
$year_id = $year_id ?? 0;
$exam_id = $exam_id ?? 0;
$class_id = $class_id ?? 0;
$section_id = $section_id ?? 0;
$result_source = $result_source ?? 'exam';
$result_status = $result_status ?? 'all';
$report_html = $report_html ?? '';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-check-circle"></i> <?= lang('Reports.heading_pass_fail_report') ?? 'Pass/Fail Report' ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-2">
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
                    <div class="col-md-2">
                        <label for="year_id" class="form-label">Session <span class="required">*</span></label>
                        <select name="year_id" id="year_id" class="form-control" required disabled>
                            <option value="">Select Session</option>
                            <?php if (!empty($year_list)): ?>
                                <?php foreach ($year_list as $yid => $yval): ?>
                                    <option value="<?= $yid ?>" <?= $yid == $year_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="result_source" class="form-label">Result Source <span class="required">*</span></label>
                        <select name="result_source" id="result_source" class="form-control">
                            <option value="exam" <?= $result_source == 'exam' ? 'selected' : '' ?>>Specific Exam</option>
                            <option value="aggregate" <?= $result_source == 'aggregate' ? 'selected' : '' ?>>Final Aggregate</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="examField" style="<?= $result_source == 'aggregate' ? 'display:none;' : '' ?>">
                        <label for="exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="exam_id" class="form-control" <?= $result_source == 'aggregate' ? 'disabled' : '' ?>>
                            <option value="">Select Exam</option>
                            <?php if (!empty($exam_list)): ?>
                                <?php foreach ($exam_list as $eid => $eVal): ?>
                                    <option value="<?= $eid ?>" <?= $eid == $exam_id ? 'selected' : '' ?>><?= esc($eVal) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required disabled>
                            <option value="">Select Class</option>
                            <?php if (!empty($class_list)): ?>
                                <?php foreach ($class_list as $cid => $cval): ?>
                                    <option value="<?= $cid ?>" <?= $cid == $class_id ? 'selected' : '' ?>><?= esc($cval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="section_id" class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-control" disabled>
                            <option value="">All Sections</option>
                            <?php if (!empty($section_list)): ?>
                                <?php foreach ($section_list as $sid => $sval): ?>
                                    <option value="<?= $sid ?>" <?= $sid == $section_id ? 'selected' : '' ?>><?= esc($sval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="result_status" class="form-label">Result Status</label>
                        <select name="result_status" id="result_status" class="form-control">
                            <option value="all" <?= $result_status == 'all' ? 'selected' : '' ?>>All</option>
                            <option value="pass" <?= $result_status == 'pass' ? 'selected' : '' ?>>Pass</option>
                            <option value="fail" <?= $result_status == 'fail' ? 'selected' : '' ?>>Fail</option>
                            <option value="absent" <?= $result_status == 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="incomplete" <?= $result_status == 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="button" id="generateBtn" class="btn btn-primary" disabled>
                            <i class="bi bi-check-circle"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="reportResult">
    <?php if (!empty($report_html)): ?>
        <?= $report_html ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    
    function addCsrfToken(data) { data[csrfName] = csrfHash; return data; }
    
    function checkAllFields() {
        const s = $('#school_id').val(), y = $('#year_id').val(), c = $('#class_id').val();
        let valid = s && y && c;
        if ($('#result_source').val() === 'exam') valid = valid && $('#exam_id').val();
        $('#generateBtn').prop('disabled', !valid);
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
    
    $('#result_source').change(function() {
        if ($(this).val() === 'aggregate') {
            $('#examField').hide();
            $('#exam_id').prop('disabled', true).val('');
        } else {
            $('#examField').show();
            $('#exam_id').prop('disabled', false);
            const s = $('#school_id').val(), y = $('#year_id').val();
            if (s && y) loadExams(s, y);
        }
        checkAllFields();
    });
    
    function loadExams(s, y) {
        $.post(baseUrl + 'examination/reports/pass-fail-report/ajax-get-exams', addCsrfToken({school_id: s, year_id: y}), function(r) {
            if (r.success) populate($('#exam_id'), r.data, 'Select Exam');
        }, 'json');
    }
    
    $('#school_id').change(function() {
        const s = $(this).val();
        $('#year_id,#class_id').empty().prop('disabled', true);
        $('#exam_id,#section_id').empty().prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#reportResult').empty();
        if (s) {
            $.post(baseUrl + 'examination/reports/pass-fail-report/ajax-get-years', addCsrfToken({school_id: s}), function(r) {
                if (r.success) populate($('#year_id'), r.data, 'Select Session');
            }, 'json');
            $.post(baseUrl + 'examination/reports/pass-fail-report/ajax-get-classes', addCsrfToken({school_id: s}), function(r) {
                if (r.success) populate($('#class_id'), r.data, 'Select Class');
            }, 'json');
        }
    });
    
    $('#year_id').change(function() {
        const s = $('#school_id').val(), y = $(this).val();
        $('#exam_id').empty().prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#reportResult').empty();
        if (s && y && $('#result_source').val() === 'exam') loadExams(s, y);
        checkAllFields();
    });
    
    $('#class_id').change(function() {
        const s = $('#school_id').val(), c = $(this).val();
        $('#section_id').empty().prop('disabled', true);
        if (s && c) {
            $.post(baseUrl + 'examination/reports/pass-fail-report/ajax-get-sections', addCsrfToken({school_id: s}), function(r) {
                if (r.success) { populate($('#section_id'), r.data, 'All Sections'); $('#section_id').prepend('<option value="">All Sections</option>').val(''); }
            }, 'json');
        }
        checkAllFields();
    });
    
    $('#exam_id, #section_id, #result_status').change(function() { checkAllFields(); });
    
    $('#generateBtn').click(function() {
        if (!checkAllFields()) return;
        $('#reportResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Generating report...</div></div></div>');
        $.post(baseUrl + 'examination/reports/pass-fail-report/ajax-generate-report', addCsrfToken({
            school_id: $('#school_id').val(), year_id: $('#year_id').val(), exam_id: $('#exam_id').val(),
            class_id: $('#class_id').val(), section_id: $('#section_id').val(), result_source: $('#result_source').val(),
            result_status: $('#result_status').val()
        }), function(r) {
            if (r.success) $('#reportResult').html(r.html);
            else $('#reportResult').html('<div class="alert alert-danger">' + r.message + '</div>');
        }, 'json').fail(function() { $('#reportResult').html('<div class="alert alert-danger">Error generating report.</div>'); });
    });
});
</script>