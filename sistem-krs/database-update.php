<?php
require_once __DIR__ . '/config/config.php';

if ($_POST && isset($_POST['action'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'add_column') {
        $column_name = $_POST['column_name'];
        $column_type = $_POST['column_type'];
        
        try {
            // Mapping posisi kolom
            $position_map = [
                'tempat_lahir' => 'AFTER nama_lengkap',
                'tanggal_lahir' => 'AFTER tempat_lahir', 
                'jenis_kelamin' => 'AFTER tanggal_lahir',
                'alamat' => 'AFTER jenis_kelamin',
                'no_telepon' => 'AFTER alamat'
            ];
            
            $position = $position_map[$column_name] ?? '';
            
            $sql = "ALTER TABLE mahasiswa ADD COLUMN $column_name $column_type $position";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Kolom berhasil ditambahkan']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    exit;
}
?>
