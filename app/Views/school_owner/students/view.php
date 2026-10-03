<?php
$status_list = get_status();
$student = $student ?? null;
$enrollment = $enrollment ?? null;
$enrollments = $enrollments ?? ($enrollment ? [$enrollment] : []);
$guardians = $guardians ?? [];
$address = $address ?? null;
$documents = $documents ?? [];

$photo_path = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
$full_name = $student->first_name;
if ($student->middle_name) $full_name .= ' ' . $student->middle_name;
if ($student->last_name) $full_name .= ' ' . $student->last_name;
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-people"></i> <?= esc($full_name) ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('school-owner/students/profile-print/' . $student->token) ?>" target="_blank" class="btn btn-sm btn-primary">
            <i class="fa fa-print"></i> Print
        </a>
        <a href="<?= base_url('school-owner/students/profile-pdf/' . $student->token) ?>" target="_blank" class="btn btn-sm btn-danger">
            <i class="fa fa-file-pdf-o"></i> Download PDF
        </a>
        <a href="<?= base_url('school-owner/students/edit/' . $student->token) ?>" class="btn btn-sm btn-info">
            <i class="fa fa-edit"></i> <?= lang('Common.text_edit') ?>
        </a>
        <a href="<?= base_url('school-owner/students') ?>" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Student.back_to_student') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <!-- Photo Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?= esc($photo_path) ?>" class="img-thumbnail mb-3" style="max-width: 140px; max-height: 140px;" alt="<?= esc($full_name) ?>">
                <h5><?= esc($full_name) ?></h5>
                <p class="text-muted mb-1"><?= esc($student->student_code) ?></p>
                <p class="mb-0">
                    <?php if ((int) $student->status === 1): ?>
                        <span class="badge text-bg-success"><?= esc($status_list[1] ?? 'Published') ?></span>
                    <?php elseif ((int) $student->status === 2): ?>
                        <span class="badge text-bg-danger"><?= esc($status_list[2] ?? 'Trash') ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-warning"><?= esc($status_list[0] ?? 'Unpublished') ?></span>
                    <?php endif; ?>
                </p>
                <p class="mt-2"><span class="badge text-bg-info"><?= esc($student->student_status ?? 'Active') ?></span></p>
            </div>
        </div>

        <!-- QR Code Card -->
        <?php if (!empty($student->student_qr_code)): ?>
        <div class="card mb-4">
            <div class="card-header text-center">QR Code</div>
            <div class="card-body text-center">
                <?php
                    $qr_data = base_url('school-owner/students/view/' . $student->token);
                    $qr_label = esc($student->student_qr_code);
                ?>
                <a href="<?= $qr_data ?>" target="_blank">
                    <img src="<?= generate_qr_code($qr_data, 150) ?>" class="img-fluid" alt="QR Code" style="max-width: 180px;">
                </a>
                <p class="mt-2 mb-0 text-muted small"><?= $qr_label ?></p>
                <p class="mb-0 text-muted" style="font-size: 11px;">Scan with mobile camera to view student profile</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Address Card -->
        <?php if ($address): ?>
        <div class="card mb-4">
            <div class="card-header">Address</div>
            <div class="card-body">
                <p><strong>Present:</strong><br><?= nl2br(esc($address->present_address ?: '-')) ?></p>
                <p><strong>Permanent:</strong><br><?= nl2br(esc($address->permanent_address ?: '-')) ?></p>
                <?php if ($address->city || $address->district): ?>
                <p><strong>City/District:</strong> <?= esc($address->city ?: '') ?><?= ($address->city && $address->district) ? ', ' : '' ?><?= esc($address->district ?: '') ?></p>
                <?php endif; ?>
                <?php if ($address->state || $address->postal_code): ?>
                <p><strong>State/Postal:</strong> <?= esc($address->state ?: '') ?><?= ($address->state && $address->postal_code) ? ', ' : '' ?><?= esc($address->postal_code ?: '') ?></p>
                <?php endif; ?>
                <?php if ($address->country): ?>
                <p><strong>Country:</strong> <?= esc($address->country) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <!-- Personal Info -->
        <div class="card mb-4">
            <div class="card-header">Basic Information</div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr><th width="180">Student Code</th><td><?= esc($student->student_code) ?></td></tr>
                    <tr><th>Registration No</th><td><?= esc($student->registration_no ?: '-') ?></td></tr>
                    <tr><th>First Name</th><td><?= esc($student->first_name) ?></td></tr>
                    <tr><th>Middle Name</th><td><?= esc($student->middle_name ?: '-') ?></td></tr>
                    <tr><th>Last Name</th><td><?= esc($student->last_name ?: '-') ?></td></tr>
                    <tr><th>Gender</th><td><?= esc($student->gender ?: '-') ?></td></tr>
                    <tr><th>Date of Birth</th><td><?= $student->date_of_birth ? esc(date('d M, Y', strtotime($student->date_of_birth))) : '-' ?></td></tr>
                    <tr><th>Blood Group</th><td><?= esc($student->blood_group ?: '-') ?></td></tr>
                    <tr><th>Religion</th><td><?= esc($student->religion ?: '-') ?></td></tr>
                    <tr><th>Nationality</th><td><?= esc($student->nationality ?: '-') ?></td></tr>
                    <tr><th>Phone</th><td><?= esc($student->phone ?: '-') ?></td></tr>
                    <tr><th>Email</th><td><?= esc($student->email ?: '-') ?></td></tr>
                    <tr><th>Admission Date</th><td><?= $student->admission_date ? esc(date('d M, Y', strtotime($student->admission_date))) : '-' ?></td></tr>
                    <tr><th>Admission Source</th><td><?= esc($student->admission_source ?? '-') ?></td></tr>
                    <tr><th>Student Status</th><td><?= esc(ucfirst($student->student_status ?? 'Active')) ?></td></tr>
                    <tr><th>RFID Number</th><td><?= esc($student->rfid_number ?: '-') ?></td></tr>
                    <tr><th>Created</th><td><?= $student->created_at ? esc(date('d M, Y H:i', strtotime($student->created_at))) : '-' ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Academic Info -->
        <?php if (!empty($enrollments)): ?>
        <div class="card mb-4">
            <div class="card-header">Academic Information</div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Shift</th>
                            <th>Roll No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enr): ?>
                        <tr>
                            <td><?= esc($enr->session_title ?? '-') ?></td>
                            <td><?= esc($enr->class_title ?? '-') ?></td>
                            <td><?= esc($enr->section_title ?? '-') ?></td>
                            <td><?= esc($enr->department_title ?? '-') ?></td>
                            <td><?= esc($enr->category_title ?? '-') ?></td>
                            <td><?= esc($enr->shift_title ?? '-') ?></td>
                            <td><?= esc($enr->roll_no ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Guardians -->
        <?php if (!empty($guardians)): ?>
        <div class="card mb-4">
            <div class="card-header">Guardians</div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($guardians as $guardian): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><?= esc($guardian->name) ?></h6>
                                <p class="mb-1"><strong>Relation:</strong> <?= esc($guardian->relation_type) ?></p>
                                <?php if ($guardian->phone): ?><p class="mb-1"><strong>Phone:</strong> <?= esc($guardian->phone) ?></p><?php endif; ?>
                                <?php if ($guardian->email): ?><p class="mb-1"><strong>Email:</strong> <?= esc($guardian->email) ?></p><?php endif; ?>
                                <?php if ($guardian->occupation): ?><p class="mb-0"><strong>Occupation:</strong> <?= esc($guardian->occupation) ?></p><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subjects -->
        <?php if (!empty($subjects)): ?>
        <div class="card mb-4">
            <div class="card-header">Subjects</div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subj): ?>
                        <tr>
                            <td><?= esc($subj->title ?? '-') ?></td>
                            <td><?= esc($subj->subject_code ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Custom Fields (Grouped) -->
        <?php if (!empty($customFieldsByGroup)): ?>
            <?php foreach ($customFieldsByGroup as $groupName => $groupFields): ?>
            <div class="card mb-4">
                <div class="card-header"><?= esc($groupName) ?></div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <?php foreach ($groupFields as $cf): ?>
                            <tr>
                                <th width="200"><?= esc($cf['field']->label) ?></th>
                                <td><?= esc($cf['value'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Documents -->
        <?php if (!empty($documents)): ?>
        <div class="card mb-4">
            <div class="card-header">Documents</div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Title</th>
                            <th>File</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= esc($doc->document_type ?? '-') ?></td>
                            <td><?= esc($doc->document_title ?? '-') ?></td>
                            <td><a href="<?= base_url('uploads/' . esc($doc->file_name)) ?>" target="_blank"><?= esc($doc->file_name) ?></a></td>
                            <td><?= $doc->file_size ? round($doc->file_size / 1024, 2) . ' KB' : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
</div>
