<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing MySQL connection...<br><br>";

$conn = new mysqli("localhost", "root", "", "", 3306);

if ($conn->connect_error) {
    echo "❌ Connection FAILED<br>";
    echo "Error: " . $conn->connect_error . "<br>";
    echo "Port: 3306";
} else {
    echo "✅ Connected successfully!<br>";
    $conn->close();
}
?>