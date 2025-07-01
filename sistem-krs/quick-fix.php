<?php
// Script PHP sederhana untuk fix database
require_once __DIR__ . '/config/config.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h1>Quick Database Fix</h1>";

// Daftar kolom yang perlu ditambahkan
$columns_to_add = [
    'tempat_lahir' => "VARCHAR(50) AFTER nama_lengkap",
    'tanggal_lahir' => "DATE AFTER tempat_lahir", 
    'jenis_kelamin' => "ENUM('L', 'P') AFTER tanggal_lahir",
    'alamat' => "TEXT AFTER jenis_kelamin",
    'no_telepon' => "VARCHAR(15) AFTER alamat"
];

foreach ($columns_to_add as $column_name => $column_def) {
    try {
        // Cek apakah kolom sudah ada
        $check_sql = "SHOW COLUMNS FROM mahasiswa LIKE '$column_name'";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Kolom belum ada, tambahkan
            $add_sql = "ALTER TABLE mahasiswa ADD COLUMN $column_name $column_def";
            $conn->exec($add_sql);
            echo "✅ Added column: $column_name<br>";
        } else {
            echo "ℹ️ Column $column_name already exists<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error with $column_name: " . $e->getMessage() . "<br>";
    }
}

// Update data existing
try {
    $update_sql = "UPDATE mahasiswa SET 
                   tempat_lahir = COALESCE(tempat_lahir, 'Jakarta'),
                   tanggal_lahir = COALESCE(tanggal_lahir, '2000-01-01'),
                   jenis_kelamin = COALESCE(jenis_kelamin, 'L'),
                   alamat = COALESCE(alamat, 'Jakarta, Indonesia'),
                   no_telepon = COALESCE(no_telepon, '081234567890')";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->execute();
    echo "✅ Updated existing data<br>";
} catch (Exception $e) {
    echo "❌ Update error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Database fix completed!</strong><br>";
echo "<a href='register.php'>Test Registration</a> | ";
echo "<a href='login-soft.php'>Go to Login</a>";
?>
