<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Payment.php';
require_once '../models/Notification.php';
require_once '../helpers/EmailHelper.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$paymentModel = new Payment($db);

$success = '';
$error = '';

// Handle verify/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($payment_id && in_array($action, ['verified', 'rejected'])) {
        if ($paymentModel->verify($payment_id, $action, $_SESSION['user_id'], $notes)) {
            $payment = $paymentModel->getById($payment_id);

            // Notify client
            $notifModel = new Notification($db);
            $notifModel->create(
                $payment['client_id'] ?? 0,
                'Payment ' . ucfirst($action),
                "Your payment of ₱" . number_format($payment['amount'], 2) . " has been " . $action . ".",
                'payment'
            );

            $success = "Payment has been $action successfully.";
        } else {
            $error = 'Failed to update payment status.';
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$payments = $paymentModel->getAll($statusFilter ? ['status' => $statusFilter] : []);
$stats = $paymentModel->getStats();

$selectedId = intval($_GET['id'] ?? 0);
$selectedPayment = null;
if ($selectedId) {
    $selectedPayment = $paymentModel->getById($selectedId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin | GeoSurvey Portal</title>
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
            <div>
                <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
                <p>Verify and manage client payments</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid mini">
            <div class="stat-card mini" style="--accent: #f093fb;">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['pending'] ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
            <div class="stat-card mini" style="--accent: #43e97b;">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['verified'] ?></span>
                    <span class="stat-label">Verified</span>
                </div>
            </div>
            <div class="stat-card mini" style="--accent: #f5576c;">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['rejected'] ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
            </div>
            <div class="stat-card mini" style="--accent: #667eea;">
                <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-info">
                    <span class="stat-value">₱<?= number_format($stats['total_revenue'] ?? 0, 0) ?></span>
                    <span class="stat-label">Total Revenue</span>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="payments.php" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
                <a href="payments.php?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="payments.php?status=verified" class="filter-tab <?= $statusFilter === 'verified' ? 'active' : '' ?>">Verified</a>
                <a href="payments.php?status=rejected" class="filter-tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
            </div>
        </div>

        <div class="admin-layout">
            <!-- Payments List -->
            <div class="admin-list">
                <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No payments found</p>
                </div>
                <?php else: ?>
                <?php foreach ($payments as $pay): ?>
                <a href="payments.php?id=<?= $pay['id'] ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>" 
                   class="admin-list-item <?= $selectedId == $pay['id'] ? 'active' : '' ?>">
                    <div class="admin-item-avatar">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $pay['client_photo'] ?? 'default_avatar.png' ?>" 
                             alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                    </div>
                    <div class="admin-item-info">
                        <strong><?= htmlspecialchars($pay['client_name']) ?></strong>
                        <span><?= htmlspecialchars($pay['service_type']) ?></span>
                        <span class="admin-item-date"><?= date('M d, Y', strtotime($pay['created_at'])) ?></span>
                    </div>
                    <div class="admin-item-right">
                        <span class="pay-amount-sm">₱<?= number_format($pay['amount'], 2) ?></span>
                        <span class="status-badge <?= $pay['status'] ?>"><?= ucfirst($pay['status']) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Payment Detail -->
            <div class="admin-detail">
                <?php if (!$selectedPayment): ?>
                <div class="admin-placeholder">
                    <i class="fas fa-credit-card"></i>
                    <h3>Select a payment</h3>
                    <p>Click on a payment to view details and verify</p>
                </div>
                <?php else: ?>
                <div class="payment-detail-content">
                    <div class="payment-detail-header">
                        <h3>Payment Details</h3>
                        <span class="status-badge-lg <?= $selectedPayment['status'] ?>"><?= ucfirst($selectedPayment['status']) ?></span>
                    </div>

                    <div class="payment-detail-grid">
                        <div class="detail-section">
                            <h4>Client Information</h4>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span>Name</span>
                                    <strong><?= htmlspecialchars($selectedPayment['client_name']) ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>Service</span>
                                    <strong><?= htmlspecialchars($selectedPayment['service_type']) ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>Confirmation</span>
                                    <strong><?= htmlspecialchars($selectedPayment['confirmation_code']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>Payment Information</h4>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span>Amount</span>
                                    <strong class="amount-highlight">₱<?= number_format($selectedPayment['amount'], 2) ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>Method</span>
                                    <strong><?= ucfirst(str_replace('_', ' ', $selectedPayment['payment_method'])) ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>Reference</span>
                                    <strong><?= htmlspecialchars($selectedPayment['reference_number'] ?? 'N/A') ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>Submitted</span>
                                    <strong><?= date('M d, Y h:i A', strtotime($selectedPayment['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($selectedPayment['proof_image']): ?>
                    <div class="proof-section">
                        <h4>Proof of Payment</h4>
                        <div class="proof-image-container">
                            <img src="<?= UPLOADS_URL ?>/payments/<?= $selectedPayment['proof_image'] ?>" 
                                 alt="Payment Proof" class="proof-image">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($selectedPayment['status'] === 'pending'): ?>
                    <div class="verify-actions">
                        <form method="POST" id="verifyForm">
                            <input type="hidden" name="payment_id" value="<?= $selectedPayment['id'] ?>">
                            <div class="form-group">
                                <label>Admin Notes (optional)</label>
                                <textarea name="notes" rows="2" placeholder="Add notes..."></textarea>
                            </div>
                            <div class="verify-buttons">
                                <button type="submit" name="action" value="verified" class="btn-verify">
                                    <i class="fas fa-check"></i> Verify Payment
                                </button>
                                <button type="submit" name="action" value="rejected" class="btn-reject">
                                    <i class="fas fa-times"></i> Reject Payment
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>
