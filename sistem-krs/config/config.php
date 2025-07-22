<?php
// Konfigurasi aplikasi
define('BASE_URL', 'http://localhost/sistem-krs/');
define('APP_NAME', 'Sistem KRS - Universitas Touhou Indonesia');
define('APP_VERSION', '2.0.0');

// Konfigurasi session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Include database dengan path yang benar
require_once __DIR__ . '/database.php';

// Include auth class
require_once __DIR__ . '/../includes/auth.php';

// Fungsi helper
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserData() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'nim' => $_SESSION['nim'] ?? $_SESSION['nomor_induk'] ?? null,
        'nama' => $_SESSION['nama'] ?? $_SESSION['nama_lengkap'] ?? null,
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? null,
        'nomor_induk' => $_SESSION['nomor_induk'] ?? $_SESSION['nim'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

function formatTanggal($tanggal) {
    return date('d/m/Y', strtotime($tanggal));
}

function formatWaktu($waktu) {
    return date('H:i', strtotime($waktu));
}

function getCurrentAcademicYear() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT * FROM tahun_akademik WHERE status = 'Aktif' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['display'] = $result['tahun_akademik'] . ' - Semester ' . $result['semester_akademik'];
        }
        
        return $result ?: ['display' => 'Semester Aktif'];
    } catch (Exception $e) {
        return ['display' => 'Semester Aktif'];
    }
}
?>
