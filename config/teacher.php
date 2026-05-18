<?php
session_start();
if ($_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
}
?>
<link rel="stylesheet" href="../assets/css/style.css">
<h2>Teacher Dashboard</h2>

<ul>
    <li><a href="../teacher/add_assessment.php">Add Assessment</a></li>
    <li><a href="../teacher/upload_material.php">Upload Materials</a></li>
</ul>