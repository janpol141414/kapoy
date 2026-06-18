<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'admin') redirect('/admin/dashboard.php');
    elseif ($role === 'engineer') redirect('/engineer/dashboard.php');
    else redirect('/client/dashboard.php');
}

// Live engineers from DB
$db = (new Database())->getConnection();
$engStmt = $db->query(
    "SELECT e.id, u.name, u.profile_photo, e.specialization, e.rating,
            e.total_reviews, e.experience_years, e.availability_status,
            c.name AS company_name
     FROM engineers e
     JOIN users u ON e.user_id = u.id
     LEFT JOIN companies c ON e.company_id = c.id
     WHERE u.is_active = 1
     ORDER BY e.rating DESC, e.total_reviews DESC
     LIMIT 3"
);
$liveEngineers = $engStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LandSurvey Portal - Professional Land Surveying Solutions</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/chatbot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="#home" class="nav-brand">
            <div class="brand-icon"><i class="fas fa-map-marked-alt"></i></div>
            <div class="brand-text">
                <span class="brand-name">LandSurvey</span>
                <span class="brand-tagline">Portal</span>
            </div>
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#home"      class="nav-link" data-section="home">Home</a></li>
            <li><a href="#services"  class="nav-link" data-section="services">Services</a></li>
            <li><a href="#engineers" class="nav-link" data-section="engineers">Engineers</a></li>
            <li><a href="#about"     class="nav-link" data-section="about">About</a></li>
            <li><a href="#contact"   class="nav-link" data-section="contact">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <a href="auth/login.php"    class="nav-btn-outline">Sign In</a>
            <a href="auth/register.php" class="nav-btn-primary"><i class="fas fa-rocket"></i> Get Started</a>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-bg">
        <div class="hero-overlay"></div>
        <div class="hero-particles" id="particles"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-certificate"></i>
            <span>PRC Licensed Geodetic Engineers</span>
        </div>
        <h1 class="hero-title">
            Precision Land Surveying
            <span class="gradient-text">Made Simple</span>
        </h1>
        <p class="hero-subtitle">
            Connect with certified geodetic engineers, book appointments seamlessly,
            and track your survey projects in real-time — all in one professional platform.
        </p>
        <div class="hero-actions">
            <a href="auth/register.php" class="btn-hero-primary">
                <i class="fas fa-rocket"></i> Start Your Project
            </a>
            <a href="#services" class="btn-hero-secondary">
                <i class="fas fa-play-circle"></i> Explore Services
            </a>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-number" data-target="500">0</span><span>+</span>
                <span class="stat-label">Projects Done</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number" data-target="50">0</span><span>+</span>
                <span class="stat-label">Engineers</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number" data-target="98">0</span><span>%</span>
                <span class="stat-label">Satisfaction</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number" data-target="10">0</span><span>+</span>
                <span class="stat-label">Years Experience</span>
            </div>
        </div>
    </div>
    <div class="hero-scroll"><a href="#services"><i class="fas fa-chevron-down"></i></a></div>
</section>

<!-- Services Section -->
<section class="services-section" id="services">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Our Services</span>
            <h2>Comprehensive Surveying Solutions</h2>
            <p>From boundary determination to geodetic surveys, we cover all your land surveying needs with precision and expertise.</p>
        </div>
        <div class="services-grid">
            <?php
            $services = [
                ['icon'=>'fa-border-all',       'grad'=>'#667eea,#764ba2', 'name'=>'Boundary Survey',     'desc'=>'Precise determination of property boundaries and corners for legal documentation and title transfer.',    'price'=>'₱5,000'],
                ['icon'=>'fa-mountain',          'grad'=>'#f093fb,#f5576c', 'name'=>'Topographic Survey',  'desc'=>'Detailed mapping of terrain features, elevations, and contours for development planning.',              'price'=>'₱8,000'],
                ['icon'=>'fa-building',          'grad'=>'#4facfe,#00f2fe', 'name'=>'Construction Layout', 'desc'=>'Accurate staking of building positions, grades, and alignments for construction projects.',            'price'=>'₱6,000'],
                ['icon'=>'fa-th-large',          'grad'=>'#43e97b,#38f9d7', 'name'=>'Subdivision Survey',  'desc'=>'Professional division of land into smaller parcels for residential and commercial development.',       'price'=>'₱15,000'],
                ['icon'=>'fa-globe',             'grad'=>'#fa709a,#fee140', 'name'=>'Geodetic Survey',     'desc'=>'Large-scale precise measurements using GPS and advanced geodetic instruments.',                        'price'=>'₱25,000'],
                ['icon'=>'fa-water',             'grad'=>'#a18cd1,#fbc2eb', 'name'=>'Hydrographic Survey', 'desc'=>'Specialized mapping of underwater terrain and water bodies for marine projects.',                     'price'=>'₱20,000'],
            ];
            foreach ($services as $s): ?>
            <div class="service-card">
                <div class="service-icon" style="background:linear-gradient(135deg,<?= $s['grad'] ?>)">
                    <i class="fas <?= $s['icon'] ?>"></i>
                </div>
                <h3><?= $s['name'] ?></h3>
                <p><?= $s['desc'] ?></p>
                <div class="service-price">Starting at <strong><?= $s['price'] ?></strong></div>
                <a href="auth/register.php" class="service-link">Book Now <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Process</span>
            <h2>How It Works</h2>
            <p>Get your survey done in 4 simple steps</p>
        </div>
        <div class="steps-grid">
            <div class="step-item">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                <h3>Create Account</h3>
                <p>Register as a client and complete your profile to get started.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>Book Appointment</h3>
                <p>Choose your service, select an engineer, and pick an available date.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-hard-hat"></i></div>
                <h3>Survey Conducted</h3>
                <p>Our licensed engineer visits your site and conducts the survey.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item">
                <div class="step-number">04</div>
                <div class="step-icon"><i class="fas fa-award"></i></div>
                <h3>Get Results</h3>
                <p>Receive your official survey documents and digital reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- Engineers Section — Live from DB -->
<section class="engineers-section" id="engineers">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Our Team</span>
            <h2>Meet Our Expert Engineers</h2>
            <p>Licensed geodetic engineers with years of field experience — profiles updated in real-time</p>
        </div>
        <div class="engineers-grid">
            <?php foreach ($liveEngineers as $eng):
                $avail = $eng['availability_status'];
                $availLabel = ucfirst($avail);
                $availClass = $avail;
                $stars = '';
                for ($i=1;$i<=5;$i++) $stars .= ($i<=round($eng['rating'])) ? '★' : '☆';
            ?>
            <div class="engineer-card">
                <div class="engineer-photo">
                    <img src="<?= UPLOADS_URL ?>/profiles/<?= htmlspecialchars($eng['profile_photo'] ?? 'default_avatar.png') ?>"
                         alt="<?= htmlspecialchars($eng['name']) ?>"
                         onerror="this.src='assets/images/default_avatar.png'">
                    <div class="availability-badge <?= $availClass ?>"><?= $availLabel ?></div>
                </div>
                <div class="engineer-info">
                    <h3><?= htmlspecialchars($eng['name']) ?></h3>
                    <p class="specialization"><?= htmlspecialchars($eng['specialization'] ?? 'Geodetic Engineer') ?></p>
                    <div class="rating">
                        <div class="stars"><?= $stars ?></div>
                        <span><?= number_format($eng['rating'],1) ?> (<?= $eng['total_reviews'] ?> reviews)</span>
                    </div>
                    <div class="engineer-meta">
                        <span><i class="fas fa-briefcase"></i> <?= $eng['experience_years'] ?> yrs exp.</span>
                        <?php if ($eng['company_name']): ?>
                        <span><i class="fas fa-building"></i> <?= htmlspecialchars($eng['company_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="auth/register.php" class="btn-book">Book Now</a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="section-cta">
            <a href="auth/register.php" class="btn-primary-lg">View All Engineers <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Testimonials</span>
            <h2>What Our Clients Say</h2>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                <p>"Excellent work! Very professional and accurate. The boundary survey was completed on time and the documentation was perfect for our title transfer."</p>
                <div class="testimonial-author">
                    <img src="assets/images/client1.jpg" alt="Juan dela Cruz" onerror="this.src='assets/images/default_avatar.png'">
                    <div><strong>Juan dela Cruz</strong><span>Property Owner, Manila</span></div>
                    <div class="stars">★★★★★</div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                <p>"The topographic survey for our resort development was incredibly detailed. The drone technology they used saved us weeks of work. Highly recommended!"</p>
                <div class="testimonial-author">
                    <img src="assets/images/client2.jpg" alt="Ana Reyes" onerror="this.src='assets/images/default_avatar.png'">
                    <div><strong>Ana Reyes</strong><span>Developer, Cebu</span></div>
                    <div class="stars">★★★★★</div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                <p>"The online booking system made everything so easy. I could track the progress in real-time and the engineer kept me updated throughout the project."</p>
                <div class="testimonial-author">
                    <img src="assets/images/client3.jpg" alt="Carlos Mendoza" onerror="this.src='assets/images/default_avatar.png'">
                    <div><strong>Carlos Mendoza</strong><span>Contractor, Davao</span></div>
                    <div class="stars">★★★★★</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <span class="section-badge">About Us</span>
                <h2>The Philippines' Most Trusted Land Surveying Platform</h2>
                <p>LandSurvey Portal connects property owners, developers, and contractors with PRC-licensed geodetic engineers across the Philippines. We make professional land surveying accessible, transparent, and hassle-free.</p>
                <div class="about-stats-row">
                    <div class="about-stat"><span class="about-stat-num">10+</span><span class="about-stat-lbl">Years in Service</span></div>
                    <div class="about-stat"><span class="about-stat-num">500+</span><span class="about-stat-lbl">Projects Completed</span></div>
                    <div class="about-stat"><span class="about-stat-num">50+</span><span class="about-stat-lbl">Licensed Engineers</span></div>
                </div>
                <div class="about-features">
                    <div class="about-feature-item">
                        <div class="about-feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <div><strong>PRC Licensed & Verified</strong><p>Every engineer on our platform is verified with a valid PRC license.</p></div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon"><i class="fas fa-clock"></i></div>
                        <div><strong>Fast Turnaround</strong><p>Book same-week appointments and receive results on time, every time.</p></div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon"><i class="fas fa-map-marked-alt"></i></div>
                        <div><strong>Nationwide Coverage</strong><p>Engineers available across Metro Manila, Visayas, and Mindanao.</p></div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon"><i class="fas fa-headset"></i></div>
                        <div><strong>24/7 AI Support</strong><p>Landbot AI is always available to answer your questions.</p></div>
                    </div>
                </div>
                <a href="auth/register.php" class="btn-hero-primary" style="margin-top:8px;display:inline-flex">
                    <i class="fas fa-rocket"></i> Get Started Today
                </a>
            </div>
            <div class="about-visual">
                <div class="about-card-stack">
                    <div class="about-card about-card-1"><i class="fas fa-map-marked-alt"></i><strong>Boundary Survey</strong><span>Completed in 3–5 days</span></div>
                    <div class="about-card about-card-2"><i class="fas fa-star" style="color:#f59e0b"></i><strong>4.9 / 5.0</strong><span>Average client rating</span></div>
                    <div class="about-card about-card-3"><i class="fas fa-check-circle" style="color:#10b981"></i><strong>98% Satisfaction</strong><span>From 500+ projects</span></div>
                    <div class="about-bg-circle"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Start Your Survey Project?</h2>
            <p>Join hundreds of satisfied clients who trust LandSurvey Portal for their land surveying needs.</p>
            <div class="cta-actions">
                <a href="auth/register.php" class="btn-cta-primary"><i class="fas fa-user-plus"></i> Create Free Account</a>
                <a href="auth/login.php"    class="btn-cta-secondary"><i class="fas fa-sign-in-alt"></i> Sign In</a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer" id="contact">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="nav-brand" style="margin-bottom:16px">
                    <div class="brand-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div class="brand-text">
                        <span class="brand-name">LandSurvey</span>
                        <span class="brand-tagline">Portal</span>
                    </div>
                </div>
                <p>Professional land surveying services connecting clients with licensed geodetic engineers across the Philippines.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/janjan.ogwon14/" target="_blank" aria-label="Facebook">
    <i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/in/jhon-ogwon-034505392/" target="_blank" aria-label="LinkedIn">
    <i class="fab fa-linkedin"></i></a>
                    <a href="https://www.instagram.com/janpol_ogwon/" target="_blank" aria-label="Instagram">
    <i class="fab fa-instagram"></i>
</a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Services</h4>
                <ul>
                    <li><a href="#services">Boundary Survey</a></li>
                    <li><a href="#services">Topographic Survey</a></li>
                    <li><a href="#services">Construction Layout</a></li>
                    <li><a href="#services">Subdivision Survey</a></li>
                    <li><a href="#services">Geodetic Survey</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Company</h4>
                <ul>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#engineers">Our Engineers</a></li>
                    <li><a href="auth/register.php">Get Started</a></li>
                    <li><a href="auth/login.php">Sign In</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>123 Survey St., Davao City, Davao del Sur</span></div>
                <div class="contact-item"><i class="fas fa-phone"></i><span>+63 82 234 5678</span></div>
                <div class="contact-item"><i class="fas fa-envelope"></i><span>info@landsurveyportal.ph</span></div>
                <div class="contact-item"><i class="fas fa-clock"></i><span>Mon–Sat: 8:00 AM – 5:00 PM</span></div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> LandSurvey Portal. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Landbot AI Chatbot -->
<div class="chatbot-widget" id="chatbotWidget">
    <div class="chatbot-widget-header">
        <div class="chatbot-widget-avatar">
            <i class="fas fa-hard-hat"></i>
            <div class="online-indicator"></div>
        </div>
        <div class="chatbot-widget-info">
            <span class="chatbot-widget-name">Landbot AI</span>
            <span class="chatbot-widget-status">Always Online</span>
        </div>
        <button class="chatbot-widget-close" id="chatbotWidgetClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="chatbot-widget-messages" id="chatbotWidgetMessages">
        <div class="chatbot-message bot">
            <div class="chatbot-msg-avatar"><i class="fas fa-hard-hat"></i></div>
            <div class="chatbot-msg-bubble">
                <p>Hello! 👷 I'm <strong>Landbot AI</strong>, your smart assistant for LandSurvey Portal. How can I help you today?</p>
                <span class="chatbot-msg-time">Just now</span>
            </div>
        </div>
        <div class="chatbot-suggestions">
            <button class="chatbot-suggestion-btn" onclick="sendChatbotMessage('What services do you offer?')">Services</button>
            <button class="chatbot-suggestion-btn" onclick="sendChatbotMessage('How do I book an appointment?')">Book Appointment</button>
            <button class="chatbot-suggestion-btn" onclick="sendChatbotMessage('What are your prices?')">Pricing</button>
            <button class="chatbot-suggestion-btn" onclick="sendChatbotMessage('How do I register?')">Register</button>
        </div>
    </div>
    <div class="chatbot-widget-input">
        <input type="text" id="chatbotWidgetInput" placeholder="Ask me anything..." autocomplete="off">
        <button id="chatbotWidgetSend"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<button class="chatbot-widget-toggle" id="chatbotWidgetToggle">
    <i class="fas fa-hard-hat"></i>
    <span class="chatbot-widget-badge" id="chatbotWidgetBadge" style="display:none">1</span>
</button>

<script src="assets/js/landing.js"></script>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
