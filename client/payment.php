<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Payment.php';
require_once '../models/Appointment.php';
require_once '../models/Notification.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$paymentModel = new Payment($db);
$appointmentModel = new Appointment($db);

$appointments = $appointmentModel->getByClientId($_SESSION['user_id']);
$payments = $paymentModel->getByClientId($_SESSION['user_id']);

$preselectedAppointment = intval($_GET['appointment_id'] ?? 0);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = trim($_POST['reference_number'] ?? '');

    if (!$appointment_id || !$amount || !$payment_method) {
        $error = 'Please fill in all required fields.';
    } else {
        $proof_image = null;
        if (!empty($_FILES['proof_image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'payment_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], PAYMENT_PROOF_PATH . $filename)) {
                    $proof_image = $filename;
                }
            }
        }

        $data = [
            'appointment_id' => $appointment_id,
            'client_id' => $_SESSION['user_id'],
            'amount' => $amount,
            'payment_method' => $payment_method,
            'reference_number' => $reference_number,
            'proof_image' => $proof_image,
        ];

        $paymentId = $paymentModel->create($data);

        if ($paymentId) {
            $notifModel = new Notification($db);
            $notifModel->create(
                $_SESSION['user_id'],
                'Payment Submitted',
                "Your payment of ₱" . number_format($amount, 2) . " has been submitted and is pending verification.",
                'payment'
            );

            // Notify admin (user_id 3 is admin in seed data)
            $notifModel->create(3, 'New Payment Pending', 
                "A payment of ₱" . number_format($amount, 2) . " from " . $_SESSION['name'] . " is pending verification.",
                'payment'
            );

            $success = 'Payment submitted successfully! It will be verified within 24 hours.';
        } else {
            $error = 'Failed to submit payment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/payment.css">
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
                <h1><i class="fas fa-credit-card"></i> Payments</h1>
                <p>Submit and track your payments</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="payment-layout">
            <!-- Submit Payment Form -->
            <div class="payment-form-section">
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h3><i class="fas fa-plus-circle"></i> Submit Payment</h3>
                        <p>Upload your payment proof for verification</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <!-- Step 1: Select Appointment -->
                        <div class="payment-step active" id="pay-step-1">
                            <div class="pay-step-header">
                                <div class="pay-step-num">1</div>
                                <h4>Select Appointment</h4>
                            </div>
                            <div class="form-group">
                                <select name="appointment_id" id="appointmentSelect" onchange="loadAppointmentDetails(this.value)" required>
                                    <option value="">-- Select an appointment --</option>
                                    <?php foreach ($appointments as $apt): ?>
                                    <?php if ($apt['status'] !== 'cancelled'): ?>
                                    <option value="<?= $apt['id'] ?>" 
                                            data-amount="<?= $apt['total_amount'] ?>"
                                            data-service="<?= htmlspecialchars($apt['service_type']) ?>"
                                            <?= $preselectedAppointment == $apt['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($apt['service_type']) ?> - <?= date('M d, Y', strtotime($apt['appointment_date'])) ?> 
                                        (<?= htmlspecialchars($apt['engineer_name']) ?>)
                                    </option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="appointment-detail-preview" id="aptPreview" style="display:none;">
                                <div class="apt-preview-row">
                                    <span>Service</span><strong id="prev-service">-</strong>
                                </div>
                                <div class="apt-preview-row">
                                    <span>Amount Due</span><strong id="prev-amount" class="amount-highlight">-</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Payment Method -->
                        <div class="payment-step" id="pay-step-2">
                            <div class="pay-step-header">
                                <div class="pay-step-num">2</div>
                                <h4>Payment Method</h4>
                            </div>
                            <div class="payment-methods">
                                <div class="payment-method-card" onclick="selectPaymentMethod('gcash')">
                                    <input type="radio" name="payment_method" value="gcash" style="display:none">
                                    <div class="pm-icon gcash"><i class="fas fa-mobile-alt"></i></div>
                                    <span>GCash</span>
                                    <div class="pm-check"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <div class="payment-method-card" onclick="selectPaymentMethod('bank_transfer')">
                                    <input type="radio" name="payment_method" value="bank_transfer" style="display:none">
                                    <div class="pm-icon bank"><i class="fas fa-university"></i></div>
                                    <span>Bank Transfer</span>
                                    <div class="pm-check"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <div class="payment-method-card" onclick="selectPaymentMethod('credit_card')">
                                    <input type="radio" name="payment_method" value="credit_card" style="display:none">
                                    <div class="pm-icon card"><i class="fas fa-credit-card"></i></div>
                                    <span>Credit Card</span>
                                    <div class="pm-check"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <div class="payment-method-card" onclick="selectPaymentMethod('cash')">
                                    <input type="radio" name="payment_method" value="cash" style="display:none">
                                    <div class="pm-icon cash"><i class="fas fa-money-bill-wave"></i></div>
                                    <span>Cash</span>
                                    <div class="pm-check"><i class="fas fa-check-circle"></i></div>
                                </div>
                            </div>

                            <!-- Payment Instructions -->
                            <div class="payment-instructions" id="paymentInstructions" style="display:none;">
                                <div id="gcash-instructions" class="pm-instructions" style="display:none;">
                                    <h5><i class="fas fa-mobile-alt"></i> GCash Payment</h5>
                                    <p>Send payment to: <strong>0917-123-4567</strong> (LandSurvey Portal)</p>
                                </div>
                                <div id="bank_transfer-instructions" class="pm-instructions" style="display:none;">
                                    <h5><i class="fas fa-university"></i> Bank Transfer</h5>
                                    <p>BDO Account: <strong>1234-5678-9012</strong><br>Account Name: <strong>LandSurvey Portal Inc.</strong></p>
                                </div>
                                <div id="credit_card-instructions" class="pm-instructions" style="display:none;">
                                    <h5><i class="fas fa-credit-card"></i> Credit Card</h5>
                                    <p>Pay via our secure payment gateway. Upload screenshot as proof.</p>
                                </div>
                                <div id="cash-instructions" class="pm-instructions" style="display:none;">
                                    <h5><i class="fas fa-money-bill-wave"></i> Cash Payment</h5>
                                    <p>Pay directly to the engineer on the day of the survey. Upload receipt as proof.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Amount & Reference -->
                        <div class="payment-step" id="pay-step-3">
                            <div class="pay-step-header">
                                <div class="pay-step-num">3</div>
                                <h4>Payment Details</h4>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Amount Paid (₱) *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-peso-sign input-icon"></i>
                                        <input type="number" name="amount" id="amountInput" placeholder="0.00" step="0.01" min="1" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Reference Number</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-hashtag input-icon"></i>
                                        <input type="text" name="reference_number" placeholder="Transaction reference">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Upload Proof -->
                        <div class="payment-step" id="pay-step-4">
                            <div class="pay-step-header">
                                <div class="pay-step-num">4</div>
                                <h4>Upload Proof of Payment</h4>
                            </div>
                            <div class="proof-upload" id="proofUpload">
                                <input type="file" name="proof_image" id="proofInput" accept="image/*,.pdf" onchange="previewProof(this)">
                                <div class="proof-preview" id="proofPreview">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click or drag to upload screenshot/receipt</p>
                                    <span>JPG, PNG, PDF up to 5MB</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit-payment" id="submitPayBtn">
                            <i class="fas fa-paper-plane"></i> Submit Payment
                        </button>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <div class="payment-history-section">
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h3><i class="fas fa-history"></i> Payment History</h3>
                    </div>
                    <?php if (empty($payments)): ?>
                    <div class="empty-state small">
                        <i class="fas fa-receipt"></i>
                        <p>No payment records yet</p>
                    </div>
                    <?php else: ?>
                    <div class="payment-history-list">
                        <?php foreach ($payments as $pay): ?>
                        <div class="payment-history-item">
                            <div class="pay-hist-icon <?= $pay['payment_method'] ?>">
                                <i class="fas <?= $pay['payment_method'] === 'gcash' ? 'fa-mobile-alt' : ($pay['payment_method'] === 'bank_transfer' ? 'fa-university' : 'fa-credit-card') ?>"></i>
                            </div>
                            <div class="pay-hist-info">
                                <strong><?= htmlspecialchars($pay['service_type']) ?></strong>
                                <span><?= ucfirst(str_replace('_', ' ', $pay['payment_method'])) ?></span>
                                <span class="pay-hist-date"><?= date('M d, Y', strtotime($pay['created_at'])) ?></span>
                            </div>
                            <div class="pay-hist-right">
                                <span class="pay-hist-amount">₱<?= number_format($pay['amount'], 2) ?></span>
                                <span class="status-badge <?= $pay['status'] ?>"><?= ucfirst($pay['status']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function loadAppointmentDetails(id) {
    const select = document.getElementById('appointmentSelect');
    const option = select.options[select.selectedIndex];
    if (id) {
        document.getElementById('aptPreview').style.display = 'block';
        document.getElementById('prev-service').textContent = option.dataset.service;
        document.getElementById('prev-amount').textContent = '₱' + parseFloat(option.dataset.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
        document.getElementById('amountInput').value = option.dataset.amount;
    } else {
        document.getElementById('aptPreview').style.display = 'none';
    }
}

function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('input[type=radio]').checked = true;
    
    document.getElementById('paymentInstructions').style.display = 'block';
    document.querySelectorAll('.pm-instructions').forEach(el => el.style.display = 'none');
    document.getElementById(method + '-instructions').style.display = 'block';
}

function previewProof(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById('proofPreview');
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:200px;max-height:150px;border-radius:8px;">';
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '<i class="fas fa-file-pdf" style="font-size:3rem;color:#e74c3c;"></i><p>' + file.name + '</p>';
        }
    }
}

// Pre-select appointment if passed via URL
if (<?= $preselectedAppointment ?>) {
    document.getElementById('appointmentSelect').value = <?= $preselectedAppointment ?>;
    loadAppointmentDetails(<?= $preselectedAppointment ?>);
}
</script>
</body>
</html>
