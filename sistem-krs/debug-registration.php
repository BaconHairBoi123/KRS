<?php
require_once __DIR__ . '/config/database.php';

// Test database connection and table structure
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed!");
}

echo "<h2>Database Debug Information</h2>";

// Check if tables exist
$tables = ['users', 'mahasiswa', 'dosen'];
foreach ($tables as $table) {
    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Table '$table' exists</p>";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE $table");
        echo "<h4>Structure of $table:</h4>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Show existing data count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Records in $table: " . $count['count'] . "</p>";
        
        // Show sample data if exists
        if ($count['count'] > 0) {
            $stmt = $conn->query("SELECT * FROM $table LIMIT 3");
            echo "<h4>Sample data from $table:</h4>";
            echo "<table border='1'>";
            $first = true;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $key) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br>";
        }
        
    } else {
        echo "<p>❌ Table '$table' does not exist</p>";
    }
}

// Test a simple registration check
echo "<h3>Testing Registration Check</h3>";
$testNim = "123456789";
$testEmail = "test@example.com";

$checkQuery = "SELECT nim, email FROM mahasiswa WHERE nim = :nim OR email = :email";
$checkStmt = $conn->prepare($checkQuery);

if ($checkStmt) {
    $checkStmt->bindValue(':nim', $testNim);
    $checkStmt->bindValue(':email', $testEmail);
    
    if ($checkStmt->execute()) {
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "<p>❌ Test data already exists: NIM=" . $result['nim'] . ", Email=" . $result['email'] . "</p>";
        } else {
            echo "<p>✅ Test data does not exist - registration should work</p>";
        }
    } else {
        echo "<p>❌ Failed to execute check query: " . implode(", ", $checkStmt->errorInfo()) . "</p>";
    }
} else {
    echo "<p>❌ Failed to prepare check query: " . implode(", ", $conn->errorInfo()) . "</p>";
}
?>