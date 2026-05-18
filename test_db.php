<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Connection Test</h2>";

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cbc_kenya";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    echo "<h3 style='color:red;'>❌ Connection Failed</h3>";
    echo "Error: " . $conn->connect_error . "<br>";
    echo "Port used: " . $port . "<br>";
} else {
    echo "<h3 style='color:green;'>✅ SUCCESS! Connected to MySQL successfully.</h3>";
    echo "Database: " . $dbname . "<br>";
    echo "Port: " . $port . "<br>";
    
    // Check if cbc_kenya database exists
    $result = $conn->query("SHOW DATABASES LIKE 'cbc_kenya'");
    if ($result->num_rows > 0) {
        echo "✅ Database 'cbc_kenya' exists.<br>";
    } else {
        echo "⚠️ Database 'cbc_kenya' does not exist yet.<br>";
    }
}

echo "<br><a href='index.php'>Go to Login Page</a>";
?>