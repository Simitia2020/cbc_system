<?php
$host = getenv("DB_HOST") ?: "127.0.0.1";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "cbc_system";
$port = (int)(getenv("DB_PORT") ?: 3306);

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
