<?php

$subscription_status_array = [
    '0' => lang('Subscription.status_pending'),
    '1' => lang('Subscription.status_trial'),
    '2' => lang('Subscription.status_active'),
    '3' => lang('Subscription.status_suspended'),
    '4' => lang('Subscription.status_expired'),
    '5' => lang('Subscription.status_cancelled'),
];

$data = isset($subscription_data) && is_object($subscription_data) ? $subscription_data : null;

?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-repeat"></i>
            <?= lang('Subscription.heading_view'); ?>
        </h3>
    </div>

    <div class="col-sm-6 title_right text-end">
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
                <?php if (! $data): ?>
                    <?= message_generator('error', lang('Common.data_error_id_missing')); ?>
                <?php else: ?>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="220"><?= lang('Subscription.user'); ?></th>
                                <td>
                                    <?= esc($data->user_name) ?>
                                    <?php if (! empty($data->user_email)): ?>
                                        <br><small class="text-muted"><?= esc($data->user_email) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.plan'); ?></th>
                                <td><?= esc($data->plan_name) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.status'); ?></th>
                                <td><?= esc($subscription_status_array[(string) $data->status] ?? $data->status) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.is_trial'); ?></th>
                                <td><?= ((int) $data->is_trial === 1) ? lang('Common.sys_yes') : lang('Common.sys_no') ?></td>
                            </tr>

                            <?php if((int) $data->is_trial === 1): ?>
                                <tr>
                                    <th><?= lang('Subscription.trial_start'); ?></th>
                                    <td><?= esc($data->trial_start) ?></td>
                                </tr>
                                <tr>
                                    <th><?= lang('Subscription.trial_end'); ?></th>
                                    <td><?= esc($data->trial_end) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?= lang('Subscription.start_date'); ?></th>
                                <td><?= esc($data->start_date) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.end_date'); ?></th>
                                <td><?= esc($data->end_date ?: '-') ?></td>
                            </tr>
                            
                            <tr>
                                <th><?= lang('Subscription.amount'); ?></th>
                                <td><?= esc(number_format((float) $data->amount, 2) . ' ' . $data->currency) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.billing_cycle'); ?></th>
                                <td><?= esc(ucfirst($data->billing_cycle)) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.payment_gateway'); ?></th>
                                <td><?= esc($data->payment_gateway ?: '-') ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Common.th_created_at'); ?></th>
                                <td><?= esc($data->created_at) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Common.th_updated_at'); ?></th>
                                <td><?= esc($data->updated_at) ?></td>
                            </tr>
                            <tr>
                                <th><?= lang('Subscription.meta'); ?></th>
                                <td><pre class="mb-0"><?= esc($data->meta ?: '-') ?></pre></td>
                            </tr>

                            <!-- token -->
                            <tr>
                                <th><?= lang('Subscription.token'); ?></th>
                                <td><code><?= esc($data->token ?: '-') ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details -->
<?php if ($data && !empty($related_payments)): ?>
<div class="row mt-4">
    <div class="col-sm-12 col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-credit-card"></i> <?= lang('Payment.heading_related_payments'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th><?= lang('Payment.th_transaction_id') ?></th>
                            <th><?= lang('Payment.th_gateway') ?></th>
                            <th class="text-center"><?= lang('Payment.th_amount') ?></th>
                            <th class="text-center"><?= lang('Payment.th_status') ?></th>
                            <th class="text-center"><?= lang('Payment.th_paid_at') ?></th>
                            <th class="text-center"><?= lang('Common.th_action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $status_badges = [
                            'pending' => 'badge text-bg-warning',
                            'paid' => 'badge text-bg-success',
                            'failed' => 'badge text-bg-danger',
                            'cancelled' => 'badge text-bg-dark',
                        ];
                        $status_labels = [
                            'pending' => lang('Payment.status_pending'),
                            'paid' => lang('Payment.status_paid'),
                            'failed' => lang('Payment.status_failed'),
                            'cancelled' => lang('Payment.status_cancelled'),
                        ];
                        ?>
                        <?php foreach ($related_payments as $payment): ?>
                            <?php 
                            $status_value = strtolower((string) $payment->status);
                            $status_class = $status_badges[$status_value] ?? 'badge text-bg-secondary';
                            $status_label = $status_labels[$status_value] ?? ucfirst($status_value);
                            $amount = number_format((float) $payment->amount, 2) . ' ' . esc($payment->currency);
                            $paid_at = $payment->paid_at ? date('d M, Y H:i', strtotime($payment->paid_at)) : '-';
                            $gateway_name = $payment->gateway ?? '';
                            ?>
                            <tr>
                                <td><?= esc($payment->transaction_id ?: '-') ?></td>
                                <td><?= esc(ucfirst($gateway_name)) ?></td>
                                <td class="text-center"><?= $amount ?></td>
                                <td class="text-center"><span class="<?= esc($status_class) ?>"><?= esc($status_label) ?></span></td>
                                <td class="text-center"><?= esc($paid_at) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('saas-admin/payments/view/' . $payment->id) ?>" class="btn btn-sm btn-info" title="<?= lang('Common.text_view') ?>"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


