<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    http_response_code(403);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$dosen_id = $_GET['dosen_id'] ?? 0;

try {
    $query = "SELECT id_matakuliah FROM dosen_matakuliah WHERE id_dosen = :dosen_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':dosen_id', $dosen_id);
    $stmt->execute();
    
    $matakuliah_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode($matakuliah_ids);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
