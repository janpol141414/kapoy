<?php
/**
 * Fix Passwords Script
 * Run this ONCE in your browser: http://localhost/kapoy/fix_passwords.php
 * It resets all demo account passwords to '123456' using correct bcrypt hashes.
 * DELETE this file after running it.
 */

require_once 'config/database.php';

$db = (new Database())->getConnection();

// Generate a fresh correct hash for '123456'
$hash = password_hash('123456', PASSWORD_BCRYPT);

// Verify the hash works before applying
if (!password_verify('123456', $hash)) {
    die('<p style="color:red">ERROR: Hash verification failed. Something is wrong with your PHP setup.</p>');
}

// Update all demo accounts
$accounts = [
    'client@test.com',
    'engineer@test.com',
    'admin@test.com',
    'ana.reyes@email.com',
    'carlos.m@email.com',
    'roberto.c@email.com',
    'lisa.t@email.com',
    'mark.v@email.com',
    'grace.l@email.com',
    'james.a@email.com',
];

$stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");

$results = [];
foreach ($accounts as $email) {
    $stmt->execute([':password' => $hash, ':email' => $email]);
    $rows = $stmt->rowCount();
    $results[] = ['email' => $email, 'updated' => $rows > 0];
}

// Verify login works for the 3 main accounts
$verify_stmt = $db->prepare("SELECT password FROM users WHERE email = :email");
$verifications = [];
foreach (['client@test.com', 'engineer@test.com', 'admin@test.com'] as $email) {
    $verify_stmt->execute([':email' => $email]);
    $row = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    $verifications[$email] = $row ? password_verify('123456', $row['password']) : false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Passwords</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 60px auto; padding: 20px; }
        h2 { color: #1a3c5e; }
        .ok  { color: #065f46; background: #d1fae5; padding: 8px 14px; border-radius: 6px; margin: 6px 0; display:flex; justify-content:space-between; }
        .err { color: #991b1b; background: #fee2e2; padding: 8px 14px; border-radius: 6px; margin: 6px 0; display:flex; justify-content:space-between; }
        .box { background: #f0f7ff; border: 2px solid #2d6a9f; border-radius: 10px; padding: 20px; margin-top: 24px; }
        .hash { font-family: monospace; font-size: 12px; word-break: break-all; color: #555; background:#eee; padding:8px; border-radius:4px; margin-top:8px; }
        a.btn { display:inline-block; margin-top:20px; padding:12px 28px; background:#1a3c5e; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; }
    </style>
</head>
<body>
    <h2>🔧 Password Fix Results</h2>
    <p>Hash used: <span class="hash"><?= htmlspecialchars($hash) ?></span></p>

    <h3>Updated Accounts</h3>
    <?php foreach ($results as $r): ?>
    <div class="<?= $r['updated'] ? 'ok' : 'err' ?>">
        <span><?= htmlspecialchars($r['email']) ?></span>
        <strong><?= $r['updated'] ? '✅ Updated' : '⚠️ Not found (skipped)' ?></strong>
    </div>
    <?php endforeach; ?>

    <h3>Login Verification</h3>
    <?php foreach ($verifications as $email => $ok): ?>
    <div class="<?= $ok ? 'ok' : 'err' ?>">
        <span><?= htmlspecialchars($email) ?></span>
        <strong><?= $ok ? '✅ password_verify() PASS' : '❌ password_verify() FAIL' ?></strong>
    </div>
    <?php endforeach; ?>

    <?php if (array_sum(array_column($results, 'updated')) > 0 && !in_array(false, $verifications)): ?>
    <div class="box">
        <h3>✅ All Done!</h3>
        <p>All accounts now use password: <strong>123456</strong></p>
        <table style="width:100%;border-collapse:collapse;margin-top:12px;">
            <tr style="background:#e0f0ff"><th style="padding:8px;text-align:left">Role</th><th style="padding:8px;text-align:left">Email</th><th style="padding:8px;text-align:left">Password</th></tr>
            <tr><td style="padding:8px">Client</td><td style="padding:8px">client@test.com</td><td style="padding:8px"><strong>123456</strong></td></tr>
            <tr><td style="padding:8px">Engineer</td><td style="padding:8px">engineer@test.com</td><td style="padding:8px"><strong>123456</strong></td></tr>
            <tr><td style="padding:8px">Admin</td><td style="padding:8px">admin@test.com</td><td style="padding:8px"><strong>123456</strong></td></tr>
        </table>
        <a href="auth/login.php" class="btn">Go to Login →</a>
        <p style="margin-top:16px;color:#991b1b;font-size:13px;">⚠️ <strong>Delete this file</strong> (fix_passwords.php) after confirming login works.</p>
    </div>
    <?php else: ?>
    <div style="background:#fee2e2;padding:20px;border-radius:10px;margin-top:20px;">
        <h3>❌ Something went wrong</h3>
        <p>Check your database connection in <code>config/database.php</code> and make sure the database is imported.</p>
    </div>
    <?php endif; ?>
</body>
</html>
