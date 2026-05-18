<?php
session_start();
include("config/db.php");

echo "<h2>Debug Learning Materials</h2>";

// Check if logged in
if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in as user ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    
    // Check materials for this teacher
    $teacher_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM learning_materials WHERE teacher_id = $teacher_id");
    
    echo "<h3>Materials for Teacher ID $teacher_id:</h3>";
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Grade</th><th>Subject</th><th>Type</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['grade']}</td>";
            echo "<td>{$row['subject']}</td>";
            echo "<td>{$row['material_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No materials found for this teacher.<br>";
    }
    
    // Show all materials in database
    echo "<h3>All Materials in Database:</h3>";
    $all = $conn->query("SELECT * FROM learning_materials");
    if ($all->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Teacher ID</th><th>Title</th><th>Grade</th><th>Subject</th></tr>";
        while($row = $all->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['teacher_id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['grade']}</td>";
            echo "<td>{$row['subject']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No materials in database at all.<br>";
    }
    
} else {
    echo "❌ Not logged in. Please login as teacher first.<br>";
}
?>