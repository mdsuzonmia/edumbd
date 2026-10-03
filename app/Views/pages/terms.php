<?php
$app_name = esc(setting('application', 'app_name', 'eDum'));
$updated  = date('F j, Y');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header brand-header bg-transparent border-0">
                    <div class="text-center py-3">
                        <h2 class="fw-bold mb-1">Terms &amp; Conditions</h2>
                        <p class="text-muted mb-0">Last updated: <?= esc($updated) ?></p>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">

                    <p class="text-muted">
                        Welcome to <?= esc($app_name) ?>. By accessing or using our platform (the
                        &quot;Service&quot;), you agree to be bound by these Terms &amp; Conditions.
                        Please read them carefully before creating a school account.
                    </p>

                    <h5 class="fw-bold mt-4">1. Acceptance of Terms</h5>
                    <p>
                        By creating an account, registering a school, or otherwise using the Service,
                        you acknowledge that you have read, understood, and agree to be bound by these
                        Terms, our Privacy Policy, and all applicable laws and regulations.
                    </p>

                    <h5 class="fw-bold mt-4">2. Use of the Service</h5>
                    <p>
                        You agree to use the Service only for lawful purposes and in accordance with
                        these Terms. You are responsible for maintaining the confidentiality of your
                        account credentials and for all activities that occur under your account.
                    </p>

                    <h5 class="fw-bold mt-4">3. School Data &amp; Content</h5>
                    <p>
                        You retain ownership of the data and content you submit to the Service. You
                        grant us a limited license to host, process, and store such content solely to
                        provide and improve the Service.
                    </p>

                    <h5 class="fw-bold mt-4">4. Subscriptions &amp; Billing</h5>
                    <p>
                        Certain features are provided on a subscription basis. You agree to pay all
                        fees associated with your chosen plan. Subscription fees may be rounded to the
                        nearest whole number at the time of billing. Fees are non-refundable except as
                        required by applicable law.
                    </p>

                    <h5 class="fw-bold mt-4">5. Acceptable Use</h5>
                    <p>
                        You agree not to misuse the Service, including, without limitation: attempting
                        to gain unauthorized access, transmitting malicious code, violating the rights
                        of others, or using the Service in a way that violates applicable law.
                    </p>

                    <h5 class="fw-bold mt-4">6. Intellectual Property</h5>
                    <p>
                        The Service, including its software, design, logos, and documentation, is owned
                        by <?= esc($app_name) ?> and protected by intellectual property laws. You may
                        not copy, modify, or distribute it without our prior written consent.
                    </p>

                    <h5 class="fw-bold mt-4">7. Disclaimer of Warranties</h5>
                    <p>
                        The Service is provided on an &quot;as is&quot; and &quot;as available&quot;
                        basis without warranties of any kind, whether express or implied. We do not
                        warrant that the Service will be uninterrupted, secure, or error-free.
                    </p>

                    <h5 class="fw-bold mt-4">8. Limitation of Liability</h5>
                    <p>
                        To the maximum extent permitted by law, <?= esc($app_name) ?> shall not be
                        liable for any indirect, incidental, special, consequential, or punitive
                        damages arising out of or related to your use of the Service.
                    </p>

                    <h5 class="fw-bold mt-4">9. Termination</h5>
                    <p>
                        We may suspend or terminate your access to the Service if you breach these
                        Terms. You may stop using the Service at any time by closing your account.
                    </p>

                    <h5 class="fw-bold mt-4">10. Changes to These Terms</h5>
                    <p>
                        We may update these Terms from time to time. We will notify you of material
                        changes by posting the updated Terms on this page with a new effective date.
                    </p>

                    <h5 class="fw-bold mt-4">11. Contact Us</h5>
                    <p>
                        If you have any questions about these Terms, please contact our support team.
                    </p>

                    <a href="<?= base_url('registration') ?>" class="btn btn-primary">
                        Back to Registration
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>
