<?php
/**
 * Application Configuration
 * Land Surveying Services Portal System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration — auto-detects the folder name so it works anywhere
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_script   = $_SERVER['SCRIPT_NAME'] ?? '';
// Walk up to the project root (the folder containing config/)
$_root     = rtrim(dirname(dirname($_script)), '/\\');
define('BASE_URL',    $_protocol . '://' . $_host . $_root);
define('ASSETS_URL',  BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// File Upload Paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PROFILE_PHOTO_PATH', UPLOAD_PATH . 'profiles/');
define('PAYMENT_PROOF_PATH', UPLOAD_PATH . 'payments/');
define('COMPANY_LOGO_PATH', UPLOAD_PATH . 'companies/');
define('PROGRESS_PHOTO_PATH', UPLOAD_PATH . 'progress/');

// Email Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Change this
define('SMTP_PASSWORD', 'your-app-password'); // Change this - use App Password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Land Surveying Portal');

// AI Configuration (Optional - for future integration)
define('AI_API_KEY', ''); // Add your AI API key here
define('AI_ENABLED', false); // Set to true when AI is configured

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create upload directories if they don't exist
$directories = [
    PROFILE_PHOTO_PATH,
    PAYMENT_PROOF_PATH,
    COMPANY_LOGO_PATH,
    PROGRESS_PHOTO_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Helper function to format date
function formatDate($date) {
    return date('F d, Y', strtotime($date));
}

// Helper function to format datetime
function formatDateTime($datetime) {
    return date('F d, Y h:i A', strtotime($datetime));
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))), 1, $length);
}
