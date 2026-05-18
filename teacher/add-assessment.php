<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success = '';
$error = '';
$selected_grade = isset($_GET['grade']) ? $_GET['grade'] : (isset($_POST['grade']) ? $_POST['grade'] : '');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_assessment'])) {
    $student_id = intval($_POST['student_id']);
    $term = $_POST['term'];
    $exam_type = $_POST['exam_type'];
    $grade = $_POST['grade'];
    
    $success_count = 0;
    
    foreach ($_POST['performance'] as $subject => $level) {
        if (!empty($level)) {
            // Verify teacher is assigned to this subject
            $check = $conn->query("SELECT id FROM teacher_assignments 
                                   WHERE teacher_id = $teacher_id 
                                   AND grade = '$grade' 
                                   AND subject = '$subject'");
            if ($check && $check->num_rows > 0) {
                $competency = "$term - $exam_type";
                $stmt = $conn->prepare("INSERT INTO assessments (student_id, teacher_id, subject, competency, performance_level, assessment_date) 
                                      VALUES (?, ?, ?, ?, ?, CURDATE())");
                $stmt->bind_param("iisss", $student_id, $teacher_id, $subject, $competency, $level);
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "✅ $success_count assessment(s) saved successfully for $term $exam_type!";
    } else {
        $error = "❌ Failed to save assessments. Please try again.";
    }
}

// Get ONLY subjects assigned to this teacher for the selected grade
$assigned_subjects = [];
if ($selected_grade) {
    $subjects_query = "SELECT subject FROM teacher_assignments 
                       WHERE teacher_id = $teacher_id AND grade = '$selected_grade'
                       ORDER BY subject";
    $subjects_result = $conn->query($subjects_query);
    if ($subjects_result && $subjects_result->num_rows > 0) {
        while($row = $subjects_result->fetch_assoc()) {
            $assigned_subjects[] = $row['subject'];
        }
    }
}

// Get students for the selected grade
$students_result = null;
if ($selected_grade) {
    $stmt = $conn->prepare("SELECT id, name FROM students WHERE grade = ? ORDER BY name");
    $stmt->bind_param("s", $selected_grade);
    $stmt->execute();
    $students_result = $stmt->get_result();
}

// Get ONLY grades assigned to this teacher
$grades_query = "SELECT DISTINCT grade FROM teacher_assignments WHERE teacher_id = $teacher_id ORDER BY grade";
$grades_result = $conn->query($grades_query);
$available_grades = [];
if ($grades_result && $grades_result->num_rows > 0) {
    while($row = $grades_result->fetch_assoc()) {
        $available_grades[] = $row['grade'];
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📝 Record CBC Assessment</h3>
    
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

    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">1. Select Grade (My Assigned Grades Only)</label>
        <select name="grade" onchange="this.form.submit()" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Choose Grade --</option>
            <?php foreach($available_grades as $grade): ?>
                <option value="<?= $grade ?>" <?= ($selected_grade == $grade) ? 'selected' : '' ?>>
                    <?= $grade ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selected_grade && $students_result && $students_result->num_rows > 0): ?>
            <label style="font-weight: bold; display: block; margin-top: 15px;">2. Select Student from <?= htmlspecialchars($selected_grade) ?></label>
            <select name="student_id" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
                <option value="">-- Choose Student --</option>
                <?php while($row = $students_result->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label style="font-weight: bold; display: block; margin-top: 15px;">3. Term</label>
            <select name="term" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>

            <label style="font-weight: bold; display: block; margin-top: 15px;">4. Exam Type</label>
            <select name="exam_type" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
                <option value="Mid Term">Mid Term</option>
                <option value="End Term">End Term</option>
            </select>

            <h4 style="margin-top: 25px; color: #00a651;">5. My Assigned Subjects for <?= htmlspecialchars($selected_grade) ?></h4>
            <?php if (count($assigned_subjects) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                        <thead>
                            <tr style="background: #00a651; color: white;">
                                <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                                <th style="padding: 12px; border: 1px solid #ddd;">Performance Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assigned_subjects as $subject): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($subject) ?></strong></td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <select name="performance[<?= htmlspecialchars($subject) ?>]" style="width: 100%; padding: 8px;">
                                            <option value="">— Select Level —</option>
                                            <option value="EE">EE - Exceeding Expectations</option>
                                            <option value="ME">ME - Meeting Expectations</option>
                                            <option value="AE">AE - Approaching Expectations</option>
                                            <option value="BE">BE - Below Expectations</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="submit_assessment" style="background: #00a651; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-top: 10px;">
                    💾 Save All Assessments
                </button>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    ⚠️ No subjects assigned to you for <?= htmlspecialchars($selected_grade) ?>. Please contact the administrator.
                </div>
            <?php endif; ?>
            
        <?php elseif ($selected_grade && (!$students_result || $students_result->num_rows == 0)): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px;">
                ⚠️ No students found in <?= htmlspecialchars($selected_grade) ?>. Please add students first.
            </div>
        <?php elseif (count($available_grades) == 0): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px;">
                ⚠️ No grades assigned to you yet. Please contact the administrator.
            </div>
        <?php endif; ?>
    </form>
</div>

</div>
</body>
</html>