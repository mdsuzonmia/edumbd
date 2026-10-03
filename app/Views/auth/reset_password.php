<?php
// App settings
$app_logo    = esc(setting('application', 'logo', 'default_logo.png'));
$app_favicon = esc(setting('application', 'favicon'));
$app_name    = esc(setting('application', 'app_name'));
$token = isset($token) ? $token : '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="text-center mb-4">
                <div class="text-center mb-4">
                    <img src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" 
                        alt="Logo"
                        class="img-fluid mb-3"
                        style="max-height:80px;">
                </div>
                <h2 class="fw-bold">Reset Your Password</h2>
                <p class="text-muted">
                    Enter a new password below to restore access to your account.
                </p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?= get_system_message(); ?>

                    <form action="<?= site_url('reset-password') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= esc($token) ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password"
                                       name="password"
                                       class="form-control <?= session('errors.password') ? 'is-invalid' : '' ?>"
                                       placeholder="Enter your new password"
                                       required>
                                <?php if (session('errors.password')) : ?>
                                    <div class="invalid-feedback">
                                        <?= session('errors.password') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password"
                                       name="confirm_password"
                                       class="form-control <?= session('errors.confirm_password') ? 'is-invalid' : '' ?>"
                                       placeholder="Repeat your new password"
                                       required>
                                <?php if (session('errors.confirm_password')) : ?>
                                    <div class="invalid-feedback">
                                        <?= session('errors.confirm_password') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= site_url('login') ?>" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
