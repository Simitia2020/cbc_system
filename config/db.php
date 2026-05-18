<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "cbc_system";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error . " (Port: " . $port . ")");
}
?>