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
                        <h2 class="fw-bold mb-1">Privacy Policy</h2>
                        <p class="text-muted mb-0">Last updated: <?= esc($updated) ?></p>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">

                    <p class="text-muted">
                        This Privacy Policy explains how <?= esc($app_name) ?> collects, uses, and
                        protects your information when you use our platform (the &quot;Service&quot;).
                    </p>

                    <h5 class="fw-bold mt-4">1. Information We Collect</h5>
                    <p>
                        We collect information you provide directly, such as your name, email address,
                        phone number, school details, and payment information. We also collect certain
                        technical information automatically, such as your IP address, browser type, and
                        usage data.
                    </p>

                    <h5 class="fw-bold mt-4">2. How We Use Your Information</h5>
                    <p>
                        We use your information to provide, maintain, and improve the Service; to
                        process transactions; to communicate with you; and to personalize your
                        experience. We do not sell your personal information to third parties.
                    </p>

                    <h5 class="fw-bold mt-4">3. Legal Basis for Processing</h5>
                    <p>
                        We process personal data based on your consent, the performance of a contract,
                        compliance with legal obligations, and our legitimate interests in operating
                        and securing the Service.
                    </p>

                    <h5 class="fw-bold mt-4">4. Data Sharing</h5>
                    <p>
                        We may share your information with trusted service providers who help us operate
                        the Service (such as payment processors and hosting providers), only to the
                        extent necessary, and we require them to keep your information confidential.
                    </p>

                    <h5 class="fw-bold mt-4">5. Data Security</h5>
                    <p>
                        We implement appropriate technical and organizational measures to protect your
                        information against unauthorized access, alteration, disclosure, or destruction.
                        However, no method of transmission over the internet is completely secure.
                    </p>

                    <h5 class="fw-bold mt-4">6. Data Retention</h5>
                    <p>
                        We retain your personal information only as long as necessary to fulfill the
                        purposes described in this Policy and to comply with legal obligations.
                    </p>

                    <h5 class="fw-bold mt-4">7. Your Rights</h5>
                    <p>
                        Depending on your jurisdiction, you may have the right to access, correct,
                        delete, or restrict the processing of your personal information, as well as the
                        right to data portability and to withdraw consent at any time.
                    </p>

                    <h5 class="fw-bold mt-4">8. Cookies</h5>
                    <p>
                        We may use cookies and similar technologies to enhance your experience, analyze
                        usage, and remember your preferences. You can control cookies through your
                        browser settings.
                    </p>

                    <h5 class="fw-bold mt-4">9. Children&apos;s Privacy</h5>
                    <p>
                        The Service is not intended for children under the age of 13, and we do not
                        knowingly collect personal information from children.
                    </p>

                    <h5 class="fw-bold mt-4">10. Changes to This Policy</h5>
                    <p>
                        We may update this Privacy Policy from time to time. Changes will be posted on
                        this page with a new effective date.
                    </p>

                    <h5 class="fw-bold mt-4">11. Contact Us</h5>
                    <p>
                        If you have any questions about this Privacy Policy or how we handle your data,
                        please contact our support team.
                    </p>

                    <a href="<?= base_url('registration') ?>" class="btn btn-primary">
                        Back to Registration
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>
