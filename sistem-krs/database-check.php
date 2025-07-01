<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$conn = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - Sistem KRS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .warning { color: #ffc107; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Structure Check</h1>
        
        <div class="step">
            <h3>1. Struktur Tabel Mahasiswa Saat Ini</h3>
            <?php
            try {
                $query = "DESCRIBE mahasiswa";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $existing_columns = [];
                foreach ($columns as $column) {
                    $existing_columns[] = $column['Field'];
                    echo "<tr>";
                    echo "<td>" . $column['Field'] . "</td>";
                    echo "<td>" . $column['Type'] . "</td>";
                    echo "<td>" . $column['Null'] . "</td>";
                    echo "<td>" . $column['Key'] . "</td>";
                    echo "<td>" . $column['Default'] . "</td>";
                    echo "<td>" . $column['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span>";
            }
            ?>
        </div>

        <div class="step">
            <h3>2. Check Kolom yang Diperlukan</h3>
            <?php
            $required_columns = [
                'tempat_lahir' => 'VARCHAR(50)',
                'tanggal_lahir' => 'DATE', 
                'jenis_kelamin' => 'ENUM(\'L\', \'P\')',
                'alamat' => 'TEXT',
                'no_telepon' => 'VARCHAR(15)'
            ];
            
            $missing_columns = [];
            
            foreach ($required_columns as $col_name => $col_type) {
                if (in_array($col_name, $existing_columns)) {
                    echo "<span class='success'>✅ Kolom '$col_name' sudah ada</span><br>";
                } else {
                    echo "<span class='error'>❌ Kolom '$col_name' belum ada</span><br>";
                    $missing_columns[$col_name] = $col_type;
                }
            }
            ?>
        </div>

        <?php if (!empty($missing_columns)): ?>
        <div class="step">
            <h3>3. Tambah Kolom yang Hilang</h3>
            <p class="warning">⚠️ Kolom berikut perlu ditambahkan:</p>
            
            <?php foreach ($missing_columns as $col_name => $col_type): ?>
                <div style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                    <strong><?php echo $col_name; ?></strong> (<?php echo $col_type; ?>)
                    <br>
                    <button class="btn btn-primary" onclick="addColumn('<?php echo $col_name; ?>', '<?php echo $col_type; ?>')">
                        Tambah Kolom
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="step">
            <h3>3. Status Database</h3>
            <span class="success">✅ Semua kolom sudah lengkap! Database siap digunakan.</span>
            <br><br>
            <a href="register.php" class="btn btn-success">Test Registrasi</a>
            <a href="login-soft.php" class="btn btn-primary">Ke Halaman Login</a>
        </div>
        <?php endif; ?>

        <div class="step">
            <h3>4. Data Sample Mahasiswa</h3>
            <?php
            try {
                $query = "SELECT id, nim, nama_lengkap, program_studi, angkatan FROM mahasiswa LIMIT 5";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $mahasiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($mahasiswa)) {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>NIM</th><th>Nama</th><th>Prodi</th><th>Angkatan</th></tr>";
                    foreach ($mahasiswa as $mhs) {
                        echo "<tr>";
                        echo "<td>" . $mhs['id'] . "</td>";
                        echo "<td>" . $mhs['nim'] . "</td>";
                        echo "<td>" . $mhs['nama_lengkap'] . "</td>";
                        echo "<td>" . $mhs['program_studi'] . "</td>";
                        echo "<td>" . $mhs['angkatan'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<span class='info'>ℹ️ Belum ada data mahasiswa</span>";
                }
                
            } catch (Exception $e) {
                echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span>";
            }
            ?>
        </div>
    </div>

    <script>
    function addColumn(columnName, columnType) {
        if (confirm('Tambah kolom ' + columnName + '?')) {
            fetch('database-update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_column&column_name=' + columnName + '&column_type=' + encodeURIComponent(columnType)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kolom berhasil ditambahkan!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    }
    </script>
</body>
</html>
