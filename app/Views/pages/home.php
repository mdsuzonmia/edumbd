

<script>
function toggleMobileMenu() {
    document.getElementById('navMenu').classList.toggle('active');
}

// Add scrolled class to nav on scroll
window.addEventListener('scroll', function() {
    const nav = document.querySelector('.top-nav');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const nav = document.querySelector('.top-nav');
    const menu = document.getElementById('navMenu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (!nav.contains(e.target) && menu.classList.contains('active')) {
        menu.classList.remove('active');
    }
});
</script>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row hero-content text-center">
            <div class="col-lg-10 mx-auto">
                
                
                <h1 class="hero-title">
                    Professional Result Sheets for Your School — Made Simple
                </h1>
                
                <p class="hero-subtitle">
                    Create accurate, professional and print-ready student result sheets in minutes. Manage marks, grades, GPA and academic results with Edum.
                </p>
                
                <div class="hero-buttons">
                    <a href="<?= base_url('login'); ?>" class="btn-hero-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </a>
                    <a href="<?= base_url('registration'); ?>" class="btn-hero-secondary">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="section-title">Everything Your School Needs</h2>
                <p class="section-subtitle">
                    Powerful features designed to streamline school management and improve educational outcomes.
                </p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="feature-title">Student Management</h3>
                    <p class="feature-description">
                        Easily manage student information, enrollment, attendance, and academic records in one centralized system.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <h3 class="feature-title">Examination & Results</h3>
                    <p class="feature-description">
                        Create exams, manage mark distributions, generate report cards, and track student performance effortlessly.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3 class="feature-title">Attendance Tracking</h3>
                    <p class="feature-description">
                        Monitor daily attendance, generate reports, and keep parents informed about student presence.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <h3 class="feature-title">Academic Management</h3>
                    <p class="feature-description">
                        Organize classes, sections, subjects, and academic years with flexible categorization and scheduling.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h3 class="feature-title">Fee & Payment Management</h3>
                    <p class="feature-description">
                        Streamline fee collection, generate invoices, and track payments with integrated payment gateways.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <h3 class="feature-title">Reports & Analytics</h3>
                    <p class="feature-description">
                        Generate comprehensive reports and gain insights into school performance with powerful analytics.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">10+</div>
                    <div class="stat-label">User Roles</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Features</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="cta-title">Ready to Transform Your School?</h2>
        <p class="cta-text">
            Join hundreds of schools already using edum to streamline their operations.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= base_url('registration'); ?>" class="btn-hero-primary">
                Get Started Free
            </a>
            <a href="<?= base_url('login'); ?>" class="btn-hero-secondary">
                Sign In
            </a>
        </div>
    </div>
</section>
