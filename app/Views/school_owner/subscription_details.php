<?= get_system_message(); ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-credit-card-2-front"></i> Subscription Details</h3>
        <p class="text-muted mb-0">Review your subscription information and renewal options.</p>
    </div>
    <a href="<?= site_url('school-owner/subscriptions') ?>" class="btn btn-outline-secondary">Back to Subscriptions</a>
</div>

<?php if (empty($subscription)): ?>
    <div class="alert alert-warning">Subscription not found.</div>
<?php else: ?>
    <?php
    $statusLabels = [
        0 => ['Pending', 'warning'],
        1 => ['Trial', 'info'],
        2 => ['Active', 'success'],
        3 => ['Suspended', 'warning'],
        4 => ['Expired', 'danger'],
        5 => ['Cancelled', 'secondary'],
        6 => ['Trashed', 'danger'],
    ];
    $status = $statusLabels[(int) $subscription->status] ?? [ucfirst($subscription->status ?? '—'), 'secondary'];
    $isActive = in_array($subscription->status, [1, 2]);
    $isExpired = $subscription->status == 4;
    ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Plan Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Plan Name</small></p>
                            <h6 class="mb-0"><?= esc($subscription->plan_name ?: '—') ?></h6>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Billing Cycle</small></p>
                            <h6 class="mb-0"><?= esc(ucfirst($subscription->billing_cycle ?? '—')) ?></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Amount</small></p>
                            <h6 class="mb-0"><?= esc($subscription->currency ?: 'USD') ?> <?= number_format((float) ($subscription->amount ?? 0), 2) ?></h6>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Payment Gateway</small></p>
                            <h6 class="mb-0"><?= esc(ucfirst($subscription->payment_gateway ?? '—')) ?></h6>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Subscription Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Start Date</small></p>
                            <p class="mb-0"><?= ! empty($subscription->start_date) ? esc(date('d M, Y', strtotime($subscription->start_date))) : '—' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">End Date</small></p>
                            <p class="mb-0"><?= ! empty($subscription->end_date) ? esc(date('d M, Y', strtotime($subscription->end_date))) : 'Lifetime' ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Last Payment</small></p>
                            <p class="mb-0"><?= ! empty($subscription->last_payment_at) ? esc(date('d M, Y h:i A', strtotime($subscription->last_payment_at))) : '—' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Next Billing</small></p>
                            <p class="mb-0"><?= ! empty($subscription->next_billing_at) ? esc(date('d M, Y', strtotime($subscription->next_billing_at))) : '—' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Status & Actions</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-2"><small class="text-muted">Current Status</small></p>
                        <div>
                            <span class="badge text-bg-<?= esc($status[1]) ?> fs-6"><?= esc($status[0]) ?></span>
                        </div>
                    </div>

                    <hr>

                    <?php if ($isActive): ?>
                        <p class="text-muted text-sm mb-3">Your subscription is active. You can renew it before the end date to extend your access.</p>
                    <?php elseif ($isExpired): ?>
                        <p class="text-muted text-sm mb-3">Your subscription has expired. Please renew to regain access.</p>
                    <?php else: ?>
                        <p class="text-muted text-sm">This subscription is not available for renewal at this time.</p>
                    <?php endif; ?>

                    <?php if ($isActive || $isExpired): ?>
                        <div class="mb-3">
                            <label for="renew_payment_method" class="form-label small text-muted">Payment Method</label>
                            <select id="renew_payment_method" class="form-select">
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="renewSubscription('<?= esc($subscription->token) ?>')">
                            <i class="bi bi-arrow-repeat"></i> Renew Subscription
                        </button>
                    <?php endif; ?>

                    <hr>

                    <div>
                        <p class="mb-1"><small class="text-muted">Subscription Token</small></p>
                        <p class="text-break small"><?= esc($subscription->token) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function renewSubscription(subscription_token) {
    if (! confirm('Are you sure you want to renew this subscription?')) {
        return;
    }

    const formData = new FormData();
    formData.append('subscription_token', subscription_token);

    const paymentMethod = document.getElementById('renew_payment_method');
    if (paymentMethod) {
        formData.append('payment_method', paymentMethod.value);
    }
    

    const csrfToken = document.getElementById('csrf_token')?.value;

    fetch('<?= site_url("school-owner/subscription/renew") ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
        body: formData,
    })
        .then(response => {
            const newToken = response.headers.get('X-CSRF-TOKEN');
            if (newToken) {
                document.getElementById('csrf_token').value = newToken;
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                alert('Error: ' + data.message);
                return;
            }

            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            if (data.options) {
                const method = document.getElementById('renew_payment_method')?.value || 'stripe';
                const url = data.options[method];

                if (url) {
                    window.location.href = url;
                } else {
                    alert('Payment method not available. Please choose a valid option.');
                }
                return;
            }

            alert('Payment options are not available.');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}
</script>
