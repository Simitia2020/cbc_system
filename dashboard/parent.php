<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$parent_id = $_SESSION['user_id'];
$linked_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE parent_id = $parent_id")->fetch_assoc()['count'];
?>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 25px; border-radius: 12px; text-align: center;">
        <h2 style="font-size: 48px; margin: 0;"><?= $linked_count ?></h2>
        <p style="margin: 10px 0 0;">Linked Children</p>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
        <a href="../parent/link_child.php" style="display: block; padding: 15px; background: #00a651; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            🔗 Link Your Child (Use Admission No)
        </a>
        <a href="../parent/grading_system.php" style="display: block; padding: 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            📘 CBC Grading System
        </a>
        <a href="../parent/pathways.php" style="display: block; padding: 15px; background: #FF9800; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            🛤️ Learning Pathways
        </a>
    </div>
</div>

</div>
</body>
</html>