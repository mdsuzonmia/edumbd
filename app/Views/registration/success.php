<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Registration Successful</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <style>

        body{
            background: linear-gradient(135deg,#f5f9ff,#eef4ff);
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family: Arial, sans-serif;
        }

        .success-card{
            
            width:100%;
            border:none;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 15px 40px rgba(0,0,0,0.08);
            background:#fff;
        }

        .success-header{
            background:linear-gradient(135deg,#198754,#20c997);
            padding:50px 30px;
            text-align:center;
            color:#fff;
        }

        .success-icon{
            width:110px;
            height:110px;
            background:rgba(255,255,255,0.15);
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 25px;
            font-size:55px;
        }

        .success-body{
            padding:45px;
        }

        .feature-box{
            border:1px solid #edf1f7;
            border-radius:18px;
            padding:18px;
            transition:all 0.2s ease;
            height:100%;
            background:#fff;
        }

        .feature-box:hover{
            transform:translateY(-4px);
            box-shadow:0 10px 25px rgba(0,0,0,0.05);
        }

        .feature-icon{
            width:55px;
            height:55px;
            border-radius:14px;
            background:#eef5ff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:24px;
            margin-bottom:14px;
            color:#0d6efd;
        }

        .login-btn{
            padding:14px 28px;
            border-radius:14px;
            font-size:18px;
            font-weight:600;
        }

        .small-note{
            font-size:14px;
            color:#6c757d;
        }

    </style>

</head>
<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-12">

            <div class="card success-card">

                <!-- HEADER -->
                <div class="success-header">

                    <div class="success-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>

                    <h1 class="fw-bold mb-3">
                        Registration Successful 🎉
                    </h1>

                    <p class="mb-0 fs-5">
                        Your school <b><?php echo $data->school_name; ?></b> account has been created successfully.
                    </p>

                </div>

                <!-- BODY -->
                <div class="success-body">

                    <?php 
                    if($data->payment_status == 'paid'):
                    ?>
                    <div class="alert alert-success border-0 rounded-4">

                        <div class="d-flex align-items-start">

                            <div class="me-3 fs-2">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div>

                                <h5 class="fw-bold mb-2">
                                    Payment Confirmed
                                </h5>

                                <p class="mb-0">
                                    Your subscription is now active and your school portal is ready to use.
                                </p>

                            </div>

                        </div>

                    </div>
                    <?php 
                    elseif($data->payment_status == 'pending'):
                    ?>
                    <div class="alert alert-danger border-0 rounded-4">

                        <div class="d-flex align-items-start">

                            <div class="me-3 fs-2">
                                <i class="bi bi-x-octagon"></i>
                            </div>

                            <div>

                                <h5 class="fw-bold mb-2">
                                    Payment Pending
                                </h5>

                                <p class="mb-2 ">
                                    Your plan upgrade was saved, but it will not become active until payment is confirmed. It can take a few minutes for the payment to be processed. Please check your email for payment confirmation and try again after a while. If you have already made the payment, please click the button below to verify your payment manually.
                                </p>

                                <?php 
                                $tempToken = $data->temp_token;
                                ?>

                                <a href="<?= base_url('registration-success/'.$tempToken) ?>"
                                   class="btn btn-primary">
                                    Verify Payment
                                </a>

                                

                            </div>

                        </div>

                    </div>
                    <?php 
                    endif;

                    if($data->payment_status == 'trial'):
                    ?>
                    <div class="alert alert-info border-0 rounded-4">

                        <div class="d-flex align-items-start">

                            <div class="me-3 fs-2">
                                <i class="bi bi-hourglass-split"></i>
                            </div>

                            <div>

                                <h5 class="fw-bold mb-2">
                                    Trial Activated
                                </h5>

                                <p class="mb-0">
                                    Your trial subscription is now active and your school portal is ready to use.
                                </p>

                            </div>

                        </div>

                    </div>
                    <?php 
                    endif;
                    ?>

                    <!-- Email Verification Message -->
                    <div class="alert alert-info border-0 rounded-4">

                        <div class="d-flex align-items-start">

                            <div class="me-3 fs-2">
                                <i class="bi bi-envelope"></i>
                            </div>

                            <div>

                                <h5 class="fw-bold mb-2">
                                    Email Verification
                                </h5>

                                <p class="mb-0">
                                    A confirmation email has been sent to your registered email address. Please click the link in the email to verify your email address. If you have not received the email, please check your spam folder.
                                </p>

                            </div>

                        </div>

                    </div>

                    

                    <!-- LOGIN BUTTON -->
                    <div class="text-center">

                        <a href="<?= base_url('login') ?>"
                           class="btn btn-primary btn-lg login-btn">

                            <i class="bi bi-box-arrow-in-right me-2"></i>

                            Login to Dashboard

                        </a>

                    </div>

                    <!-- NOTE -->
                    <div class="text-center mt-4">

                        <p class="small-note mb-1">
                            A confirmation email has been sent to your registered email address.
                        </p>

                        <p class="small-note mb-0">
                            Need help? Contact support anytime.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>