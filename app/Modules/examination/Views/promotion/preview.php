<div class="table-responsive">
    <div class="row mb-3 mt-3">
        <div class="col-md-6">
            <div class="alert alert-info">
                <strong>Promotion Summary:</strong><br>
                From: <strong><?= esc($from_class->title ?? 'N/A') ?></strong> (<?= esc($from_session->title ?? 'N/A') ?>) 
                To: <strong><?= esc($to_class->title ?? 'N/A') ?></strong> (<?= esc($to_session->title ?? 'N/A') ?>)<br>
                <strong>Eligible Students: <?= $eligible_count ?></strong>
                <?php if ($already_count > 0): ?>
                    <span class="text-warning"> (<?= $already_count ?> already enrolled)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6 text-right">
            <div class="form-group">
                <label for="promotion_notes">Notes</label>
                <textarea name="promotion_notes" id="promotion_notes" class="form-control" rows="2" placeholder="Optional notes about this promotion"></textarea>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <button type="button" class="btn btn-success" onclick="promoteAll()">
                <i class="fa fa-arrow-up"></i> Promote All
            </button>
            <button type="button" class="btn btn-primary" onclick="promoteSelected()">
                <i class="fa fa-check"></i> Promote Selected
            </button>
        </div>
    </div>

    <table class="table table-striped table-bordered">
        <thead>
            <tr style="background:#1a73e8;color:#fff;">
                <th width="30"><input type="checkbox" onchange="selectAllStudents(this)"></th>
                <th>#</th>
                <th>Roll</th>
                <th>Student</th>
                <th class="text-center">Grade</th>
                <th class="text-center">Position</th>
                <th class="text-center">GPA</th>
                <th>Result</th>
                <th>Recommendation</th>
                <th>To Session</th>
                <th>To Class</th>
                <th>To Section</th>
                <th>New Roll</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="11" class="text-center text-muted">No eligible students found.</td>
                </tr>
            <?php else: ?>
                <?php $sl = 0; ?>
                <?php foreach ($students as $student): ?>
                    <?php $sl++; ?>
                    <?php
                        $resultStatus = !empty($student['result_status']) ? $student['result_status'] : 'N/A';
                        $recommendation = !empty($student['recommendation']) ? $student['recommendation'] : 'N/A';
                        $badgeClass = $recommendation === 'Promote' ? 'success' : ($recommendation === 'Repeat' ? 'warning' : ($recommendation === 'Skip' ? 'danger' : 'secondary'));
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="student-checkbox" id="student_<?= $student['student_id'] ?>" value="<?= $student['student_id'] ?>" onchange="toggleRollInput(<?= $student['student_id'] ?>)" checked>
                        </td>
                        <td><?= $sl ?></td>
                        <td><?= esc($student['roll_no']) ?></td>
                        <td><?= esc($student['name']) ?></td>
                        <td class="text-center"><?= esc(!empty($student['grade']) ? $student['grade'] : 'N/A') ?></td>
                        <td class="text-center"><?= esc(!empty($student['position']) ? $student['position'] : 'N/A') ?></td>
                        <td class="text-center"><?= isset($student['gpa']) ? number_format((float) $student['gpa'], 2) : 'N/A' ?></td>
                        <td><?= esc($resultStatus) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= esc($badgeClass) ?>" style="color:#fff;">
                                <?= esc($recommendation) ?>
                            </span>
                        </td>
                        <td>
                            <select class="form-control form-control-sm to-session-select" data-student="<?= $student['student_id'] ?>" style="width:100px;">
                                <?php foreach ($year_list as $yid => $yval): ?>
                                    <option value="<?= $yid ?>" <?= $yid == $to_session_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm to-class-select" data-student="<?= $student['student_id'] ?>" style="width:120px;">
                                <?php foreach ($class_list as $cid => $cval): ?>
                                    <option value="<?= $cid ?>" <?= $cid == $to_class_id ? 'selected' : '' ?>><?= esc($cval) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm to-section-select" data-student="<?= $student['student_id'] ?>" style="width:100px;">
                                <option value="0">Same</option>
                                <?php foreach ($section_list as $sid => $sval): ?>
                                    <option value="<?= $sid ?>" <?= ($student['to_section_id'] ?? 0) == $sid ? 'selected' : '' ?>><?= esc($sval) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" id="roll_no_<?= $student['student_id'] ?>" value="<?= esc($student['new_roll']) ?>" style="width:80px;" onchange="autoGenerateRoll(<?= $student['student_id'] ?>)">
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row mt-3">
        <div class="col-md-12">
            <button type="button" class="btn btn-success" onclick="promoteAll()">
                <i class="fa fa-arrow-up"></i> Promote All
            </button>
            <button type="button" class="btn btn-primary" onclick="promoteSelected()">
                <i class="fa fa-check"></i> Promote Selected
            </button>
        </div>
    </div>
</div>

<script>
var csrfToken = '<?= csrf_hash() ?>';

function promoteSelected() {
    var selectedStudents = [];
    var rollNos = [];
    var recommendations = [];
    var toSessions = [];
    var toClasses = [];
    var toSections = [];

    $('.student-checkbox:checked').each(function() {
        var studentId = parseInt($(this).val());
        if (studentId > 0) {
            selectedStudents.push(studentId);
            var rollInput = $('#roll_no_' + studentId);
            rollNos.push(rollInput.length ? rollInput.val() : '');
            
            // Get recommendation from the row
            var row = $(this).closest('tr');
            var rec = row.find('td:eq(8)').text().trim();
            recommendations.push(rec);
            
            // Get selected to_session, to_class, to_section from dropdowns
            var toSession = row.find('.to-session-select').val();
            var toClass = row.find('.to-class-select').val();
            var toSection = row.find('.to-section-select').val();
            toSessions.push(toSession);
            toClasses.push(toClass);
            toSections.push(toSection);
        }
    });

    if (selectedStudents.length === 0) {
        alert('Please select at least one student');
        return;
    }

    if (!confirm('Are you sure you want to promote ' + selectedStudents.length + ' students?')) {
        return;
    }

    var formData = {
        school_id: parseInt($('#school_id').val()),
        from_session_id: parseInt($('#from_session_id').val()),
        from_class_id: parseInt($('#from_class_id').val()),
        notes: $('#promotion_notes').val() || '',
        '<?= csrf_token() ?>': csrfToken
    };

    $.each(selectedStudents, function(i, id) {
        formData['student_ids[' + i + ']'] = id;
        formData['roll_nos[' + i + ']'] = rollNos[i] || '';
        formData['recommendations[' + i + ']'] = recommendations[i] || 'Hold';
        formData['to_session_ids[' + i + ']'] = toSessions[i];
        formData['to_class_ids[' + i + ']'] = toClasses[i];
        formData['to_section_ids[' + i + ']'] = toSections[i];
    });

    $.ajax({
        url: '<?= base_url('examination/promotion/process') ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                alert(resp.message);
                $('#loadStudentsBtn').click();
            } else {
                alert(resp.message);
            }
        },
        error: function() {
            alert('An error occurred during promotion.');
        }
    });
}

