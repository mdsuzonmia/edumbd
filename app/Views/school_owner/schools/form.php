<?php



// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('School.page_title_edit');
}else{
    $header_title = lang('School.page_title_create');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$default_country = isset($post_data['country']) ? $post_data['country'] : detect_user_country();
$countries = get_country_list();

// Get Phone Code
$phone_code = '';
if(isset($post_data['country']) && array_key_exists($post_data['country'], $countries)){
    $phone_code = $countries[$post_data['country']]['phone_code'];
}

// Timezone List
$timezone_list = timezone_identifiers_list();
$post_data = $post_data ?? [];
$school_id = $post_data['id'] ?? $post_data['school_id'] ?? '';
$form_action = !empty($is_edit) && !empty($school_id)
    ? 'school-owner/schools/update/' . $school_id
    : 'school-owner/schools/store';

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'school_form',
    'method'  => 'post',
    'enctype' => 'multipart/form-data',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-building"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/school-owner/schools') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('School.back_to_school') ?>
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

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="schoolTab" role="tablist">

                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="general-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#general"
                                type="button"
                                role="tab">
                            <i class="bi bi-gear"></i> General
                        </button>
                    </li>

                    <!-- <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="modules-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#modules"
                                type="button"
                                role="tab">
                            <i class="bi bi-grid"></i> School Modules
                        </button>
                    </li> -->

                    <!-- School Settings -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="settings-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#settings"
                                type="button"
                                role="tab">
                            <i class="bi bi-gear"></i> School Academic Settings
                        </button>
                    </li>

                    <!-- Result Sheet Configuration -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="result-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#result"
                                type="button"
                                role="tab">
                            <i class="bi bi-clipboard-data"></i> Result Sheet Configuration
                        </button>
                    </li>

                </ul>

                <div class="tab-content" id="schoolTabContent">

                    <!-- ========================================= -->
                    <!-- General Tab -->
                    <!-- ========================================= -->
                    <div class="tab-pane fade show active"
                         id="general"
                         role="tabpanel">

                        <div class="row">

                            <!-- Name -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">School Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       required
                                       value="<?= isset($post_data['name']) ? esc($post_data['name']) : '' ?>">
                            </div>

                            <!-- Slug -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text"
                                       name="slug"
                                       class="form-control"
                                       value="<?= isset($post_data['slug']) ? esc($post_data['slug']) : '' ?>">
                            </div>

                            <!-- Country -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <select name="country" id="country" class="form-select form-select-lg">

                                    <?php foreach ($countries as $code => $country): ?>
                                        <option value="<?= $code; ?>" <?= ($code == $default_country) ? 'selected' : '' ?>>
                                            <?= esc($country['name']); ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                               
                            </div>

                            <!-- Timezone -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Timezone</label>

                                <select name="timezone" class="form-select">
                                    <?php foreach($timezone_list as $timezone): ?>
                                        <option value="<?= $timezone ?>"
                                            <?= ((isset($post_data['timezone']) ? $post_data['timezone'] : 'Asia/Dhaka') == $timezone) ? 'selected' : '' ?>>
                                            <?= $timezone ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       value="<?= isset($post_data['email']) ? esc($post_data['email']) : '' ?>">
                            </div>

                            <!-- Phone -->
                             <div class="col-md-3">
                                <label class="form-label">Phone Code</label>
                                <input type="text" id="phone_code" name="phone_code" value="<?= isset($post_data['phone_code']) ? esc($post_data['phone_code']) : '' ?>" class="form-control " readonly>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       value="<?= isset($post_data['phone']) ? esc($post_data['phone']) : '' ?>">
                            </div>

                            <!-- Address -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                          class="form-control"
                                          rows="3"><?= isset($post_data['address']) ? esc($post_data['address']) : '' ?></textarea>
                            </div>

                            <!-- Logo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo</label>

                                <input type="file"
                                       name="logo"
                                       class="form-control"
                                       onchange="readURL(this, 'logo');">

                                <div class="mt-2">
                                    <?php
                                    $current_logo = $post_data['logo'] ?? ($school_data->logo ?? '');
                                    $logo = !empty($current_logo)
                                        ? base_url('uploads/' . $current_logo)
                                        : 'https://placehold.co/120x120?text=Logo';
                                    ?>

                                    <img src="<?= $logo; ?>"
                                         id="preview_logo"
                                         style="max-width:120px; max-height:120px;"
                                         class="img-thumbnail">
                                </div>
                            </div>

                            <!-- Custom Domain -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Custom Domain</label>

                                <input type="text"
                                       name="custom_domain"
                                       class="form-control"
                                       placeholder="schooldomain.com"
                                       value="<?= isset($post_data['custom_domain']) ? esc($post_data['custom_domain']) : '' ?>">
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <?= $status_list ?>
                            </div>

                        </div>
                    </div>

                    <!-- ========================================= -->
                    <!-- Modules Tab -->
                    <!-- ========================================= -->
                    <!-- <div class="tab-pane fade"
                         id="modules"
                         role="tabpanel">

                        <div class="row">

                            <?php
                            $selected_modules = isset($post_data['modules']) && is_array($post_data['modules']) ? $post_data['modules'] : [];
                            $available_modules = $modules ?? [];
                            ?>

                            <?php if (empty($available_modules)): ?>
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        No published modules are available.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($available_modules as $module): ?>
                                    <?php $slug = $module->slug; ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="modules[]"
                                                   value="<?= esc($slug) ?>"
                                                   id="module_<?= esc($slug) ?>"
                                                   <?= in_array($slug, $selected_modules) ? 'checked' : '' ?>>

                                            <label class="form-check-label" for="module_<?= esc($slug) ?>">
                                                <?= esc($module->name) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>

                    </div> -->


                    <!-- =========================================   -->
                    <!-- Settings Tab -->
                    <!-- =========================================   -->
                    <div class="tab-pane fade"
                         id="settings"
                         role="tabpanel">

                        <div class="row">
                            <div class="col-md-12">
                                
                            </div>

                            <!-- Student Account enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="student_account_enabled"
                                           value="1"
                                           id="student_account_enabled"
                                           <?= isset($post_data['student_account_enabled']) && $post_data['student_account_enabled'] == 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label" for="student_account_enabled">
                                        Enable Student Account
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">This will allow you to create student account and assign to student account.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Parent Account enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="parent_account_enabled"
                                           value="1"
                                           id="parent_account_enabled"
                                           <?= isset($post_data['parent_account_enabled']) && $post_data['parent_account_enabled'] == 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label" for="parent_account_enabled">
                                        Enable Parent Account
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">This will allow you to create parent account and assign to student</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Academic Class Roll enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="academic_class_roll_enabled"
                                           value="1"
                                           id="academic_class_roll_enabled"
                                           checked disabled>
                                    <input type="hidden" name="academic_class_roll_enabled" value="1">

                                    <label class="form-check-label" for="academic_class_roll_enabled">
                                        Enable Academic Class Roll
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">Always enabled for Bangladesh-focused schools.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Academic Category enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="academic_category_enabled"
                                           value="1"
                                           id="academic_category_enabled"
                                           checked disabled>
                                    <input type="hidden" name="academic_category_enabled" value="1">

                                    <label class="form-check-label" for="academic_category_enabled">
                                        Enable Academic Category 
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">Always enabled for Bangladesh-focused schools.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Academic Section enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="academic_section_enabled"
                                           value="1"
                                           id="academic_section_enabled"
                                           checked disabled>
                                    <input type="hidden" name="academic_section_enabled" value="1">

                                    <label class="form-check-label" for="academic_section_enabled">
                                        Enable Academic Section
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">Always enabled for Bangladesh-focused schools.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Academic Shift enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="academic_shift_enabled"
                                           value="1"
                                           id="academic_shift_enabled"
                                           <?= isset($post_data['academic_shift_enabled']) && $post_data['academic_shift_enabled'] == 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label" for="academic_shift_enabled">
                                        Enable Academic Shift
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">This will allow you to create academic shift and assign to student.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Academic Department enabled/disabled field -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="academic_department_enabled"
                                           value="1"
                                           id="academic_department_enabled"
                                           <?= isset($post_data['academic_department_enabled']) && $post_data['academic_department_enabled'] == 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label" for="academic_department_enabled">
                                        Enable Academic Department
                                        <br>
                                        <!-- Description -->
                                        <small class="form-text text-muted">This will allow you to create academic department and assign to student.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- ========================================= -->
                            <!-- ID Format Settings Section -->
                            <!-- ========================================= -->
                            <div class="col-md-12 mt-4 mb-3">
                                <h5 class="border-bottom pb-2">
                                    <i class="bi bi-hash"></i> ID Format Settings
                                </h5>
                            </div>

                            <!-- Enable/Disable Toggles -->
                            <div class="col-md-12 mb-3">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="enable_student_id"
                                                   value="1"
                                                   id="enable_student_id"
                                                   <?= isset($post_data['enable_student_id']) && $post_data['enable_student_id'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="enable_student_id">
                                                <strong>Enable Student ID</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="enable_qr_code"
                                                   value="1"
                                                   id="enable_qr_code"
                                                   <?= isset($post_data['enable_qr_code']) && $post_data['enable_qr_code'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="enable_qr_code">
                                                <strong>Enable QR Code</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="enable_registration_no"
                                                   value="1"
                                                   id="enable_registration_no"
                                                   <?= isset($post_data['enable_registration_no']) && $post_data['enable_registration_no'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="enable_registration_no">
                                                <strong>Enable Registration No</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="enable_rfid"
                                                   value="1"
                                                   id="enable_rfid"
                                                   <?= isset($post_data['enable_rfid']) && $post_data['enable_rfid'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="enable_rfid">
                                                <strong>Enable RFID Number</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Student ID Format -->
                            <div class="col-md-3 mb-3">
                                <div class="card border">
                                    <div class="card-header bg-light fw-bold">
                                        <i class="bi bi-person-badge"></i> Student ID Format
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="use_id_prefix"
                                                   value="1"
                                                   id="use_id_prefix"
                                                   <?= isset($post_data['use_id_prefix']) && $post_data['use_id_prefix'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="use_id_prefix">
                                                Use ID Prefix
                                            </label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">ID Prefix</label>
                                            <input type="text"
                                                   name="id_prefix"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. STU-"
                                                   value="<?= isset($post_data['id_prefix']) ? esc($post_data['id_prefix']) : '' ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small">ID Digit</label>
                                            <input type="number"
                                                   name="id_digit"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. 8"
                                                   min="1"
                                                   value="<?= isset($post_data['id_digit']) ? esc($post_data['id_digit']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Format -->
                            <div class="col-md-3 mb-3">
                                <div class="card border">
                                    <div class="card-header bg-light fw-bold">
                                        <i class="bi bi-qr-code"></i> QR Code Format
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="use_qr_prefix"
                                                   value="1"
                                                   id="use_qr_prefix"
                                                   <?= isset($post_data['use_qr_prefix']) && $post_data['use_qr_prefix'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="use_qr_prefix">
                                                Use QR Prefix
                                            </label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">QR Prefix</label>
                                            <input type="text"
                                                   name="qr_prefix"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. QR-"
                                                   value="<?= isset($post_data['qr_prefix']) ? esc($post_data['qr_prefix']) : '' ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small">QR Digit</label>
                                            <input type="number"
                                                   name="qr_digit"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. 8"
                                                   min="1"
                                                   value="<?= isset($post_data['qr_digit']) ? esc($post_data['qr_digit']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration No Format -->
                            <div class="col-md-3 mb-3">
                                <div class="card border">
                                    <div class="card-header bg-light fw-bold">
                                        <i class="bi bi-card-list"></i> Registration No Format
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="use_reg_prefix"
                                                   value="1"
                                                   id="use_reg_prefix"
                                                   <?= isset($post_data['use_reg_prefix']) && $post_data['use_reg_prefix'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="use_reg_prefix">
                                                Use Registration Prefix
                                            </label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Registration Prefix</label>
                                            <input type="text"
                                                   name="reg_prefix"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. REG-"
                                                   value="<?= isset($post_data['reg_prefix']) ? esc($post_data['reg_prefix']) : '' ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small">Registration Digit</label>
                                            <input type="number"
                                                   name="reg_digit"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. 8"
                                                   min="1"
                                                   value="<?= isset($post_data['reg_digit']) ? esc($post_data['reg_digit']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RFID Format -->
                            <div class="col-md-3 mb-3">
                                <div class="card border">
                                    <div class="card-header bg-light fw-bold">
                                        <i class="bi bi-upc-scan"></i> RFID Format
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="use_rfid_prefix"
                                                   value="1"
                                                   id="use_rfid_prefix"
                                                   <?= isset($post_data['use_rfid_prefix']) && $post_data['use_rfid_prefix'] == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="use_rfid_prefix">
                                                Use RFID Prefix
                                            </label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">RFID Prefix</label>
                                            <input type="text"
                                                   name="rfid_prefix"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. RFID-"
                                                   value="<?= isset($post_data['rfid_prefix']) ? esc($post_data['rfid_prefix']) : '' ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small">RFID Digit</label>
                                            <input type="number"
                                                   name="rfid_digit"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. 8"
                                                   min="1"
                                                   value="<?= isset($post_data['rfid_digit']) ? esc($post_data['rfid_digit']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>


                    <!-- =========================================   -->
                    <!-- Result sheet configuration Tab -->
                    <!-- =========================================   -->
                    <div class="tab-pane fade" id="result" role="tabpanel" aria-labelledby="result-tab" tabindex="0">

                        <!-- Load result_sheet_configuration.php -->
                        <?= view('school_owner/schools/result_sheet_configuration') ?>

                    </div>
                    

                </div>

            </div>

            <input type="hidden"
                   name="school_id"
                   value="<?= esc($school_id) ?>">

        </div>

    </div>
</div>

<?= form_close() ?>


<!-- Javascript functions -->
<script>
    const countries = <?= json_encode($countries); ?>;

    // ================= PHONE CODE =================
    function updatePhoneCode() {

        const code = document.getElementById('country').value;

        document.getElementById('phone_code').value =
            countries[code].phone_code;
    }

    document.getElementById('country')
        .addEventListener('change', updatePhoneCode);

    updatePhoneCode();

    function readURL(input, id) {

        if (input.files && input.files[0]) {

            var reader = new FileReader();

            reader.onload = function (e) {
                jQuery('#preview_' + id).attr('src', e.target.result);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

</script>
