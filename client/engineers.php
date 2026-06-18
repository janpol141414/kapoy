<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);

$filters = [];
if (!empty($_GET['availability'])) $filters['availability'] = $_GET['availability'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$engineers = $engineerModel->getAll($filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Engineers - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/engineers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">

<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-hard-hat"></i> Browse Engineers</h1>
                <p>Find the perfect licensed geodetic engineer for your project</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <form method="GET" class="filter-form" id="engineerSearchForm">
                <div class="search-filter search-history-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="engineerSearch"
                           placeholder="Search by name, specialization, company..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           autocomplete="off"
                           oninput="liveFilterEngineers(this.value)">
                </div>
                <select name="availability" class="filter-select" id="availFilter" onchange="liveFilterEngineers(document.getElementById('engineerSearch').value)">
                    <option value="">All Availability</option>
                    <option value="available" <?= ($_GET['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="busy" <?= ($_GET['availability'] ?? '') === 'busy' ? 'selected' : '' ?>>Busy</option>
                </select>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="engineers.php" class="btn-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>
            <span class="results-count" id="engResultCount"><?= count($engineers) ?> engineers found</span>
        </div>

        <!-- Engineers Grid -->
        <div class="engineers-grid-page">
            <?php if (empty($engineers)): ?>
            <div class="empty-state full">
                <i class="fas fa-hard-hat"></i>
                <h3>No engineers found</h3>
                <p>Try adjusting your search filters</p>
            </div>
            <?php else: ?>
            <?php foreach ($engineers as $eng): ?>
            <div class="engineer-card-page" onclick="window.location='engineer-profile.php?id=<?= $eng['id'] ?>'">
                <div class="eng-card-header">
                    <div class="eng-photo-wrapper">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>" 
                             alt="<?= htmlspecialchars($eng['name']) ?>"
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <div class="availability-dot <?= $eng['availability_status'] ?>"></div>
                    </div>
                    <div class="eng-basic-info">
                        <h3><?= htmlspecialchars($eng['name']) ?></h3>
                        <p class="eng-specialization"><?= htmlspecialchars($eng['specialization']) ?></p>
                        <div class="eng-company">
                            <i class="fas fa-building"></i>
                            <?= htmlspecialchars($eng['company_name'] ?? 'Independent') ?>
                        </div>
                    </div>
                    <div class="eng-availability-badge <?= $eng['availability_status'] ?>">
                        <?= ucfirst($eng['availability_status']) ?>
                    </div>
                </div>

                <div class="eng-card-body">
                    <div class="eng-stats-row">
                        <div class="eng-stat">
                            <div class="eng-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= round($eng['rating']) ? 'filled' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span><?= number_format($eng['rating'], 1) ?> (<?= $eng['total_reviews'] ?> reviews)</span>
                        </div>
                        <div class="eng-stat">
                            <i class="fas fa-briefcase"></i>
                            <span><?= $eng['experience_years'] ?> years exp.</span>
                        </div>
                        <div class="eng-stat">
                            <i class="fas fa-peso-sign"></i>
                            <span>₱<?= number_format($eng['hourly_rate'], 0) ?>/hr</span>
                        </div>
                    </div>

                    <?php if ($eng['bio']): ?>
                    <p class="eng-bio"><?= htmlspecialchars(substr($eng['bio'], 0, 100)) ?>...</p>
                    <?php endif; ?>

                    <?php if ($eng['skills']): ?>
                    <div class="eng-skills">
                        <?php foreach (array_slice(explode(',', $eng['skills']), 0, 3) as $skill): ?>
                        <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="eng-card-footer">
                    <a href="engineer-profile.php?id=<?= $eng['id'] ?>" class="btn-view-profile" onclick="event.stopPropagation()">
                        <i class="fas fa-user"></i> View Profile
                    </a>
                    <?php if ($eng['availability_status'] === 'available'): ?>
                    <a href="book-appointment.php?engineer_id=<?= $eng['id'] ?>" class="btn-book-eng" onclick="event.stopPropagation()">
                        <i class="fas fa-calendar-plus"></i> Book Now
                    </a>
                    <?php else: ?>
                    <button class="btn-book-eng disabled" disabled>
                        <i class="fas fa-clock"></i> Unavailable
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initSearchHistory === 'function') {
        initSearchHistory('engineerSearch', 'geosurvey_search_engineers');
    }
});

// Live filter — no page reload
function liveFilterEngineers(query) {
    query = query.toLowerCase().trim();
    const avail = document.getElementById('availFilter')?.value || '';
    const cards = document.querySelectorAll('.engineer-card-page');
    let visible = 0;

    cards.forEach(card => {
        const name  = card.querySelector('.eng-basic-info h3')?.textContent.toLowerCase() || '';
        const spec  = card.querySelector('.eng-specialization')?.textContent.toLowerCase() || '';
        const comp  = card.querySelector('.eng-company')?.textContent.toLowerCase() || '';
        const skills= card.querySelector('.eng-skills')?.textContent.toLowerCase() || '';
        const availBadge = card.querySelector('.eng-availability-badge')?.textContent.toLowerCase().trim() || '';

        const matchText  = !query || name.includes(query) || spec.includes(query) || comp.includes(query) || skills.includes(query);
        const matchAvail = !avail || availBadge === avail;

        if (matchText && matchAvail) {
            card.style.display = '';
            card.style.animation = 'fadeSlideUp 0.25s ease both';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    const counter = document.getElementById('engResultCount');
    if (counter) counter.textContent = visible + ' engineer' + (visible !== 1 ? 's' : '') + ' found';
}
</script>
</body>
</html>
