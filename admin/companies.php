<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Company.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$companyModel = new Company($db);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = intval($_POST['id'] ?? 0);
        $logo = null;
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $fn = 'company_'.time().'_'.rand(1000,9999).'.'.$ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], COMPANY_LOGO_PATH.$fn)) $logo = $fn;
            }
        }
        $data = [
            'name'        => sanitize($_POST['name'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'address'     => sanitize($_POST['address'] ?? ''),
            'phone'       => sanitize($_POST['phone'] ?? ''),
            'email'       => sanitize($_POST['email'] ?? ''),
            'website'     => sanitize($_POST['website'] ?? ''),
            'services'    => sanitize($_POST['services'] ?? ''),
        ];
        if ($logo) $data['logo'] = $logo;

        if ($id) {
            $companyModel->update($id, $data);
            $success = 'Company updated successfully.';
        } else {
            if (!$logo) $data['logo'] = 'default_company.png';
            $companyModel->create($data);
            $success = 'Company added successfully.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { $companyModel->delete($id); $success = 'Company deleted.'; }
    }
}

$companies = $companyModel->getAll();
$editId    = intval($_GET['edit'] ?? 0);
$editData  = $editId ? $companyModel->getById($editId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Companies – Admin | GeoSurvey</title>
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
    <div><h1><i class="fas fa-building"></i> Companies</h1><p>Manage surveying companies</p></div>
    <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Company</button>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

<!-- Company Grid -->
<div class="company-grid">
    <?php if (empty($companies)): ?>
    <div class="empty-state full"><i class="fas fa-building"></i><h3>No companies yet</h3></div>
    <?php else: foreach ($companies as $c): ?>
    <div class="company-card-admin">
        <div class="company-card-top">
            <div class="company-card-logo">
                <img src="<?= UPLOADS_URL ?>/companies/<?= $c['logo'] ?? 'default_company.png' ?>" alt=""
                     onerror="this.innerHTML='<i class=\'fas fa-building\'></i>';this.style.display='flex'">
            </div>
            <div class="company-card-info">
                <h4><?= htmlspecialchars($c['name']) ?></h4>
                <span><?= $c['engineer_count'] ?> engineers</span>
            </div>
        </div>
        <p class="company-card-desc"><?= htmlspecialchars($c['description'] ?? 'No description.') ?></p>
        <div class="company-card-actions">
            <button class="btn-edit-sm" onclick="editCompany(<?= htmlspecialchars(json_encode($c)) ?>)">
                <i class="fas fa-edit"></i> Edit
            </button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this company?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn-delete-sm"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-building"></i> Add Company</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="companyForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="companyId" value="0">

            <div class="form-group">
                <label>Company Name *</label>
                <div class="input-wrapper"><i class="fas fa-building input-icon"></i>
                <input type="text" name="name" id="cName" required></div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="cDesc" rows="3"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <div class="input-wrapper"><i class="fas fa-phone input-icon"></i>
                    <input type="text" name="phone" id="cPhone"></div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper"><i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" id="cEmail"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <div class="input-wrapper"><i class="fas fa-map-marker-alt input-icon"></i>
                <input type="text" name="address" id="cAddress"></div>
            </div>
            <div class="form-group">
                <label>Website</label>
                <div class="input-wrapper"><i class="fas fa-globe input-icon"></i>
                <input type="text" name="website" id="cWebsite"></div>
            </div>
            <div class="form-group">
                <label>Services (comma-separated)</label>
                <div class="input-wrapper"><i class="fas fa-list input-icon"></i>
                <input type="text" name="services" id="cServices" placeholder="Boundary Survey, Topographic Survey..."></div>
            </div>
            <div class="form-group">
                <label>Company Logo</label>
                <input type="file" name="logo" accept="image/*">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-modal-save"><i class="fas fa-save"></i> Save Company</button>
            </div>
        </form>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
<script>
function openModal(){ document.getElementById('modalOverlay').style.display='flex'; }
function closeModal(){ document.getElementById('modalOverlay').style.display='none'; resetForm(); }
function resetForm(){
    document.getElementById('companyId').value='0';
    document.getElementById('modalTitle').innerHTML='<i class="fas fa-building"></i> Add Company';
    ['cName','cDesc','cPhone','cEmail','cAddress','cWebsite','cServices'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
}
function editCompany(c){
    document.getElementById('companyId').value=c.id;
    document.getElementById('modalTitle').innerHTML='<i class="fas fa-edit"></i> Edit Company';
    document.getElementById('cName').value=c.name||'';
    document.getElementById('cDesc').value=c.description||'';
    document.getElementById('cPhone').value=c.phone||'';
    document.getElementById('cEmail').value=c.email||'';
    document.getElementById('cAddress').value=c.address||'';
    document.getElementById('cWebsite').value=c.website||'';
    document.getElementById('cServices').value=c.services||'';
    openModal();
}
</script>
</body></html>
