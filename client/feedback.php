<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);
$engineerModel = new Engineer($db);

$completedAppointments = array_filter(
    $appointmentModel->getByClientId($_SESSION['user_id']),
    fn($a) => $a['status'] === 'completed'
);

$preselectedAppointment = intval($_GET['appointment_id'] ?? 0);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $engineer_id = intval($_POST['engineer_id'] ?? 0);
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (!$engineer_id || !$rating || $rating < 1 || $rating > 5) {
        $error = 'Please provide a valid rating.';
    } else {
        $stmt = $db->prepare("INSERT INTO feedback (client_id, engineer_id, appointment_id, rating, comment) 
                              VALUES (:client_id, :engineer_id, :appointment_id, :rating, :comment)");
        $stmt->execute([
            'client_id' => $_SESSION['user_id'],
            'engineer_id' => $engineer_id,
            'appointment_id' => $appointment_id ?: null,
            'rating' => $rating,
            'comment' => $comment
        ]);

        // Update engineer rating
        $engineerModel->updateRating($engineer_id);

        $success = 'Thank you for your feedback!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/feedback.css">
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
                <h1><i class="fas fa-star"></i> Submit Feedback</h1>
                <p>Rate your experience with our engineers</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="feedback-container">
            <div class="feedback-card">
                <form method="POST" id="feedbackForm">
                    <div class="form-group">
                        <label>Select Completed Appointment</label>
                        <select name="appointment_id" id="appointmentSelect" onchange="loadEngineer(this.value)" required>
                            <option value="">-- Select an appointment --</option>
                            <?php foreach ($completedAppointments as $apt): ?>
                            <option value="<?= $apt['id'] ?>" 
                                    data-engineer-id="<?= $apt['engineer_id'] ?>"
                                    data-engineer-name="<?= htmlspecialchars($apt['engineer_name']) ?>"
                                    data-engineer-photo="<?= $apt['engineer_photo'] ?>"
                                    <?= $preselectedAppointment == $apt['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($apt['service_type']) ?> - <?= date('M d, Y', strtotime($apt['appointment_date'])) ?>
                                (<?= htmlspecialchars($apt['engineer_name']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="engineer_id" id="engineerId">

                    <div id="engineerPreview" style="display:none;" class="engineer-preview">
                        <img id="engPhoto" src="" alt="">
                        <div>
                            <strong id="engName"></strong>
                            <span>Rate your experience</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="rating-input" id="ratingInput">
                            <input type="radio" name="rating" value="1" id="star1" required>
                            <label for="star1" onclick="setRating(1)"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2" onclick="setRating(2)"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3" onclick="setRating(3)"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4" onclick="setRating(4)"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="5" id="star5">
                            <label for="star5" onclick="setRating(5)"><i class="fas fa-star"></i></label>
                        </div>
                        <div class="rating-labels">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="comment" rows="5" placeholder="Share your experience with this engineer..."><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit-feedback">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function loadEngineer(aptId) {
    const select = document.getElementById('appointmentSelect');
    const option = select.options[select.selectedIndex];
    if (aptId) {
        document.getElementById('engineerId').value = option.dataset.engineerId;
        document.getElementById('engPhoto').src = '<?= UPLOADS_URL ?>/profiles/' + option.dataset.engineerPhoto;
        document.getElementById('engName').textContent = option.dataset.engineerName;
        document.getElementById('engineerPreview').style.display = 'flex';
    } else {
        document.getElementById('engineerPreview').style.display = 'none';
    }
}

function setRating(rating) {
    for (let i = 1; i <= 5; i++) {
        const label = document.querySelector(`label[for="star${i}"]`);
        if (i <= rating) {
            label.classList.add('filled');
        } else {
            label.classList.remove('filled');
        }
    }
}

if (<?= $preselectedAppointment ?>) {
    loadEngineer(<?= $preselectedAppointment ?>);
}
</script>
</body>
</html>
