<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead>
            <tr>
                <th>Roll</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Section</th>
                <th>Total Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
                <th>Result</th>
                <th>Principal Remark</th>
                <th>Teacher Remark</th>
                <th>Next Session</th>
                <th>Next Class</th>
                <th>Next Section</th>
                <th>Next Roll</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
                <?php 
                $studentName = trim(($result->first_name ?? '') . ' ' . ($result->middle_name ?? '') . ' ' . ($result->last_name ?? ''));
                $studentName = preg_replace('/\s+/', ' ', $studentName);
                ?>
                <tr>
                    <td><?= esc($result->roll_no ?? '') ?></td>
                    <td><?= esc($studentName) ?></td>
                    <td><?= esc($result->student_code ?? '') ?></td>
                    <td><?= esc($result->section_title ?? '') ?></td>
                    <td><?= number_format($result->total_marks ?? 0, 2) ?></td>
                    <td><?= number_format($result->percentage ?? 0, 2) ?>%</td>
                    <td><?= esc($result->grade_name ?? $result->grade_letter ?? '') ?></td>
                    <td>
                        <span class="badge bg-<?= strtoupper($result->result_status ?? '') == 'PASS' ? 'success' : 'danger' ?>">
                            <?= esc($result->result_status ?? '') ?>
                        </span>
                    </td>
                    <td><?= esc($result->principal_remark ?? '') ?></td>
                    <td><?= esc($result->teacher_remark ?? '') ?></td>
                    <td><?= esc($result->next_session_title ?? '') ?></td>
                    <td><?= esc($result->next_class_title ?? '') ?></td>
                    <td><?= esc($result->next_section_title ?? '') ?></td>
                    <td><?= esc($result->next_roll ?? '') ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-remarks-btn" 
                                data-id="<?= $result->id ?>"
                                data-principal="<?= esc($result->principal_remark ?? '') ?>"
                                data-teacher="<?= esc($result->teacher_remark ?? '') ?>"
                                data-next-session="<?= esc($result->next_session_id ?? '') ?>"
                                data-next-class="<?= esc($result->next_class_id ?? '') ?>"
                                data-next-section="<?= esc($result->next_section_id ?? '') ?>"
                                data-next-roll="<?= esc($result->next_roll ?? '') ?>">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>