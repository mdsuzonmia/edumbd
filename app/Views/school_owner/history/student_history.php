<?php
$examHistory    = $examHistory ?? [];
$subjectHistory = $subjectHistory ?? [];
$student        = $student ?? null;
$student_name   = $student_name ?? '';
?>
<div class="row">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-clock-history"></i> Student Result History: <?= esc($student_name) ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('school-owner/dashboard') ?>" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Exam Result History</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="20px">#</th>
                            <th>Exam</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Total Marks</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>GPA</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examHistory as $key => $item): ?>
                        <tr>
                            <td><?= ++$key ?></td>
                            <td><?= esc($item->exam_title ?? '-') ?></td>
                            <td><?= esc($item->class_title ?? '-') ?></td>
                            <td><?= esc($item->section_title ?? '-') ?></td>
                            <td><?= $item->total_marks ?? 0 ?></td>
                            <td><?= $item->obtained_marks ?? 0 ?></td>
                            <td><?= $item->percentage ?? 0 ?>%</td>
                            <td><?= $item->gpa ?? 0 ?></td>
                            <td><?= $item->letter_grade ?? $item->grade ?? '-' ?></td>
                            <td><?= $item->result_status == 'passed' ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>' ?></td>
                            <td><?= esc($item->action_type ?? '-') ?></td>
                            <td><?= $item->version_no ?? 1 ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($examHistory)): ?>
                        <tr><td colspan="12" class="text-center">No exam result history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Subject Result History</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="20px">#</th>
                            <th>Exam</th>
                            <th>Subject</th>
                            <th>Full Mark</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectHistory as $key => $item): ?>
                        <tr>
                            <td><?= ++$key ?></td>
                            <td><?= esc($item->exam_title ?? '-') ?></td>
                            <td><?= esc($item->subject_title ?? '-') ?></td>
                            <td><?= $item->full_mark ?? 0 ?></td>
                            <td><?= $item->obtained_mark ?? 0 ?></td>
                            <td><?= $item->percentage ?? 0 ?>%</td>
                            <td><?= $item->letter_grade ?? $item->grade ?? '-' ?></td>
                            <td><?= $item->grade_point ?? 0 ?></td>
                            <td><?= $item->is_fail ? '<span class="badge text-bg-danger">Failed</span>' : '<span class="badge text-bg-success">Passed</span>' ?></td>
                            <td><?= esc($item->action_type ?? '-') ?></td>
                            <td><?= $item->version_no ?? 1 ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subjectHistory)): ?>
                        <tr><td colspan="11" class="text-center">No subject result history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>