<div class="row justify-content-center">

    <div class="col-lg-12">

        <?= get_system_message(); ?>

        <div class="">

            <!-- Header -->
            <div class=""
                 >

                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fa fa-shield-alt fa-3x"></i>
                    </div>

                    <div>
                        <h3 class="mb-1">
                            <?= lang('Auth.change_password'); ?>
                        </h3>

                        <p class="mb-0 opacity-75">
                            Keep your account secure with a strong password
                        </p>
                    </div>
                </div>

            </div>

            <div class="card-body p-4">

                <?= form_open('auth/update-password', [
                    'id' => 'user_change_passsword_form'
                ]); ?>

                <!-- Old Password -->
                <div class="mb-4">

                    <label class="form-label fw-bold">
                        <?= lang('Auth.old_password'); ?>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-lock"></i>
                        </span>

                        <input type="password"
                               name="old_password"
                               class="form-control"
                               required>

                        <button class="btn btn-outline-secondary toggle-password"
                                type="button">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                </div>

                <!-- New Password -->
                <div class="mb-4">

                    <label class="form-label fw-bold">
                        <?= lang('Auth.new_password'); ?>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-key"></i>
                        </span>

                        <input type="password"
                               name="new_password"
                               id="new_password"
                               class="form-control"
                               required>

                        <button class="btn btn-outline-secondary toggle-password"
                                type="button">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <small class="text-muted">
                        Minimum 8 characters, including uppercase, lowercase and number.
                    </small>

                </div>

                <!-- Confirm Password -->
                <div class="mb-4">

                    <label class="form-label fw-bold">
                        <?= lang('Auth.confirm_password'); ?>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-check-circle"></i>
                        </span>

                        <input type="password"
                               name="confirm_password"
                               class="form-control"
                               required>

                        <button class="btn btn-outline-secondary toggle-password"
                                type="button">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                </div>

                <!-- Security Notice -->
                <div class="alert alert-light border">

                    <h6 class="fw-bold mb-2">
                        <i class="fa fa-info-circle text-primary"></i>
                        Security Tips
                    </h6>

                    <ul class="mb-0">
                        <li>Use at least 8 characters.</li>
                        <li>Avoid using your name or email.</li>
                        <li>Include numbers and special characters.</li>
                        <li>Do not reuse old passwords.</li>
                    </ul>

                </div>

                <!-- Actions -->
                <div class="text-end mt-4">

                    <a href="<?= previous_url(); ?>"
                       class="btn btn-light border">
                        <i class="fa fa-arrow-left"></i>
                        Back
                    </a>

                    <button type="submit"
                            class="btn btn-success px-4">
                        <i class="fa fa-save"></i>
                        <?= lang('Auth.btn_change_password'); ?>
                    </button>

                </div>

                <?= form_close(); ?>

            </div>
        </div>

    </div>
</div>

<style>
    .card {
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.form-control,
.input-group-text {
    height: 48px;
}

.form-control:focus {
    border-color: #416499;
    box-shadow: 0 0 0 .2rem rgba(65,100,153,.15);
}

.btn-success {
    border-radius: 8px;
}

.input-group-text {
    background: #f8f9fa;
}
</style>

<script>
    $('.toggle-password').on('click', function () {

    let input = $(this).closest('.input-group').find('input');

    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    }

});
</script>