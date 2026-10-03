<?php
$history = $history ?? [];
$exam    = $exam ?? null;
?>
<div class="row">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-clock-history"></i> Exam Result History: <?= esc($exam->title ?? 'N/A') ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('school-owner/dashboard') ?>" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="20px">#</th>
                            <th>Student</th>
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
                            <th>Changed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $key => $item): 
                            $studentName = trim($item->first_name . ' ' . ($item->middle_name ?? '') . ' ' . ($item->last_name ?? ''));
                            $studentName = preg_replace('/\s+/', ' ', $studentName);
                        ?>
                        <tr>
                            <td><?= ++$key ?></td>
                            <td><b><?= esc($studentName) ?></b><br><small>Code: <?= esc($item->student_code ?? '-') ?></small></td>
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
                            <td><?= $item->changed_by ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                        <tr><td colspan="13" class="text-center">No history records found for this exam.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>