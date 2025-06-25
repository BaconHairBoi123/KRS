<?php
require_once __DIR__ . '/config/config.php';

// AJAX endpoint untuk check username, email, NIM
if ($_POST && isset($_POST['action'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'check_username') {
        $username = $_POST['username'];
        $query = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $exists = $stmt->fetchColumn() > 0;
        echo json_encode(['exists' => $exists]);
        exit;
    }
    
    if ($_POST['action'] == 'check_email') {
        $email = $_POST['email'];
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $exists = $stmt->fetchColumn() > 0;
        echo json_encode(['exists' => $exists]);
        exit;
    }
    
    if ($_POST['action'] == 'check_nim') {
        $nim = $_POST['nim'];
        $query = "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nim', $nim);
        $stmt->execute();
        $exists = $stmt->fetchColumn() > 0;
        echo json_encode(['exists' => $exists]);
        exit;
    }
}
?>
