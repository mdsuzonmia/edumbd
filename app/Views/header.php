<?php 
use App\Models\ModuleModel;
use Config\MyConstants;

// Get module data
//$module_model = new ModuleModel();
//$module_data = $module_model->where('sidebar_menu', 1)->where('status', 1)->findAll();

// app_logo and favicon
$app_logo       = esc(setting('application', 'logo', 'default_logo.png'));
$app_favicon    = esc(setting('application', 'favicon'));

// app_name
$app_name = esc(setting('application', 'app_name'));

// Get user data from session
$photo      = session()->get('user_photo');

$page_title = isset($page_title) ? esc($page_title) : 'Edum - School Management System';
$body_class = isset($body_class) ? esc($body_class) : '';
$admin_area = isset($admin_area) ? esc($admin_area) : 'no';

if($photo){
  $photo_path = base_url('uploads/' . esc($photo));
}else{
  $photo_path = base_url('uploads/photo.png');
}

$userPermissions = session()->get('permissions') ?? [];

?>

<!DOCTYPE html>
<html lang="<?= service('language')->getLocale(); ?>">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('public/uploads/settings/' .$app_favicon); ?>">


    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Font Awesome 6 (latest) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="<?= base_url('assets/css/styles.css'); ?>" rel="stylesheet">

    <?php foreach (($page_styles ?? []) as $pageStyle): ?>
      <link href="<?= base_url(esc($pageStyle)); ?>" rel="stylesheet">
    <?php endforeach; ?>

    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
    
    <!-- Custom Theme Scripts -->
    <script src="<?= base_url('assets/js/scripts.js'); ?>"></script>

  </head>
<body class="<?= $body_class; ?>">
    
      <?php if($admin_area =='yes' && session()->get('logged_in')): ?>
      <div id="page-dashboard" >
          <!-- Sidebar -->
          <?php include 'sidebar.php'; ?>
          <!-- /Sidebar -->

         
        

        <div class="content-wrap">

          <!-- Header -->
          <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm  mb-4">
            <div class="container-fluid px-3">
              <button class="btn btn-outline-primary d-lg-none me-2" id="mobileOpenBtn" onclick="showSidebar()"><i class="bi bi-list"></i></button>
              <p class="mb-0 "><?= $app_name; ?> </p>

              <div class="d-flex align-items-center ms-auto">
                <?php $currentLocale = service('language')->getLocale(); ?>
                <div class="d-flex align-items-center me-3 small" aria-label="<?= esc(lang('Common.language')) ?>">
                  <i class="bi bi-translate me-2" aria-hidden="true"></i>
                  <a href="<?= base_url('language/en') ?>" class="text-decoration-none <?= $currentLocale === 'en' ? 'fw-bold text-primary' : 'text-secondary' ?>">English</a>
                  <span class="mx-2 text-muted">|</span>
                  <a href="<?= base_url('language/bn') ?>" class="text-decoration-none <?= $currentLocale === 'bn' ? 'fw-bold text-primary' : 'text-secondary' ?>">বাংলা</a>
                </div>
                
                <div class="dropdown">
                  <a class="d-flex align-items-center text-decoration-none" href="#" id="userDrop" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= $photo_path; ?>" alt="<?= session()->get('user_name') ?>" width="32px" height="32px" class="rounded-circle me-2">
                    <strong><?= session()->get('user_name') ?></strong>
                  <i class="fa fa-caret-down ms-2"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDrop">
                    <li><a class="dropdown-item" href="<?= base_url('auth/my-profile') ?>"><?= lang('Auth.btn_my_profile'); ?></a></li>
                    <li><a class="dropdown-item"  href="<?= base_url('auth/edit-profile') ?>"><i class="fa fa-edit "></i> <?= lang('Auth.btn_edit_profile'); ?></a></li>
                    <li><a class="dropdown-item"  href="<?= base_url('auth/change-password') ?>"><i class="fa fa-lock "></i> <?= lang('Auth.btn_change_password'); ?></a></li>
                    <?php if (session('role') === 'school-owner'): ?><li><a class="dropdown-item" href="<?= base_url('school-owner/billing') ?>"><i class="fa fa-credit-card"></i> <?= lang('Auth.billing') ?></a></li><?php endif; ?>
                    <li><a class="dropdown-item"  href="<?= base_url('logout') ?>"><i class="fa fa-sign-out "></i> <?= lang('Auth.btn_logout'); ?></a></li>
                  </ul>
                </div>
              </div>
            </div>
          </nav>

          <div class=" main-content ps-4 pe-4 mb-3">

    <?php if (session('role') === 'super-admin'): ?>
      <?php $subscription_data = calculateSubscriptionPercent(); echo $subscription_data['html'] ?? ''; ?>
    <?php endif; ?>



        <script>
   
    // Mobile helpers
    function showSidebar(){ document.getElementById('appSidebar').classList.add('show'); }
    function toggleSidebarMobile(){ document.getElementById('appSidebar').classList.toggle('show'); }

    // Accessibility: close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e){
      const sidebar = document.getElementById('appSidebar');
      if(window.innerWidth < 992 && sidebar.classList.contains('show')){
        if(!sidebar.contains(e.target) && !document.getElementById('mobileOpenBtn').contains(e.target)){
          sidebar.classList.remove('show');
        }
      }
    });
  </script>

  <script>
  function toggleSubmenu(id, link){
    const submenu = document.getElementById(id);
    const chevron = link.querySelector('.chevron');

    if(submenu.style.maxHeight){
      submenu.style.maxHeight = null;
      chevron.style.transform = 'rotate(0deg)';
      link.classList.remove('active');
    } else {
      //submenu.style.maxHeight = submenu.scrollHeight + "px";
      chevron.style.transform = 'rotate(180deg)';
      link.classList.add('active');
    }
  }

  // Auto-activate parent if a submenu is active
  document.addEventListener("DOMContentLoaded", () => {
    const activeLink = document.querySelector(".submenu .nav-link.active");
    if(activeLink){
      const submenu = activeLink.closest(".submenu");
      const parentLink = submenu.previousElementSibling;
      //submenu.style.maxHeight = submenu.scrollHeight + "px"; // keep open
      parentLink.classList.add("active"); // highlight parent
      const chevron = parentLink.querySelector(".chevron");
      if(chevron){ chevron.style.transform = 'rotate(180deg)'; }
    }
  });
</script>
      <?php endif; ?>
