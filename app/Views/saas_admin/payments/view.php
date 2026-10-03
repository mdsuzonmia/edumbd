<?php $data = isset($payment_data) && is_object($payment_data) ? $payment_data : null; ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-credit-card"></i>
            <?= lang('Payment.heading_view'); ?>
        </h3>
    </div>

    <div class="col-sm-6 title_right text-end">
        <a href="<?= base_url('/saas-admin/payments') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Payment.back_to_list') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>
                <?php if (! $data): ?>
                    <?= message_generator('error', lang('Common.data_error_id_missing')); ?>
                <?php else: ?>
                    <?php $gateway_name = $data->gateway ?? ''; ?>
                    <table class="table table-bordered">
                        <tbody>
                            
                            <tr><th><?= lang('Payment.user'); ?></th><td><?= esc($data->user_name ?: '-') ?><br><small class="text-muted"><?= esc($data->user_email ?: '') ?></small></td></tr>
                            <tr><th><?= lang('Payment.plan'); ?></th><td><?= esc($data->plan_name ?: '-') ?></td></tr>
                            <tr><th><?= lang('Payment.gateway'); ?></th><td><?= esc(ucfirst($gateway_name)) ?></td></tr>
                            <tr><th><?= lang('Payment.billing_cycle'); ?></th><td><?= esc(ucfirst($data->billing_cycle ?: '-')) ?></td></tr>
                            <tr><th><?= lang('Payment.amount'); ?></th><td><?= esc(number_format((float) $data->amount, 2) . ' ' . $data->currency) ?></td></tr>
                            <tr><th><?= lang('Payment.status'); ?></th><td><?= esc(ucfirst($data->status)) ?></td></tr>
                            <?php if (strtolower($gateway_name) === 'manual'): ?>
                                <tr>
                                    <th><?= lang('Payment.change_status'); ?></th>
                                    <td>
                                        <form action="<?= base_url('saas-admin/payments/status/' . $data->id) ?>" method="post" class="d-flex flex-wrap align-items-center gap-2">
                                            <?= csrf_field() ?>
                                            <?php $status_options = [
                                                'pending' => lang('Payment.status_pending'),
                                                'paid' => lang('Payment.status_paid'),
                                                'failed' => lang('Payment.status_failed'),
                                                'cancelled' => lang('Payment.status_cancelled'),
                                            ]; ?>
                                            <select name="status" class="form-select form-select-sm w-auto">
                                                <?php foreach ($status_options as $status_key => $status_label): ?>
                                                    <option value="<?= esc($status_key) ?>" <?= $data->status === $status_key ? 'selected' : '' ?>><?= esc($status_label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary"><?= lang('Common.btn_save') ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr><th><?= lang('Payment.transaction_id'); ?></th><td><?= esc($data->transaction_id ?: '-') ?></td></tr>
                            <tr><th><?= lang('Payment.gateway_payment_id'); ?></th><td><?= esc($data->gateway_payment_id ?: '-') ?></td></tr>
                            <tr><th><?= lang('Payment.paid_at'); ?></th><td><?= esc($data->paid_at ?: '-') ?></td></tr>
                            <tr><th><?= lang('Common.th_created_at'); ?></th><td><?= esc($data->created_at ?: '-') ?></td></tr>
                            <tr><th><?= lang('Common.th_updated_at'); ?></th><td><?= esc($data->updated_at ?: '-') ?></td></tr>
                            <tr><th><?= lang('Payment.payment_payload'); ?></th><td><pre class="mb-0"><?= esc($data->payment_payload ?: '-') ?></pre></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
