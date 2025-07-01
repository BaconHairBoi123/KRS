<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Setup - Sistem KRS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Setup Test - Sistem KRS</h1>
        
        <div class="step">
            <h3>1. Test File Structure</h3>
            <?php
            $files = [
                'config/config.php',
                'config/database.php', 
                'includes/auth.php',
                'login-soft.php'
            ];
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    echo "<span class='success'>✅ $file - EXISTS</span><br>";
                } else {
                    echo "<span class='error'>❌ $file - MISSING</span><br>";
                }
            }
            ?>
        </div>

        <div class="step">
            <h3>2. Test Database Connection</h3>
            <?php
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=sistem_krs", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<span class='success'>✅ Database connection successful!</span><br>";
                
                // Test tables
                $tables = ['users', 'mahasiswa', 'dosen', 'administrator'];
                foreach ($tables as $table) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                        $count = $stmt->fetchColumn();
                        echo "<span class='success'>✅ Table '$table' exists ($count records)</span><br>";
                    } catch (Exception $e) {
                        echo "<span class='error'>❌ Table '$table' missing</span><br>";
                    }
                }
                
            } catch(PDOException $e) {
                echo "<span class='error'>❌ Database connection failed: " . $e->getMessage() . "</span><br>";
                echo "<div class='info'>";
                echo "<strong>Troubleshooting:</strong><br>";
                echo "1. Make sure XAMPP MySQL is running<br>";
                echo "2. Create database 'sistem_krs' in phpMyAdmin<br>";
                echo "3. Import the SQL file<br>";
                echo "</div>";
            }
            ?>
        </div>

        <div class="step">
            <h3>3. Test PHP Configuration</h3>
            <?php
            echo "<span class='info'>PHP Version: " . phpversion() . "</span><br>";
            echo "<span class='info'>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</span><br>";
            
            if (extension_loaded('pdo_mysql')) {
                echo "<span class='success'>✅ PDO MySQL extension loaded</span><br>";
            } else {
                echo "<span class='error'>❌ PDO MySQL extension not loaded</span><br>";
            }
            ?>
        </div>

        <div class="step">
            <h3>4. Next Steps</h3>
            <p>If all tests pass:</p>
            <ul>
                <li><a href="login-soft.php">🚀 Go to Login Page</a></li>
                <li><a href="index.php">🏠 Go to Home Page</a></li>
            </ul>
            
            <p><strong>Demo Login Credentials:</strong></p>
            <ul>
                <li>Username: <code>mahasiswa1</code>, Password: <code>password</code></li>
                <li>Username: <code>dosen1</code>, Password: <code>password</code></li>
                <li>Username: <code>admin</code>, Password: <code>password</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
