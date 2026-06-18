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
                <span class="sidebar-role-badge admin">Admin</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Overview</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>" data-tooltip="My Profile">
                <i class="fas fa-user-shield"></i> <span>My Profile</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Management</span>
            <a href="<?= BASE_URL ?>/admin/appointments.php" class="nav-item <?= $current_page === 'appointments.php' ? 'active' : '' ?>" data-tooltip="Appointments">
                <i class="fas fa-calendar-alt"></i> <span>Appointments</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/payments.php" class="nav-item <?= $current_page === 'payments.php' ? 'active' : '' ?>" data-tooltip="Payments">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/schedules.php" class="nav-item <?= $current_page === 'schedules.php' ? 'active' : '' ?>" data-tooltip="Schedules">
                <i class="fas fa-clock"></i> <span>Schedules</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/feedback.php" class="nav-item <?= $current_page === 'feedback.php' ? 'active' : '' ?>" data-tooltip="Feedback">
                <i class="fas fa-star"></i> <span>Feedback</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Directory</span>
            <a href="<?= BASE_URL ?>/admin/engineers.php" class="nav-item <?= $current_page === 'engineers.php' ? 'active' : '' ?>" data-tooltip="Engineers">
                <i class="fas fa-hard-hat"></i> <span>Engineers</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/companies.php" class="nav-item <?= $current_page === 'companies.php' ? 'active' : '' ?>" data-tooltip="Companies">
                <i class="fas fa-building"></i> <span>Companies</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>" data-tooltip="Users">
                <i class="fas fa-users"></i> <span>Users</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item logout-nav" data-tooltip="Sign Out">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>
</aside>
