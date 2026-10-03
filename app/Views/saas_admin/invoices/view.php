<?php $data = isset($invoice_data) && is_object($invoice_data) ? $invoice_data : null; ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-receipt"></i>
            <?= lang('Invoice.heading_view'); ?>
        </h3>
    </div>

    <div class="col-sm-6 title_right text-end">
        <a href="<?= base_url('/saas-admin/invoices') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Invoice.back_to_list') ?>
        </a>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-success">
            <i class="fa fa-print"></i>
            <?= lang('Common.btn_print') ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (! $data): ?>
                    <?= message_generator('error', lang('Common.data_error_id_missing')); ?>
                <?php else: ?>
                    <?php
                    $gateway_name = $data->gateway ?? '';
                    $invoice_no = $InvoiceModel->invoiceNumber($data);
                    ?>
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h4 class="mb-1"><?= esc($invoice_no) ?></h4>
                            <p class="text-muted mb-0"><?= esc($data->paid_at ?: $data->created_at) ?></p>
                        </div>
                        <div class="col-sm-6 text-end">
                            <h4 class="mb-1"><?= esc(number_format((float) $data->amount, 2) . ' ' . $data->currency) ?></h4>
                            <span class="badge text-bg-success"><?= esc(ucfirst($data->status)) ?></span>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <tbody>
                            <tr><th width="220"><?= lang('Invoice.school'); ?></th><td><?= esc($data->school_name ?: '-') ?><br><small class="text-muted"><?= esc($data->school_email ?: '') ?></small></td></tr>
                            <tr><th><?= lang('Invoice.user'); ?></th><td><?= esc($data->user_name ?: '-') ?><br><small class="text-muted"><?= esc($data->user_email ?: '') ?></small></td></tr>
                            <tr><th><?= lang('Invoice.plan'); ?></th><td><?= esc($data->plan_name ?: '-') ?></td></tr>
                            <tr><th><?= lang('Invoice.gateway'); ?></th><td><?= esc(ucfirst($gateway_name)) ?></td></tr>
                            <tr><th><?= lang('Invoice.billing_cycle'); ?></th><td><?= esc(ucfirst($data->billing_cycle ?: '-')) ?></td></tr>
                            <tr><th><?= lang('Invoice.transaction_id'); ?></th><td><?= esc($data->transaction_id ?: '-') ?></td></tr>
                            <tr><th><?= lang('Invoice.gateway_payment_id'); ?></th><td><?= esc($data->gateway_payment_id ?: '-') ?></td></tr>
                            <tr><th><?= lang('Invoice.paid_at'); ?></th><td><?= esc($data->paid_at ?: '-') ?></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
