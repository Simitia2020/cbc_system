<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$selected_grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$selected_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Verify teacher is class teacher for this grade
if ($selected_grade) {
    $check = $conn->prepare("SELECT id FROM class_teachers WHERE teacher_id = ? AND grade = ? AND is_active = 1");
    $check->bind_param("is", $teacher_id, $selected_grade);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        header("Location: class_assessment.php");
        exit();
    }
}

// Get all students in this grade
$students = [];
if ($selected_grade) {
    $students_query = $conn->prepare("SELECT id, name, admission_no FROM students WHERE grade = ? ORDER BY name");
    $students_query->bind_param("s", $selected_grade);
    $students_query->execute();
    $students = $students_query->get_result();
}

// Get assessments for selected student
$assessments = [];
if ($selected_student > 0) {
    $assessments_query = $conn->prepare("SELECT * FROM assessments WHERE student_id = ? ORDER BY subject, assessment_date");
    $assessments_query->bind_param("i", $selected_student);
    $assessments_query->execute();
    $assessments = $assessments_query->get_result();
}

// Get class performance summary
$class_summary = [];
if ($selected_grade) {
    $summary_query = "SELECT subject, performance_level, COUNT(*) as count 
                      FROM assessments a
                      JOIN students s ON a.student_id = s.id
                      WHERE s.grade = '$selected_grade'
                      GROUP BY subject, performance_level";
    $summary_result = $conn->query($summary_query);
    while($row = $summary_result->fetch_assoc()) {
        $class_summary[$row['subject']][$row['performance_level']] = $row['count'];
    }
}
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📊 Class Reports & Analytics</h3>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <form method="GET">
            <label>Select Grade:</label>
            <select name="grade" onchange="this.form.submit()" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
                <option value="">-- Choose Grade --</option>
                <?php
                $grades = $conn->query("SELECT grade FROM class_teachers WHERE teacher_id = $teacher_id AND is_active = 1");
                while($grade = $grades->fetch_assoc()):
                ?>
                    <option value="<?= $grade['grade'] ?>" <?= ($selected_grade == $grade['grade']) ? 'selected' : '' ?>>
                        <?= $grade['grade'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
    
    <?php if ($selected_grade): ?>
        <!-- Class Summary -->
        <div style="margin-bottom: 30px;">
            <h4 style="color: #00a651;">📈 Class Performance Summary</h4>
            <div style="overflow-x: auto;">
                <table style="width:100%; border-collapse:collapse; margin-top:15px;">
                    <thead>
                        <tr style="background:#00a651; color:white;">
                            <th style="padding:10px;">Subject</th>
                            <th style="padding:10px;">EE</th>
                            <th style="padding:10px;">ME</th>
                            <th style="padding:10px;">AE</th>
                            <th style="padding:10px;">BE</th>
                            <th style="padding:10px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($class_summary as $subject => $levels): 
                            $ee = $levels['EE'] ?? 0;
                            $me = $levels['ME'] ?? 0;
                            $ae = $levels['AE'] ?? 0;
                            $be = $levels['BE'] ?? 0;
                            $total = $ee + $me + $ae + $be;
                        ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:10px;"><strong><?= $subject ?></strong></td>
                                <td style="padding:10px; color:#00a651;"><?= $ee ?></td>
                                <td style="padding:10px; color:#2196F3;"><?= $me ?></td>
                                <td style="padding:10px; color:#FF9800;"><?= $ae ?></td>
                                <td style="padding:10px; color:#f44336;"><?= $be ?></td>
                                <td style="padding:10px;"><?= $total ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Student List -->
        <div style="margin-bottom: 30px;">
            <h4 style="color: #00a651;">👨‍🎓 Student Performance</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <?php while($student = $students->fetch_assoc()): ?>
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; text-align:center;">
                        <strong><?= htmlspecialchars($student['name']) ?></strong>
                        <p style="font-size:12px; color:#666;"><?= $student['admission_no'] ?></p>
                        <a href="?grade=<?= $selected_grade ?>&student_id=<?= $student['id'] ?>" style="display:inline-block; margin-top:10px; padding:5px 10px; background:#00a651; color:white; text-decoration:none; border-radius:4px;">View Details</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Individual Student Report -->
        <?php if ($selected_student > 0 && $assessments->num_rows > 0): 
            $student_name = '';
        ?>
            <div style="background:#f8f9fa; padding:20px; border-radius:10px;">
                <h4 style="color:#00a651;">📋 Individual Student Report</h4>
                <table style="width:100%; border-collapse:collapse; margin-top:15px;">
                    <thead>
                        <tr style="background:#00a651; color:white;">
                            <th style="padding:10px;">Subject</th>
                            <th style="padding:10px;">Term/Exam</th>
                            <th style="padding:10px;">Performance</th>
                            <th style="padding:10px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($assessment = $assessments->fetch_assoc()): 
                            $student_name = $assessment['student_name'] ?? '';
                        ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:10px;"><?= $assessment['subject'] ?></td>
                                <td style="padding:10px;"><?= $assessment['competency'] ?></td>
                                <td style="padding:10px;">
                                    <strong style="
                                        <?php 
                                        $l = $assessment['performance_level'];
                                        if($l == 'EE') echo 'color:#00a651';
                                        elseif($l == 'ME') echo 'color:#2196F3';
                                        elseif($l == 'AE') echo 'color:#FF9800';
                                        else echo 'color:#f44336';
                                        ?>
                                    "><?= $l ?></strong>
                                </td>
                                <td style="padding:10px;"><?= date('d M Y', strtotime($assessment['assessment_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="margin-top:20px;">
                    <button onclick="window.print()" style="background:#2196F3; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">🖨️ Print Report</button>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>