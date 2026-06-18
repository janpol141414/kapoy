<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

$q    = trim($_GET['q'] ?? '');
$role = getCurrentUserRole();

if (strlen($q) < 1) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$db      = (new Database())->getConnection();
$results = [];
$like    = '%' . $q . '%';

/* ── Engineers ── */
$stmt = $db->prepare(
    "SELECT u.id as user_id, e.id as eng_id, u.name, u.profile_photo,
            e.specialization, e.availability_status, e.rating
     FROM engineers e
     JOIN users u ON e.user_id = u.id
     WHERE u.is_active = 1
       AND (u.name LIKE :q1 OR e.specialization LIKE :q2 OR e.skills LIKE :q3)
     ORDER BY e.rating DESC LIMIT 5"
);
$stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        'type'     => 'engineer',
        'id'       => $row['eng_id'],
        'user_id'  => $row['user_id'],
        'title'    => $row['name'],
        'subtitle' => $row['specialization'] ?? 'Geodetic Engineer',
        'meta'     => number_format($row['rating'], 1) . ' ★ · ' . ucfirst($row['availability_status']),
        'photo'    => $row['profile_photo'] ?? 'default_avatar.png',
        'url'      => BASE_URL . '/client/engineer-profile.php?id=' . $row['eng_id'],
        'icon'     => 'fa-hard-hat',
        'color'    => '#667eea',
    ];
}

/* ── Companies ── */
$stmt = $db->prepare(
    "SELECT c.id, c.name, c.logo, c.address, c.services,
            COUNT(e.id) as eng_count
     FROM companies c
     LEFT JOIN engineers e ON c.id = e.company_id
     WHERE c.is_active = 1
       AND (c.name LIKE :q1 OR c.services LIKE :q2 OR c.address LIKE :q3)
     GROUP BY c.id
     ORDER BY c.name LIMIT 4"
);
$stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        'type'     => 'company',
        'id'       => $row['id'],
        'title'    => $row['name'],
        'subtitle' => $row['address'] ?? 'Surveying Company',
        'meta'     => $row['eng_count'] . ' engineer' . ($row['eng_count'] != 1 ? 's' : ''),
        'photo'    => $row['logo'] ?? 'default_company.png',
        'url'      => BASE_URL . '/client/companies.php?id=' . $row['id'],
        'icon'     => 'fa-building',
        'color'    => '#4facfe',
        'is_company' => true,
    ];
}

/* ── Services ── */
$services = [
    ['Boundary Survey',     'fa-border-all',       '#667eea', 'Determine exact property boundaries'],
    ['Topographic Survey',  'fa-mountain',          '#f093fb', 'Map terrain features and elevations'],
    ['Construction Layout', 'fa-building',          '#4facfe', 'Stake out building positions'],
    ['Subdivision Survey',  'fa-th-large',          '#43e97b', 'Divide land into smaller parcels'],
    ['Route Survey',        'fa-road',              '#fa709a', 'Survey for roads and pipelines'],
    ['Geodetic Survey',     'fa-globe',             '#a18cd1', 'Large-scale precise measurements'],
    ['Hydrographic Survey', 'fa-water',             '#4facfe', 'Map underwater terrain'],
    ['As-Built Survey',     'fa-drafting-compass',  '#f5576c', 'Document completed construction'],
];
foreach ($services as [$name, $icon, $color, $desc]) {
    if (stripos($name, $q) !== false || stripos($desc, $q) !== false) {
        $results[] = [
            'type'     => 'service',
            'title'    => $name,
            'subtitle' => $desc,
            'meta'     => 'Book a survey',
            'photo'    => null,
            'url'      => BASE_URL . '/client/book-appointment.php?service=' . urlencode($name),
            'icon'     => $icon,
            'color'    => $color,
        ];
    }
}

/* ── Appointments (for client/engineer) ── */
if ($role === 'client') {
    $stmt = $db->prepare(
        "SELECT a.id, a.service_type, a.appointment_date, a.status,
                eu.name as engineer_name, a.confirmation_code
         FROM appointments a
         JOIN engineers e ON a.engineer_id = e.id
         JOIN users eu ON e.user_id = eu.id
         WHERE a.client_id = :uid
           AND (a.service_type LIKE :q1 OR eu.name LIKE :q2 OR a.confirmation_code LIKE :q3)
         ORDER BY a.created_at DESC LIMIT 4"
    );
    $stmt->execute([':uid' => $_SESSION['user_id'], ':q1' => $like, ':q2' => $like, ':q3' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type'     => 'appointment',
            'id'       => $row['id'],
            'title'    => $row['service_type'],
            'subtitle' => 'with ' . $row['engineer_name'],
            'meta'     => date('M d, Y', strtotime($row['appointment_date'])) . ' · ' . ucfirst(str_replace('_', ' ', $row['status'])),
            'photo'    => null,
            'url'      => BASE_URL . '/client/track-status.php?id=' . $row['id'],
            'icon'     => 'fa-calendar-check',
            'color'    => '#43e97b',
        ];
    }
} elseif ($role === 'engineer') {
    $engStmt = $db->prepare("SELECT id FROM engineers WHERE user_id = :uid LIMIT 1");
    $engStmt->execute([':uid' => $_SESSION['user_id']]);
    $eng = $engStmt->fetch(PDO::FETCH_ASSOC);
    if ($eng) {
        $stmt = $db->prepare(
            "SELECT a.id, a.service_type, a.appointment_date, a.status,
                    u.name as client_name
             FROM appointments a
             JOIN users u ON a.client_id = u.id
             WHERE a.engineer_id = :eid
               AND (a.service_type LIKE :q1 OR u.name LIKE :q2)
             ORDER BY a.created_at DESC LIMIT 4"
        );
        $stmt->execute([':eid' => $eng['id'], ':q1' => $like, ':q2' => $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'type'     => 'appointment',
                'id'       => $row['id'],
                'title'    => $row['service_type'],
                'subtitle' => 'Client: ' . $row['client_name'],
                'meta'     => date('M d, Y', strtotime($row['appointment_date'])) . ' · ' . ucfirst(str_replace('_', ' ', $row['status'])),
                'photo'    => null,
                'url'      => BASE_URL . '/engineer/appointments.php?id=' . $row['id'],
                'icon'     => 'fa-calendar-check',
                'color'    => '#43e97b',
            ];
        }
    }
}

/* ── Admin: users, appointments, engineers ── */
if ($role === 'admin') {
    // Users
    $stmt = $db->prepare(
        "SELECT id, name, email, role, profile_photo
         FROM users
         WHERE name LIKE :q1 OR email LIKE :q2
         ORDER BY name LIMIT 5"
    );
    $stmt->execute([':q1' => $like, ':q2' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type'     => 'user',
            'id'       => $row['id'],
            'title'    => $row['name'],
            'subtitle' => $row['email'],
            'meta'     => ucfirst($row['role']),
            'photo'    => $row['profile_photo'] ?? 'default_avatar.png',
            'url'      => BASE_URL . '/admin/users.php',
            'icon'     => 'fa-user',
            'color'    => '#f093fb',
        ];
    }

    // Appointments
    $stmt = $db->prepare(
        "SELECT a.id, a.service_type, a.appointment_date, a.status,
                u.name AS client_name, eu.name AS engineer_name, a.confirmation_code
         FROM appointments a
         JOIN users u ON a.client_id = u.id
         JOIN engineers e ON a.engineer_id = e.id
         JOIN users eu ON e.user_id = eu.id
         WHERE a.service_type LIKE :q1 OR u.name LIKE :q2 OR eu.name LIKE :q3
            OR a.confirmation_code LIKE :q4
         ORDER BY a.created_at DESC LIMIT 5"
    );
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type'     => 'appointment',
            'id'       => $row['id'],
            'title'    => $row['service_type'],
            'subtitle' => $row['client_name'] . ' → ' . $row['engineer_name'],
            'meta'     => date('M d, Y', strtotime($row['appointment_date'])) . ' · ' . ucfirst(str_replace('_', ' ', $row['status'])),
            'photo'    => null,
            'url'      => BASE_URL . '/admin/appointments.php?id=' . $row['id'],
            'icon'     => 'fa-calendar-check',
            'color'    => '#43e97b',
        ];
    }

    // Engineers
    $stmt = $db->prepare(
        "SELECT u.id as user_id, e.id as eng_id, u.name, u.profile_photo,
                e.specialization, e.availability_status, e.rating
         FROM engineers e
         JOIN users u ON e.user_id = u.id
         WHERE u.name LIKE :q1 OR e.specialization LIKE :q2 OR e.license_number LIKE :q3
         ORDER BY e.rating DESC LIMIT 4"
    );
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type'     => 'engineer',
            'id'       => $row['eng_id'],
            'user_id'  => $row['user_id'],
            'title'    => $row['name'],
            'subtitle' => $row['specialization'] ?? 'Geodetic Engineer',
            'meta'     => number_format($row['rating'], 1) . ' ★ · ' . ucfirst($row['availability_status']),
            'photo'    => $row['profile_photo'] ?? 'default_avatar.png',
            'url'      => BASE_URL . '/admin/engineers.php',
            'icon'     => 'fa-hard-hat',
            'color'    => '#667eea',
        ];
    }
}

echo json_encode([
    'success' => true,
    'query'   => $q,
    'count'   => count($results),
    'results' => $results,
]);
