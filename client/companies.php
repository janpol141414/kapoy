<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Company.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$companyModel = new Company($db);

$search = trim($_GET['search'] ?? '');
$companies = $companyModel->getAll($search);

$selectedId = intval($_GET['id'] ?? 0);
$selectedCompany = null;
$companyEngineers = [];

if ($selectedId) {
    $selectedCompany = $companyModel->getById($selectedId);
    if ($selectedCompany) {
        $companyEngineers = $companyModel->getEngineers($selectedId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/companies.css">
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
                <h1><i class="fas fa-building"></i> Surveying Companies</h1>
                <p>Browse our partner companies and their services</p>
            </div>
        </div>

        <!-- Search -->
        <div class="filter-bar">
            <form method="GET" class="filter-form" id="companySearchForm">
                <div class="search-filter search-history-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="companySearch"
                           placeholder="Search companies by name, services, location..." 
                           value="<?= htmlspecialchars($search) ?>"
                           autocomplete="off"
                           oninput="liveFilterCompanies(this.value)">
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                <a href="companies.php" class="btn-reset"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
            <span class="results-count" id="compResultCount"><?= count($companies) ?> companies</span>
        </div>

        <div class="companies-layout">
            <!-- Companies List -->
            <div class="companies-list">
                <?php if (empty($companies)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h3>No companies found</h3>
                </div>
                <?php else: ?>
                <?php foreach ($companies as $company): ?>
                <a href="companies.php?id=<?= $company['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                   class="company-list-item <?= $selectedId == $company['id'] ? 'active' : '' ?>">
                    <div class="company-logo-sm">
                        <img src="<?= UPLOADS_URL ?>/companies/<?= $company['logo'] ?? 'default_company.png' ?>" 
                             alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_company.png'">
                    </div>
                    <div class="company-list-info">
                        <strong><?= htmlspecialchars($company['name']) ?></strong>
                        <span><?= $company['engineer_count'] ?> engineers</span>
                        <?php if ($company['avg_rating']): ?>
                        <div class="company-rating-sm">
                            <i class="fas fa-star filled"></i>
                            <?= number_format($company['avg_rating'], 1) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Company Detail -->
            <div class="company-detail">
                <?php if (!$selectedCompany): ?>
                <div class="company-placeholder">
                    <i class="fas fa-building"></i>
                    <h3>Select a company</h3>
                    <p>Click on a company to view its profile and engineers</p>
                </div>
                <?php else: ?>
                <div class="company-profile">
                    <div class="company-profile-header">
                        <div class="company-logo-lg">
                            <img src="<?= UPLOADS_URL ?>/companies/<?= $selectedCompany['logo'] ?? 'default_company.png' ?>" 
                                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_company.png'">
                        </div>
                        <div class="company-profile-info">
                            <h2><?= htmlspecialchars($selectedCompany['name']) ?></h2>
                            <div class="company-meta">
                                <?php if ($selectedCompany['address']): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($selectedCompany['address']) ?></span>
                                <?php endif; ?>
                                <?php if ($selectedCompany['phone']): ?>
                                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($selectedCompany['phone']) ?></span>
                                <?php endif; ?>
                                <?php if ($selectedCompany['email']): ?>
                                <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($selectedCompany['email']) ?></span>
                                <?php endif; ?>
                                <?php if ($selectedCompany['website']): ?>
                                <span><i class="fas fa-globe"></i> <?= htmlspecialchars($selectedCompany['website']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($selectedCompany['description']): ?>
                    <div class="company-description">
                        <h4>About</h4>
                        <p><?= nl2br(htmlspecialchars($selectedCompany['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($selectedCompany['services']): ?>
                    <div class="company-services">
                        <h4>Services Offered</h4>
                        <div class="services-tags">
                            <?php foreach (explode(',', $selectedCompany['services']) as $svc): ?>
                            <span class="service-tag"><?= htmlspecialchars(trim($svc)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Company Engineers -->
                    <div class="company-engineers">
                        <h4><i class="fas fa-hard-hat"></i> Our Engineers (<?= count($companyEngineers) ?>)</h4>
                        <?php if (empty($companyEngineers)): ?>
                        <p>No engineers listed.</p>
                        <?php else: ?>
                        <div class="company-engineers-grid">
                            <?php foreach ($companyEngineers as $eng): ?>
                            <div class="company-engineer-card">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>" 
                                     alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <div class="ceng-info">
                                    <strong><?= htmlspecialchars($eng['name']) ?></strong>
                                    <span><?= htmlspecialchars($eng['specialization']) ?></span>
                                    <div class="ceng-rating">
                                        <i class="fas fa-star filled"></i> <?= number_format($eng['rating'], 1) ?>
                                        <span class="availability-dot <?= $eng['availability_status'] ?>"></span>
                                    </div>
                                </div>
                                <a href="engineer-profile.php?id=<?= $eng['id'] ?>" class="btn-view-sm">View</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Google Map -->
                    <?php if (!empty($selectedCompany['address'])): ?>
                    <div class="company-map-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
                        <div class="company-map-container">
                            <?php
                            $mapQuery = urlencode($selectedCompany['address'] . ', Philippines');
                            $mapSrc   = "https://maps.google.com/maps?q={$mapQuery}&output=embed&z=15";
                            ?>
                            <iframe
                                src="<?= $mapSrc ?>"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="<?= htmlspecialchars($selectedCompany['name']) ?> location">
                            </iframe>
                        </div>
                        <a href="https://www.google.com/maps/search/<?= $mapQuery ?>"
                           target="_blank" rel="noopener" class="company-map-open-link">
                            <i class="fas fa-external-link-alt"></i>
                            Open in Google Maps
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initSearchHistory === 'function') {
        initSearchHistory('companySearch', 'geosurvey_search_companies');
    }
});

// Live filter companies — instant results, no page reload
function liveFilterCompanies(query) {
    query = query.toLowerCase().trim();
    const items = document.querySelectorAll('.company-list-item');
    let visible = 0;
    items.forEach(function(item) {
        const name = (item.querySelector('strong') || {}).textContent || '';
        const show = !query || name.toLowerCase().includes(query);
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const counter = document.getElementById('compResultCount');
    if (counter) counter.textContent = visible + (visible === 1 ? ' company' : ' companies');
}
</script>
</body>
</html>
