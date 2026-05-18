<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include("../includes/sidebar.php");

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch_assoc()['count'];
$total_parents = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='parent'")->fetch_assoc()['count'];
$total_assessments = $conn->query("SELECT COUNT(*) as count FROM assessments")->fetch_assoc()['count'];
$total_assignments = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments")->fetch_assoc()['count'];
?>

<!-- Main Content Area -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_students ?></h2>
        <p>Total Students</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_teachers ?></h2>
        <p>Total Teachers</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #FF9800, #F57C00); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_parents ?></h2>
        <p>Total Parents</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #9C27B0, #7B1FA2); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_assessments ?></h2>
        <p>Assessments Recorded</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #f44336, #d32f2f); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_assignments ?></h2>
        <p>Teacher Assignments</p>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">⚡ Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
        <a href="admin_add_user.php" style="display: block; padding: 15px; background: #00a651; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            ➕ Add User/Student
        </a>
        <a href="view_users.php" style="display: block; padding: 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            👥 View All Users
        </a>
        <a href="assign_teacher.php" style="display: block; padding: 15px; background: #FF9800; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            📚 Assign Teacher to Grade
        </a>
        <a href="teacher_assignments.php" style="display: block; padding: 15px; background: #9C27B0; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            📋 View All Assignments
        </a>
    </div>
</div>

</div>
</body>
</html>
