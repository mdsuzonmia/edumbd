<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-person-badge"></i> Student Public Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($status === 'error'): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?= esc($message) ?>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <?php
                                    $photo = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
                                    ?>
                                    <img src="<?= $photo ?>" alt="Student Photo" class="img-thumbnail rounded-circle" style="width: 180px; height: 180px; object-fit: cover;">
                                    <h5 class="mt-2"><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></h5>
                                    <p class="text-muted"><?= esc($student->student_code) ?></p>
                                </div>

                                <div class="col-md-9">
                                    <h6 class="border-bottom pb-2">Personal Information</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th width="30%">Full Name</th>
                                            <td><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gender</th>
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
                                        <tr>
                                            <th>Religion</th>
                                            <td><?= esc($student->religion ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Nationality</th>
                                            <td><?= esc($student->nationality ?? 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-building"></i> School Information</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th width="40%">School Name</th>
                                            <td><?= esc($school->name ?? 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6><i class="bi bi-geo-alt"></i> Address</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th width="40%">Present Address</th>
                                            <td><?= esc($address->present_address ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Permanent Address</th>
                                            <td><?= esc($address->permanent_address ?? 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <h6><i class="bi bi-book"></i> Academic Enrollments</h6>
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

                            <?php if (!empty($guardians)): ?>
                                <hr>
                                <h6><i class="bi bi-people"></i> Guardian Information</h6>
                                <?php foreach ($guardians as $guardian): ?>
                                    <div class="card mb-2">
                                        <div class="card-body py-2">
                                            <strong><?= esc($guardian->name) ?></strong> 
                                            <span class="badge bg-info"><?= esc($guardian->relation_type ?? 'Guardian') ?></span>
                                            <?php if (!empty($guardian->phone)): ?>
                                                <br><small><i class="bi bi-telephone"></i> <?= esc($guardian->phone) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($guardian->email)): ?>
                                                <br><small><i class="bi bi-envelope"></i> <?= esc($guardian->email) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?= base_url() ?>" class="btn btn-primary">Go to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>