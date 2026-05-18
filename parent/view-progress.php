<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// If no student_id, go back
if ($student_id == 0) {
    header("Location: parent.php");
    exit();
}

// Verify this student belongs to the parent
$parent_id = $_SESSION['user_id'];
$verify_query = "SELECT id, name, grade FROM students WHERE id = $student_id AND parent_id = $parent_id";
$verify_result = $conn->query($verify_query);

if (!$verify_result || $verify_result->num_rows == 0) {
    header("Location: parent.php");
    exit();
}

$student = $verify_result->fetch_assoc();

// Get assessments for this student
$assessments_query = "SELECT * FROM assessments 
                      WHERE student_id = $student_id 
                      ORDER BY assessment_date DESC";
$assessments_result = $conn->query($assessments_query);
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 10px;">📊 Progress Report for <?= htmlspecialchars($student['name']) ?></h3>
    <p style="color: #666; margin-bottom: 20px;">Grade: <?= $student['grade'] ?></p>
    
    <?php if ($assessments_result && $assessments_result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Term/Exam</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($assessment = $assessments_result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $assessment['assessment_date'] ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($assessment['subject']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($assessment['competency']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <strong style="
                                <?php 
                                $level = $assessment['performance_level'];
                                if($level == 'EE') echo 'color: #00a651;';
                                elseif($level == 'ME') echo 'color: #2196F3;';
                                elseif($level == 'AE') echo 'color: #FF9800;';
                                else echo 'color: #f44336;';
                                ?>
                            "><?= $level ?></strong>
                        </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">📭 No assessments recorded yet for this student.</p>
    <?php endif; ?>
    
    <a href="parent.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 6px;">
        ← Back to My Children
    </a>
</div>

</div>
</body>
</html>