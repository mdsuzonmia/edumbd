<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Security Error</title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?= base_url('assets/vendors/bootstrap/dist/css/bootstrap.min.css'); ?>">
    
    <!-- Font Awesome -->
    <link href="<?= base_url('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?= base_url('assets/vendors/nprogress/nprogress.css'); ?>" rel="stylesheet">

    <!-- Animate.css -->
    <link href="<?= base_url('assets/vendors/animate.css/animate.min.css'); ?>" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom styling plus plugins -->
    <link href="<?= base_url('assets/css/custom.min.css'); ?>" rel="stylesheet">
    
    <style>
        .error-number {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0;
        }
        .error-details {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container body">
<div class="main_container">
    <!-- page content -->
    <div class="col-md-12">
        <div class="col-middle">
            <div class="text-center py-5">
                <i class="bi bi-shield-lock text-danger" style="font-size: 80px;"></i>
                <h1 class="error-number text-danger mt-3">403</h1>
                <h2 class="text-danger mt-3">Security Error</h2>
                <p class="lead text-muted mt-3">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <?php if (ENVIRONMENT !== 'production') : ?>
                        <?= nl2br(esc($message)) ?>
                    <?php else : ?>
                        A security error occurred. Please try again or contact your school administrator.
                    <?php endif; ?>
                </p>
                <div class="mt-4">
                    <a href="javascript:history.back()" class="btn btn-warning btn-lg me-2">
                        <i class="bi bi-arrow-left"></i> Go Back
                    </a>
                    <a href="<?= base_url('login') ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Login
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