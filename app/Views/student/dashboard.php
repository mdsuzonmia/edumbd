<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-sm-6">
            <h3 class="text-secondary mb-0"><i class="bi bi-speedometer2"></i> Student Dashboard</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <?php
                            $photo = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
                            ?>
                            <img src="<?= $photo ?>" alt="Student Photo" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <h5 class="mt-2"><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></h5>
                            <p class="text-muted"><?= esc($student->student_code) ?></p>
                        </div>

                        <div class="col-md-8">
                            <h6 class="border-bottom pb-2">Personal Information</h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="30%">Gender</th>
                                    <td><?= esc($student->gender ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td><?= !empty($student->date_of_birth) ? date('d M, Y', strtotime($student->date_of_birth)) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?= esc($student->phone ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= esc($student->email ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <h6 class="border-bottom pb-2">Current Enrollments</h6>
                    <?php if (!empty($enrollments)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Roll No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enr): ?>
                                        <tr>
                                            <td><?= esc($enr->session_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->class_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->section_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->roll_no ?? 'N/A') ?></td>
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
</div>