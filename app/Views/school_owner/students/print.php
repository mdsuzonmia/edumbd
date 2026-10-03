<?php
$status_list = get_status();
$student = $student ?? null;
$enrollment = $enrollment ?? null;
$enrollments = $enrollments ?? ($enrollment ? [$enrollment] : []);
$guardians = $guardians ?? [];
$address = $address ?? null;
$documents = $documents ?? [];
$school = $school ?? null;

$photo_path = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
$baseUrl = rtrim(base_url(), '/');
$photo_path_abs = $baseUrl . '/uploads/' . ($student->photo ?: 'default.png');
$photo_path_fs = str_replace('\\', '/', FCPATH) . 'uploads/' . ($student->photo ?: 'default.png');
$full_name = $student->first_name;
if ($student->middle_name) $full_name .= ' ' . $student->middle_name;
if ($student->last_name) $full_name .= ' ' . $student->last_name;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Profile - <?= esc($full_name) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        /* School Header */
        .school-header {
            
            padding-bottom: 15px;
            margin-bottom: 0px;
        }
        
        .school-header table {
            width: 100%;
            border-collapse: collapse;
            padding: 0;
        }
        
        .school-header td {
            padding: 0;
            vertical-align: top;
        }
        
        .school-logo {
            max-width: 250px;
            display: inline-block;
        }
        
        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .school-address {
            font-size: 12px;
            color: #666;
        }
        
        /* Title */
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        
        /* Cards */
        .card {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }
        
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        
        .card-body {
            padding: 10px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table td, table th {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 180px;
        }

        .academic-info-table th {
            width: auto;
        }

        .academic-info-table th,
        .academic-info-table td {
            font-size: 11px;
        }

        .table-header {
            padding: 8px 10px;
            font-size: 13px;
        }
        
        /* Photo and QR */
        .photo-section {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .photo-section img {
            max-width: 140px;
            max-height: 140px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        
        .student-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .student-code {
            color: #666;
            margin-bottom: 5px;
        }

        .student-photo-card {
            text-align: center;
            margin-bottom: 15px;
            margin-top: 15px;
        }
        
        .student-photo-card img {
            max-width: 140px;
            max-height: 140px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        
        .student-photo-card .student-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .student-photo-card .student-code {
            color: #666;
            margin-bottom: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
            margin: 2px;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .qr-code-card {
            text-align: center;
            margin-bottom: 15px;
        }

        .qr-code-card h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        
        .qr-code {
            text-align: center;
            margin-top: 10px;
        }
        
        .qr-code img {
            max-width: 180px;
        }
        
        /* Guardians Grid */
        .guardians-grid {
            width: 100%;
        }
        
        .guardians-grid td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        
        .guardian-card {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .guardian-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .guardian-info {
            margin: 3px 0;
            font-size: 11px;
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 10px;
            }
            
            .card {
                border: none;
                border-bottom: 1px solid #000;
            }
            
            .card-header {
                background-color: transparent !important;
                border-bottom: none;
                border-bottom: 1px solid #000;
            }
            
            table td, table th {
                border: 1px solid #000;
            }
            
            .badge {
                border: 1px solid #000;
            }
        }
        
        /* Text utilities */
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .mb-1 {
            margin-bottom: 5px;
        }
        
        .mb-2 {
            margin-bottom: 10px;
        }
        
        .mb-3 {
            margin-bottom: 15px;
        }
        
        .mb-4 {
            margin-bottom: 20px;
        }
        
        .mt-1 {
            margin-top: 5px;
        }
        
        .mt-2 {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- School Header -->
    <?php if ($school): ?>
    <?php
        $schoolParams = json_decode($school->params, true);
    
        $schoolLogo = !empty($school->logo) ? $baseUrl . '/uploads/' . $school->logo : null;
        $schoolLogo_fs = !empty($school->logo) ? str_replace('\\', '/', FCPATH) . 'uploads/' . $school->logo : null;
        $school_name = $school->name;
        $school_email = $school->email;
        $school_phone_code = $school->phone_code;
        $school_phone = $school->phone;
        $full_phone_number = $school_phone_code . $school_phone;
        $school_website = $school->custom_domain;
        $school_country = $school->country;
        $school_address = $school->address;


    ?>
    <div class="school-header">
        <table>
            
            <?php if ($schoolLogo): ?>
            <tr>
            <td width="100" style="text-align: center; border: none;">
                <img src="<?= !empty($is_pdf) && $is_pdf && !empty($school_logo_base64) ? 'data:' . esc($school_logo_mime) . ';base64,' . esc($school_logo_base64) : esc($schoolLogo) ?>" alt="School Logo" class="school-logo">
            </td>
            </tr>
            <?php endif; ?>

            <tr>
                <td style="border: none;">
                    <div class="school-name text-center"><?= esc($school_name) ?></div>
                    <!-- Phone, email, website -->
                    <div class="school-info text-center">
                        <?php if ($full_phone_number): ?>
                        <span class="school-phone"><i class="fas fa-phone"></i> Phone: <?= esc($full_phone_number) ?></span>
                        <?php endif; ?>
                        <?php if ($school_email): ?>
                        <span class="school-email"><i class="fas fa-envelope"></i> Email: <?= esc($school_email) ?></span>
                        <?php endif; ?>
                        <?php if ($school_website): ?>
                        <span class="school-website"><i class="fas fa-globe"></i> Website: <?= esc($school_website) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($school_address): ?>
                    <div class="school-address text-center"><?= esc($school_address) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <h2>Student Profile</h2>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
        <tr>
            <td width="15%" valign="top" style="border: none;">
                <!-- Photo Card -->
                <div class="student-photo-card">
                <img src="<?= !empty($is_pdf) && $is_pdf && !empty($student_photo_base64) ? 'data:' . esc($student_photo_mime) . ';base64,' . esc($student_photo_base64) : esc($photo_path_abs) ?>" alt="<?= esc($full_name) ?>">
                    <div class="student-name"><?= esc($full_name) ?></div>
                    <div class="student-code"><?= esc($student->student_code) ?></div>
                </div>

                <!-- QR Code Card -->
                <?php if (!empty($student->student_qr_code)): ?>
                <div class="qr-code-card">
                    <h3>QR Code</h3>
                    <?php
                        $qr_data = base_url('school-owner/students/view/' . $student->token);
                        $qr_label = esc($student->student_qr_code);
                    ?>
                    <img src="<?= !empty($is_pdf) && $is_pdf && !empty($qr_base64) ? 'data:' . esc($qr_mime) . ';base64,' . esc($qr_base64) : generate_qr_code($qr_data, 150) ?>" alt="QR Code" style="max-width: 150px;">
                    <div class="text-muted mt-1" style="font-size: 10px;"><?= $qr_label ?></div>
                </div>
                <?php endif; ?>
            </td>
            
            <td width="85%" valign="top" style="border: none; padding-left: 10px;">
                <!-- Basic Information -->
                <h3 class="table-header">Basic Information</h3>
                
                <table>
                    <tr><th>Student Code</th><td><?= esc($student->student_code) ?></td></tr>
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
                    

                
            </td>
        </tr>
    </table>


    <!-- Academic Information -->
    <?php if (!empty($enrollments)): ?>
    <h3 class="table-header">Academic Information</h3>
    <table class="academic-info-table">
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
    <?php endif; ?>

    <!-- Subjects -->
    <?php if (!empty($subjects)): ?>
    <h3 class="table-header" style="margin-top: 20px;" >Subjects</h3>
    <table>
        <thead>
            <tr>
                <th width="50%">Subject Name</th>
                <th width="50%">Subject Code</th>
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
    <?php endif; ?>



    <!-- Address -->
    <?php if ($address): ?>
    <h3 class="table-header" style="margin-top: 20px;" >Address</h3>
    <table>
        <tr><th width="180">Present Address</th><td><?= nl2br(esc($address->present_address ?: '-')) ?></td></tr>
        <tr><th>Permanent Address</th><td><?= nl2br(esc($address->permanent_address ?: '-')) ?></td></tr>
        <tr><th>City/District</th><td><?= esc($address->city ?: '') ?><?= ($address->city && $address->district) ? ', ' : '' ?><?= esc($address->district ?: '') ?></td></tr>
        <tr><th>State/Postal</th><td><?= esc($address->state ?: '') ?><?= ($address->state && $address->postal_code) ? ', ' : '' ?><?= esc($address->postal_code ?: '') ?></td></tr>
        <tr><th>Country</th><td><?= esc($address->country ?: '-') ?></td></tr>
    </table>
    <?php endif; ?>

    <!-- Guardians -->
    <?php if (!empty($guardians)): ?>
    <h3 class="table-header" style="margin-top: 20px;" >Guardians</h3>
       
    <table>
        <tr>
            <?php foreach ($guardians as $index => $guardian): ?>
            <td>
                <div class="guardian-cardd">
                    <div class="guardian-name"><?= esc($guardian->name) ?></div>
                    <div class="guardian-info"><strong>Relation:</strong> <?= esc($guardian->relation_type) ?></div>
                    <?php if ($guardian->phone): ?>
                    <div class="guardian-info"><strong>Phone:</strong> <?= esc($guardian->phone) ?></div>
                    <?php endif; ?>
                    <?php if ($guardian->email): ?>
                    <div class="guardian-info"><strong>Email:</strong> <?= esc($guardian->email) ?></div>
                    <?php endif; ?>
                    <?php if ($guardian->occupation): ?>
                    <div class="guardian-info"><strong>Occupation:</strong> <?= esc($guardian->occupation) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <?php if (($index + 1) % 2 == 0 && ($index + 1) < count($guardians)): ?>
            </tr><tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </table>
        
    <?php endif; ?>

    

    <!-- Custom Fields (Grouped) -->
    <?php if (!empty($customFieldsByGroup)): ?>
        <?php foreach ($customFieldsByGroup as $groupName => $groupFields): ?>
        <h3 class="table-header" style="margin-top: 20px;" ><?= esc($groupName) ?></h3>
        
        <table>
            <tbody>
                <?php foreach ($groupFields as $cf): ?>
                <tr>
                    <th width="200"><?= esc($cf['field']->label) ?></th>
                    <td><?= esc($cf['value'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Documents -->
    <?php if (!empty($documents)): ?>
        <h3 class="table-header" style="margin-top: 20px;">Documents</h3>

        The student has the following documents:
        <?php foreach ($documents as $index => $doc): ?>
            <b><?= esc($doc->document_title ?? '-') ?></b><?= ($index < count($documents) - 1) ? ', ' : '' ?>
        <?php endforeach; ?>

    <?php endif; ?>

    <script type="text/javascript">
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 100);
        };
    </script>
</body>
</html>
