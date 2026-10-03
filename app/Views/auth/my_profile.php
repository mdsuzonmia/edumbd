<!-- page content -->
<style type="text/css">
    .img-circle {
    border-radius: 50%;
}

.x_panel {
    border-radius: 10px;
    
}

.table th {
    background: #f8f9fa;
}

.btn {
    border-radius: 6px;
}

.label {
    font-size: 12px;
    padding: 6px 10px;
}

.alert {
    border-radius: 8px;
}
</style>
<div class="right_col" role="main">

    

    <?= get_system_message(); ?>

    <div class="row">

        <!-- Profile Card -->
        <div class="col-md-4 col-sm-12">
            <div class="x_panel text-center">

                <div class="x_content">

                    <?php
                    $photo = !empty($user->photo)
                        ? base_url('uploads/' . $user->photo)
                        : base_url('assets/images/default-user.png');
                    ?>

                    <img src="<?= $photo; ?>"
                         class="img-circle img-responsive center-block"
                         style="width:150px;height:150px;object-fit:cover;border:4px solid #e6e9ed;">

                    <h3 style="margin-top:15px;">
                        <?= esc($user->name); ?>
                    </h3>

                    <?php if (!empty($email_verification) && $email_verification->is_verified && $email_verification->verified_at):   ?>
                        <span class="label label-success">
                            <i class="fa fa-check-circle"></i>
                            <?= lang('Auth.verified') ?? 'Verified'; ?>
                        </span>
                    <?php else: ?>
                        <span class="label label-warning">
                            <i class="fa fa-exclamation-circle"></i>
                            <?= lang('Auth.unverified') ?? 'Unverified'; ?>
                        </span>
                    <?php endif; ?>

                    <hr>

                    <div class="text-left">
                        <p>
                            <i class="fa fa-envelope text-primary"></i>
                            <?= esc($user->email); ?>
                        </p>

                        <p>
                            <i class="fa fa-phone text-success"></i>
                            <?= esc($user->phone); ?>
                        </p>
                    </div>

                </div>
            </div>

            <!-- Email Verification -->
            <div class="x_panel">

                <div class="x_title">
                    <h2>
                        <i class="fa fa-envelope"></i>
                        <?= lang('Auth.email_verification') ?? 'Email Verification'; ?>
                    </h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">

                    <?php if (!empty($email_verification) && $email_verification->is_verified && $email_verification->verified_at): ?>

                        <div class="alert alert-success">
                            <h4>
                                <i class="fa fa-check-circle"></i>
                                <?= lang('Auth.verified') ?? 'Verified'; ?>
                            </h4>

                            <p>
                                <?= lang('Auth.verified_date') ?? 'Verified On'; ?>:
                                <strong>
                                    <?= date('M d, Y h:i A', strtotime($email_verification->verified_at)); ?>
                                </strong>
                            </p>
                        </div>

                    <?php else: ?>

                        <div class="alert alert-warning">

                            <h4>
                                <i class="fa fa-exclamation-triangle"></i>
                                <?= lang('Auth.unverified') ?? 'Unverified'; ?>
                            </h4>

                            <p>
                                <?= lang('Auth.verify_email_msg') ?? 'Please verify your email address to unlock full access to your account.'; ?>
                            </p>

                            <a href="<?= base_url('email/verification/send'); ?>"
                               class="btn btn-warning">
                                <i class="fa fa-paper-plane"></i>
                                <?= lang('Auth.resend_verification') ?? 'Resend Verification Email'; ?>
                            </a>

                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-md-8 col-sm-12">

            <div class="x_panel">

                <div class="x_title">
                    <h2>
                        <i class="fa fa-user"></i>
                        <?= lang('Auth.profile_information'); ?>
                    </h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">

                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <th width="200">
                                    <?= lang('Auth.name'); ?>
                                </th>
                                <td><?= esc($user->name); ?></td>
                            </tr>

                            <tr>
                                <th>
                                    <?= lang('Auth.email'); ?>
                                </th>
                                <td><?= esc($user->email); ?></td>
                            </tr>

                            <tr>
                                <th>
                                    <?= lang('Auth.phone'); ?>
                                </th>
                                <td><?= esc($user->phone); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-right">

                        <a href="<?= base_url('/auth/edit-profile') ?>"
                           class="btn btn-primary">
                            <i class="fa fa-edit"></i>
                            <?= lang('Auth.btn_edit_profile'); ?>
                        </a>

                        <a href="<?= base_url('/auth/change-password') ?>"
                           class="btn btn-dark">
                            <i class="fa fa-lock"></i>
                            <?= lang('Auth.btn_change_password'); ?>
                        </a>

                    </div>

                </div>
            </div>

            

        </div>
    </div>

</div>
<!-- /page content -->