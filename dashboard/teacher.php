<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get statistics
$total_assessments = $conn->query("SELECT COUNT(*) as count FROM assessments WHERE teacher_id = $teacher_id")->fetch_assoc()['count'];
$total_materials = $conn->query("SELECT COUNT(*) as count FROM learning_materials WHERE teacher_id = $teacher_id")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM assessments WHERE teacher_id = $teacher_id")->fetch_assoc()['count'];

// Get assigned grades
$grades_result = $conn->query("SELECT DISTINCT grade FROM teacher_assignments WHERE teacher_id = $teacher_id ORDER BY grade");
?>

<!-- Main Content Area -->
<div style="padding: 20px;">
    <h2 style="color: #00a651; margin-bottom: 20px;">Teacher Dashboard</h2>
    
    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h2 style="font-size: 36px; margin: 0;"><?= $total_assessments ?></h2>
            <p style="margin: 10px 0 0;">Total Assessments</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h2 style="font-size: 36px; margin: 0;"><?= $total_materials ?></h2>
            <p style="margin: 10px 0 0;">Learning Materials</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #FF9800, #F57C00); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h2 style="font-size: 36px; margin: 0;"><?= $total_students ?></h2>
            <p style="margin: 10px 0 0;">Students Assessed</p>
        </div>
    </div>
    
    <!-- My Assigned Grades -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="color: #00a651; margin-bottom: 20px;">📚 My Assigned Grades</h3>
        
        <?php if ($grades_result && $grades_result->num_rows > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                <?php while($grade = $grades_result->fetch_assoc()): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #00a651;">
                        <h4 style="color: #00a651; margin: 0 0 10px 0;"><?= $grade['grade'] ?></h4>
                        <a href="../teacher/add-assessment.php?grade=<?= urlencode($grade['grade']) ?>" 
                           style="display: inline-block; padding: 5px 10px; background: #00a651; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">
                            Record Assessment
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No grades assigned yet. Contact administrator.</p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #00a651; margin-bottom: 15px;">📝 Assessments</h4>
            <a href="../teacher/add-assessment.php" style="display: block; padding: 10px; background: #00a651; color: white; text-decoration: none; border-radius: 6px; text-align: center; margin-bottom: 10px;">
                + Record New Assessment
            </a>
            <a href="../teacher/assessments.php" style="display: block; padding: 10px; background: #2196F3; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                📊 View Past Assessments
            </a>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #00a651; margin-bottom: 15px;">📚 Learning Materials</h4>
            <a href="../teacher/upload_material.php" style="display: block; padding: 10px; background: #00a651; color: white; text-decoration: none; border-radius: 6px; text-align: center; margin-bottom: 10px;">
                📤 Upload Material
            </a>
            <a href="../teacher/manage_materials.php" style="display: block; padding: 10px; background: #FF9800; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                📋 Manage My Materials
            </a>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: #00a651; margin-bottom: 15px;">🔑 Student Management</h4>
            <a href="../teacher/student_codes.php" style="display: block; padding: 10px; background: #9C27B0; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                🔑 Student Linking Codes
            </a>
        </div>
    </div>
</div>

</div>
</body>
</html>