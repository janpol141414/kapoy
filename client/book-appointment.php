<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Engineer.php';
require_once '../models/Schedule.php';
require_once '../models/Notification.php';
require_once '../helpers/EmailHelper.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$appointmentModel = new Appointment($db);
$scheduleModel = new Schedule($db);

$preselectedEngineer = intval($_GET['engineer_id'] ?? 0);
$preselectedService = $_GET['service'] ?? '';
$preselectedDate = $_GET['date'] ?? '';

$engineers = $engineerModel->getAll(['availability' => 'available']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $engineer_id = intval($_POST['engineer_id'] ?? 0);
    $service_type = trim($_POST['service_type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$engineer_id || !$service_type || !$location || !$appointment_date || !$appointment_time) {
        $error = 'Please fill in all required fields.';
    } else {
        // Get service price
        $servicePrices = [
            'Boundary Survey' => 5000,
            'Topographic Survey' => 8000,
            'Construction Layout' => 6000,
            'Subdivision Survey' => 15000,
            'Route Survey' => 12000,
            'Hydrographic Survey' => 20000,
            'Geodetic Survey' => 25000,
            'As-Built Survey' => 7000,
        ];
        $total_amount = $servicePrices[$service_type] ?? 5000;

        $data = [
            'client_id' => $_SESSION['user_id'],
            'engineer_id' => $engineer_id,
            'schedule_id' => null,
            'service_type' => $service_type,
            'location' => $location,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'notes' => $notes,
            'total_amount' => $total_amount,
            'ai_suggested' => 0,
        ];

        $appointmentId = $appointmentModel->create($data);

        if ($appointmentId) {
            // Create notifications
            $notifModel = new Notification($db);
            $appointment = $appointmentModel->getById($appointmentId);

            // Notify client
            $notifModel->create(
                $_SESSION['user_id'],
                'Appointment Booked',
                "Your appointment for {$service_type} has been booked. Confirmation: {$appointment['confirmation_code']}",
                'appointment',
                BASE_URL . '/client/track-status.php?id=' . $appointmentId
            );

            // Notify engineer
            $notifModel->create(
                $appointment['engineer_id'],
                'New Appointment Assigned',
                "You have a new appointment for {$service_type} on " . date('M d, Y', strtotime($appointment_date)),
                'appointment'
            );

            // Send email
            EmailHelper::sendAppointmentConfirmation(
                $_SESSION['email'],
                $_SESSION['name'],
                $appointment
            );

            $success = "Appointment booked successfully! Confirmation code: <strong>{$appointment['confirmation_code']}</strong>";
        } else {
            $error = 'Failed to book appointment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
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
                <h1><i class="fas fa-calendar-plus"></i> Book Appointment</h1>
                <p>Schedule a survey with a licensed engineer</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success booking-success">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <div>
                <h4>Booking Confirmed!</h4>
                <p><?= $success ?></p>
                <div class="success-actions">
                    <a href="track-status.php" class="btn-primary">Track Status</a>
                    <a href="book-appointment.php" class="btn-outline">Book Another</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <!-- Booking Steps -->
        <div class="booking-steps">
            <div class="step active" id="step-indicator-1">
                <div class="step-circle">1</div>
                <span>Select Engineer</span>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-circle">2</div>
                <span>Choose Service</span>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-3">
                <div class="step-circle">3</div>
                <span>Pick Date & Time</span>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-4">
                <div class="step-circle">4</div>
                <span>Confirm</span>
            </div>
        </div>

        <form method="POST" id="bookingForm">
            <!-- Step 1: Select Engineer -->
            <div class="booking-step-content active" id="step-1">
                <div class="booking-card">
                    <h3><i class="fas fa-hard-hat"></i> Select an Engineer</h3>
                    <p class="step-desc">Choose a licensed geodetic engineer for your project</p>

                    <div class="ai-suggestion-box">
                        <div class="ai-icon"><i class="fas fa-robot"></i></div>
                        <div>
                            <strong>AI Suggestion</strong>
                            <p>Based on availability and ratings, we recommend <strong>Engr. Maria Santos</strong> or <strong>Engr. Roberto Cruz</strong> for your project.</p>
                        </div>
                    </div>

                    <div class="engineer-select-grid">
                        <?php foreach ($engineers as $eng): ?>
                        <div class="engineer-select-card <?= $preselectedEngineer == $eng['id'] ? 'selected' : '' ?>" 
                             onclick="selectEngineer(<?= $eng['id'] ?>, '<?= htmlspecialchars($eng['name']) ?>')">
                            <input type="radio" name="engineer_id" value="<?= $eng['id'] ?>" 
                                   <?= $preselectedEngineer == $eng['id'] ? 'checked' : '' ?> style="display:none">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>" 
                                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <div class="eng-select-info">
                                <strong><?= htmlspecialchars($eng['name']) ?></strong>
                                <span><?= htmlspecialchars($eng['specialization']) ?></span>
                                <div class="eng-select-rating">
                                    <i class="fas fa-star filled"></i> <?= number_format($eng['rating'], 1) ?>
                                    <span class="eng-select-rate">₱<?= number_format($eng['hourly_rate'], 0) ?>/hr</span>
                                </div>
                            </div>
                            <div class="select-check"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-next" onclick="nextStep(2)">
                        Continue <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Service & Details -->
            <div class="booking-step-content" id="step-2">
                <div class="booking-card">
                    <h3><i class="fas fa-map"></i> Service Details</h3>

                    <div class="form-group">
                        <label>Service Type *</label>
                        <div class="service-select-grid">
                            <?php
                            $services = [
                                ['name' => 'Boundary Survey', 'icon' => 'fa-border-all', 'price' => '₱5,000', 'color' => '#667eea'],
                                ['name' => 'Topographic Survey', 'icon' => 'fa-mountain', 'price' => '₱8,000', 'color' => '#f093fb'],
                                ['name' => 'Construction Layout', 'icon' => 'fa-building', 'price' => '₱6,000', 'color' => '#4facfe'],
                                ['name' => 'Subdivision Survey', 'icon' => 'fa-th-large', 'price' => '₱15,000', 'color' => '#43e97b'],
                                ['name' => 'Route Survey', 'icon' => 'fa-road', 'price' => '₱12,000', 'color' => '#fa709a'],
                                ['name' => 'Geodetic Survey', 'icon' => 'fa-globe', 'price' => '₱25,000', 'color' => '#a18cd1'],
                                ['name' => 'Hydrographic Survey', 'icon' => 'fa-water', 'price' => '₱20,000', 'color' => '#4facfe'],
                                ['name' => 'As-Built Survey', 'icon' => 'fa-drafting-compass', 'price' => '₱7,000', 'color' => '#f5576c'],
                            ];
                            foreach ($services as $svc):
                            ?>
                            <div class="service-select-card <?= $preselectedService === $svc['name'] ? 'selected' : '' ?>"
                                 onclick="selectService('<?= $svc['name'] ?>')">
                                <input type="radio" name="service_type" value="<?= $svc['name'] ?>"
                                       <?= $preselectedService === $svc['name'] ? 'checked' : '' ?> style="display:none">
                                <div class="svc-select-icon" style="background: <?= $svc['color'] ?>20; color: <?= $svc['color'] ?>">
                                    <i class="fas <?= $svc['icon'] ?>"></i>
                                </div>
                                <span class="svc-select-name"><?= $svc['name'] ?></span>
                                <span class="svc-select-price"><?= $svc['price'] ?>+</span>
                                <div class="select-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Survey Location *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" name="location" placeholder="Enter the survey location address" required
                                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Additional Notes</label>
                        <textarea name="notes" rows="3" placeholder="Any special instructions or requirements..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="step-nav">
                        <button type="button" class="btn-prev" onclick="prevStep(1)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-next" onclick="nextStep(3)">
                            Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Calendar -->
            <div class="booking-step-content" id="step-3">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-alt"></i> Select Date & Time</h3>

                    <div class="booking-calendar-layout">
                        <div class="calendar-section">
                            <div class="calendar-header">
                                <button type="button" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <span id="calendarTitle"></span>
                                <button type="button" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-legend">
                                <span><span class="legend-dot available"></span> Available</span>
                                <span><span class="legend-dot unavailable"></span> Unavailable</span>
                                <span><span class="legend-dot selected"></span> Selected</span>
                            </div>
                            <div class="calendar-grid" id="calendarGrid"></div>
                            <input type="hidden" name="appointment_date" id="selectedDate" value="<?= $preselectedDate ?>">
                        </div>

                        <div class="time-section">
                            <h4><i class="fas fa-clock"></i> Available Time Slots</h4>
                            <div id="timeSlots" class="time-slots-grid">
                                <div class="time-placeholder">
                                    <i class="fas fa-calendar-day"></i>
                                    <p>Select a date to see available time slots</p>
                                </div>
                            </div>
                            <input type="hidden" name="appointment_time" id="selectedTime">

                            <div class="ai-time-suggestion" id="aiTimeSuggestion" style="display:none;">
                                <div class="ai-icon"><i class="fas fa-robot"></i></div>
                                <div>
                                    <strong>AI Recommendation</strong>
                                    <p id="aiSuggestionText"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="step-nav">
                        <button type="button" class="btn-prev" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-next" onclick="nextStep(4)">
                            Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Confirm -->
            <div class="booking-step-content" id="step-4">
                <div class="booking-card">
                    <h3><i class="fas fa-check-circle"></i> Confirm Booking</h3>

                    <div class="booking-summary">
                        <div class="summary-row">
                            <span class="summary-label"><i class="fas fa-hard-hat"></i> Engineer</span>
                            <span class="summary-value" id="summary-engineer">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="fas fa-map"></i> Service</span>
                            <span class="summary-value" id="summary-service">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="fas fa-calendar"></i> Date</span>
                            <span class="summary-value" id="summary-date">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="fas fa-clock"></i> Time</span>
                            <span class="summary-value" id="summary-time">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                            <span class="summary-value" id="summary-location">-</span>
                        </div>
                        <div class="summary-row total-row">
                            <span class="summary-label"><i class="fas fa-peso-sign"></i> Estimated Cost</span>
                            <span class="summary-value total-value" id="summary-total">-</span>
                        </div>
                    </div>

                    <div class="booking-note">
                        <i class="fas fa-info-circle"></i>
                        <p>A confirmation email will be sent to <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>. Payment can be submitted after booking.</p>
                    </div>

                    <div class="step-nav">
                        <button type="button" class="btn-prev" onclick="prevStep(3)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-confirm" id="confirmBtn">
                            <i class="fas fa-calendar-check"></i> Confirm Booking
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/booking.js"></script>
<script src="../assets/js/chatbot.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const PRESELECTED_ENGINEER = <?= $preselectedEngineer ?>;
const PRESELECTED_DATE = '<?= $preselectedDate ?>';
</script>
</body>
</html>
