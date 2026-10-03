<?php 
use Config\MyConstants;

// App settings
$app_logo    = esc(setting('application', 'logo', 'default_logo.png'));
$app_favicon = esc(setting('application', 'favicon'));
$app_name    = esc(setting('application', 'app_name'));
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">

            <div class="text-center mb-4">
                <img src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" 
                     alt="Logo"
                     class="img-fluid mb-3"
                     style="max-height:80px;">

                <h2 class="fw-bold">Forgot Password?</h2>
                <p class="text-muted">
                    Enter your email address and we'll send you a password reset link.
                </p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?= get_system_message(); ?>

                    <form action="<?= site_url('forgot-password') ?>" method="post">

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Email Address
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>

                                <input type="email"
                                       name="email"
                                       class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>"
                                       value="<?= old('email') ?>"
                                       placeholder="Enter your registered email"
                                       required>

                                <?php if (session('errors.email')) : ?>
                                    <div class="invalid-feedback">
                                        <?= session('errors.email') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                    class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-1"></i>
                                Send Reset Link
                            </button>
                        </div>

                    </form>

                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= site_url('login') ?>"
                   class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i>
                    Back to Login
                </a>
            </div>

            
        </div>
    </div>
</div>
