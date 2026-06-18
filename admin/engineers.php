<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Engineer.php';
require_once '../models/Company.php';
require_once '../models/User.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$companyModel  = new Company($db);
$userModel     = new User($db);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = intval($_POST['id'] ?? 0);
        $data = [
            'company_id'          => intval($_POST['company_id']) ?: null,
            'license_number'      => sanitize($_POST['license_number'] ?? ''),
            'specialization'      => sanitize($_POST['specialization'] ?? ''),
            'experience_years'    => intval($_POST['experience_years'] ?? 0),
            'availability_status' => $_POST['availability_status'] ?? 'available',
            'skills'              => sanitize($_POST['skills'] ?? ''),
            'certifications'      => sanitize($_POST['certifications'] ?? ''),
            'hourly_rate'         => floatval($_POST['hourly_rate'] ?? 0),
            'bio'                 => sanitize($_POST['bio'] ?? ''),
        ];
        if ($id) {
            $engineerModel->update($id, $data);
            // Also update user name/phone if provided
            $uData = [];
            if (!empty($_POST['name']))  $uData['name']  = sanitize($_POST['name']);
            if (!empty($_POST['phone'])) $uData['phone'] = sanitize($_POST['phone']);
            if ($uData) {
                $stmt = $db->prepare("SELECT user_id FROM engineers WHERE id=:id");
                $stmt->execute([':id'=>$id]);
                $row = $stmt->fetch();
                if ($row) $userModel->update($row['user_id'], $uData);
            }
            $success = 'Engineer updated.';
        } else {
            $user_id = intval($_POST['user_id'] ?? 0);
            if ($user_id) {
                $data['user_id'] = $user_id;
                $engineerModel->create($data);
                $success = 'Engineer profile created.';
            } else { $error = 'Please select a user.'; }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { $engineerModel->delete($id); $success = 'Engineer removed.'; }
    }
}

$engineers = $engineerModel->getAll();
$companies = $companyModel->getAll();

// Users with engineer role but no engineer profile
$stmt = $db->prepare("SELECT u.id, u.name, u.email FROM users u
    LEFT JOIN engineers e ON u.id=e.user_id
    WHERE u.role='engineer' AND e.id IS NULL");
$stmt->execute();
$unlinkedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editId   = intval($_GET['edit'] ?? 0);
$editData = $editId ? $engineerModel->getById($editId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Engineers – Admin | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-hard-hat"></i> Engineers</h1><p>Manage engineer profiles and assignments</p></div>
    <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Engineer</button>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Engineers Table -->
<div class="dashboard-card">
    <div class="card-header"><h3><i class="fas fa-hard-hat"></i> All Engineers (<?= count($engineers) ?>)</h3></div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr>
                    <th>Engineer</th><th>Company</th><th>Specialization</th>
                    <th>Rating</th><th>Status</th><th>Rate/hr</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($engineers)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af">No engineers found</td></tr>
                <?php else: foreach ($engineers as $eng): ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>" alt=""
                                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <div>
                                <strong><?= htmlspecialchars($eng['name']) ?></strong>
                                <div style="font-size:11px;color:#9ca3af"><?= htmlspecialchars($eng['license_number'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($eng['company_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($eng['specialization'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:4px">
                            <i class="fas fa-star" style="color:#f59e0b;font-size:12px"></i>
                            <?= number_format($eng['rating'],1) ?> (<?= $eng['total_reviews'] ?>)
                        </div>
                    </td>
                    <td><span class="status-badge <?= $eng['availability_status'] ?>"><?= ucfirst($eng['availability_status']) ?></span></td>
                    <td>₱<?= number_format($eng['hourly_rate'],0) ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <button class="btn-table-action" onclick="editEngineer(<?= htmlspecialchars(json_encode($eng)) ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this engineer profile?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $eng['id'] ?>">
                                <button type="submit" class="btn-table-action" style="background:#fee2e2;color:#dc2626" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-hard-hat"></i> Add Engineer Profile</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" id="engineerForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="engId" value="0">

            <div class="form-group" id="userSelectGroup">
                <label>Link to User Account</label>
                <select name="user_id">
                    <option value="">-- Select engineer user --</option>
                    <?php foreach ($unlinkedUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#9ca3af">Only users with 'engineer' role and no existing profile are shown</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name (update)</label>
                    <div class="input-wrapper"><i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" id="engName" placeholder="Leave blank to keep current"></div>
                </div>
                <div class="form-group">
                    <label>Phone (update)</label>
                    <div class="input-wrapper"><i class="fas fa-phone input-icon"></i>
                    <input type="text" name="phone" id="engPhone"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>License Number</label>
                    <div class="input-wrapper"><i class="fas fa-id-card input-icon"></i>
                    <input type="text" name="license_number" id="engLicense" placeholder="GE-XXXX-XXXXXX"></div>
                </div>
                <div class="form-group">
                    <label>Experience (years)</label>
                    <div class="input-wrapper"><i class="fas fa-briefcase input-icon"></i>
                    <input type="number" name="experience_years" id="engExp" min="0" max="50"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Specialization</label>
                <div class="input-wrapper"><i class="fas fa-star input-icon"></i>
                <input type="text" name="specialization" id="engSpec" placeholder="e.g. Boundary & Topographic Survey"></div>
            </div>

            <div class="form-group">
                <label>Assign to Company</label>
                <select name="company_id" id="engCompany">
                    <option value="">-- Independent --</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Hourly Rate (₱)</label>
                    <div class="input-wrapper"><i class="fas fa-peso-sign input-icon"></i>
                    <input type="number" name="hourly_rate" id="engRate" min="0" step="100"></div>
                </div>
                <div class="form-group">
                    <label>Availability</label>
                    <select name="availability_status" id="engAvail">
                        <option value="available">Available</option>
                        <option value="busy">Busy</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Skills (comma-separated)</label>
                <div class="input-wrapper"><i class="fas fa-tools input-icon"></i>
                <input type="text" name="skills" id="engSkills" placeholder="GPS, AutoCAD, Total Station..."></div>
            </div>
            <div class="form-group">
                <label>Certifications</label>
                <div class="input-wrapper"><i class="fas fa-certificate input-icon"></i>
                <input type="text" name="certifications" id="engCerts" placeholder="PRC Licensed GE, NAMRIA Accredited..."></div>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" id="engBio" rows="3" placeholder="Short professional bio..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-modal-save"><i class="fas fa-save"></i> Save Engineer</button>
            </div>
        </form>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
<script>
function openModal(){ document.getElementById('modalOverlay').style.display='flex'; document.getElementById('userSelectGroup').style.display='block'; }
function closeModal(){ document.getElementById('modalOverlay').style.display='none'; }
function editEngineer(e){
    document.getElementById('engId').value=e.id;
    document.getElementById('modalTitle').innerHTML='<i class="fas fa-edit"></i> Edit Engineer';
    document.getElementById('userSelectGroup').style.display='none';
    document.getElementById('engLicense').value=e.license_number||'';
    document.getElementById('engSpec').value=e.specialization||'';
    document.getElementById('engExp').value=e.experience_years||0;
    document.getElementById('engRate').value=e.hourly_rate||0;
    document.getElementById('engSkills').value=e.skills||'';
    document.getElementById('engCerts').value=e.certifications||'';
    document.getElementById('engBio').value=e.bio||'';
    document.getElementById('engAvail').value=e.availability_status||'available';
    const cSel=document.getElementById('engCompany');
    if(cSel) cSel.value=e.company_id||'';
    openModal();
}
</script>
</body></html>
