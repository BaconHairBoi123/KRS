<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$conn = $database->getConnection();

echo "<!DOCTYPE html>
<html><head><title>Fix Database</title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style></head><body>";

echo "<h1>🔧 Database Fix Script</h1>";

try {
    // 1. Check existing columns
    echo "<h3>1. Checking existing columns...</h3>";
    $query = "SHOW COLUMNS FROM mahasiswa";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>Existing columns: " . implode(', ', $existing_columns) . "</div><br>";
    
    // 2. Define required columns
    $required_columns = [
        'tempat_lahir' => ['type' => 'VARCHAR(50)', 'after' => 'nama_lengkap'],
        'tanggal_lahir' => ['type' => 'DATE', 'after' => 'tempat_lahir'],
        'jenis_kelamin' => ['type' => 'ENUM(\'L\', \'P\')', 'after' => 'tanggal_lahir'],
        'alamat' => ['type' => 'TEXT', 'after' => 'jenis_kelamin'],
        'no_telepon' => ['type' => 'VARCHAR(15)', 'after' => 'alamat']
    ];
    
    // 3. Add missing columns
    echo "<h3>2. Adding missing columns...</h3>";
    foreach ($required_columns as $column_name => $column_info) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $sql = "ALTER TABLE mahasiswa ADD COLUMN $column_name {$column_info['type']} AFTER {$column_info['after']}";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                echo "<div class='success'>✅ Added column: $column_name</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Failed to add $column_name: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ Column $column_name already exists</div>";
        }
    }
    
    // 4. Update existing data
    echo "<h3>3. Updating existing data...</h3>";
    $update_sql = "UPDATE mahasiswa SET 
                   tempat_lahir = COALESCE(tempat_lahir, 'Jakarta'),
                   tanggal_lahir = COALESCE(tanggal_lahir, '2000-01-01'),
                   jenis_kelamin = COALESCE(jenis_kelamin, 'L'),
                   alamat = COALESCE(alamat, 'Jakarta, Indonesia'),
                   no_telepon = COALESCE(no_telepon, '081234567890')
                   WHERE id IS NOT NULL";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "<div class='success'>✅ Updated $affected records with default values</div>";
    
    echo "<h3>4. Database Fix Complete!</h3>";
    echo "<div class='success'>✅ Database is now ready for registration system</div>";
    echo "<br><a href='register.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Test Registration</a>";
    echo " <a href='login-soft.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
