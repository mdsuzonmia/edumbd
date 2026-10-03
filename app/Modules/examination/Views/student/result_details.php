<?php 
$student_name = $student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '');
$student_code = $student->student_code ?? '';
$roll_no = $student->roll_no ?? 'N/A';
$school_name = $school->name ?? 'N/A';
$exam_name = $exam->title ?? 'N/A';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-person"></i> My Result Details</h3>
    </div>
    <div class="col-sm-6 text-end pt-0">
        <a href="<?= base_url('examination/student/result') ?>" class="btn btn-secondary">
            <i class="fa fa-list"></i> Back to Results
        </a>
        <button type="button" class="btn btn-success" onclick="printResult()">
            <i class="bi bi-printer"></i> Print Result
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <!-- Student Information -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Student Information</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="30%">Name</th>
                                <td><?= esc($student_name) ?></td>
                            </tr>
                            <tr>
                                <th>Student Code</th>
                                <td><?= esc($student_code) ?></td>
                            </tr>
                            <tr>
                                <th>Roll No</th>
                                <td><?= esc($roll_no) ?></td>
                            </tr>
                            <tr>
                                <th>School</th>
                                <td><?= esc($school_name) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Exam Information</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="30%">Exam Name</th>
                                <td><?= esc($exam_name) ?></td>
                            </tr>
                            <tr>
                                <th>Result Status</th>
                                <td>
                                    <?php if ($result->passed_subjects > 0 && $result->failed_subjects == 0): ?>
                                        <span class="badge bg-success">PASSED</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">FAILED</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Subject-wise Marks with Distribution -->
                <h6 class="border-bottom pb-2">Subject-wise Marks</h6>
                <?php if (!empty($subject_marks)): 
                    // Get all unique distribution names
                    $allDistNames = [];
                    foreach ($distribution_data as $subject_id => $dists) {
                        foreach (array_keys($dists) as $distName) {
                            if (!in_array($distName, $allDistNames)) {
                                $allDistNames[] = $distName;
                            }
                        }
                    }
                ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th style="text-align:left;">Subject</th>
                                    <?php foreach ($allDistNames as $distName): ?>
                                        <th style="text-align:center;"><?= esc(ucfirst($distName)) ?></th>
                                    <?php endforeach; ?>
                                    <th style="text-align:center;">Total Obtained</th>
                                    <th style="text-align:center;">%</th>
                                    <th style="text-align:center;">Grade</th>
                                    <th style="text-align:center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($subject_marks as $sm): 
                                    $subjectId = $sm->subject_id ?? 0;
                                    $obtained = $sm->obtained_mark ?? 0;
                                    $full = $sm->full_mark ?? 0;
                                    $percentage = $full > 0 ? round(($obtained / $full) * 100) : 0;
                                    $subjectDistributions = $distribution_data[$subjectId] ?? [];
                                ?>
                                    <tr>
                                        <td><?= esc($sm->subject_title ?? '-') ?></td>
                                        <?php foreach ($allDistNames as $distName): 
                                            if (isset($subjectDistributions[$distName])) {
                                                $distMark = $subjectDistributions[$distName];
                                                $distObtained = round($distMark['obtained'] ?? 0);
                                                $distFull = round($distMark['full'] ?? 0);
                                                echo '<td style="text-align:center;">' . $distObtained . '/' . $distFull . '</td>';
                                            } else {
                                                echo '<td style="text-align:center;"></td>';
                                            }
                                        ?>
                                        <?php endforeach; ?>
                                        <td style="text-align:center;"><?= round($obtained) ?></td>
                                        <td style="text-align:center;"><?= $percentage ?>%</td>
                                        <td style="text-align:center;"><?= esc($sm->letter_grade ?? $sm->grade ?? '-') ?></td>
                                        <td style="text-align:center;">
                                            <?php 
                                            $is_pass = isset($sm->is_fail) ? !$sm->is_fail : ($obtained >= ($full * 0.33));
                                            if ($is_pass): ?>
                                                <span class="badge bg-success">Pass</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Fail</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:bold; background:#f9f9f9;">
                                    <td>Total</td>
                                    <?php foreach ($allDistNames as $distName): ?>
                                        <td style="text-align:center;"></td>
                                    <?php endforeach; ?>
                                    <td style="text-align:center;"><?= round($result->obtained_marks ?? 0) ?></td>
                                    <td style="text-align:center;"><?= round($result->percentage ?? 0) ?>%</td>
                                    <td style="text-align:center;"><?= esc($result->letter_grade ?? $result->grade ?? '-') ?></td>
                                    <td style="text-align:center;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No subject marks found for this result.</p>
                <?php endif; ?>

                <!-- Result Summary -->
                <?php if ($result): ?>
                    <hr>
                    <h6 class="border-bottom pb-2">Result Summary</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Subjects</h5>
                                    <p class="card-text display-6"><?= esc($result->total_subjects ?? 0) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Passed</h5>
                                    <p class="card-text display-6"><?= esc($result->passed_subjects ?? 0) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Failed</h5>
                                    <p class="card-text display-6"><?= esc($result->failed_subjects ?? 0) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Percentage</h5>
                                    <p class="card-text display-6"><?= esc(number_format($result->percentage ?? 0, 2)) ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="30%">Grade</th>
                                    <td><strong><?= esc($result->letter_grade ?? $result->grade ?? 'N/A') ?></strong></td>
                                </tr>
                                <tr>
                                    <th>GPA</th>
                                    <td><strong><?= esc(number_format($result->gpa ?? 0, 2)) ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Total Marks</th>
                                    <td><strong><?= esc($result->obtained_marks ?? 0) ?> / <?= esc($result->total_marks ?? 0) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function printResult() {
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Result</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
    printWindow.document.write('th { background-color: #f8f9fa; font-weight: bold; }');
    printWindow.document.write('.card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(document.querySelector('.card-body').innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() { printWindow.print(); }, 500);
}
</script>