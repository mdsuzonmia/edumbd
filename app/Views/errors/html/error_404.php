<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= lang('Errors.pageNotFound') ?> - <?= lang('Errors.pageNotFound') ?></title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?= base_url('assets/vendors/bootstrap/dist/css/bootstrap.min.css'); ?>">
    
    <!-- Font Awesome -->
    <link href="<?= base_url('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?= base_url('assets/vendors/nprogress/nprogress.css'); ?>" rel="stylesheet">

    <!-- Animate.css -->
    <link href="<?= base_url('assets/vendors/animate.css/animate.min.css'); ?>" rel="stylesheet">

    <!-- bootstrap-wysiwyg -->
    <link href="<?= base_url('assets/vendors/google-code-prettify/bin/prettify.min.css'); ?>" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom styling plus plugins -->
    <link href="<?= base_url('assets/css/custom.min.css'); ?>" rel="stylesheet">
    
</head>
<body>
<div class="container body">
<div class="main_container">
    <!-- page content -->
    <div class="col-md-12">
        <div class="col-middle">
            <div class="text-center py-5">
                <i class="bi bi-emoji-frown text-warning" style="font-size: 80px;"></i>
                <h1 class="display-4 text-warning mt-4">404 - <?= lang('Errors.pageNotFound') ?></h1>
                <h2 class="text-muted mt-3"><?= lang('Errors.sorryCannotFind') ?></h2>
                <p class="lead text-muted mt-3">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <?php if (ENVIRONMENT !== 'production') : ?>
                        <?= nl2br(esc($message)) ?>
                    <?php else : ?>
                        The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
                    <?php endif; ?>
                </p>
                <div class="mt-4">
                    <a href="<?= base_url('login') ?>" class="btn btn-primary btn-lg me-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <a href="<?= base_url('student/dashboard') ?>" class="btn btn-secondary btn-lg">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- /page content -->
</div>
</div>
</body>
</html>