<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-qr-code-scan"></i> Student Verification</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($status === 'error'): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?= esc($message) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Student Verified Successfully
                            </div>

                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <?php
                                    $photo = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
                                    ?>
                                    <img src="<?= $photo ?>" alt="Student Photo" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                    <h5 class="mt-2"><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></h5>
                                </div>

                                <div class="col-md-8">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Student Code</th>
                                            <td><?= esc($student->student_code) ?></td>
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
                                    <h6><i class="bi bi-book"></i> Current Enrollment</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th width="40%">Academic Year</th>
                                            <td><?= esc($enrollment->session_title ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Class</th>
                                            <td><?= esc($enrollment->class_title ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Roll No</th>
                                            <td><?= esc($enrollment->roll_no ?? 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
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