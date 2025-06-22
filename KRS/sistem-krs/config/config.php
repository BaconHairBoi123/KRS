<?php
// Konfigurasi aplikasi
define('BASE_URL', 'http://localhost/sistem-krs/');
define('APP_NAME', 'Sistem KRS Online');
define('APP_VERSION', '1.0.0');

// Konfigurasi session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Include database dengan path yang benar
require_once __DIR__ . '/database.php';

// Fungsi helper
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login-soft.php');
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function formatTanggal($tanggal) {
    return date('d/m/Y', strtotime($tanggal));
}

function formatWaktu($waktu) {
    return date('H:i', strtotime($waktu));
}
?>
