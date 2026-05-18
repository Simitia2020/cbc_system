<?php
// includes/sidebar.php

// Get the correct path to config file
$root_path = dirname(__DIR__); // Go up one level from includes folder
include($root_path . "/config/db.php");

$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Add Chart.js for graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBC Kenya System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f6f9; 
            display: flex; 
            min-height: 100vh; 
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #00a651;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .sidebar-header h2 { 
            font-size: 24px; 
            margin-bottom: 5px;
        }
        .sidebar-header p { 
            font-size: 14px; 
            opacity: 0.9; 
        }

        .menu {
            margin-top: 30px;
        }
        .menu a {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        .menu a:hover, .menu a.active {
            background: #008c44;
            padding-left: 35px;
        }
        .menu a i { margin-right: 10px; width: 20px; text-align: center; }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: 100%;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>CBC Kenya</h2>
            <p>Competency Based Curriculum</p>
        </div>
        
        <div class="menu">
        <?php if ($role == 'admin'): ?>
    <a href="../dashboard/admin.php"><i>🏠</i> Dashboard</a>
    <a href="../dashboard/pending_users.php"><i>⏳</i> Pending Approvals</a>
    <a href="../dashboard/assign_class_teacher.php"><i>👨‍🏫</i> Assign Class Teacher</a>
    <a href="../dashboard/admin_add_user.php"><i>👤</i> Add User</a>
    <a href="../dashboard/admin_add_student.php"><i>👨‍🎓</i> Add Student</a>
    <a href="../dashboard/view_students.php"><i>📋</i> View Students</a>
    <a href="../dashboard/view_users.php"><i>👥</i> View Users</a>
    <a href="../dashboard/assign_teacher.php"><i>📚</i> Assign Subject Teacher</a>
<?php elseif ($role == 'teacher'): ?>
    <a href="../dashboard/teacher.php"><i>🏠</i> Dashboard</a>
    <a href="../dashboard/class_teacher.php"><i>🏫</i> Class Teacher Dashboard</a>
    <a href="../teacher/class_teacher_report.php"><i>📋</i> Combined Class Report</a>
    <a href="../teacher/add-assessment.php"><i>📝</i> Record Assessment</a>
    <a href="../teacher/assessments.php"><i>📊</i> Past Assessments</a>
    <a href="../teacher/upload_material.php"><i>📤</i> Upload Material</a>
    <a href="../teacher/manage_materials.php"><i>📚</i> My Materials</a>
  <?php elseif ($role == 'parent'): ?>
    <a href="../dashboard/parent.php"><i>🏠</i> Dashboard</a>
    <a href="../parent/link_child.php"><i>🔗</i> Link Your Child</a>
    <a href="../parent/child_progress.php"><i>📊</i> Child Progress</a>
    <a href="../parent/child_progress_graph.php"><i>📈</i> Progress Graphs</a>
    <a href="../parent/view_materials.php"><i>📚</i> Learning Materials</a>
    <a href="../parent/grading_system.php"><i>📘</i> Grading System</a>
    <a href="../parent/pathways.php"><i>🛤️</i> Learning Pathways</a>
            <?php endif; ?>
            
            <a href="../actions/logout.php" style="color:#ffcccc; margin-top:50px;"><i>🚪</i> Logout</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <h3>Welcome, <?= htmlspecialchars($name) ?> (<?= ucfirst($role) ?>)</h3>
            <div><?= date('d M Y') ?></div>
        </div>