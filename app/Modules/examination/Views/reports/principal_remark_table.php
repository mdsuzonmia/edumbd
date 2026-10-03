<?php 
$serial = 1;
if (empty($results)): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> No results found.</div>
<?php else: ?>
    <?php foreach ($results as $result): ?>
    <?php 
    $studentId = $result->student_id;
    $studentName = trim(($result->first_name ?? '') . ' ' . ($result->middle_name ?? '') . ' ' . ($result->last_name ?? ''));
    $studentName = preg_replace('/\s+/', ' ', $studentName);
    $studentCode = $result->student_code ?? '';
    $rollNo = $result->roll_no ?? '-';
    $teacherRemark = $result->teacher_remarks ?? '';
    $resultStatus = ($result->failed_subjects ?? 0) > 0 ? 'FAILED' : 'PASSED';
    $statusClass = ($result->failed_subjects ?? 0) > 0 ? 'badge bg-danger' : 'badge bg-success';
    ?>
    <div class="card p-2 mb-2">
        <div class="body">
            <h5 class="mb-3">
                <?= esc($studentName) ?>
                <br>
                <span class="mark-student-id" style="font-size: 12px; color: #666;">
                    <i class="bi bi-person-badge"></i> Student ID: <?= esc($studentCode) ?>
                </span>
                <span class="mark-roll" style="font-size: 12px; color: #666; margin-left: 15px;">
                    <i class="bi bi-bookmark"></i> Roll: <?= esc($rollNo) ?>
                </span>
                <span class="mark-rank" style="font-size: 12px; color: #666; margin-left: 15px;">
                    <i class="bi bi-trophy"></i> Position: <?= esc($result->class_rank ?? '-') ?>
                </span>
            </h5>
            
            <!-- Academic Results Table -->
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered mb-3" style="font-size: 13px;">
                        <thead>
                            <tr style="background:#f0f0f0;">
                                <th class="text-center p-1" style="width: 60px;">Rank</th>
                                
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <th class="text-center p-1"><?= esc($subject->title) ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <th class="text-center p-1" style="width: 80px;">Total</th>
                                <th class="text-center p-1" style="width: 70px;">%</th>
                                <th class="text-center p-1" style="width: 60px;">GPA</th>
                                <th class="text-center p-1" style="width: 70px;">Grade</th>
                                <th class="text-center p-1" style="width: 80px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center p-1" style="font-weight: bold;"><?= $serial++ ?></td>
                                
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <?php 
                                        $mark = $subject_marks[$studentId][$subject->id] ?? null;
                                        $obtained = $mark ? ($mark->obtained_mark ?? 0) : 0;
                                        $full = $mark ? ($mark->full_mark ?? 0) : 0;
                                        $percentage = $full > 0 ? round(($obtained / $full) * 100, 2) : 0;
                                        $grade = $mark ? ($mark->letter_grade ?? $mark->grade ?? '-') : '-';
                                        ?>
                                        <td class="text-center p-1">
                                            <div style="font-size: 12px;">
                                                <strong><?= round($obtained) ?>/<?= round($full) ?></strong><br>
                                                <small class="text-muted"><?= $percentage ?>%</small><br>
                                                <span class="badge bg-info"><?= esc($grade) ?></span>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <td class="text-center p-1" style="font-weight: bold;"><?= round($result->obtained_marks ?? 0) ?></td>
                                <td class="text-center p-1"><?= number_format($result->percentage ?? 0, 2) ?>%</td>
                                <td class="text-center p-1"><?= number_format($result->gpa ?? 0, 2) ?></td>
                                <td class="text-center p-1" style="font-weight: bold; font-size: 14px;">
                                    <span class="badge bg-primary"><?= esc($result->letter_grade ?? $result->grade ?? '-') ?></span>
                                </td>
                                <td class="text-center p-1">
                                    <span class="<?= $statusClass ?>"><?= $resultStatus ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Attendance and Remarks Section -->
            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label mb-2"><strong><i class="bi bi-calendar-week"></i> Attendance</strong></label>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label mb-1" style="font-size: 11px; color: #666;">Working Days</label>
                            <input type="number" 
                                   class="form-control form-control-sm attendance-input working-days" 
                                   data-result-id="<?= $result->id ?>"
                                   value="<?= esc($result->working_days ?? '') ?>" 
                                   min="0" 
                                   placeholder="e.g. 200"
                                   style="text-align: center;">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1" style="font-size: 11px; color: #666;">Present Days</label>
                            <input type="number" 
                                   class="form-control form-control-sm attendance-input present-days" 
                                   data-result-id="<?= $result->id ?>"
                                   value="<?= esc($result->present_days ?? '') ?>" 
                                   min="0" 
                                   placeholder="e.g. 180"
                                   style="text-align: center;">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1" style="font-size: 11px; color: #666;">Absent Days</label>
                            <input type="number" 
                                   class="form-control form-control-sm attendance-input absent-days" 
                                   data-result-id="<?= $result->id ?>"
                                   value="<?= esc($result->absent_days ?? '') ?>" 
                                   min="0" 
                                   placeholder="e.g. 20"
                                   style="text-align: center;">
                        </div>
                    </div>
                    <div class="attendance-percentage text-center mt-2 p-2" style="font-size: 13px; font-weight: 600; background: #f8f9fa; border-radius: 4px;">
                        <?php 
                        $attPct = $result->attendance_percentage ?? 0;
                        $attClass = $attPct >= 75 ? 'text-success' : ($attPct >= 60 ? 'text-warning' : 'text-danger');
                        ?>
                        <span class="<?= $attClass ?>">
                            <i class="bi bi-calendar-check"></i> 
                            <?= $attPct > 0 ? number_format($attPct, 1) . '%' : 'N/A' ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label mb-2"><strong><i class="bi bi-chat-left-text"></i> Teacher Remarks</strong></label>
                    <textarea class="form-control teacher-remark-textarea" 
                              data-result-id="<?= $result->id ?>" 
                              rows="3" 
                              placeholder="Enter teacher remark..."><?= esc($teacherRemark) ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label mb-2"><strong><i class="bi bi-chat-square-quote"></i> Principal Remarks</strong></label>
                    <textarea class="form-control principal-remark-textarea" 
                              data-result-id="<?= $result->id ?>" 
                              rows="3" 
                              placeholder="Enter principal remark..."><?= esc($result->principal_remarks ?? '') ?></textarea>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100 save-student-btn" data-result-id="<?= $result->id ?>">
                        <i class="bi bi-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
