<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = '';
$error = '';

// Get the assessment details and verify teacher is assigned to this subject
$query = "SELECT a.*, s.name as student_name, s.grade 
          FROM assessments a 
          JOIN students s ON a.student_id = s.id 
          WHERE a.id = $assessment_id AND a.teacher_id = $teacher_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    header("Location: assessments.php");
    exit();
}

$assessment = $result->fetch_assoc();

// Verify this teacher is actually assigned to teach this subject and grade
$verify_assigned = $conn->query("SELECT id FROM teacher_assignments 
                                 WHERE teacher_id = $teacher_id 
                                 AND grade = '{$assessment['grade']}' 
                                 AND subject = '{$assessment['subject']}'");

if ($verify_assigned->num_rows == 0) {
    // Teacher is not assigned to this subject - redirect
    header("Location: assessments.php");
    exit();
}

// Process update form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_assessment'])) {
    $performance_level = $_POST['performance_level'];
    
    $update = $conn->prepare("UPDATE assessments SET performance_level = ? WHERE id = ? AND teacher_id = ?");
    $update->bind_param("sii", $performance_level, $assessment_id, $teacher_id);
    
    if ($update->execute()) {
        $success = "✅ Assessment updated successfully!";
        // Refresh assessment data
        $result = $conn->query($query);
        $assessment = $result->fetch_assoc();
    } else {
        $error = "❌ Failed to update assessment.";
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">✏️ Edit Assessment</h3>
    
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <p><strong>👨‍🎓 Student:</strong> <?= htmlspecialchars($assessment['student_name']) ?></p>
        <p><strong>📚 Grade:</strong> <?= $assessment['grade'] ?></p>
        <p><strong>📖 Subject:</strong> <?= htmlspecialchars($assessment['subject']) ?></p>
        <p><strong>📝 Term/Exam:</strong> <?= htmlspecialchars($assessment['competency']) ?></p>
        <p><strong>📅 Assessment Date:</strong> <?= $assessment['assessment_date'] ?></p>
    </div>
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Performance Level</label>
        <select name="performance_level" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Select Level --</option>
            <option value="EE" <?= ($assessment['performance_level'] == 'EE') ? 'selected' : '' ?>>EE - Exceeding Expectations (85-100%)</option>
            <option value="ME" <?= ($assessment['performance_level'] == 'ME') ? 'selected' : '' ?>>ME - Meeting Expectations (70-84%)</option>
            <option value="AE" <?= ($assessment['performance_level'] == 'AE') ? 'selected' : '' ?>>AE - Approaching Expectations (50-69%)</option>
            <option value="BE" <?= ($assessment['performance_level'] == 'BE') ? 'selected' : '' ?>>BE - Below Expectations (0-49%)</option>
        </select>
        
        <div style="margin-top: 20px;">
            <button type="submit" name="update_assessment" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer;">
                💾 Update Assessment
            </button>
            <a href="assessments.php" style="background: #666; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin-left: 10px;">
                ← Cancel
            </a>
        </div>
    </form>
</div>

</div>
</body>
</html>