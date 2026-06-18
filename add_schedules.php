<?php
/**
 * Add schedule slots for Eng. Maria Santos (engineer_id = 1)
 * Run once: http://localhost/kapoy/add_schedules.php
 * Delete after running.
 */
require_once 'config/database.php';

$db = (new Database())->getConnection();

// Get engineer ID for Maria Santos (user_id = 2)
$stmt = $db->prepare("SELECT id FROM engineers WHERE user_id = 2 LIMIT 1");
$stmt->execute();
$eng = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$eng) {
    die('<p style="color:red">Engineer not found. Make sure the database is imported.</p>');
}

$engId = $eng['id'];

// Delete existing schedules for this engineer to start fresh
$db->prepare("DELETE FROM schedules WHERE engineer_id = :id")->execute([':id' => $engId]);

$slots = [];
$today = new DateTime();

// Generate 30 days of schedule
for ($i = 1; $i <= 30; $i++) {
    $date = (clone $today)->modify("+{$i} days");
    $dateStr = $date->format('Y-m-d');
    $dow = (int)$date->format('N'); // 1=Mon, 7=Sun

    // Weekends = unavailable
    if ($dow >= 6) {
        $slots[] = [
            'engineer_id'  => $engId,
            'date'         => $dateStr,
            'start_time'   => '08:00:00',
            'end_time'     => '17:00:00',
            'is_available' => 0,
            'slot_type'    => 'full_day',
            'notes'        => 'Weekend - unavailable',
        ];
        continue;
    }

    // Specific unavailable dates (every 3rd weekday)
    if ($i % 3 === 0) {
        $slots[] = [
            'engineer_id'  => $engId,
            'date'         => $dateStr,
            'start_time'   => '08:00:00',
            'end_time'     => '17:00:00',
            'is_available' => 0,
            'slot_type'    => 'full_day',
            'notes'        => 'Booked / unavailable',
        ];
        continue;
    }

    // Available days — morning slot 8AM-12PM
    $slots[] = [
        'engineer_id'  => $engId,
        'date'         => $dateStr,
        'start_time'   => '08:00:00',
        'end_time'     => '12:00:00',
        'is_available' => 1,
        'slot_type'    => 'morning',
        'notes'        => '',
    ];

    // Available days — afternoon slot 1PM-5PM
    $slots[] = [
        'engineer_id'  => $engId,
        'date'         => $dateStr,
        'start_time'   => '13:00:00',
        'end_time'     => '17:00:00',
        'is_available' => 1,
        'slot_type'    => 'afternoon',
        'notes'        => '',
    ];
}

// Insert all slots
$insert = $db->prepare(
    "INSERT INTO schedules (engineer_id, date, start_time, end_time, is_available, slot_type, notes)
     VALUES (:engineer_id, :date, :start_time, :end_time, :is_available, :slot_type, :notes)"
);

$count = 0;
foreach ($slots as $slot) {
    $insert->execute($slot);
    $count++;
}

// Also add slots for other engineers (engineers 2-6)
$otherEngStmt = $db->query("SELECT id FROM engineers WHERE user_id != 2 LIMIT 5");
$otherEngs = $otherEngStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($otherEngs as $oe) {
    $db->prepare("DELETE FROM schedules WHERE engineer_id = :id")->execute([':id' => $oe['id']]);

    for ($i = 1; $i <= 30; $i++) {
        $date = (clone $today)->modify("+{$i} days");
        $dateStr = $date->format('Y-m-d');
        $dow = (int)$date->format('N');

        if ($dow >= 6) {
            $insert->execute([
                'engineer_id' => $oe['id'], 'date' => $dateStr,
                'start_time' => '08:00:00', 'end_time' => '17:00:00',
                'is_available' => 0, 'slot_type' => 'full_day', 'notes' => 'Weekend',
            ]);
            continue;
        }

        // Different unavailability pattern per engineer
        $unavail = ($i % ($oe['id'] + 2) === 0);
        if ($unavail) {
            $insert->execute([
                'engineer_id' => $oe['id'], 'date' => $dateStr,
                'start_time' => '08:00:00', 'end_time' => '17:00:00',
                'is_available' => 0, 'slot_type' => 'full_day', 'notes' => 'Booked',
            ]);
        } else {
            // Morning
            $insert->execute([
                'engineer_id' => $oe['id'], 'date' => $dateStr,
                'start_time' => '08:00:00', 'end_time' => '12:00:00',
                'is_available' => 1, 'slot_type' => 'morning', 'notes' => '',
            ]);
            // Afternoon
            $insert->execute([
                'engineer_id' => $oe['id'], 'date' => $dateStr,
                'start_time' => '13:00:00', 'end_time' => '17:00:00',
                'is_available' => 1, 'slot_type' => 'afternoon', 'notes' => '',
            ]);
        }
        $count++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Schedules</title>
<style>
body{font-family:Arial,sans-serif;max-width:600px;margin:60px auto;padding:20px}
.ok{background:#d1fae5;color:#065f46;padding:20px;border-radius:12px;margin-bottom:16px}
.legend{display:flex;gap:16px;margin:16px 0}
.dot{width:14px;height:14px;border-radius:50%;display:inline-block;margin-right:6px}
.green{background:#22c55e}.red{background:#ef4444}
a.btn{display:inline-block;margin-top:16px;padding:12px 28px;background:#1a3c5e;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold}
</style>
</head>
<body>
<div class="ok">
    <h2>✅ Schedules Added Successfully!</h2>
    <p><?= $count ?> schedule slots created for all engineers.</p>
    <div class="legend">
        <span><span class="dot green"></span> Green = Available (8AM–12PM, 1PM–5PM)</span>
        <span><span class="dot red"></span> Red = Unavailable / Booked</span>
    </div>
    <p>The booking calendar will now show green and red dates correctly.</p>
    <a href="client/book-appointment.php" class="btn">Test Booking Calendar →</a>
    <p style="margin-top:16px;color:#991b1b;font-size:13px">⚠️ <strong>Delete this file</strong> (add_schedules.php) after confirming it works.</p>
</div>
</body>
</html>
