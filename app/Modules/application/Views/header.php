<?php 

$schol_name = esc(get_setting_value('school_name'));
$logo = esc(get_setting_value('logo'));
$icon = esc(get_setting_value('icon'));
$photo = session()->get('photo');

if(!empty($photo)){
  $photo = $photo;
}else{
  $photo = 'avator.png';
}

$photo_path = base_url('uploads/' .$photo);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('uploads/' .$icon); ?>">

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

    <!-- Switchery -->
    <link href="<?= base_url('assets/vendors/switchery/dist/switchery.min.css'); ?>" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="<?= base_url('assets/css/custom.css'); ?>" rel="stylesheet">

    <!-- jQuery -->
    <script src="<?= base_url('assets/vendors/jquery/dist/jquery.min.js'); ?>"></script>
    <!-- Bootstrap -->
    <script src="<?= base_url('assets/vendors/bootstrap/dist/js/bootstrap.bundle.min.js'); ?>"></script>

    <!-- validator -->
    <script src="<?= base_url('assets/vendors/parsleyjs/dist/parsley.min.js'); ?>"></script>
    
  </head>
<body class="<?= $body_class; ?>">
    <div class="container body">
      <div class="main_container">

      <section class="application-header">
        <div class="container-public">
          <div class="row">
            <div class="col-sm-12 text-center">
              <img src="<?= base_url('uploads/' .$logo); ?>" width="200px" height="30px" alt="Logo" />
              <h1><?= lang('Student.application_form_title'); ?></h1>
            </div>
          </div>
          
        </div>
      </section>

      