<?php

if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Student.page_title_student_edit');
}else{
    $header_title = lang('Student.page_title_student_new');
}

$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$is_edit = $is_edit ?? false;
$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['token'])
    ? 'school-owner/students/update/' . $post_data['token']
    : 'school-owner/students/store';

$school_list        = $school_list ?? [];
$year_list          = $year_list ?? [];
$class_list         = $class_list ?? [];
$section_list       = $section_list ?? [];
$department_list    = $department_list ?? [];
$category_list      = $category_list ?? [];
$shift_list         = $shift_list ?? [];
$student_statuses   = $student_statuses ?? [];
$admission_sources  = $admission_sources ?? [];
$gender_list        = $gender_list ?? [];
$blood_groups       = $blood_groups ?? [];
$guardians          = $guardians ?? [];
$documents          = isset($post_data['documents']) ? $post_data['documents'] : [];

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'student_form',
    'method'  => 'post',
    'enctype' => 'multipart/form-data',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-people"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i> <?= lang('Student.btn_save') ?>
        </button>
        <a href="<?= base_url('/school-owner/students') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i> <?= lang('Student.back_to_student') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if(isset($validation)): ?>
                    <div class="alert alert-danger">
                        <?= $validation->listErrors(); ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="rms_csrf_token" value="<?= csrf_hash() ?>" />

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="studentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                            <i class="bi bi-person"></i> Basic Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab">
                            <i class="bi bi-book"></i> Academic Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="guardian-tab" data-bs-toggle="tab" data-bs-target="#guardian" type="button" role="tab">
                            <i class="bi bi-people"></i> Guardian
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="document-tab" data-bs-toggle="tab" data-bs-target="#document" type="button" role="tab">
                            <i class="bi bi-file-earmark"></i> Documents
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="customfields-tab" data-bs-toggle="tab" data-bs-target="#customfields" type="button" role="tab">
                            <i class="bi bi-input-cursor-text"></i> Custom Fields
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" id="loginTabLi" style="display:none;">
                        <button class="nav-link" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> Student Login Info
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="studentTabContent">

                    <!-- ========== BASIC INFO TAB ========== -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <div class="row mt-3">
                            <!-- Row 1: School & Student Code -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('Student.field_school') ?> <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-select" required>
                                    <option value=""><?= lang('Student.field_select_school') ?></option>
                                    <?php foreach ($school_list as $sid => $sname): ?>
                                        <option value="<?= $sid ?>" <?= (isset($post_data['school_id']) && $post_data['school_id'] == $sid) ? 'selected' : '' ?>>
                                            <?= esc($sname) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('Student.student_id') ?></label>
                                <input type="text" name="student_code" class="form-control" value="<?= isset($post_data['student_code']) ? esc($post_data['student_code']) : '' ?>">
                            </div>

                            <!-- Row 2: Name Fields -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?= lang('Student.first_name') ?> <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required value="<?= isset($post_data['first_name']) ? esc($post_data['first_name']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?= isset($post_data['middle_name']) ? esc($post_data['middle_name']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?= lang('Student.last_name') ?></label>
                                <input type="text" name="last_name" class="form-control" value="<?= isset($post_data['last_name']) ? esc($post_data['last_name']) : '' ?>">
                            </div>

                            <!-- Row 3: Personal Details -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($gender_list as $gk => $gv): ?>
                                        <option value="<?= $gk ?>" <?= (isset($post_data['gender']) && $post_data['gender'] == $gk) ? 'selected' : '' ?>><?= $gv ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?= isset($post_data['date_of_birth']) ? esc($post_data['date_of_birth']) : '' ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($blood_groups as $bk => $bv): ?>
                                        <option value="<?= $bk ?>" <?= (isset($post_data['blood_group']) && $post_data['blood_group'] == $bk) ? 'selected' : '' ?>><?= $bv ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Religion</label>
                                <input type="text" name="religion" class="form-control" value="<?= isset($post_data['religion']) ? esc($post_data['religion']) : '' ?>">
                            </div>

                            <!-- Row 4: Contact & Photo -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?= isset($post_data['nationality']) ? esc($post_data['nationality']) : '' ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= lang('Student.field_phone') ?></label>
                                <input type="text" name="phone" class="form-control" value="<?= isset($post_data['phone']) ? esc($post_data['phone']) : '' ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= lang('Student.field_email') ?></label>
                                <input type="email" name="email" class="form-control" value="<?= isset($post_data['email']) ? esc($post_data['email']) : '' ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= lang('Student.student_photo') ?></label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <?php if (!empty($post_data['photo'])): ?>
                                    <p class="mt-1"><img src="<?= base_url('uploads/' . esc($post_data['photo'])) ?>" style="max-width: 60px; max-height: 60px;" class="img-thumbnail"></p>
                                <?php endif; ?>
                            </div>

                            <!-- Row 5: Admission Details -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Student Status</label>
                                <select name="student_status" class="form-select">
                                    <?php foreach ($student_statuses as $sk => $sv): ?>
                                        <option value="<?= $sk ?>" <?= (isset($post_data['student_status']) && $post_data['student_status'] == $sk) ? 'selected' : '' ?>><?= $sv ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Admission Source</label>
                                <select name="admission_source" class="form-select">
                                    <?php foreach ($admission_sources as $ak => $av): ?>
                                        <option value="<?= $ak ?>" <?= (isset($post_data['admission_source']) && $post_data['admission_source'] == $ak) ? 'selected' : '' ?>><?= $av ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Admission Date</label>
                                <input type="date" name="admission_date" class="form-control" value="<?= isset($post_data['admission_date']) ? esc($post_data['admission_date']) : '' ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <?= $status_list ?>
                            </div>

                            <!-- Row 5: Registration No & RFID -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration No</label>
                                <input type="text" name="registration_no" class="form-control" value="<?= isset($post_data['registration_no']) ? esc($post_data['registration_no']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RFID Number</label>
                                <input type="text" name="rfid_number" class="form-control" value="<?= isset($post_data['rfid_number']) ? esc($post_data['rfid_number']) : '' ?>">
                            </div>

                            <!-- Row 6: QR Code -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">QR Code</label>
                                <input type="text" name="student_qr_code" class="form-control" value="<?= isset($post_data['student_qr_code']) ? esc($post_data['student_qr_code']) : '' ?>">
                            </div>
                        </div>

                        <!-- Address Fields (on Basic tab) -->
                        <div class="row mt-2">
                            <div class="col-12"><hr><h6 class="text-muted">Address Information</h6></div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Present Address</label>
                                <textarea name="present_address" class="form-control" rows="3"><?= isset($post_data['present_address']) ? esc($post_data['present_address']) : '' ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Permanent Address</label>
                                <textarea name="permanent_address" class="form-control" rows="3"><?= isset($post_data['permanent_address']) ? esc($post_data['permanent_address']) : '' ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= isset($post_data['city']) ? esc($post_data['city']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control" value="<?= isset($post_data['district']) ? esc($post_data['district']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="<?= isset($post_data['state']) ? esc($post_data['state']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?= isset($post_data['postal_code']) ? esc($post_data['postal_code']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="address_country" class="form-control" value="<?= isset($post_data['address_country']) ? esc($post_data['address_country']) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ========== ACADEMIC INFO TAB ========== -->
                    <div class="tab-pane fade" id="academic" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <p class="text-muted">Manage academic enrollment records for each academic year. Click "Add Academic Year" to add a new enrollment.</p>
                            </div>
                            <div class="col-md-12">
                                <table class="table table-bordered" id="enrollmentTable">
                                    <thead>
                                        <tr>
                                            <th width="18%">Year</th>
                                            <th width="16%">Class</th>
                                            <th class="col-section" width="10%">Section</th>
                                            <th class="col-department" width="10%">Department</th>
                                            <th class="col-category" width="10%">Category</th>
                                            <th class="col-shift" width="10%">Shift</th>
                                            <th class="col-roll" width="10%">Roll No</th>
                                            <th width="7%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $enrollments = $enrollments ?? [];
                                        // Ensure all enrollments are objects (join queries may return arrays)
                                        foreach ($enrollments as $key => $enr) {
                                            if (is_array($enr)) {
                                                $enrollments[$key] = (object) $enr;
                                            }
                                        }
                                        if (empty($enrollments) && !empty($post_data['session_id'])) {
                                            // Build a single row from post_data for new students
                                            $enrollments = [(object) [
                                                'id' => null,
                                                'session_id' => $post_data['session_id'] ?? '',
                                                'class_id' => $post_data['class_id'] ?? '',
                                                'section_id' => $post_data['section_id'] ?? '',
                                                'department_id' => $post_data['department_id'] ?? '',
                                                'category_id' => $post_data['category_id'] ?? '',
                                                'shift_id' => $post_data['shift_id'] ?? '',
                                                'roll_no' => $post_data['roll_no'] ?? '',
                                            ]];
                                        }
                                        foreach ($enrollments as $idx => $enr):
                                        ?>
                                        <tr class="enrollment-row" data-index="<?= $idx ?>">
                                            <td>
                                                <input type="hidden" name="enrollments[<?= $idx ?>][id]" value="<?= esc($enr->id ?? '') ?>">
                                                <select name="enrollments[<?= $idx ?>][session_id]" class="form-select enrollment-session" required>
                                                    <option value="">Select Year</option>
                                                    <?php foreach ($year_list as $yid => $ytitle): ?>
                                                        <option value="<?= $yid ?>" <?= ($enr->session_id == $yid) ? 'selected' : '' ?>><?= esc($ytitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="enrollments[<?= $idx ?>][class_id]" class="form-select enrollment-class" required>
                                                    <option value="">Select Class</option>
                                                    <?php foreach ($class_list as $cid => $ctitle): ?>
                                                        <option value="<?= $cid ?>" <?= ($enr->class_id == $cid) ? 'selected' : '' ?>><?= esc($ctitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="col-section">
                                                <select name="enrollments[<?= $idx ?>][section_id]" class="form-select enrollment-section">
                                                    <option value="">None</option>
                                                    <?php foreach ($section_list as $sid => $stitle): ?>
                                                        <option value="<?= $sid ?>" <?= ($enr->section_id == $sid) ? 'selected' : '' ?>><?= esc($stitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="col-department">
                                                <select name="enrollments[<?= $idx ?>][department_id]" class="form-select enrollment-department">
                                                    <option value="">None</option>
                                                    <?php foreach ($department_list as $did => $dtitle): ?>
                                                        <option value="<?= $did ?>" <?= ($enr->department_id == $did) ? 'selected' : '' ?>><?= esc($dtitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="col-category">
                                                <select name="enrollments[<?= $idx ?>][category_id]" class="form-select enrollment-category">
                                                    <option value="">None</option>
                                                    <?php foreach ($category_list as $catid => $cattitle): ?>
                                                        <option value="<?= $catid ?>" <?= ($enr->category_id == $catid) ? 'selected' : '' ?>><?= esc($cattitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="col-shift">
                                                <select name="enrollments[<?= $idx ?>][shift_id]" class="form-select enrollment-shift">
                                                    <option value="">None</option>
                                                    <?php foreach ($shift_list ?? [] as $shid => $shtitle): ?>
                                                        <option value="<?= $shid ?>" <?= ($enr->shift_id == $shid) ? 'selected' : '' ?>><?= esc($shtitle) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="col-roll">
                                                <input type="text" name="enrollments[<?= $idx ?>][roll_no]" class="form-control" value="<?= esc($enr->roll_no ?? '') ?>">
                                            </td>
                                            <td class="text-center">
                                                <?php if ($idx > 0 || $is_edit): ?>
                                                    <button type="button" class="btn btn-sm btn-danger btn-remove-enrollment" title="Remove">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-success" id="btnAddEnrollment">
                                    <i class="fas fa-plus"></i> Add Academic Year
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========== GUARDIAN TAB ========== -->
                    <div class="tab-pane fade" id="guardian" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12 text-end mb-2">
                                <button type="button" class="btn btn-sm btn-success" id="addGuardian">
                                    <i class="fa fa-plus"></i> Add Guardian
                                </button>
                            </div>
                        </div>
                        <div id="guardianContainer">
                            <?php if (!empty($guardians)): ?>
                                <?php foreach ($guardians as $gindex => $guardian): ?>
                                <div class="row guardian-row mb-3 border-bottom pb-3">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="guardian_name[]" class="form-control" value="<?= esc($guardian->name) ?>">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">Relation</label>
                                        <select name="guardian_relation[]" class="form-select">
                                            <option value="Father" <?= $guardian->relation_type == 'Father' ? 'selected' : '' ?>>Father</option>
                                            <option value="Mother" <?= $guardian->relation_type == 'Mother' ? 'selected' : '' ?>>Mother</option>
                                            <option value="Guardian" <?= $guardian->relation_type == 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="guardian_phone[]" class="form-control" value="<?= esc($guardian->phone ?? '') ?>">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="guardian_email[]" class="form-control" value="<?= esc($guardian->email ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Occupation</label>
                                        <input type="text" name="guardian_occupation[]" class="form-control" value="<?= esc($guardian->occupation ?? '') ?>">
                                    </div>
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-danger removeGuardian"><i class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div id="guardianTemplate" style="display:none;">
                            <div class="row guardian-row mb-3 border-bottom pb-3">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="guardian_name[]" class="form-control" placeholder="Full Name">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Relation</label>
                                    <select name="guardian_relation[]" class="form-select">
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian" selected>Guardian</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="guardian_phone[]" class="form-control" placeholder="Phone Number">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="guardian_email[]" class="form-control" placeholder="Email">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Occupation</label>
                                    <input type="text" name="guardian_occupation[]" class="form-control" placeholder="Occupation">
                                </div>
                                <div class="col-md-2 mb-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-danger removeGuardian"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== DOCUMENTS TAB ========== -->
                    <div class="tab-pane fade" id="document" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12 text-end mb-2">
                                <button type="button" class="btn btn-sm btn-success" id="addDocument">
                                    <i class="fa fa-plus"></i> Add Document
                                </button>
                            </div>
                        </div>
                        <div id="documentContainer">
                            <?php if (!empty($documents)): ?>
                                <?php foreach ($documents as $dindex => $doc): ?>
                                <div class="row document-row mb-3 border-bottom pb-3">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Document Type</label>
                                        <input type="text" name="document_type[]" class="form-control" value="<?= esc($doc->document_type ?? '') ?>" placeholder="e.g., Certificate, ID Card">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Document Title</label>
                                        <input type="text" name="document_title[]" class="form-control" value="<?= esc($doc->document_title ?? '') ?>" placeholder="Document Title">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Upload File</label>
                                        <input type="file" name="documents[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                        <?php if (!empty($doc->file_name)): ?>
                                            <small><a href="<?= base_url('uploads/' . esc($doc->file_name)) ?>" target="_blank"><?= esc($doc->file_name) ?></a></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-danger removeDocument"><i class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div id="documentTemplate" style="display:none;">
                            <div class="row document-row mb-3 border-bottom pb-3">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Document Type</label>
                                    <input type="text" name="document_type[]" class="form-control" placeholder="e.g., Certificate, ID Card">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Document Title</label>
                                    <input type="text" name="document_title[]" class="form-control" placeholder="Document Title">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Upload File</label>
                                    <input type="file" name="documents[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>
                                <div class="col-md-2 mb-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-danger removeDocument"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== CUSTOM FIELDS TAB ========== -->
                    <div class="tab-pane fade" id="customfields" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div id="custom-fields-container">
                                    <p class="text-muted">Select a school to load custom fields.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== LOGIN INFO TAB ========== -->
                    <div class="tab-pane fade" id="login" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <p class="text-muted">Create or manage student login account. Leave password blank to keep unchanged (edit mode).</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="login_name" class="form-control" value="<?= isset($login_name) ? esc($login_name) : '' ?>" placeholder="Student's display name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email (Username) <span class="text-danger">*</span></label>
                                <input type="email" name="login_email" class="form-control" value="<?= isset($login_email) ? esc($login_email) : '' ?>" placeholder="login@email.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="login_password" class="form-control" value="<?= isset($login_password) ? esc($login_password) : '' ?>" placeholder="<?= isset($is_edit) && $is_edit ? 'Leave blank to keep current password' : 'Enter password' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="login_phone" class="form-control" value="<?= isset($login_phone) ? esc($login_phone) : '' ?>" placeholder="Phone number">
                            </div>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div>

            <input type="hidden" name="token" value="<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>">
        </div>
    </div>
</div>

<?= form_close() ?>

<script type="text/javascript">
$(document).ready(function() {
    // Helper to get current CSRF token from hidden input
    function getCsrfToken() {
        return $('input[name="rms_csrf_token"]').val();
    }

    // Helper to update CSRF token from response
    function updateCsrfToken(xhr) {
        var newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (newToken) {
            $('input[name="rms_csrf_token"]').val(newToken);
        }
    }

    // Add guardian
    $('#addGuardian').on('click', function() {
        var template = $('#guardianTemplate').html();
        $('#guardianContainer').append(template);
    });

    // Remove guardian
    $(document).on('click', '.removeGuardian', function() {
        $(this).closest('.guardian-row').remove();
    });

    // Add document
    $('#addDocument').on('click', function() {
        var template = $('#documentTemplate').html();
        $('#documentContainer').append(template);
    });

    // Remove document
    $(document).on('click', '.removeDocument', function() {
        $(this).closest('.document-row').remove();
    });

    // Helper to show/hide fields based on enable settings
    function applyFieldVisibility(enableStudentId, enableQrCode, enableRegNo, enableRfid) {
        // Student ID field (always visible as it's the primary identifier, but we can disable it)
        // QR Code
        if (enableQrCode) {
            $('input[name="student_qr_code"]').closest('.col-md-6').show();
        } else {
            $('input[name="student_qr_code"]').closest('.col-md-6').hide();
        }
        // Registration No
        if (enableRegNo) {
            $('input[name="registration_no"]').closest('.col-md-6').show();
        } else {
            $('input[name="registration_no"]').closest('.col-md-6').hide();
        }
        // RFID
        if (enableRfid) {
            $('input[name="rfid_number"]').closest('.col-md-6').show();
        } else {
            $('input[name="rfid_number"]').closest('.col-md-6').hide();
        }
    }

    var enrollmentIndex = <?= count($enrollments ?? []) ?>;
    var yearList = <?= json_encode($year_list) ?>;
    var classList = <?= json_encode($class_list) ?>;
    var sectionList = <?= json_encode($section_list) ?>;
    var deptList = <?= json_encode($department_list) ?>;
    var catList = <?= json_encode($category_list) ?>;
    var shiftList = <?= json_encode($shift_list) ?>;
    var currentAcademicSettings = null;

    function escapeOptionText(value) {
        return $('<div>').text(value || '').html();
    }

    function getAcademicList(response, primaryKey, aliasKey) {
        return response[primaryKey] || response[aliasKey] || {};
    }

    function buildOptions(list, placeholder, selectedValue) {
        var selected = String(selectedValue || '');
        var options = '<option value="">' + escapeOptionText(placeholder) + '</option>';
        $.each(list || {}, function(id, title) {
            options += '<option value="' + escapeOptionText(id) + '" ' + (String(id) === selected ? 'selected' : '') + '>' + escapeOptionText(title) + '</option>';
        });
        return options;
    }

    function refreshEnrollmentRows(addFirstRow) {
        var $tbody = $('#enrollmentTable tbody');
        if (addFirstRow && !$tbody.find('.enrollment-row').length) {
            $tbody.append(getEnrollmentRowTemplate(false));
        }

        $tbody.find('.enrollment-row').each(function() {
            var $row = $(this);
            var sessionValue = $row.find('.enrollment-session').val();
            var classValue = $row.find('.enrollment-class').val();
            var sectionValue = $row.find('.enrollment-section').val();
            var departmentValue = $row.find('.enrollment-department').val();
            var categoryValue = $row.find('.enrollment-category').val();
            var shiftValue = $row.find('.enrollment-shift').val();

            $row.find('.enrollment-session').html(buildOptions(yearList, 'Select Year', sessionValue));
            $row.find('.enrollment-class').html(buildOptions(classList, 'Select Class', classValue));
            $row.find('.enrollment-section').html(buildOptions(sectionList, 'None', sectionValue));
            $row.find('.enrollment-department').html(buildOptions(deptList, 'None', departmentValue));
            $row.find('.enrollment-category').html(buildOptions(catList, 'None', categoryValue));
            $row.find('.enrollment-shift').html(buildOptions(shiftList, 'None', shiftValue));
        });
    }

    function applyAcademicDataResponse(response, addFirstEnrollmentRow) {
        yearList = getAcademicList(response, 'years', 'year_list');
        classList = getAcademicList(response, 'classes', 'class_list');
        sectionList = getAcademicList(response, 'sections', 'section_list');
        deptList = getAcademicList(response, 'departments', 'department_list');
        catList = getAcademicList(response, 'categories', 'category_list');
        shiftList = getAcademicList(response, 'shifts', 'shift_list');

        refreshEnrollmentRows(addFirstEnrollmentRow);
    }

    // Load academic data and auto-gen codes when school changes
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        
        if (!schoolId) {
            yearList = {};
            classList = {};
            sectionList = {};
            deptList = {};
            catList = {};
            shiftList = {};
            $('#enrollmentTable tbody').empty();
            return;
        }

        // Sequential: first auto-gen codes (new), then load academic data
        var loadAcademicData = function(enableStudentId, enableQrCode, enableRegNo, enableRfid) {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('school-owner/students/getAcademicData') ?>',
                data: { school_id: schoolId, rms_csrf_token: getCsrfToken() },
                headers: { 'X-CSRF-TOKEN': getCsrfToken() },
                success: function(response, status, xhr) {
                    updateCsrfToken(xhr);
                    applyAcademicDataResponse(response, true);

                    var $yearSelect = $('#session_id');
                    $yearSelect.html('<option value="">Select Academic Year</option>');
                    var selectedYear = '<?= isset($post_data['session_id']) ? $post_data['session_id'] : '' ?>';
                    if (response.years) {
                        $.each(response.years, function(id, title) {
                            $yearSelect.append('<option value="' + id + '" ' + (id == selectedYear ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $classSelect = $('#class_id');
                    $classSelect.html('<option value="">Select Class</option>');
                    var selectedClass = '<?= isset($post_data['class_id']) ? $post_data['class_id'] : '' ?>';
                    if (response.classes) {
                        $.each(response.classes, function(id, title) {
                            $classSelect.append('<option value="' + id + '" ' + (id == selectedClass ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $sectionSelect = $('#section_id');
                    $sectionSelect.html('<option value="">Select Section</option>');
                    var selectedSection = '<?= isset($post_data['section_id']) ? $post_data['section_id'] : '' ?>';
                    if (response.sections) {
                        $.each(response.sections, function(id, title) {
                            $sectionSelect.append('<option value="' + id + '" ' + (id == selectedSection ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $deptSelect = $('#department_id');
                    $deptSelect.html('<option value="">Select Department</option>');
                    var selectedDept = '<?= isset($post_data['department_id']) ? $post_data['department_id'] : '' ?>';
                    if (response.departments) {
                        $.each(response.departments, function(id, title) {
                            $deptSelect.append('<option value="' + id + '" ' + (id == selectedDept ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $catSelect = $('#category_id');
                    $catSelect.html('<option value="">Select Category</option>');
                    var selectedCat = '<?= isset($post_data['category_id']) ? $post_data['category_id'] : '' ?>';
                    if (response.categories) {
                        $.each(response.categories, function(id, title) {
                            $catSelect.append('<option value="' + id + '" ' + (id == selectedCat ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $shiftSelect = $('#shift_id');
                    $shiftSelect.html('<option value="">Select Shift</option>');
                    var selectedShift = '<?= isset($post_data['shift_id']) ? $post_data['shift_id'] : '' ?>';
                    if (response.shifts) {
                        $.each(response.shifts, function(id, title) {
                            $shiftSelect.append('<option value="' + id + '" ' + (id == selectedShift ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    // Show/hide Student Login tab
                    if (response.student_account_enabled) {
                        $('#loginTabLi').show();
                    } else {
                        $('#loginTabLi').hide();
                    }

                    // Show/hide academic fields based on school settings
                    if (response.academic_section_enabled) {
                        $('#sectionFieldWrapper').show();
                    } else {
                        $('#sectionFieldWrapper').hide();
                    }
                    if (response.academic_department_enabled) {
                        $('#departmentFieldWrapper').show();
                    } else {
                        $('#departmentFieldWrapper').hide();
                    }
                    if (response.academic_category_enabled) {
                        $('#categoryFieldWrapper').show();
                    } else {
                        $('#categoryFieldWrapper').hide();
                    }
                    if (response.academic_shift_enabled) {
                        $('#shiftFieldWrapper').show();
                    } else {
                        $('#shiftFieldWrapper').hide();
                    }
                    if (response.academic_class_roll_enabled) {
                        $('#rollFieldWrapper').show();
                    } else {
                        $('#rollFieldWrapper').hide();
                    }

                    // Apply ID/QR/Reg/RFID field visibility
                    applyFieldVisibility(
                        response.enable_student_id,
                        response.enable_qr_code,
                        response.enable_registration_no,
                        response.enable_rfid
                    );

                    // Apply enrollment table column visibility
                    applyEnrollmentFieldVisibility({
                        academic_section_enabled: response.academic_section_enabled,
                        academic_department_enabled: response.academic_department_enabled,
                        academic_category_enabled: response.academic_category_enabled,
                        academic_shift_enabled: response.academic_shift_enabled,
                        academic_class_roll_enabled: response.academic_class_roll_enabled
                    });
                }
            });
        };

        <?php if (!isset($is_edit) || !$is_edit): ?>
        // New student: auto-gen codes first
        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('school-owner/students/getAutoGenCodes') ?>',
            data: { school_id: schoolId, rms_csrf_token: getCsrfToken() },
            headers: { 'X-CSRF-TOKEN': getCsrfToken() },
            success: function(response, status, xhr) {
                updateCsrfToken(xhr);
                if (response.status) {
                    var $codeField = $('input[name="student_code"]');
                    var $regField = $('input[name="registration_no"]');
                    var $qrField = $('input[name="student_qr_code"]');
                    if (!$codeField.val()) $codeField.val(response.student_code);
                    if (!$regField.val()) $regField.val(response.registration_no);
                    if (!$qrField.val()) $qrField.val(response.student_qr_code);
                }
                loadAcademicData(
                    response.enable_student_id,
                    response.enable_qr_code,
                    response.enable_registration_no,
                    response.enable_rfid
                );
            },
            error: function() {
                loadAcademicData(true, true, true, true);
            }
        });
        <?php else: ?>
        // Edit mode: just load academic data
        loadAcademicData(true, true, true, true);
        <?php endif; ?>
    });

    // On page load, if a school is already selected (edit mode), load its academic data
    var selectedSchool = $('#school_id').val();
    if (selectedSchool) {

        var loadAcademicData = function() {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('school-owner/students/getAcademicData') ?>',
                data: { school_id: selectedSchool, rms_csrf_token: getCsrfToken() },
                headers: { 'X-CSRF-TOKEN': getCsrfToken() },
                success: function(response, status, xhr) {
                    updateCsrfToken(xhr);
                    applyAcademicDataResponse(response, true);

                    // Apply enrollment table column visibility on page load
                    applyEnrollmentFieldVisibility({
                        academic_section_enabled: response.academic_section_enabled,
                        academic_department_enabled: response.academic_department_enabled,
                        academic_category_enabled: response.academic_category_enabled,
                        academic_shift_enabled: response.academic_shift_enabled,
                        academic_class_roll_enabled: response.academic_class_roll_enabled
                    });

                    var $yearSelect = $('#session_id');
                    $yearSelect.html('<option value="">Select Academic Year</option>');
                    var selectedYear = '<?= isset($post_data['session_id']) ? $post_data['session_id'] : '' ?>';
                    if (response.years) {
                        $.each(response.years, function(id, title) {
                            $yearSelect.append('<option value="' + id + '" ' + (id == selectedYear ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $classSelect = $('#class_id');
                    $classSelect.html('<option value="">Select Class</option>');
                    var selectedClass = '<?= isset($post_data['class_id']) ? $post_data['class_id'] : '' ?>';
                    if (response.classes) {
                        $.each(response.classes, function(id, title) {
                            $classSelect.append('<option value="' + id + '" ' + (id == selectedClass ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $sectionSelect = $('#section_id');
                    $sectionSelect.html('<option value="">Select Section</option>');
                    var selectedSection = '<?= isset($post_data['section_id']) ? $post_data['section_id'] : '' ?>';
                    if (response.sections) {
                        $.each(response.sections, function(id, title) {
                            $sectionSelect.append('<option value="' + id + '" ' + (id == selectedSection ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $deptSelect = $('#department_id');
                    $deptSelect.html('<option value="">Select Department</option>');
                    var selectedDept = '<?= isset($post_data['department_id']) ? $post_data['department_id'] : '' ?>';
                    if (response.departments) {
                        $.each(response.departments, function(id, title) {
                            $deptSelect.append('<option value="' + id + '" ' + (id == selectedDept ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    var $catSelect = $('#category_id');
                    $catSelect.html('<option value="">Select Category</option>');
                    var selectedCat = '<?= isset($post_data['category_id']) ? $post_data['category_id'] : '' ?>';
                    if (response.categories) {
                        $.each(response.categories, function(id, title) {
                            $catSelect.append('<option value="' + id + '" ' + (id == selectedCat ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    // academic_shift
                    var $shiftSelect = $('#shift_id');
                    $shiftSelect.html('<option value="">Select Shift</option>');
                    var selectedShift = '<?= isset($post_data['shift_id']) ? $post_data['shift_id'] : '' ?>';
                    if (response.shifts) {
                        $.each(response.shifts, function(id, title) {
                            $shiftSelect.append('<option value="' + id + '" ' + (id == selectedShift ? 'selected' : '') + '>' + title + '</option>');
                        });
                    }

                    // Show/hide Student Login tab
                    if (response.student_account_enabled) {
                        $('#loginTabLi').show();
                    } else {
                        $('#loginTabLi').hide();
                    }

                    // Show/hide academic fields based on school settings
                    if (response.academic_section_enabled) {
                        $('#sectionFieldWrapper').show();
                    } else {
                        $('#sectionFieldWrapper').hide();
                    }
                    if (response.academic_department_enabled) {
                        $('#departmentFieldWrapper').show();
                    } else {
                        $('#departmentFieldWrapper').hide();
                    }
                    if (response.academic_category_enabled) {
                        $('#categoryFieldWrapper').show();
                    } else {
                        $('#categoryFieldWrapper').hide();
                    }
                    if (response.academic_shift_enabled) {
                        $('#shiftFieldWrapper').show();
                    } else {
                        $('#shiftFieldWrapper').hide();
                    }
                    if (response.academic_class_roll_enabled) {
                        $('#rollFieldWrapper').show();
                    } else {
                        $('#rollFieldWrapper').hide();
                    }

                    // Load custom fields AFTER academic data is loaded (CSRF is now stable)
                    var initialRecordId = '<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>';
                    console.log('Loading custom fields for record ID:', initialRecordId);
                    loadCustomFields(selectedSchool, initialRecordId);
                }
            });
        };
        loadAcademicData();
    } else {
        // No school selected on load, but still check if we need to load custom fields
        var initialRecordId = '<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>';
        // Don't load custom fields if no school is selected
    }

    // Handle initial student_account_enabled for the loaded school on page init
    <?php if (isset($student_account_enabled) && $student_account_enabled): ?>
    $('#loginTabLi').show();
    <?php endif; ?>

    // ========== DYNAMIC ENROLLMENT ROWS ==========
    // Template for a new enrollment row
    function getEnrollmentRowTemplate(allowRemove) {
        var idx = enrollmentIndex++;
        var yearOptions = buildOptions(yearList, 'Select Year', '');
        var classOptions = buildOptions(classList, 'Select Class', '');
        var sectionOptions = buildOptions(sectionList, 'None', '');
        var deptOptions = buildOptions(deptList, 'None', '');
        var catOptions = buildOptions(catList, 'None', '');
        var shiftOptions = buildOptions(shiftList, 'None', '');
        
        return '<tr class="enrollment-row" data-index="' + idx + '">' +
            '<td><select name="enrollments[' + idx + '][session_id]" class="form-select enrollment-session" required>' + yearOptions + '</select></td>' +
            '<td><select name="enrollments[' + idx + '][class_id]" class="form-select enrollment-class" required>' + classOptions + '</select></td>' +
            '<td class="col-section"><select name="enrollments[' + idx + '][section_id]" class="form-select enrollment-section">' + sectionOptions + '</select></td>' +
            '<td class="col-department"><select name="enrollments[' + idx + '][department_id]" class="form-select enrollment-department">' + deptOptions + '</select></td>' +
            '<td class="col-category"><select name="enrollments[' + idx + '][category_id]" class="form-select enrollment-category">' + catOptions + '</select></td>' +
            '<td class="col-shift"><select name="enrollments[' + idx + '][shift_id]" class="form-select enrollment-shift">' + shiftOptions + '</select></td>' +
            '<td class="col-roll"><input type="text" name="enrollments[' + idx + '][roll_no]" class="form-control"></td>' +
            '<td class="text-center">' + (allowRemove === false ? '' : '<button type="button" class="btn btn-sm btn-danger btn-remove-enrollment" title="Remove"><i class="fas fa-trash"></i></button>') + '</td>' +
        '</tr>';
    }

    // Add enrollment row
    $('#btnAddEnrollment').on('click', function() {
        var template = getEnrollmentRowTemplate(true);
        $('#enrollmentTable tbody').append(template);
        if (currentAcademicSettings) {
            applyEnrollmentFieldVisibility(currentAcademicSettings);
        }
    });

    // Remove enrollment row
    $(document).on('click', '.btn-remove-enrollment', function() {
        $(this).closest('.enrollment-row').remove();
    });

    // Show/hide enrollment columns based on school settings
    function applyEnrollmentFieldVisibility(settings) {
        currentAcademicSettings = settings;
        if (settings.academic_section_enabled) {
            $('.col-section').show();
        } else {
            $('.col-section').hide();
        }
        if (settings.academic_department_enabled) {
            $('.col-department').show();
        } else {
            $('.col-department').hide();
        }
        if (settings.academic_category_enabled) {
            $('.col-category').show();
        } else {
            $('.col-category').hide();
        }
        if (settings.academic_shift_enabled) {
            $('.col-shift').show();
        } else {
            $('.col-shift').hide();
        }
        if (settings.academic_class_roll_enabled) {
            $('.col-roll').show();
        } else {
            $('.col-roll').hide();
        }
    }

    // Load custom fields when school changes
    function loadCustomFields(schoolId, recordId) {
        if (!schoolId) {
            $('#custom-fields-container').html('<p class="text-muted">Select a school to load custom fields.</p>');
            return;
        }

        var csrfToken = getCsrfToken();
        console.log('Loading custom fields - School:', schoolId, 'Record:', recordId, 'CSRF token length:', csrfToken.length);
        
        // Ensure recordId is a string (token) or number (ID)
        var sendRecordId = recordId;
        if (typeof recordId === 'undefined' || recordId === null || recordId === 0) {
            sendRecordId = '';
        }
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('school-owner/custom-fields/getFieldsByEntity') ?>',
            data: { 
                school_id: schoolId, 
                entity_slug: 'student', 
                record_id: sendRecordId, 
                rms_csrf_token: csrfToken 
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response, status, xhr) {
                updateCsrfToken(xhr);
                console.log('Custom fields success:', response);

                if (response.status && response.html) {
                    $('#custom-fields-container').html(response.html);
                } else {
                    var msg = response.html || '<p class="text-muted">No custom fields found for this school.</p>';
                    if (response.debug) {
                        msg += '<pre style="background:#f8f9fa;padding:10px;margin-top:10px;font-size:12px;">' + JSON.stringify(response.debug, null, 2) + '</pre>';
                    }
                    $('#custom-fields-container').html(msg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Custom fields AJAX error:', status, error, xhr.responseText);
                var errorMsg = '<p class="text-danger">Error loading custom fields: ' + error;
                if (xhr.status === 403) {
                    errorMsg += ' (Forbidden - Check console for details)';
                }
                errorMsg += '</p>';
                $('#custom-fields-container').html(errorMsg);
            }
        });
    }

    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        var recordId = '<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>';
        console.log('School changed, loading custom fields for record ID:', recordId);
        loadCustomFields(schoolId, recordId);
    });
});
</script>
