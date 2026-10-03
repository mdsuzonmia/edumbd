<?php 
// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Plan.page_title_edit');
}else{
    $header_title = lang('Plan.page_title_create');
}

// Get Status
$status_value = isset($plan_data->status) ? $plan_data->status : 1;
$status_list  = get_status_list(
    lang('Common.th_status'),
    'status',
    'status',
    $status_value,
    true
);


// Currency List
$currency = isset($plan_data->currency) ? $plan_data->currency : 'BDT';


?>

<?= form_open('saas-admin/plans/store', [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'plan_form',
    'method'  => 'post',
    'enctype' => 'multipart/form-data',
    'data-parsley-validate' => ''
]); ?>

<!-- Exiting ID field -->
<input type="hidden" name="id" value="<?= isset($plan_data->id) ? $plan_data->id : '' ?>">

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-box"></i>
            <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 title_right text-end">
        <button type="submit" class="btn btn-sm btn-success add">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/saas-admin/plans') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Plan.back_to_list') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card">
            <div class="card-body">

                <?php if(isset($validation)): ?>
                    <div style="color:red;">
                        <?= $validation->listErrors(); ?>
                    </div>
                <?php endif; ?>

                <div class="row">

                    <!-- Left Side -->
                    <div class="col-sm-6">

                        <?= field_text(
                            lang('Plan.name'),
                            'name',
                            'name',
                            'mb-3',
                            isset($plan_data->name) ? $plan_data->name : '',
                            true
                        ); ?>

                        <?= field_text(
                            lang('Plan.slug'),
                            'slug',
                            'slug',
                            'mb-3',
                            isset($plan_data->slug) ? $plan_data->slug : '',
                            true
                        ); ?>

                        <!-- ================== PRICES (BDT + USD) ================== -->

                        <?php
                            // prices column keeps a JSON string:
                            // { "BDT": {monthly_price, yearly_price, lifetime_price}, "USD": {...} }
                            $prices_data = isset($plan_data->prices) ? json_decode((string) $plan_data->prices, true) : null;
                            if (!is_array($prices_data)) {
                                $prices_data = [];
                            }

                            // BDT prices (fallback to the legacy price columns)
                            $bdt_monthly_price  = isset($prices_data['BDT']['monthly_price'])  ? $prices_data['BDT']['monthly_price']  : (isset($plan_data->monthly_price)  ? $plan_data->monthly_price  : 0);
                            $bdt_yearly_price   = isset($prices_data['BDT']['yearly_price'])   ? $prices_data['BDT']['yearly_price']   : (isset($plan_data->yearly_price)   ? $plan_data->yearly_price   : 0);
                            $bdt_lifetime_price = isset($prices_data['BDT']['lifetime_price']) ? $prices_data['BDT']['lifetime_price'] : (isset($plan_data->lifetime_price) ? $plan_data->lifetime_price : 0);

                            // USD prices (for old plans that were stored directly in USD via the legacy columns)
                            $usd_monthly_price  = isset($prices_data['USD']['monthly_price'])  ? $prices_data['USD']['monthly_price']  : ((isset($plan_data->currency) && $plan_data->currency == 'USD') ? (isset($plan_data->monthly_price)  ? $plan_data->monthly_price  : 0) : 0);
                            $usd_yearly_price   = isset($prices_data['USD']['yearly_price'])   ? $prices_data['USD']['yearly_price']   : ((isset($plan_data->currency) && $plan_data->currency == 'USD') ? (isset($plan_data->yearly_price)   ? $plan_data->yearly_price   : 0) : 0);
                            $usd_lifetime_price = isset($prices_data['USD']['lifetime_price']) ? $prices_data['USD']['lifetime_price'] : ((isset($plan_data->currency) && $plan_data->currency == 'USD') ? (isset($plan_data->lifetime_price) ? $plan_data->lifetime_price : 0) : 0);
                        ?>

                        <div class="card border shadow-sm mb-3">
                            <div class="card-header py-2">
                                <strong><i class="bi bi-currency-exchange"></i> <?= lang('Plan.pricing'); ?></strong>
                            </div>

                            <div class="card-body">
                                <div class="row">

                                    <!-- BDT Price -->
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="fw-bold text-primary mb-3"><?= lang('Plan.price_bdt'); ?></h6>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="bdt_monthly_price"><?= lang('Plan.monthly_price'); ?></label>
                                                <input type="number" name="bdt_monthly_price" id="bdt_monthly_price" class="form-control" value="<?= $bdt_monthly_price; ?>" min="0" step="0.01">
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="bdt_yearly_price"><?= lang('Plan.yearly_price'); ?></label>
                                                <input type="number" name="bdt_yearly_price" id="bdt_yearly_price" class="form-control" value="<?= $bdt_yearly_price; ?>" min="0" step="0.01">
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="bdt_lifetime_price"><?= lang('Plan.lifetime_price'); ?></label>
                                                <input type="number" name="bdt_lifetime_price" id="bdt_lifetime_price" class="form-control" value="<?= $bdt_lifetime_price; ?>" min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- USD Price -->
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="fw-bold text-primary mb-3"><?= lang('Plan.price_usd'); ?></h6>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="usd_monthly_price"><?= lang('Plan.monthly_price'); ?></label>
                                                <input type="number" name="usd_monthly_price" id="usd_monthly_price" class="form-control" value="<?= $usd_monthly_price; ?>" min="0" step="0.01">
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="usd_yearly_price"><?= lang('Plan.yearly_price'); ?></label>
                                                <input type="number" name="usd_yearly_price" id="usd_yearly_price" class="form-control" value="<?= $usd_yearly_price; ?>" min="0" step="0.01">
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small mb-1" for="usd_lifetime_price"><?= lang('Plan.lifetime_price'); ?></label>
                                                <input type="number" name="usd_lifetime_price" id="usd_lifetime_price" class="form-control" value="<?= $usd_lifetime_price; ?>" min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>


                        <!-- Currency (primary / display currency) -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.currency'); ?>
                            </label>

                            <div class="col-sm-8">
                                <select name="currency" id="currency" class="form-control">
                                    <option value="BDT" <?= $currency == 'BDT' ? 'selected' : ''; ?>><?= lang('Plan.price_bdt'); ?></option>
                                    <option value="USD" <?= $currency == 'USD' ? 'selected' : ''; ?>><?= lang('Plan.price_usd'); ?></option>
                                </select>
                            </div>
                        </div>


                        <!-- custom_domain Yes/No -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.custom_domain'); ?>
                            </label>
                            <div class="col-sm-8">
                                <select name="custom_domain" id="custom_domain" class="form-control">
                                    <option value="1" <?= isset($plan_data->custom_domain) && $plan_data->custom_domain == 1 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_yes'); ?>
                                    </option>
                                    <option value="0" <?= isset($plan_data->custom_domain) && $plan_data->custom_domain == 0 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_no'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- mobile_app_access Yes/No -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.mobile_app_access'); ?>
                            </label>
                            <div class="col-sm-8">
                                <select name="mobile_app_access" id="mobile_app_access" class="form-control">
                                    <option value="1" <?= isset($plan_data->mobile_app_access) && $plan_data->mobile_app_access == 1 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_yes'); ?>
                                    </option>
                                    <option value="0" <?= isset($plan_data->mobile_app_access) && $plan_data->mobile_app_access == 0 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_no'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- api_access Yes/No -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.api_access'); ?>
                            </label>
                            <div class="col-sm-8">
                                <select name="api_access" id="api_access" class="form-control">
                                    <option value="1" <?= isset($plan_data->api_access) && $plan_data->api_access == 1 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_yes'); ?>
                                    </option>
                                    <option value="0" <?= isset($plan_data->api_access) && $plan_data->api_access == 0 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_no'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- is_popular Yes/No -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.is_popular'); ?>
                            </label>
                            <div class="col-sm-8">
                                <select name="is_popular" id="is_popular" class="form-control">
                                    <option value="1" <?= isset($plan_data->is_popular) && $plan_data->is_popular == 1 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_yes'); ?>
                                    </option>
                                    <option value="0" <?= isset($plan_data->is_popular) && $plan_data->is_popular == 0 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_no'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- is_featured Yes/No -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.is_featured'); ?>
                            </label>
                            <div class="col-sm-8">
                                <select name="is_featured" id="is_featured" class="form-control">
                                    <option value="1" <?= isset($plan_data->is_featured) && $plan_data->is_featured == 1 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_yes'); ?>
                                    </option>
                                    <option value="0" <?= isset($plan_data->is_featured) && $plan_data->is_featured == 0 ? 'selected' : ''; ?>>
                                        <?= lang('Common.sys_no'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- Right Side -->
                    <div class="col-sm-6">

                        <!-- Trial Days Number field -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.trial_days'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="trial_days" 
                                    id="trial_days" 
                                    class="form-control"
                                    value="<?= isset($plan_data->trial_days) ? $plan_data->trial_days : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- Student Limit Number field -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.student_limit'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="student_limit" 
                                    id="student_limit" 
                                    class="form-control"
                                    value="<?= isset($plan_data->student_limit) ? $plan_data->student_limit : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- teachers_limit -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.teachers_limit'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="teachers_limit" 
                                    id="teachers_limit" 
                                    class="form-control"
                                    value="<?= isset($plan_data->teachers_limit) ? $plan_data->teachers_limit : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- branch_limit -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.branch_limit'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="branch_limit" 
                                    id="branch_limit" 
                                    class="form-control"
                                    value="<?= isset($plan_data->branch_limit) ? $plan_data->branch_limit : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- admin_limit -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.admin_limit'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="admin_limit" 
                                    id="admin_limit" 
                                    class="form-control"
                                    value="<?= isset($plan_data->admin_limit) ? $plan_data->admin_limit : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- sms_limit -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.sms_limit'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="sms_limit" 
                                    id="sms_limit" 
                                    class="form-control"
                                    value="<?= isset($plan_data->sms_limit) ? $plan_data->sms_limit : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <!-- storage_limit_mb -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.storage_limit_mb'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="storage_limit_mb" 
                                    id="storage_limit_mb" 
                                    class="form-control"
                                    value="<?= isset($plan_data->storage_limit_mb) ? $plan_data->storage_limit_mb : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>



                        <!-- sort_order field -->
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label">
                                <?= lang('Plan.sort_order'); ?>
                            </label>

                            <div class="col-sm-8">
                                <input 
                                    type="number" 
                                    name="sort_order" 
                                    id="sort_order" 
                                    class="form-control"
                                    value="<?= isset($plan_data->sort_order) ? $plan_data->sort_order : 0; ?>"
                                    min="0"
                                >
                            </div>
                        </div>

                        <?= $status_list; ?>

                    </div>

                    <!-- Full Width -->
                    <div class="col-sm-12">

                        <div class="mb-3">
                            <label class="form-label">
                                <?= lang('Plan.description'); ?>
                            </label>

                            <textarea 
                                name="description"
                                class="form-control"
                                rows="5"
                            ><?= isset($plan_data->description) ? $plan_data->description : ''; ?></textarea>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?= form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        // Slag generation from name
        $('#name').on('input', function() {
            var name = $(this).val();
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-') // Replace non-alphanumeric characters with hyphens
                .replace(/^-+|-+$/g, '');    // Remove leading and trailing hyphens

            $('#slug').val(slug);
        });
    });

</script>