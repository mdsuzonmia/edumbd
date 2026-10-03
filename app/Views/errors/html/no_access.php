<?php 
$institution_name = esc(get_setting_value('institution_name'));
$logo = esc(get_setting_value('logo_light'));

if(session()->get('isLoggedIn')){
    $class = 'right_col';
}else{
    $class ='container-fluid';
}
?>
<div class="<?= $class ?> " role="main">

    <?php if(session()->get('isLoggedIn')){ ?>
    <div class="row">
        <div class="col-md-12 ">
        <?= get_system_message(); ?>
        </div>
    </div>
    <?php }else{ ?>
    <div class="row">
        <div class="col-md-12 text-center mt-5">
            <img src="<?= base_url('uploads/' .$logo); ?>" width="200px" height="30px" alt="Logo" />
            <h2><?= $institution_name ?></h2>
        </div>
    </div>
    <?php } ?>
    
    <div class="row">
        <div class="col-md-12 text-center py-5">
            <i class="bi bi-shield-exclamation text-danger" style="font-size: 80px;"></i>
            <h1 class="display-4 text-danger mt-4">403 - <?= lang('System.access_denied_ttle'); ?></h1>
            <p class="lead text-muted mt-3">
                <i class="bi bi-exclamation-triangle"></i> 
                <?= lang('System.access_denied_msg'); ?>
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