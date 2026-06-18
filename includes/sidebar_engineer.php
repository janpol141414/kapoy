<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-user">
            <img src="<?= UPLOADS_URL ?>/profiles/<?= $_SESSION['profile_photo'] ?? 'default_avatar.png' ?>" 
                 alt="Profile" class="sidebar-avatar"
                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
            <div class="sidebar-user-info">
                <span class="sidebar-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <span class="sidebar-role-badge engineer">Engineer</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Main</span>
            <a href="<?= BASE_URL ?>/engineer/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/engineer/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>" data-tooltip="My Profile">
                <i class="fas fa-user-circle"></i> <span>My Profile</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Work</span>
            <a href="<?= BASE_URL ?>/engineer/appointments.php" class="nav-item <?= $current_page === 'appointments.php' ? 'active' : '' ?>" data-tooltip="Appointments">
                <i class="fas fa-calendar-alt"></i> <span>Appointments</span>
            </a>
            <a href="<?= BASE_URL ?>/engineer/schedule.php" class="nav-item <?= $current_page === 'schedule.php' ? 'active' : '' ?>" data-tooltip="My Schedule">
                <i class="fas fa-clock"></i> <span>My Schedule</span>
            </a>
            <a href="<?= BASE_URL ?>/engineer/progress.php" class="nav-item <?= $current_page === 'progress.php' ? 'active' : '' ?>" data-tooltip="Update Progress">
                <i class="fas fa-tasks"></i> <span>Update Progress</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Communication</span>
            <a href="<?= BASE_URL ?>/engineer/messages.php" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>" data-tooltip="Messages">
                <i class="fas fa-comments"></i> <span>Messages</span>
            </a>
            <a href="<?= BASE_URL ?>/engineer/feedback.php" class="nav-item <?= $current_page === 'feedback.php' ? 'active' : '' ?>" data-tooltip="Feedback">
                <i class="fas fa-star"></i> <span>Feedback</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item logout-nav" data-tooltip="Sign Out">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>
</aside>
