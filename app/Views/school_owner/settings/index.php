<?php
echo get_system_message();

$phone_code = '';
if (isset($school->country) && isset($countries[$school->country])) {
    $phone_code = $countries[$school->country]['phone_code'];
}

$value = function ($key, $default = '') use ($school) {
    return isset($school->{$key}) && $school->{$key} !== null && $school->{$key} !== ''
        ? $school->{$key}
        : $default;
};

$setting_value = function ($key, $default = '') use ($settings) {
    if (empty($settings)) {
        return $default;
    }
    foreach ($settings as $row) {
        if (isset($row['key']) && $row['key'] === $key) {
            return $row['value'] !== null && $row['value'] !== '' ? $row['value'] : $default;
        }
    }
    return $default;
};
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-gear"></i> School Settings
        </h3>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active"
                        id="general-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#general"
                        type="button"
                        role="tab">
                    <i class="bi bi-building"></i> General
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="modules-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#modules"
                        type="button"
                        role="tab">
                    <i class="bi bi-grid"></i> School Modules
                </button>
            </li>

        </ul>

        <div class="tab-content">

            <!-- ================= General ================= -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" tabindex="0">

                <?= form_open('school-owner/settings/update', [
                    'class'   => 'form-horizontal form-label-left',
                    'method'  => 'post',
                    'data-parsley-validate' => '',
                ]); ?>

                <div class="row">

                    <!-- School Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">School Name</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($value('name')) ?>">
                    </div>

                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= esc($value('email')) ?>">
                    </div>

                    <!-- Country -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Country</label>
                        <select name="country" id="country" class="form-control" onchange="updatePhoneCode();">
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $code => $country): ?>
                                <option value="<?= esc($code) ?>"
                                    data-phone-code="<?= esc($country['phone_code'] ?? '') ?>"
                                    <?= (isset($school->country) && $school->country === $code) ? 'selected' : '' ?>>
                                    <?= esc($country['name'] ?? $code) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <div class="input-group">
                            <span class="input-group-text" id="phone_code_display"><?= esc($phone_code) ?></span>
                            <input type="text" name="phone" class="form-control" value="<?= esc($value('phone')) ?>">
                        </div>
                    </div>


                    <!-- Timezone -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-control">
                            <option value="">Select Timezone</option>
                            <?php foreach ($timezone_list as $tz): ?>
                                <option value="<?= esc($tz) ?>" <?= (isset($school->timezone) && $school->timezone === $tz) ? 'selected' : '' ?>>
                                    <?= esc($tz) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Custom Domain -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Custom Domain</label>
                        <input type="text" name="custom_domain" class="form-control"
                               placeholder="schooldomain.com"
                               value="<?= esc($value('custom_domain')) ?>">
                    </div>

                    <!-- Address -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= esc($value('address')) ?></textarea>
                    </div>

                    <!-- Additional school setting stored in school_settings -->
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="setting[enable_registration]"
                                   value="1"
                                   id="enable_registration"
                                   <?= $setting_value('enable_registration') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enable_registration">
                                Allow student self registration
                            </label>
                        </div>
                    </div>

                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-floppy-disk"></i> Save General Settings
                    </button>
                </div>

                <?= form_close(); ?>

            </div>



            <!-- ================= Modules ================= -->
            <div class="tab-pane fade" id="modules" role="tabpanel" tabindex="0">

                <?= form_open('school-owner/settings/modules/save', [
                    'class'   => 'form-horizontal form-label-left',
                    'method'  => 'post',
                ]); ?>

                <div class="alert alert-info">
                    Enable the modules you want to use for this school. Only published modules are shown here.
                </div>

                <?php if (empty($modules)): ?>

                    <div class="alert alert-warning mb-0">
                        No published modules are available.
                    </div>

                <?php else: ?>

                    <div class="row">
                        <?php foreach ($modules as $module): ?>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="modules[]"
                                           value="<?= esc($module->id) ?>"
                                           id="module_<?= esc($module->slug) ?>"
                                           <?= in_array($module->slug, $enabled_module_slugs) ? 'checked' : '' ?>>

                                    <label class="form-check-label" for="module_<?= esc($module->slug) ?>">
                                        <?= esc($module->name) ?>
                                        <?php if (!empty($module->description)): ?>
                                            <br>
                                            <small class="form-text text-muted"><?= esc($module->description) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fa-solid fa-floppy-disk"></i> Save Modules
                        </button>
                    </div>

                <?php endif; ?>

                <?= form_close(); ?>

            </div>

        </div>

    </div>
</div>

<script>
    const countries = <?= json_encode($countries); ?>;

    function updatePhoneCode() {
        const select = document.getElementById('country');
        const selected = select.options[select.selectedIndex];
        const code = selected ? (selected.dataset.phoneCode || '') : '';
        document.getElementById('phone_code_display').textContent = code;
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput && !phoneInput.value) {
            phoneInput.value = code;
        }
    }
</script>

