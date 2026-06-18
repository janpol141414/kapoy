// LandSurvey Portal — Landing Page JS
document.addEventListener('DOMContentLoaded', () => {

    /* ── Navbar scroll effect ── */
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
        updateActiveNav();
    }, { passive: true });

    /* ── Mobile nav toggle ── */
    const navToggle = document.getElementById('navToggle');
    const navLinks  = document.getElementById('navLinks');
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
            navToggle.classList.toggle('open');
        });
    }

    /* ── Close mobile nav on link click ── */
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('show');
            navToggle && navToggle.classList.remove('open');
        });
    });

    /* ── Smooth scroll ── */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    /* ── Active nav highlight on scroll ── */
    function updateActiveNav() {
        const sections = ['home','services','engineers','about','contact'];
        const scrollY = window.scrollY + 100;
        let current = 'home';
        sections.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.offsetTop <= scrollY) current = id;
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.toggle('active', link.dataset.section === current);
        });
    }
    updateActiveNav();

    /* ── Animated counters ── */
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { el.textContent = target; clearInterval(timer); }
            else el.textContent = Math.floor(current);
        }, 16);
    };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.stat-number').forEach(counter => {
                    if (!counter.classList.contains('animated')) {
                        counter.classList.add('animated');
                        animateCounter(counter);
                    }
                });
            }
        });
    });
    const heroStats = document.querySelector('.hero-stats');
    if (heroStats) observer.observe(heroStats);

    /* ── Particles ── */
    createParticles();

    /* ── Dark mode persistence (landing page uses body.landing-dark) ── */
    if (localStorage.getItem('geosurvey_dark') === '1') {
        document.body.classList.add('landing-dark');
    }
});

function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    for (let i = 0; i < 50; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `left:${Math.random()*100}%;top:${Math.random()*100}%;animation-delay:${Math.random()*20}s;animation-duration:${Math.random()*10+10}s`;
        container.appendChild(p);
    }
}
