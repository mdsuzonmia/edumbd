<?php
echo get_system_message();

$currentPlanId = isset($current_subscription->plan_id) ? (int) $current_subscription->plan_id : 0;
$currentCycle = $current_subscription->billing_cycle ?? '';

function plan_features($features): array
{
    if (empty($features)) {
        return [];
    }

    $decoded = json_decode((string) $features, true);
    if (is_array($decoded)) {
        return array_filter(array_map('trim', $decoded));
    }

    return array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $features)));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-box-arrow-up-right"></i> Upgrade Subscription Plan</h3>
        <p class="text-muted mb-0">Choose the plan and billing cycle for your school.</p>
    </div>
    <a href="<?= site_url('school-owner/plans/history') ?>" class="btn btn-outline-primary">
        <i class="bi bi-clock-history"></i> Plan History
    </a>
</div>

<?php if (!empty($current_subscription)): ?>
    <div class="alert alert-info border-0 shadow-sm">
        Current plan:
        <strong><?= esc($current_subscription->plan_name ?? 'Trial Plan') ?></strong>
        <?php if (!empty($current_subscription->billing_cycle)): ?>
            (<?= esc(ucfirst($current_subscription->billing_cycle)) ?>)
        <?php endif; ?>
        <?php if (!empty($current_subscription->end_date)): ?>
            expires on <strong><?= esc(date('d M, Y', strtotime($current_subscription->end_date))) ?></strong>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $plan): ?>
            <?php
            $features = plan_features($plan->features ?? '');
            $isCurrentPlan = $currentPlanId === (int) $plan->id;
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1"><?= esc($plan->name) ?></h5>
                                <span class="text-muted"><?= esc($plan->currency ?? 'USD') ?></span>
                            </div>
                            <?php if (!empty($plan->is_popular)): ?>
                                <span class="badge text-bg-success">Popular</span>
                            <?php elseif ($isCurrentPlan): ?>
                                <span class="badge text-bg-primary">Current</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($plan->description)): ?>
                            <p class="text-muted small"><?= esc($plan->description) ?></p>
                        <?php endif; ?>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="border rounded p-2 h-100">
                                    <small class="text-muted d-block">Monthly</small>
                                    <strong><?= number_format((float) $plan->monthly_price, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 h-100">
                                    <small class="text-muted d-block">Yearly</small>
                                    <strong><?= number_format((float) $plan->yearly_price, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 h-100">
                                    <small class="text-muted d-block">Lifetime</small>
                                    <strong><?= number_format((float) $plan->lifetime_price, 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <ul class="list-unstyled small mb-3">
                            <li><i class="bi bi-people text-success"></i> <?= ($plan->student_limit == 0) ? 'Unlimited' : number_format((int) $plan->student_limit) ?> students</li>
                            <li><i class="bi bi-person-badge text-success"></i> <?= ($plan->teachers_limit == 0) ? 'Unlimited' : number_format((int) $plan->teachers_limit) ?> teachers</li>
                            <li><i class="bi bi-building text-success"></i> <?= ($plan->branch_limit == 0) ? 'Unlimited' : number_format((int) $plan->branch_limit) ?> branches</li>
                            <li><i class="bi bi-hdd text-success"></i> <?= ($plan->storage_limit_mb == 0) ? 'Unlimited' : number_format((int) $plan->storage_limit_mb) ?> MB storage</li>
                            <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                                <li><i class="bi bi-check-circle text-success"></i> <?= esc($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <?= form_open('school-owner/plans/upgrade', ['class' => 'mt-auto']); ?>
                            <input type="hidden" name="plan_id" value="<?= esc($plan->id) ?>">
                            <input type="hidden" name="payment_gateway" class="payment-gateway-field" value="paypal">
                            <div class="mb-3">
                                <label class="form-label">Billing Cycle</label>
                                <select name="billing_cycle" class="form-select billing-cycle-field">
                                    <option value="monthly" data-price="<?= esc($plan->monthly_price) ?>" <?= $isCurrentPlan && $currentCycle === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                    <option value="yearly" data-price="<?= esc($plan->yearly_price) ?>" <?= $isCurrentPlan && $currentCycle === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                    <option value="lifetime" data-price="<?= esc($plan->lifetime_price) ?>" <?= $isCurrentPlan && $currentCycle === 'lifetime' ? 'selected' : '' ?>>Lifetime</option>
                                </select>
                            </div>

                            <label class="form-label">Payment Method</label>
                            <div class="row g-2 mb-3 payment-methods">
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-primary active w-100 payment-method-btn" data-payment="paypal">
                                        <img src="<?= base_url('assets/images/payments/paypal.png') ?>" height="24" class="d-block mx-auto mb-1" alt="PayPal">
                                        <small>PayPal</small>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-primary w-100 payment-method-btn" data-payment="stripe">
                                        <img src="<?= base_url('assets/images/payments/stripe.png') ?>" height="24" class="d-block mx-auto mb-1" alt="Stripe">
                                        <small>Stripe</small>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-primary w-100 payment-method-btn" data-payment="manual">
                                        <img src="<?= base_url('assets/images/payments/manual.png') ?>" height="24" class="d-block mx-auto mb-1" alt="Manual">
                                        <small>Manual</small>
                                    </button>
                                </div>
                            </div>

                            <div class="manual-payment-area alert alert-info d-none">
                                <h6 class="fw-bold mb-2">Manual Payment Instructions</h6>
                                <ul class="mb-3 small">
                                    <li>bKash: 017XXXXXXXX</li>
                                    <li>Nagad: 018XXXXXXXX</li>
                                    <li>Bank Account: Your Bank Info</li>
                                </ul>
                                <div class="mb-2">
                                    <label class="form-label">Transaction ID</label>
                                    <input type="text" name="manual_transaction_id" class="form-control manual-transaction-field">
                                </div>
                                <div>
                                    <label class="form-label">Payment Note</label>
                                    <textarea name="manual_note" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Change subscription plan?')">
                                <i class="bi bi-arrow-up-circle"></i>
                                <?= $isCurrentPlan ? 'Change Billing Cycle' : 'Choose Plan' ?>
                            </button>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning border-0 shadow-sm">No active subscription plans are available.</div>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('form').forEach(function(form) {
    const gatewayField = form.querySelector('.payment-gateway-field');
    const manualArea = form.querySelector('.manual-payment-area');
    const manualTransaction = form.querySelector('.manual-transaction-field');
    const cycleField = form.querySelector('.billing-cycle-field');
    const paymentButtons = form.querySelectorAll('.payment-method-btn');

    function selectedAmount() {
        const selected = cycleField.options[cycleField.selectedIndex];
        return parseFloat(selected.dataset.price || '0');
    }

    function setGateway(gateway) {
        gatewayField.value = selectedAmount() <= 0 ? 'free' : gateway;

        paymentButtons.forEach(function(button) {
            button.classList.toggle('active', button.dataset.payment === gateway);
        });

        const manualSelected = gatewayField.value === 'manual';
        manualArea.classList.toggle('d-none', !manualSelected);
        manualTransaction.required = manualSelected;
    }

    paymentButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            setGateway(button.dataset.payment);
        });
    });

    cycleField.addEventListener('change', function() {
        setGateway(gatewayField.value === 'free' ? 'paypal' : gatewayField.value);
    });

    setGateway('paypal');
});
</script>
