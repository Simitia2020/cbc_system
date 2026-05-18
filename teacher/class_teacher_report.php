<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';
$sms_result = '';

// Get classes where teacher is class teacher
$class_query = "SELECT ct.grade, ct.academic_year 
                FROM class_teachers ct 
                WHERE ct.teacher_id = $teacher_id AND ct.is_active = 1";
$classes = $conn->query($class_query);
$selected_grade = isset($_GET['grade']) ? $_GET['grade'] : (isset($_POST['grade']) ? $_POST['grade'] : '');
$selected_term = isset($_POST['term']) ? $_POST['term'] : 'Term 1';
$selected_exam = isset($_POST['exam_type']) ? $_POST['exam_type'] : 'Mid Term';

// Get ALL subjects for this grade
$all_subjects = [];
if ($selected_grade) {
    if (in_array($selected_grade, ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'])) {
        $all_subjects = [
            'Mathematics', 'English', 'Kiswahili', 'Environmental Activities', 
            'Science and Technology', 'Social Studies', 'Agriculture', 
            'Religious Education', 'Creative Arts', 'Physical and Health Education'
        ];
    } else {
        $all_subjects = [
            'English', 'Mathematics', 'Kiswahili', 'Integrated Science', 
            'Social Studies', 'Agriculture', 'Religious Education', 
            'Pre-Technical Studies', 'Home Science', 'Performing Arts', 'Visual Arts'
        ];
    }
}

// Get all students in this grade
$students = [];
$student_assessments = [];
if ($selected_grade) {
    $students_query = $conn->prepare("SELECT id, name, admission_no FROM students WHERE grade = ? ORDER BY name");
    $students_query->bind_param("s", $selected_grade);
    $students_query->execute();
    $students_result = $students_query->get_result();
    
    while($student = $students_result->fetch_assoc()) {
        $students[] = $student;
        
        // Get assessments for this student from ALL teachers
        $competency = "$selected_term - $selected_exam";
        $assess_query = $conn->prepare("SELECT a.subject, a.performance_level, u.full_name as teacher_name 
                                        FROM assessments a
                                        JOIN users u ON a.teacher_id = u.id
                                        WHERE a.student_id = ? AND a.competency = ?
                                        ORDER BY a.subject");
        $assess_query->bind_param("is", $student['id'], $competency);
        $assess_query->execute();
        $assess_result = $assess_query->get_result();
        
        while($assess = $assess_result->fetch_assoc()) {
            $student_assessments[$student['id']][$assess['subject']] = [
                'level' => $assess['performance_level'],
                'teacher' => $assess['teacher_name']
            ];
        }
    }
}

// Handle sending SMS to parent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_sms'])) {
    $student_id = intval($_POST['student_id']);
    $student_name = $_POST['student_name'];
    
    // Get parent phone
    $parent_query = "SELECT u.phone, u.full_name as parent_name 
                     FROM students s
                     LEFT JOIN users u ON s.parent_id = u.id
                     WHERE s.id = $student_id";
    $parent_result = $conn->query($parent_query);
    
    if ($parent_result->num_rows > 0) {
        $parent = $parent_result->fetch_assoc();
        if (!empty($parent['phone'])) {
            // Build message with all subjects
            $subject_list = [];
            foreach ($all_subjects as $subject) {
                if (isset($student_assessments[$student_id][$subject])) {
                    $level = $student_assessments[$student_id][$subject]['level'];
                    $level_text = '';
                    switch($level) {
                        case 'EE': $level_text = 'Exceeding Expectations'; break;
                        case 'ME': $level_text = 'Meeting Expectations'; break;
                        case 'AE': $level_text = 'Approaching Expectations'; break;
                        case 'BE': $level_text = 'Below Expectations'; break;
                        default: $level_text = 'Not assessed';
                    }
                    $subject_list[] = "$subject: $level_text";
                } else {
                    $subject_list[] = "$subject: Not assessed";
                }
            }
            
            $sms_message = "CBC Report for $student_name - $selected_term $selected_exam:\n" . implode("\n", $subject_list);
            
            // For now, just show the message (simulate SMS)
            $sms_result = "📱 SMS would be sent to {$parent['parent_name']} at {$parent['phone']}<br><br><strong>Message:</strong><br>" . nl2br($sms_message);
        } else {
            $sms_result = "❌ No phone number registered for parent of $student_name";
        }
    } else {
        $sms_result = "❌ No parent linked to this student";
    }
}
?>

<style>
    @media print {
        .no-print { display: none; }
        .print-only { display: block; }
    }
    .print-only { display: none; }
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table th, .report-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    .report-table th { background: #00a651; color: white; position: sticky; top: 0; }
    .student-name { font-weight: bold; background: #f8f9fa; }
    .container { overflow-x: auto; max-height: 70vh; overflow-y: auto; }
    .ee { color: #00a651; font-weight: bold; }
    .me { color: #2196F3; font-weight: bold; }
    .ae { color: #FF9800; font-weight: bold; }
    .be { color: #f44336; font-weight: bold; }
    .not-assessed { color: #999; font-style: italic; }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Combined Class Report - All Subjects</h3>
    
    <?php if ($message): ?>
        <div class="no-print" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="no-print" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($sms_result): ?>
        <div class="no-print" style="background: #cce5ff; color: #004085; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $sms_result ?></div>
    <?php endif; ?>
    
    <!-- Grade and Filter Selection -->
    <div class="no-print" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="font-weight: bold;">Select Class:</label>
                    <select name="grade" required style="width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Choose Grade --</option>
                        <?php while($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['grade'] ?>" <?= ($selected_grade == $class['grade']) ? 'selected' : '' ?>>
                                <?= $class['grade'] ?> (<?= $class['academic_year'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight: bold;">Term:</label>
                    <select name="term" style="width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="Term 1" <?= $selected_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= $selected_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= $selected_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: bold;">Exam Type:</label>
                    <select name="exam_type" style="width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="Mid Term" <?= $selected_exam == 'Mid Term' ? 'selected' : '' ?>>Mid Term</option>
                        <option value="End Term" <?= $selected_exam == 'End Term' ? 'selected' : '' ?>>End Term</option>
                    </select>
                </div>
                <div>
                    <button type="submit" style="background: #00a651; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        Load Report
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($selected_grade && count($students) > 0): ?>
        <!-- Action Buttons -->
        <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <button type="button" onclick="window.print()" style="background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                🖨️ Print Report
            </button>
        </div>
        
        <!-- Combined Assessment Table -->
        <div class="container" style="overflow-x: auto; max-height: 60vh; overflow-y: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="position: sticky; left: 0; background: #00a651; min-width: 150px;">Student Name</th>
                        <th style="position: sticky; left: 150px; background: #00a651; min-width: 100px;">Admission No</th>
                        <?php foreach($all_subjects as $subject): ?>
                            <th><?= $subject ?></th>
                        <?php endforeach; ?>
                        <th class="no-print">Send SMS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                        <tr>
                            <td style="position: sticky; left: 0; background: white;" class="student-name">
                                <?= htmlspecialchars($student['name']) ?>
                            </td>
                            <td style="position: sticky; left: 150px; background: white;">
                                <?= $student['admission_no'] ?>
                            </td>
                            <?php foreach($all_subjects as $subject): ?>
                                <td>
                                    <?php if (isset($student_assessments[$student['id']][$subject])): 
                                        $level = $student_assessments[$student['id']][$subject]['level'];
                                        $class = '';
                                        $text = '';
                                        switch($level) {
                                            case 'EE': $class = 'ee'; $text = 'EE - Exceeding'; break;
                                            case 'ME': $class = 'me'; $text = 'ME - Meeting'; break;
                                            case 'AE': $class = 'ae'; $text = 'AE - Approaching'; break;
                                            case 'BE': $class = 'be'; $text = 'BE - Below'; break;
                                        }
                                    ?>
                                        <span class="<?= $class ?>"><?= $text ?></span>
                                        <br><small style="font-size: 10px; color: #999;">by <?= $student_assessments[$student['id']][$subject]['teacher'] ?></small>
                                    <?php else: ?>
                                        <span class="not-assessed">Not assessed yet</span>
                                    <?php endif; ?>
                                 </span>
                            <?php endforeach; ?>
                            <td class="no-print">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <input type="hidden" name="student_name" value="<?= htmlspecialchars($student['name']) ?>">
                                    <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                                    <input type="hidden" name="term" value="<?= $selected_term ?>">
                                    <input type="hidden" name="exam_type" value="<?= $selected_exam ?>">
                                    <button type="submit" name="send_sms" style="background: #FF9800; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                        📱 Send SMS
                                    </button>
                                </form>
                             </span>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
        
        <!-- Legend -->
        <div class="no-print" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="margin-bottom: 10px;">Performance Legend:</h4>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div><span class="ee">EE</span> - Exceeding Expectations (85-100%)</div>
                <div><span class="me">ME</span> - Meeting Expectations (70-84%)</div>
                <div><span class="ae">AE</span> - Approaching Expectations (50-69%)</div>
                <div><span class="be">BE</span> - Below Expectations (0-49%)</div>
                <div><span class="not-assessed">Not assessed</span> - No assessment recorded yet</div>
            </div>
        </div>
        
        <!-- Print View -->
        <div class="print-only" style="margin-top: 30px;">
            <h2 style="text-align: center;"><?= $selected_grade ?> Assessment Report</h2>
            <p style="text-align: center;">Term: <?= $selected_term ?> | Exam: <?= $selected_exam ?> | Date: <?= date('d/m/Y') ?></p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 8px; border: 1px solid #000;">Student Name</th>
                        <th style="padding: 8px; border: 1px solid #000;">Admission No</th>
                        <?php foreach($all_subjects as $subject): ?>
                            <th style="padding: 8px; border: 1px solid #000;"><?= $subject ?></th>
                        <?php endforeach; ?>
                     </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                        <tr>
                            <td style="padding: 6px; border: 1px solid #000;"><?= htmlspecialchars($student['name']) ?></td>
                            <td style="padding: 6px; border: 1px solid #000;"><?= $student['admission_no'] ?></td>
                            <?php foreach($all_subjects as $subject): ?>
                                <td style="padding: 6px; border: 1px solid #000; text-align: center;">
                                    <?php if (isset($student_assessments[$student['id']][$subject])): 
                                        echo $student_assessments[$student['id']][$subject]['level'];
                                    else: ?>
                                        ---
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 50px;">
                <p>____________________</p>
                <p>Class Teacher's Signature</p>
            </div>
        </div>
        
    <?php elseif ($selected_grade): ?>
        <div class="no-print" style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
            ⚠️ No students found in <?= $selected_grade ?>.
        </div>
    <?php endif; ?>
</div>

</body>
</html>