<div class="right_col" role="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Admission Payment Settings</h2>
        <?php if(count($schools)>1): ?><form method="get" class="d-flex gap-2"><select name="school_id" class="form-control" onchange="this.form.submit()"><?php foreach($schools as $school): ?><option value="<?= $school->id ?>" <?= $schoolId===$school->id?'selected':'' ?>><?= esc($school->name) ?></option><?php endforeach ?></select></form><?php endif ?>
    </div>
    <div class="alert alert-info">Choose which methods applicants can use. Provider credentials are encrypted before storage and are never displayed again.</div>
    <div class="card shadow-sm"><div class="card-body">
        <?= form_open('school/admission/payment-settings/save') ?>
        <input type="hidden" name="school_id" value="<?= $schoolId ?>">
        <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Payment currency</label><input class="form-control text-uppercase" name="currency" maxlength="3" value="<?= esc(old('currency',$settings->currency)) ?>" required><small class="text-muted">Use a three-letter currency supported by each enabled provider.</small></div></div>

        <div class="border rounded p-3 mb-3">
            <div class="form-check form-switch mb-2"><input type="hidden" name="manual_enabled" value="0"><input class="form-check-input" type="checkbox" name="manual_enabled" value="1" id="manual_enabled" <?= old('manual_enabled',$settings->manual_enabled)?'checked':'' ?>><label class="form-check-label fw-bold" for="manual_enabled">Enable Manual Payment</label></div>
            <label class="form-label">Payment instructions</label><textarea class="form-control" rows="3" name="manual_instructions" placeholder="Bank/mobile payment account and instructions"><?= esc(old('manual_instructions',$settings->manual_instructions)) ?></textarea>
        </div>

        <div class="border rounded p-3 mb-3">
            <div class="form-check form-switch mb-3"><input type="hidden" name="stripe_enabled" value="0"><input class="form-check-input" type="checkbox" name="stripe_enabled" value="1" id="stripe_enabled" <?= old('stripe_enabled',$settings->stripe_enabled)?'checked':'' ?>><label class="form-check-label fw-bold" for="stripe_enabled">Enable Stripe</label></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Publishable key</label><input class="form-control" name="stripe_publishable_key" value="<?= esc(old('stripe_publishable_key',$settings->stripe_publishable_key)) ?>" placeholder="pk_test_..."></div><div class="col-md-6 mb-3"><label class="form-label">Secret key</label><input type="password" class="form-control" name="stripe_secret_key" autocomplete="new-password" placeholder="<?= $credentialStatus['stripe']?'Configured — leave blank to keep':'sk_test_...' ?>"><small class="text-muted"><?= $credentialStatus['stripe']?'A secret key is securely stored.':'' ?></small></div></div>
        </div>

        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between"><div class="form-check form-switch mb-3"><input type="hidden" name="paypal_enabled" value="0"><input class="form-check-input" type="checkbox" name="paypal_enabled" value="1" id="paypal_enabled" <?= old('paypal_enabled',$settings->paypal_enabled)?'checked':'' ?>><label class="form-check-label fw-bold" for="paypal_enabled">Enable PayPal</label></div><div class="form-check form-switch"><input type="hidden" name="paypal_sandbox" value="0"><input class="form-check-input" type="checkbox" name="paypal_sandbox" value="1" id="paypal_sandbox" <?= old('paypal_sandbox',$settings->paypal_sandbox)?'checked':'' ?>><label class="form-check-label" for="paypal_sandbox">Sandbox mode</label></div></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Client ID</label><input type="password" class="form-control" name="paypal_client_id" autocomplete="new-password" placeholder="<?= $credentialStatus['paypal']?'Configured — leave blank to keep':'PayPal client ID' ?>"></div><div class="col-md-6 mb-3"><label class="form-label">Client secret</label><input type="password" class="form-control" name="paypal_client_secret" autocomplete="new-password" placeholder="<?= $credentialStatus['paypal']?'Configured — leave blank to keep':'PayPal client secret' ?>"></div></div>
        </div>
        <button class="btn btn-success">Save Payment Settings</button>
        <?= form_close() ?>
    </div></div>
</div>
