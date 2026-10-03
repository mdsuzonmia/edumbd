<?php

$source = [];
if (isset($subscription_data) && is_object($subscription_data)) {
    $source = (array) $subscription_data;
}
if (isset($post_data) && is_array($post_data)) {
    $source = array_merge($source, $post_data);
}

$field_value = static function ($key, $default = '') use ($source) {
    return $source[$key] ?? $default;
};

$header_title = (isset($is_edit) && $is_edit == true)
    ? lang('Subscription.page_title_edit')
    : lang('Subscription.page_title_create');

$school_options = ['' => lang('Subscription.select_school')];
foreach (($schools ?? []) as $school) {
    $school_options[$school->id] = $school->name;
}

$plan_options = ['' => lang('Subscription.select_plan')];
foreach (($plans ?? []) as $plan) {
    $plan_options[$plan->id] = $plan->name;
}

$status_options = [
    '' => lang('Subscription.select_status'),
    '0' => lang('Subscription.status_pending'),
    '1' => lang('Subscription.status_trial'),
    '2' => lang('Subscription.status_active'),
    '3' => lang('Subscription.status_suspended'),
    '4' => lang('Subscription.status_expired'),
    '5' => lang('Subscription.status_cancelled'),
];

$billing_cycle_options = [
    '' => lang('Subscription.select_billing_cycle'),
    'monthly' => lang('Subscription.monthly'),
    'yearly' => lang('Subscription.yearly'),
    'lifetime' => lang('Subscription.lifetime'),
];

$yes_no_options = [
    '0' => lang('Common.sys_no'),
    '1' => lang('Common.sys_yes'),
];

?>

<?= form_open('saas-admin/subscriptions/store', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'subscription_form',
    'method' => 'post',
    'data-parsley-validate' => '',
]); ?>

<input type="hidden" name="id" value="<?= esc($field_value('id')) ?>">

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-repeat"></i>
            <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 title_right text-end">
        <button type="submit" class="btn btn-sm btn-success add">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/saas-admin/subscriptions') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Subscription.back_to_list') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card">
            <div class="card-body">

                <?php if (isset($validation)): ?>
                    <div style="color:red;">
                        <?= $validation->listErrors(); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.school'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <?= form_dropdown('school_id', $school_options, $field_value('school_id'), ['id' => 'school_id', 'class' => 'form-control', 'required' => 'required']); ?>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.plan'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <?= form_dropdown('plan_id', $plan_options, $field_value('plan_id'), ['id' => 'plan_id', 'class' => 'form-control', 'required' => 'required']); ?>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.status'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <?= form_dropdown('status', $status_options, $field_value('status', '0'), ['id' => 'status', 'class' => 'form-control', 'required' => 'required']); ?>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.is_trial'); ?></label>
                            <div class="col-sm-8">
                                <?= form_dropdown('is_trial', $yes_no_options, $field_value('is_trial', '0'), ['id' => 'is_trial', 'class' => 'form-control']); ?>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.start_date'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= esc($field_value('start_date', date('Y-m-d'))) ?>" required>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.end_date'); ?></label>
                            <div class="col-sm-8">
                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= esc($field_value('end_date')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.trial_end_date'); ?></label>
                            <div class="col-sm-8">
                                <input type="date" name="trial_end_date" id="trial_end_date" class="form-control" value="<?= esc($field_value('trial_end_date')) ?>">
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.amount'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input type="number" name="amount" id="amount" class="form-control" value="<?= esc($field_value('amount', '0.00')) ?>" min="0" step="0.01" required>
                            </div>
                        </div>

                        <?= field_text(
                            lang('Subscription.currency'),
                            'currency',
                            'currency',
                            'mb-3',
                            $field_value('currency', 'USD'),
                            true
                        ); ?>

                        <div class="row form-group mb-3">
                            <label class="form-label col-sm-4 control-label"><?= lang('Subscription.billing_cycle'); ?> <span class="required">*</span></label>
                            <div class="col-sm-8">
                                <?= form_dropdown('billing_cycle', $billing_cycle_options, $field_value('billing_cycle', 'monthly'), ['id' => 'billing_cycle', 'class' => 'form-control', 'required' => 'required']); ?>
                            </div>
                        </div>

                        <?= field_text(
                            lang('Subscription.payment_gateway'),
                            'payment_gateway',
                            'payment_gateway',
                            'mb-3',
                            $field_value('payment_gateway'),
                            false
                        ); ?>
                    </div>

                    <div class="col-sm-12">
                        <div class="mb-3">
                            <label class="form-label"><?= lang('Subscription.meta'); ?></label>
                            <textarea name="meta" id="meta" class="form-control" rows="5"><?= esc($field_value('meta')) ?></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?= form_close(); ?>
