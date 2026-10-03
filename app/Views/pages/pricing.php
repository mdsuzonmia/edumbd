<?php 
use Config\MyConstants;

$app_logo    = esc(setting('application', 'logo', 'default_logo.png'));
$app_name    = esc(setting('application', 'app_name', 'Edum'));
$app_favicon = esc(setting('application', 'favicon'));
?>

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
}

.nav-menu a:hover {
    color: var(--primary-color);
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
}

.nav-btn-secondary {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.nav-btn-secondary:hover {
    background: var(--primary-color);
    color: white;
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

/* Pricing Section */
.pricing-section {
    padding: 140px 0 100px;
    background: #ffffff;
}

.pricing-header {
    text-align: center;
    margin-bottom: 60px;
}

.pricing-title {
    font-size: 3rem;
    font-weight: 800;
    color: var(--dark-color);
    margin-bottom: 20px;
}

.pricing-subtitle {
    font-size: 1.25rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

.pricing-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    height: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.pricing-card.featured {
    border-color: var(--primary-color);
}

.badge {
    position: absolute;
    top: -15px;
    right: 20px;
    background: var(--primary-color);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.pricing-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.pricing-description {
    color: #6b7280;
    margin-bottom: 24px;
    font-size: 14px;
}

.pricing-price {
    margin-bottom: 30px;
}

.pricing-amount {
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--primary-color);
    line-height: 1;
}

.pricing-currency {
    font-size: 1.5rem;
    color: var(--primary-color);
    font-weight: 600;
}

.pricing-period {
    color: #6b7280;
    font-size: 14px;
}

.pricing-features {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
}

.pricing-features li {
    padding: 12px 0;
    color: #4b5563;
    display: flex;
    align-items: center;
}

.pricing-features li i {
    color: #10b981;
    margin-right: 12px;
    font-size: 18px;
}

.pricing-features li.disabled {
    color: #9ca3af;
    text-decoration: line-through;
}

.pricing-features li.disabled i {
    color: #9ca3af;
}

.btn-pricing {
    width: 100%;
    padding: 14px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-pricing-primary {
    background: var(--primary-color);
    color: white;
    border: none;
}

.btn-pricing-primary:hover {
    background: #365a8a;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(65, 100, 153, 0.25);
}

.btn-pricing-secondary {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-pricing-secondary:hover {
    background: var(--primary-color);
    color: white;
}

/* FAQ Section */
.faq-section {
    padding: 100px 0;
    background: #f8fafc;
}

.faq-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 60px;
    text-align: center;
}

.accordion-item {
    border: none;
    margin-bottom: 16px;
    border-radius: 12px !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.accordion-button {
    background: white;
    color: var(--dark-color);
    font-weight: 600;
    padding: 20px 24px;
    border-radius: 12px !important;
}

.accordion-button:not(.collapsed) {
    background: var(--primary-color);
    color: white;
}

.accordion-button:focus {
    box-shadow: none;
}

.accordion-body {
    padding: 20px 24px;
    color: #6b7280;
    line-height: 1.7;
}

/* CTA Section */
.cta-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #416499 0%, #5b7db1 100%);
    color: white;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.cta-text {
    font-size: 1.25rem;
    opacity: 0.95;
    margin-bottom: 40px;
}

/* Footer */
.pricing-footer {
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

@media (max-width: 768px) {
    .pricing-title {
        font-size: 2rem;
    }
    .faq-title {
        font-size: 2rem;
    }
    .cta-title {
        font-size: 2rem;
    }
}

</style>

<!-- Top Navigation -->
<nav class="top-nav">
    <div class="nav-container">
        <a href="<?= base_url('/'); ?>" class="nav-brand">
            <img src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" 
                 alt="<?= esc($app_name); ?>" 
                 onerror="this.style.display='none'">
            <span class="nav-brand-text"><?= esc($app_name); ?></span>
        </a>
        
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="bi bi-list"></i>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?= base_url('/'); ?>#features">Features</a></li>
            <li><a href="<?= base_url('pricing'); ?>">Pricing</a></li>
        </ul>
        
        <div class="nav-buttons">
            <a href="<?= base_url('login'); ?>" class="nav-btn nav-btn-secondary">Sign In</a>
            <a href="<?= base_url('registration'); ?>" class="nav-btn nav-btn-primary">Get Started</a>
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    document.getElementById('navMenu').classList.toggle('active');
}

window.addEventListener('scroll', function() {
    const nav = document.querySelector('.top-nav');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

document.addEventListener('click', function(e) {
    const nav = document.querySelector('.top-nav');
    const menu = document.getElementById('navMenu');
    
    if (!nav.contains(e.target) && menu.classList.contains('active')) {
        menu.classList.remove('active');
    }
});
</script>

<!-- Pricing Section -->
<section class="pricing-section">
    <div class="container">
        <div class="pricing-header">
            <h1 class="pricing-title">Simple, Transparent Pricing</h1>
            <p class="pricing-subtitle">
                Choose the perfect plan for your school. All plans include a 30-day free trial.
            </p>
        </div>
        
        <div class="row g-4">
            <?php if(!empty($plans)): ?>
                <?php foreach($plans as $index => $plan): 
                    $isPopular = $plan->is_popular == 1;
                    $planName = esc($plan->name);
                    $planDesc = esc($plan->description);
                    
                    // Decode the plan's multi-currency prices (JSON stored in the prices column)
                    $plan_prices = isset($plan->prices) ? json_decode((string) $plan->prices, true) : null;
                    if (!is_array($plan_prices)) {
                        $plan_prices = [];
                    }
                    
                    // Get billing cycle
                    $billing = isset($plan->billing_cycle) ? esc($plan->billing_cycle) : 'monthly';
                    $period = $billing == 'yearly' ? 'per year' : 'per month';
                    
                    // Get USD price (for international display)
                    $price = isset($plan_prices['USD'][$billing . '_price']) ? (float) $plan_prices['USD'][$billing . '_price'] : 0;
                    
                    // If USD price not available, convert from BDT or use legacy fields
                    if ($price <= 0) {
                        $bdt_price = isset($plan_prices['BDT'][$billing . '_price']) ? (float) $plan_prices['BDT'][$billing . '_price'] : 0;
                        if ($bdt_price > 0) {
                            // Simple conversion: 1 USD = 110 BDT (you can adjust this rate)
                            $price = $bdt_price / 110;
                        } else {
                            // Fallback to legacy monthly_price field
                            $price = isset($plan->monthly_price) ? (float) $plan->monthly_price : 0;
                        }
                    }
                    
                    $currency_symbol = '$'; // Default to USD for international pricing
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card <?= $isPopular ? 'featured' : '' ?>">
                        <?php if($isPopular): ?>
                            <span class="badge">Most Popular</span>
                        <?php endif; ?>
                        
                        <div class="pricing-name"><?= $planName ?></div>
                        <div class="pricing-description"><?= $planDesc ?></div>
                        
                        <div class="pricing-price">
                            <span class="pricing-currency"><?= $currency_symbol ?></span>
                            <span class="pricing-amount"><?= number_format($price, 0) ?></span>
                            <div class="pricing-period"><?= $period ?></div>
                        </div>
                    
                    <?php if(!empty($plan->features)): 
                        $features = json_decode($plan->features, true);
                        if(is_array($features)):
                    ?>
                        <ul class="pricing-features">
                            <?php foreach($features as $feature): 
                                $featureName = is_array($feature) ? ($feature['name'] ?? $feature) : $feature;
                                $featureEnabled = is_array($feature) ? ($feature['enabled'] ?? true) : true;
                            ?>
                                <li class="<?= $featureEnabled ? '' : 'disabled' ?>">
                                    <i class="bi bi-<?= $featureEnabled ? 'check-circle-fill' : 'x-circle' ?>"></i>
                                    <?= esc($featureName) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php 
                        endif;
                    else:
                        // Default features if none set in DB
                        $defaultFeatures = [
                            'Student Management',
                            'Result Generator',
                            'Unlimited Exams',
                            'PDF Export'
                        ];
                    ?>
                        <ul class="pricing-features">
                            <?php foreach($defaultFeatures as $feature): ?>
                                <li><i class="bi bi-check-circle-fill"></i> <?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <a href="<?= base_url('registration'); ?>" class="btn-pricing <?= $isPopular ? 'btn-pricing-primary' : 'btn-pricing-secondary' ?>">
                        Start Free Trial
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback if no plans in database -->
            <div class="col-lg-12 text-center">
                <p>No pricing plans available at the moment. Please contact us for more information.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>
<!-- Feature Comparison Section -->
<section class="comparison-section" style="padding: 100px 0; background: #f8fafc;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-size: 2.5rem; font-weight: 700; color: #111827; margin-bottom: 16px;">Detailed Feature Comparison</h2>
            <p style="font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto;">
                Compare all features across our plans to find the perfect fit for your school
            </p>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <thead style="background: linear-gradient(135deg, #416499 0%, #5b7db1 100%); color: white;">
                    <tr>
                        <th style="padding: 20px; font-weight: 600; border: none; text-align: left;">Feature</th>
                        <th style="padding: 20px; font-weight: 600; text-align: center; border: none;">Basic</th>
                        <th style="padding: 20px; font-weight: 600; text-align: center; border: none;">Standard</th>
                        <th style="padding: 20px; font-weight: 600; text-align: center; border: none; background: rgba(65,100,153,0.2);">Premium</th>
                        <th style="padding: 20px; font-weight: 600; text-align: center; border: none;">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Student Management</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Result Generator</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Transcript & Marksheet</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Merit List</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">PDF Export</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">QR Verification</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Tabulation Sheet</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Unlimited Exams</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Custom Report Templates</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Multi-Branch Support</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fafafa;">
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">API Access</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 20px; font-weight: 600; color: #111827;">Priority Support</td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-x-circle" style="color: #ef4444; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center; background: #f8fafc;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                        <td style="padding: 16px 20px; text-align: center;"><i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 20px;"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?= base_url('registration'); ?>" class="btn-pricing btn-pricing-primary" style="padding: 16px 40px; font-size: 16px; display: inline-block;">
                Get Started Now
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="faq-title">Frequently Asked Questions</h2>
        
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        What is included in the free trial?
                    </button>
                </h3>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        All plans come with a 30-day free trial with full access to all features. No credit card required to start your trial.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        Can I upgrade or downgrade my plan?
                    </button>
                </h3>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately, and we'll prorate any billing differences.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        Is my data secure?
                    </button>
                </h3>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Absolutely! We use industry-standard encryption and security practices to protect your data. All data is backed up daily and stored on secure servers.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        Do you offer discounts for annual billing?
                    </button>
                </h3>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes! We offer a 20% discount when you choose annual billing. This applies to all plans and can be selected during registration or changed in your account settings.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        What payment methods do you accept?
                    </button>
                </h3>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for annual plans.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="cta-title">Ready to Get Started?</h2>
        <p class="cta-text">
            Join hundreds of schools already using <?= esc($app_name); ?>. Start your free trial today!
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= base_url('registration'); ?>" class="btn-pricing btn-pricing-primary" style="padding: 16px 40px; font-size: 16px;">
                Get Started Free
            </a>
            <a href="<?= base_url('login'); ?>" style="padding: 16px 40px; border-radius: 12px; font-weight: 600; text-decoration: none; color: white; border: 2px solid rgba(255,255,255,0.4);">
                Sign In
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="pricing-footer">
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
                    <li><a href="<?= base_url('/'); ?>#features">Features</a></li>
                    <li><a href="<?= base_url('pricing'); ?>">Pricing</a></li>
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
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Documentation</a></li>
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

