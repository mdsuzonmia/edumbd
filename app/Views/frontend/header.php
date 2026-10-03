<?php
$app_name = esc(setting('application', 'app_name', 'Edum'));
$app_logo = esc(setting('application', 'logo', 'default_logo.png'));
$app_favicon    = esc(setting('application', 'favicon'));
$page_title = isset($page_title) ? esc($page_title) : 'Edum - School Management System';
$body_class = isset($body_class) ? esc($body_class) : '';
?>

<!DOCTYPE html>
<html lang="<?= service('language')->getLocale(); ?>" >
<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('public/uploads/settings/' .$app_favicon); ?>">

    <meta name="description"
          content="Learn how EduMark makes student result generation simple. Add students, enter marks, calculate GPA and generate professional result sheets.">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">


    <style>

        :root {
            --edum-primary: #416499;
            --edum-primary-dark: #304d78;
            --edum-light: #f5f8fc;
            --edum-text: #25344d;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: var(--edum-text);
        }

        .navbar {
            box-shadow: 0 2px 15px rgba(0,0,0,.05);
        }

        .navbar-brand {
            font-size: 25px;
            font-weight: 700;
            color: var(--edum-primary) !important;
        }

        .btn-edum {
            background: var(--edum-primary);
            color: #fff;
            border-radius: 8px;
            padding: 11px 22px;
            font-weight: 600;
        }

        .btn-edum:hover {
            background: var(--edum-primary-dark);
            color: #fff;
        }

        .btn-outline-edum {
            border: 1px solid var(--edum-primary);
            color: var(--edum-primary);
            border-radius: 8px;
            padding: 10px 22px;
            font-weight: 600;
        }

        .btn-outline-edum:hover {
            background: var(--edum-primary);
            color: #fff;
        }

        /* Hero */

        .hero {
            background: linear-gradient(
                135deg,
                #f4f7fb,
                #ffffff
            );

            padding: 100px 0;
        }

        .hero h1 {
            font-size: clamp(40px, 5vw, 60px);
            font-weight: 800;
            line-height: 1.1;
        }

        .hero h1 span {
            color: var(--edum-primary);
        }

        .hero p {
            font-size: 18px;
            line-height: 1.8;
            color: #667085;
            max-width: 700px;
        }

        .hero-badge {
            display: inline-block;
            background: #e8eef7;
            color: var(--edum-primary);
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Sections */

        .section {
            padding: 90px 0;
        }

        .section-light {
            background: var(--edum-light);
        }

        .section-title {
            font-size: 38px;
            font-weight: 800;
        }

        .section-subtitle {
            max-width: 700px;
            color: #6b7280;
            line-height: 1.8;
        }

        /* Process */

        .process-card {
            background: #fff;
            border: 1px solid #e6ebf2;
            border-radius: 18px;
            padding: 35px 30px;
            height: 100%;
            position: relative;
            transition: .3s;
        }

        .process-card:hover {
            transform: translateY(-7px);
            box-shadow:
                0 20px 40px rgba(44, 63, 94, .10);
        }

        .step-number {
            position: absolute;
            top: 20px;
            right: 20px;

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #eaf0f8;
            color: var(--edum-primary);

            font-weight: 800;
        }

        .process-icon {
            width: 70px;
            height: 70px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 16px;

            background: #eaf0f8;
            color: var(--edum-primary);

            font-size: 32px;

            margin-bottom: 25px;
        }

        .process-card h4 {
            font-weight: 700;
            margin-bottom: 12px;
        }

        .process-card p {
            color: #6b7280;
            line-height: 1.7;
        }

        /* Timeline */

        .timeline {
            position: relative;
            max-width: 900px;
            margin: auto;
        }

        .timeline::before {
            content: "";
            position: absolute;
            left: 35px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dce4ef;
        }

        .timeline-item {
            position: relative;
            display: flex;
            gap: 30px;
            margin-bottom: 45px;
        }

        .timeline-icon {
            flex: 0 0 70px;
            width: 70px;
            height: 70px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--edum-primary);
            color: #fff;

            font-size: 25px;

            position: relative;
            z-index: 2;
        }

        .timeline-content {
            background: #fff;
            border: 1px solid #e7ebf2;
            border-radius: 15px;
            padding: 25px;
            flex-grow: 1;
        }

        .timeline-content h5 {
            font-weight: 700;
        }

        .timeline-content p {
            color: #6b7280;
            margin-bottom: 0;
            line-height: 1.7;
        }

        /* Example */

        .example-box {
            background: #fff;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(45, 67, 100, .08);
        }

        .marks-box {
            border: 1px solid #e4e8ef;
            border-radius: 10px;
            padding: 15px;
        }

        .calculation-box {
            background: #f4f7fb;
            border-radius: 10px;
            padding: 20px;
        }

        .result-box {
            border: 2px solid var(--edum-primary);
            border-radius: 10px;
            padding: 20px;
        }

        /* CTA */

        .cta {
            background: var(--edum-primary);
            color: #fff;
            padding: 80px 0;
        }

        .cta h2 {
            font-size: 40px;
            font-weight: 800;
        }

        .cta p {
            color: rgba(255,255,255,.8);
            font-size: 17px;
        }

        .btn-white {
            background: #fff;
            color: var(--edum-primary);
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 700;
        }

        .btn-white:hover {
            background: #f1f4f8;
            color: var(--edum-primary-dark);
        }

        footer {
            background: #172337;
            color: rgba(255,255,255,.65);
            padding: 30px 0;
        }

        footer a {
            color: rgba(255,255,255,.7);
            text-decoration: none;
        }

        footer a:hover {
            color: #fff;
        }

        @media (max-width: 767px) {

            .timeline::before {
                display: none;
            }

            .timeline-item {
                gap: 15px;
            }

            .timeline-icon {
                flex: 0 0 55px;
                width: 55px;
                height: 55px;
            }

        }

    </style>

</head>

<body class="<?= $body_class; ?>">






<style>
:root {
    --primary-color: #416499;
    --secondary-color: #416499;
    --dark-color: #111827;
}

body {
    background: #ffffff;
    font-family: 'Inter', sans-serif;
}

/* Top Navigation */
.top-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.top-nav.scrolled {
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.nav-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.nav-brand img {
    max-height: 50px;
    width: auto;
}

.nav-brand-text {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-left: 12px;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 32px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-menu a {
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: color 0.3s ease;
    position: relative;
}

.nav-menu a:hover {
    color: var(--primary-color);
}

.nav-menu a::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: width 0.3s ease;
}

.nav-menu a:hover::after {
    width: 100%;
}

.nav-buttons {
    display: flex;
    gap: 12px;
}

.nav-btn {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
}

.nav-btn-primary:hover {
    background: #365a8a;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(65, 100, 153, 0.25);
}

.nav-btn-secondary {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.nav-btn-secondary:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    color: var(--dark-color);
    cursor: pointer;
}

@media (max-width: 768px) {
    .nav-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        gap: 16px;
    }

    .nav-menu.active {
        display: flex;
    }

    .nav-buttons {
        display: none;
    }

    .mobile-menu-toggle {
        display: block;
    }

    .nav-brand-text {
        display: none;
    }
}


/* Footer */
.footer-section {
    background: var(--dark-color);
    color: white;
    padding: 60px 0 30px;
}

.footer-brand {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 16px;
}

.footer-text {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 24px;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: white;
}

.copyright {
    text-align: center;
    padding-top: 30px;
    margin-top: 40px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.6);
}

</style>

<nav class="top-nav">
  <div class="nav-container">
    <a href="<?= base_url('/'); ?>" class="nav-brand">
      <img src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" alt="<?= $app_name; ?>" onerror="this.style.display='none'">
    </a>
    <ul class="nav-menu">
      <li><a href="<?= base_url('features'); ?>">Features</a></li>
      <li><a href="<?= base_url('how-it-works'); ?>">How It Works</a></li>
      <li><a href="<?= base_url('pricing'); ?>">Pricing</a></li>
      <li><a href="<?= base_url('docs'); ?>" class="active">Docs</a></li>
    </ul>
    <div>
      <a href="<?= base_url('login'); ?>" class="nav-btn nav-btn-secondary">Sign In</a>
      <a href="<?= base_url('registration'); ?>" class="nav-btn nav-btn-primary">Get Started</a>
    </div>
  </div>
</nav>