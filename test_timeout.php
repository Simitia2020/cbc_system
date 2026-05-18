<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>MySQL Connection Test with Timeout</h2>";

$start = microtime(true);

$conn = new mysqli();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);   // 5 seconds timeout

if ($conn->real_connect("localhost", "root", "", "", 3306)) {
    echo "✅ SUCCESS! Connected to MySQL on port 3306<br>";
    echo "Time taken: " . (microtime(true) - $start) . " seconds";
    $conn->close();
} else {
    echo "❌ FAILED to connect<br>";
    echo "Error: " . $conn->connect_error . "<br>";
    echo "Error Code: " . $conn->connect_errno . "<br>";
    echo "Time taken: " . (microtime(true) - $start) . " seconds";
}
?>