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
                <span class="sidebar-role-badge client">Client</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Main</span>
            <a href="<?= BASE_URL ?>/client/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/client/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>" data-tooltip="My Profile">
                <i class="fas fa-user-circle"></i> <span>My Profile</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Discover</span>
            <a href="<?= BASE_URL ?>/client/engineers.php" class="nav-item <?= $current_page === 'engineers.php' ? 'active' : '' ?>" data-tooltip="Browse Engineers">
                <i class="fas fa-hard-hat"></i> <span>Browse Engineers</span>
            </a>
            <a href="<?= BASE_URL ?>/client/companies.php" class="nav-item <?= $current_page === 'companies.php' ? 'active' : '' ?>" data-tooltip="Companies">
                <i class="fas fa-building"></i> <span>Companies</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Services</span>
            <a href="<?= BASE_URL ?>/client/book-appointment.php" class="nav-item <?= $current_page === 'book-appointment.php' ? 'active' : '' ?>" data-tooltip="Book Appointment">
                <i class="fas fa-calendar-plus"></i> <span>Book Appointment</span>
            </a>
            <a href="<?= BASE_URL ?>/client/track-status.php" class="nav-item <?= $current_page === 'track-status.php' ? 'active' : '' ?>" data-tooltip="Track Status">
                <i class="fas fa-map-pin"></i> <span>Track Status</span>
            </a>
            <a href="<?= BASE_URL ?>/client/payment.php" class="nav-item <?= $current_page === 'payment.php' ? 'active' : '' ?>" data-tooltip="Payments">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </a>
            <a href="<?= BASE_URL ?>/client/feedback.php" class="nav-item <?= $current_page === 'feedback.php' ? 'active' : '' ?>" data-tooltip="Feedback">
                <i class="fas fa-star"></i> <span>Feedback</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Communication</span>
            <a href="<?= BASE_URL ?>/client/messages.php" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>" data-tooltip="Messages">
                <i class="fas fa-comments"></i> <span>Messages</span>
                <?php
                require_once __DIR__ . '/../models/Message.php';
                $msgModel = new Message($db);
                $unreadMsgs = $msgModel->getUnreadCount($_SESSION['user_id']);
                if ($unreadMsgs > 0): ?>
                <span class="nav-badge"><?= $unreadMsgs ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item logout-nav" data-tooltip="Sign Out">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>
</aside>
