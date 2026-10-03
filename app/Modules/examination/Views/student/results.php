<?php 
$student_name = $student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '');
$student_code = $student->student_code ?? '';
$school_name = $school->name ?? 'N/A';
?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-person"></i> My Results</h3>
    </div>
    <div class="col-sm-6 text-end pt-0">
        <a href="<?= base_url('student/dashboard') ?>" class="btn btn-secondary">
            <i class="fa fa-list"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
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
                                <th>School</th>
                                <td><?= esc($school_name) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <h6 class="border-bottom pb-2">Academic Enrollments</h6>
                <?php if (!empty($enrollments)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Roll No</th>
                                    <th>Exam</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enr): 
                                    $enrollment_exams_list = $enrollment_exams[$enr->id] ?? [];
                                ?>
                                    <tr>
                                        <td><?= esc($enr->session_title ?? 'N/A') ?></td>
                                        <td><?= esc($enr->class_title ?? 'N/A') ?></td>
                                        <td><?= esc($enr->section_title ?? 'N/A') ?></td>
                                        <td><?= esc($enr->roll_no ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (!empty($enrollment_exams_list)): ?>
                                                <select class="form-select form-select-sm exam-select" data-enrollment-id="<?= $enr->id ?>">
                                                    <option value="">Select Exam</option>
                                                    <?php foreach ($enrollment_exams_list as $exam): ?>
                                                        <option value="<?= $exam->id ?>" data-token="<?= esc($exam->result_token ?? '') ?>"><?= esc($exam->title) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="text-muted">No exams</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary btn-view-result" 
                                                    data-enrollment-id="<?= $enr->id ?>" 
                                                    data-token="">
                                                <i class="bi bi-eye"></i> View Result
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No enrollment records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable/disable view result button based on exam selection
    document.querySelectorAll('.exam-select').forEach(function(select) {
        select.addEventListener('change', function() {
            var enrollmentId = this.dataset.enrollmentId;
            var selectedOption = this.options[this.selectedIndex];
            var token = selectedOption ? selectedOption.dataset.token : '';
            var button = document.querySelector('.btn-view-result[data-enrollment-id="' + enrollmentId + '"]');
            if (button) {
                button.dataset.token = token;
                button.disabled = !token;
            }
        });
    });

    // View result button click
    document.querySelectorAll('.btn-view-result').forEach(function(button) {
        button.addEventListener('click', function() {
            var token = this.dataset.token;
            if (!token) {
                alert('Please select an exam first.');
                return;
            }
            window.location.href = '<?= base_url('examination/student/result/view/') ?>' + token;
        });
    });
});
</script>