/**
 * Dashboard JS — GeoSurvey Portal
 * Handles: auto-hide alerts, real-time sync polling
 * NOTE: Sidebar hamburger is handled entirely in header.php
 */
document.addEventListener('DOMContentLoaded', function() {

    // ── Auto-hide alerts after 5 s ──────────────────────────
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(el) {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 400);
        });
    }, 5000);

});
