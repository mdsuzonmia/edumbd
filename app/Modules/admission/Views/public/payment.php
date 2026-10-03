<?= view('App\Modules\admission\Views\public\_header',['title'=>'Submit payment · '.$application->application_no]) ?>
<div class="card shadow-sm border-0 mx-auto" style="max-width:760px">
    <div class="card-body p-4">
        <a href="<?= base_url('admission/application/'.$application->token) ?>">← Back to application</a>
        <h1 class="h3 mt-3"><?= esc(ucwords(str_replace('_',' ',$type))) ?></h1>
        <p><?= esc($school->name) ?> · <?= esc($application->application_no) ?></p>
        <div class="alert alert-info"><strong>Amount:</strong> <?= esc($currency) ?> <?= number_format($amount,2) ?></div>

        <?php if(!$gateways): ?>
            <div class="alert alert-warning">No payment method is currently configured. Please contact the school.</div>
        <?php else: ?>
            <?= form_open_multipart(current_url(),['id'=>'admission-payment-form']) ?>
            <h2 class="h6 mb-3">Choose a payment method</h2>
            <div class="row g-2 mb-4">
                <?php $selected=old('gateway',array_key_first($gateways));foreach($gateways as $key=>$label): ?>
                    <div class="col-md-4">
                        <label class="border rounded p-3 w-100 h-100 payment-method" style="cursor:pointer">
                            <input type="radio" name="gateway" value="<?= esc($key,'attr') ?>" <?= $selected===$key?'checked':'' ?>>
                            <strong class="ms-1"><?= esc($label) ?></strong>
                            <small class="text-muted d-block mt-1"><?= $key==='manual'?'Submit a transaction reference for school review':'Continue to secure provider checkout' ?></small>
                        </label>
                    </div>
                <?php endforeach ?>
            </div>

            <div id="manual-payment-fields" class="border rounded p-3 mb-3">
                <p class="text-muted">Complete payment using the school’s instructed channel, then submit the reference and receipt. The school must verify it before the fee is marked paid.</p>
                <?php if(!empty($paymentSettings->manual_instructions)): ?><div class="alert alert-light"><?= nl2br(esc($paymentSettings->manual_instructions)) ?></div><?php elseif($circular->instructions): ?><div class="alert alert-light"><?= nl2br(esc($circular->instructions)) ?></div><?php endif ?>
                <div class="mb-3">
                    <label class="form-label">Transaction ID / reference *</label>
                    <input class="form-control" id="manual-transaction-id" name="transaction_id" value="<?= esc(old('transaction_id')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payer reference</label>
                    <input class="form-control" name="payer_reference" value="<?= esc(old('payer_reference')) ?>" placeholder="Mobile/account used for payment">
                </div>
                <div>
                    <label class="form-label">Receipt or proof (PDF/image, max 5 MB)</label>
                    <input type="file" class="form-control" name="proof_file" accept="application/pdf,image/*">
                </div>
            </div>
            <button class="btn btn-primary" id="payment-submit">Continue</button>
            <?= form_close() ?>
        <?php endif ?>
    </div>
</div>
<?php if($gateways): ?><script>
(() => {
    const methods = document.querySelectorAll('input[name="gateway"]');
    const manualFields = document.getElementById('manual-payment-fields');
    const transaction = document.getElementById('manual-transaction-id');
    const submit = document.getElementById('payment-submit');
    const refresh = () => {
        const selected = document.querySelector('input[name="gateway"]:checked')?.value;
        const manual = selected === 'manual';
        manualFields.hidden = !manual;
        transaction.required = manual;
        submit.textContent = manual ? 'Submit payment receipt' : `Continue to ${selected === 'paypal' ? 'PayPal' : 'Stripe'}`;
    };
    methods.forEach(method => method.addEventListener('change', refresh));
    refresh();
})();
</script><?php endif ?>
<?= view('App\Modules\admission\Views\public\_footer') ?>
