<?php
// File untuk test koneksi database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=sistem_krs", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "✅ Found " . $result['total'] . " users in database<br>";
    
    echo "<br><a href='login.php'>Go to Login Page</a>";
    
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
    echo "<br><br>Troubleshooting:";
    echo "<br>1. Make sure XAMPP MySQL is running";
    echo "<br>2. Create database 'sistem_krs' in phpMyAdmin";
    echo "<br>3. Import the SQL file";
}
?>
