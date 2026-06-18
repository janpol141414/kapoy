<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Schedule.php';
require_once '../models/Engineer.php';
require_once '../models/Notification.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
if (!$engineer) redirect('/auth/login.php');

$scheduleModel = new Schedule($db);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = intval($_POST['id'] ?? 0);
        $data = [
            'engineer_id'  => $engineer['id'],
            'date'         => $_POST['date'],
            'start_time'   => $_POST['start_time'],
            'end_time'     => $_POST['end_time'],
            'slot_type'    => $_POST['slot_type'] ?? 'morning',
            'is_available' => intval($_POST['is_available'] ?? 1),
            'notes'        => sanitize($_POST['notes'] ?? ''),
        ];
        if ($id) {
            $scheduleModel->update($id, $data);
            $success = 'Slot updated.';
        } else {
            $scheduleModel->create($data);
            $success = 'Slot added.';
        }

        // Notify clients who have pending/confirmed appointments with this engineer
        // so they see the schedule change in real-time
        $notifModel = new Notification($db);
        $clientsStmt = $db->prepare(
            "SELECT DISTINCT a.client_id FROM appointments a
             WHERE a.engineer_id = :eid AND a.status IN ('pending','confirmed')
             AND a.appointment_date >= CURDATE()"
        );
        $clientsStmt->execute([':eid' => $engineer['id']]);
        $affectedClients = $clientsStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($affectedClients as $clientId) {
            $notifModel->create(
                $clientId,
                'Schedule Updated',
                'Your engineer ' . htmlspecialchars($engineer['name']) . ' has updated their availability schedule.',
                'appointment',
                BASE_URL . '/client/book-appointment.php'
            );
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { $scheduleModel->delete($id); $success = 'Slot deleted.'; }
    }
}

$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year']  ?? date('Y'));
$slots = $scheduleModel->getByEngineerId($engineer['id'], $month, $year);

// Build date => slots map for calendar
$slotMap = [];
foreach ($slots as $s) {
    $slotMap[$s['date']][] = $s;
}

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Schedule – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/booking.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-clock"></i> My Schedule</h1><p>Set your availability for clients to book</p></div>
    <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Slot</button>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
    <!-- Calendar -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> <?= $monthNames[$month] ?> <?= $year ?></h3>
            <div style="display:flex;gap:8px">
                <a href="schedule.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn-outline" style="padding:6px 12px;font-size:13px">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="schedule.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn-outline" style="padding:6px 12px;font-size:13px">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="calendar-legend" style="margin-bottom:16px">
                <span><span class="legend-dot available"></span> Available</span>
                <span><span class="legend-dot" style="background:#ef4444"></span> Unavailable</span>
            </div>
            <div class="calendar-days-header">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div><?= $d ?></div>
                <?php endforeach; ?>
            </div>
            <?php
            $firstDay = date('w', mktime(0,0,0,$month,1,$year));
            $daysInMonth = date('t', mktime(0,0,0,$month,1,$year));
            ?>
            <div class="calendar-days" style="margin-top:4px">
                <?php for ($i=0;$i<$firstDay;$i++): ?>
                <div class="calendar-day empty"></div>
                <?php endfor; ?>
                <?php for ($day=1;$day<=$daysInMonth;$day++):
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $daySlots = $slotMap[$dateStr] ?? [];
                    $hasAvail = !empty(array_filter($daySlots, fn($s) => $s['is_available']));
                    $hasUnavail = !empty(array_filter($daySlots, fn($s) => !$s['is_available']));
                    $isPast = strtotime($dateStr) < strtotime(date('Y-m-d'));
                ?>
                <div class="calendar-day <?= $isPast ? 'disabled' : 'available' ?> <?= !empty($daySlots) ? 'has-slot' : '' ?>"
                     style="<?= $hasAvail ? 'background:#d1fae5;color:#065f46' : ($hasUnavail ? 'background:#fee2e2;color:#991b1b' : '') ?>"
                     onclick="<?= !$isPast ? "selectCalendarDate('$dateStr')" : '' ?>"
                     title="<?= !empty($daySlots) ? count($daySlots).' slot(s)' : 'No slots' ?>">
                    <?= $day ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Slots List -->
    <div class="dashboard-card">
        <div class="card-header"><h3><i class="fas fa-list"></i> This Month's Slots</h3></div>
        <div class="card-body" style="padding:0;max-height:500px;overflow-y:auto">
            <?php if (empty($slots)): ?>
            <div class="empty-state small"><i class="fas fa-calendar-times"></i><p>No slots this month</p></div>
            <?php else: foreach ($slots as $slot): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f1f5f9">
                <div style="text-align:center;min-width:44px">
                    <div style="font-size:10px;color:#9ca3af;text-transform:uppercase"><?= date('D', strtotime($slot['date'])) ?></div>
                    <div style="font-size:20px;font-weight:800;color:#1a1a2e;line-height:1"><?= date('d', strtotime($slot['date'])) ?></div>
                </div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600;color:#1a1a2e">
                        <?= date('h:i A', strtotime($slot['start_time'])) ?> – <?= date('h:i A', strtotime($slot['end_time'])) ?>
                    </div>
                    <div style="font-size:11px;color:#9ca3af"><?= ucfirst(str_replace('_',' ',$slot['slot_type'])) ?></div>
                </div>
                <span class="<?= $slot['is_available'] ? 'slot-available' : 'slot-unavailable' ?>" style="font-size:11px">
                    <?= $slot['is_available'] ? '● Available' : '● Unavailable' ?>
                </span>
                <div style="display:flex;gap:4px">
                    <button class="btn-table-action" onclick="editSlot(<?= htmlspecialchars(json_encode($slot)) ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this slot?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $slot['id'] ?>">
                        <button type="submit" class="btn-table-action" style="background:#fee2e2;color:#dc2626">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-clock"></i> Add Availability Slot</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="slotId" value="0">
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="date" id="slotDate" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" id="slotStart" required value="08:00">
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" name="end_time" id="slotEnd" required value="17:00">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Slot Type</label>
                    <select name="slot_type" id="slotType">
                        <option value="morning">Morning (8AM–12PM)</option>
                        <option value="afternoon">Afternoon (1PM–5PM)</option>
                        <option value="evening">Evening (5PM–8PM)</option>
                        <option value="full_day" selected>Full Day</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_available" id="slotAvail">
                        <option value="1">Available</option>
                        <option value="0">Unavailable / Blocked</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" id="slotNotes" placeholder="Optional notes...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-modal-save"><i class="fas fa-save"></i> Save Slot</button>
            </div>
        </form>
    </div>
</div>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
<script>
function openModal(){ document.getElementById('modalOverlay').style.display='flex'; }
function closeModal(){ document.getElementById('modalOverlay').style.display='none'; }
function selectCalendarDate(date){
    document.getElementById('slotDate').value=date;
    document.getElementById('slotId').value=0;
    document.getElementById('modalTitle').innerHTML='<i class="fas fa-clock"></i> Add Slot for '+date;
    openModal();
}
function editSlot(s){
    document.getElementById('slotId').value=s.id;
    document.getElementById('modalTitle').innerHTML='<i class="fas fa-edit"></i> Edit Slot';
    document.getElementById('slotDate').value=s.date;
    document.getElementById('slotStart').value=s.start_time;
    document.getElementById('slotEnd').value=s.end_time;
    document.getElementById('slotType').value=s.slot_type;
    document.getElementById('slotAvail').value=s.is_available;
    document.getElementById('slotNotes').value=s.notes||'';
    openModal();
}
</script>
</body></html>
