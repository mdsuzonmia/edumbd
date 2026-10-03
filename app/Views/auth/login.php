<?php 
use Config\MyConstants;

// App settings
$app_logo    = esc(setting('application', 'logo', 'default_logo.png'));
$app_favicon = esc(setting('application', 'favicon'));
$app_name    = esc(setting('application', 'app_name'));
?>

<style>
:root{
    --primary-color:#416499;
    --secondary-color:#416499;
    --dark-color:#111827;
    --light-color:#f9fafb;
}

body{
    background: linear-gradient(135deg,#eef2ff 0%,#f8fafc 100%);
    min-height:100vh;
    font-family: 'Inter', sans-serif;
}

.auth-wrapper{
    min-height:100vh;
    display:flex;
    align-items:center;
    padding:40px 0;
}

.auth-card{
    border:none;
    border-radius:24px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 20px 60px rgba(15,23,42,0.12);
}

.auth-left{
    background: linear-gradient(135deg,var(--primary-color),var(--secondary-color));
    color:#fff;
    padding:60px 45px;
    position:relative;
}

.auth-left::before{
    content:'';
    position:absolute;
    top:-80px;
    right:-80px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,0.08);
    border-radius:50%;
}

.auth-left::after{
    content:'';
    position:absolute;
    bottom:-100px;
    left:-100px;
    width:260px;
    height:260px;
    background:rgba(255,255,255,0.05);
    border-radius:50%;
}

.auth-logo{
    width:80%;
    margin-bottom:20px;
}

.auth-brand{
    font-size:20px;
    font-weight:700;
    margin-bottom:10px;
}

.auth-text{
    opacity:.9;
    line-height:1.7;
    font-size:15px;
}

.auth-feature{
    margin-top:35px;
}

.auth-feature-item{
    display:flex;
    align-items:center;
    margin-bottom:18px;
    font-size:14px;
}

.auth-feature-item i{
    width:34px;
    height:34px;
    border-radius:10px;
    background:rgba(255,255,255,.15);
    display:flex;
    align-items:center;
    justify-content:center;
    margin-right:12px;
}

.auth-right{
    padding:55px 45px;
}

.auth-title{
    font-size:30px;
    font-weight:700;
    color:var(--dark-color);
}

.auth-subtitle{
    color:#6b7280;
    margin-bottom:30px;
}

.form-label{
    font-weight:600;
    margin-bottom:8px;
    color:#374151;
}

.form-control{
    height:52px;
    border-radius:14px;
    border:1px solid #d1d5db;
    padding:12px 16px;
    transition:.3s;
}

.form-control:focus{
    border-color:var(--primary-color);
    box-shadow:0 0 0 4px rgba(79,70,229,.12);
}

.input-group-text{
    border-radius:14px 0 0 14px;
}

.btn-auth{
    height:52px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,var(--primary-color),var(--secondary-color));
    font-weight:600;
    font-size:15px;
    transition:.3s;
}

.btn-auth:hover{
    transform:translateY(-1px);
    box-shadow:0 12px 24px rgba(79,70,229,.25);
}

.auth-footer{
    margin-top:30px;
    text-align:center;
    color:#6b7280;
    font-size:14px;
}

.auth-footer a{
    color:var(--primary-color);
    text-decoration:none;
    font-weight:600;
}

.version-text{
    margin-top:20px;
    font-size:12px;
    color:#9ca3af;
    text-align:center;
}

@media(max-width:991px){
    .auth-left{
        display:none;
    }

    .auth-right{
        padding:40px 25px;
    }
}
</style>

<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11">

                <div class="card auth-card">
                    <div class="row g-0">

                        <!-- Left Side -->
                        <div class="col-lg-5">
                            <div class="auth-left h-100 d-flex flex-column justify-content-center">

                                <div>
                                    <img 
                                        src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" 
                                        alt="Logo"
                                        class="auth-logo"
                                    >

                                    <h2 class="auth-brand"><?= $app_name; ?></h2>

                                    <p class="auth-text">
                                        স্কুল, শিক্ষার্থী ও পরীক্ষার কাজ সহজে পরিচালনা করুন।
                                    </p>
                                </div>

                                <div class="auth-feature">

                                    <div class="auth-feature-item">
                                        <i class="bi bi-mortarboard"></i>
                                        সহজ শিক্ষার্থী ব্যবস্থাপনা
                                    </div>

                                    <div class="auth-feature-item">
                                        <i class="bi bi-bar-chart"></i>
                                        দ্রুত রেজাল্ট ও রিপোর্ট
                                    </div>

                                    <div class="auth-feature-item">
                                        <i class="bi bi-cloud-check"></i>
                                        নিরাপদ অনলাইন সিস্টেম
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- Right Side -->
                        <div class="col-lg-7">
                            <div class="auth-right">

                                <h3 class="auth-title">
                                    Welcome Back 👋
                                </h3>

                                <p class="auth-subtitle">
                                    ড্যাশবোর্ডে যেতে লগইন করুন
                                </p>

                                <?= get_system_message(); ?>

                                <?= form_open('login', [
                                    'class'  => 'login-form',
                                    'id'     => 'login_form',
                                    'method' => 'post'
                                ]) ?>

                                    <!-- Email -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            মোবাইল নম্বর
                                        </label>

                                        <input 
                                            type="tel"
                                            inputmode="numeric"
                                            name="mobile"
                                            class="form-control"
                                            placeholder="01XXXXXXXXX"
                                            required
                                        >
                                    </div>

                                    <!-- Password -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            পাসওয়ার্ড
                                        </label>

                                        <input 
                                            type="password"
                                            name="password"
                                            class="form-control"
                                            placeholder="আপনার পাসওয়ার্ড"
                                            required
                                        >
                                    </div>

                                    <!-- Remember -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">

                                        <div class="form-check">
                                            <input 
                                                class="form-check-input"
                                                type="checkbox"
                                                name="remember"
                                                id="rememberCheck"
                                            >

                                            <label class="form-check-label" for="rememberCheck">
                                                মনে রাখুন
                                            </label>
                                        </div>

                                        <a href="<?= base_url('forgot-password'); ?>" class="text-decoration-none small">
                                            পাসওয়ার্ড ভুলে গেছেন?
                                        </a>

                                    </div>

                                    <!-- Button -->
                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-auth">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>
                                            লগইন করুন
                                        </button>
                                    </div>

                                <?= form_close(); ?>

                                <div class="auth-footer">
                                    Don’t have an account?
                                    <a href="<?= base_url('registration'); ?>">
                                        নতুন অ্যাকাউন্ট খুলুন
                                    </a>
                                </div>

                                <div class="version-text">
                                    <?= MyConstants::SITE_NAME ?> v<?= MyConstants::APP_VERSION ?>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
