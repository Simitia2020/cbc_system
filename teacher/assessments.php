<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get only assessments for subjects the teacher is assigned to
$query = "SELECT a.*, s.name as student_name, s.grade 
          FROM assessments a 
          JOIN students s ON a.student_id = s.id 
          WHERE a.teacher_id = $teacher_id 
          AND EXISTS (
              SELECT 1 FROM teacher_assignments ta 
              WHERE ta.teacher_id = $teacher_id 
              AND ta.grade = s.grade 
              AND ta.subject = a.subject
          )
          ORDER BY a.assessment_date DESC, a.id DESC";
$result = $conn->query($query);

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Verify teacher owns this assessment and subject is assigned
    $verify = $conn->query("SELECT a.*, s.grade FROM assessments a 
                            JOIN students s ON a.student_id = s.id 
                            WHERE a.id = $id AND a.teacher_id = $teacher_id");
    if ($verify->num_rows > 0) {
        $assessment = $verify->fetch_assoc();
        $check_assigned = $conn->query("SELECT id FROM teacher_assignments 
                                        WHERE teacher_id = $teacher_id 
                                        AND grade = '{$assessment['grade']}' 
                                        AND subject = '{$assessment['subject']}'");
        if ($check_assigned->num_rows > 0) {
            $conn->query("DELETE FROM assessments WHERE id=$id");
        }
    }
    header("Location: assessments.php");
    exit();
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 My Past Assessments (Only My Subjects)</h3>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Student</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Term/Exam</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Level</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['assessment_date']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['student_name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['grade']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['subject']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['competency']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <strong style="
                                    <?php 
                                    $level = $row['performance_level'];
                                    if($level == 'EE') echo 'color: #00a651;';
                                    elseif($level == 'ME') echo 'color: #2196F3;';
                                    elseif($level == 'AE') echo 'color: #FF9800;';
                                    else echo 'color: #f44336;';
                                    ?>
                                "><?= htmlspecialchars($level) ?></strong>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="edit-assessment.php?id=<?= $row['id'] ?>" style="background: #2196F3; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px;">✏️ Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this assessment?')" style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">📭 No assessments recorded for your assigned subjects yet.</p>
        <div style="text-align: center;">
            <a href="../teacher/add-assessment.php" style="display: inline-block; padding: 12px 25px; background: #00a651; color: white; text-decoration: none; border-radius: 8px;">
                + Record Your First Assessment
            </a>
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>