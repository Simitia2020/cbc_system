<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Student Dashboard</title></head>
<body>
    <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (Learner)</h2>
    <a href="../student/view-materials.php">View Revision Materials & Videos</a><br><br>
    <a href="../actions/logout.php">Logout</a>
</body>
</html>