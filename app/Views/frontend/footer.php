<?php
use Config\MyConstants;
$app_logo    = esc(setting('application', 'logo', 'default_logo.png'));
$app_name    = esc(setting('application', 'app_name', 'Edum'));
$app_favicon = esc(setting('application', 'favicon'));
?>


<!-- Footer -->
<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-brand"><?= esc($app_name); ?></div>
                <p class="footer-text">
                    Empowering schools with modern management solutions. 
                    Simplify administration, enhance learning, and connect your school community.
                </p>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4">
                <h5 class="text-white mb-3">Product</h5>
                <ul class="footer-links">
                    <li><a href="<?= base_url('registration'); ?>">Sign Up</a></li>
                    <li><a href="<?= base_url('login'); ?>">Sign In</a></li>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Pricing</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4">
                <h5 class="text-white mb-3">Company</h5>
                <ul class="footer-links">
                    <li><a href="<?= base_url('terms'); ?>">Terms & Conditions</a></li>
                    <li><a href="<?= base_url('privacy-policy'); ?>">Privacy Policy</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4">
                <h5 class="text-white mb-3">Support</h5>
                <ul class="footer-links">
                    <li><a href="<?= base_url('docs'); ?>">Help Center</a></li>
                    <li><a href="<?= base_url('docs'); ?>">Documentation</a></li>
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Contact Support</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p class="mb-0">
                &copy; <?= date('Y'); ?> <?= esc($app_name); ?>. All rights reserved. | 
                Version <?= MyConstants::APP_VERSION ?>
            </p>
        </div>
    </div>
</footer>

</body>
</html>