<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

$result = $conn->query("SELECT * FROM resources ORDER BY uploaded_at DESC");

while ($row = $result->fetch_assoc()) {
    echo "<h4>" . htmlspecialchars($row['title']) . " (" . $row['type'] . ")</h4>";
    echo "<p>" . htmlspecialchars($row['description']) . "</p>";
    echo "<a href='" . htmlspecialchars($row['url']) . "' target='_blank'>Open Link</a><hr>";
}
?>