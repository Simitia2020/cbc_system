<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Check if teacher is a class teacher
$class_query = "SELECT ct.*, COUNT(s.id) as student_count 
                FROM class_teachers ct
                LEFT JOIN students s ON s.grade = ct.grade
                WHERE ct.teacher_id = $teacher_id AND ct.is_active = 1
                GROUP BY ct.id";
$classes = $conn->query($class_query);

$is_class_teacher = $classes->num_rows > 0;
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">🏫 Class Teacher Dashboard</h3>
    
    <?php if ($is_class_teacher): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <?php while($class = $classes->fetch_assoc()): ?>
                <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 20px; border-radius: 12px;">
                    <h2 style="margin: 0 0 10px 0;"><?= $class['grade'] ?></h2>
                    <p style="margin: 5px 0;">📚 Students: <?= $class['student_count'] ?></p>
                    <p style="margin: 5px 0;">📅 Year: <?= $class['academic_year'] ?></p>
                    <div style="margin-top: 15px;">
                        <a href="../teacher/class_assessment.php?grade=<?= urlencode($class['grade']) ?>" 
                           style="background: white; color: #00a651; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                            📝 Record Assessments
                        </a>
                        <a href="../teacher/class_reports.php?grade=<?= urlencode($class['grade']) ?>" 
                           style="background: #FF9800; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block;">
                            📊 Generate Reports
                        </a>
                        <a href="../teacher/master_assessment.php?grade=<?= urlencode($class['grade']) ?>" 
   style="background: white; color: #00a651; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
    📝 Master Assessment (All Subjects)
</a>
<a href="../teacher/class_teacher_report.php?grade=<?= urlencode($class['grade']) ?>" 
   style="background: white; color: #00a651; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
    📋 View Combined Report
</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="color: #00a651;">Quick Actions</h4>
                <ul style="margin-top: 15px;">
                    <li><a href="../teacher/class_assessment.php">📋 Class Assessment Tool</a></li>
                    <li><a href="../teacher/class_reports.php">📊 Class Reports & Analytics</a></li>
                    <li><a href="../teacher/student_codes.php">🔑 Student Linking Codes</a></li>
                    <li><a href="../teacher/upload_material.php">📤 Upload Class Materials</a></li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="color: #00a651;">Recent Activity</h4>
                <?php
                $recent = $conn->query("SELECT a.*, s.name as student_name 
                                        FROM assessments a 
                                        JOIN students s ON a.student_id = s.id 
                                        WHERE a.teacher_id = $teacher_id 
                                        ORDER BY a.assessment_date DESC LIMIT 5");
                if($recent->num_rows > 0): ?>
                    <ul style="margin-top: 15px;">
                        <?php while($row = $recent->fetch_assoc()): ?>
                            <li><?= date('d M', strtotime($row['assessment_date'])) ?> - <?= $row['student_name'] ?>: <?= $row['subject'] ?> (<?= $row['performance_level'] ?>)</li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent assessments.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <div style="background: #fff3cd; color: #856404; padding: 30px; border-radius: 8px; text-align: center;">
            <h4>⚠️ You are not assigned as a Class Teacher</h4>
            <p>Only class teachers can access this dashboard. Contact the administrator to be assigned as a class teacher.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>