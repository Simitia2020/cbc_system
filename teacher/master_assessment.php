<?php
session_start();
include("../includes/sidebar.php");
include("../config/sms_config.php");

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
    // Get subjects based on grade level
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
if ($selected_grade) {
    $students_query = $conn->prepare("SELECT id, name, admission_no FROM students WHERE grade = ? ORDER BY name");
    $students_query->bind_param("s", $selected_grade);
    $students_query->execute();
    $students = $students_query->get_result();
}

// Handle bulk assessment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_assessments'])) {
    $success_count = 0;
    $failed_count = 0;
    
    foreach ($_POST['performance'] as $student_id => $subjects) {
        foreach ($subjects as $subject => $level) {
            if (!empty($level)) {
                $competency = "$selected_term - $selected_exam";
                
                // Check if assessment already exists
                $check = $conn->prepare("SELECT id FROM assessments WHERE student_id = ? AND teacher_id = ? AND subject = ? AND competency = ?");
                $check->bind_param("iiss", $student_id, $teacher_id, $subject, $competency);
                $check->execute();
                
                if ($check->get_result()->num_rows > 0) {
                    // Update existing
                    $update = $conn->prepare("UPDATE assessments SET performance_level = ? WHERE student_id = ? AND teacher_id = ? AND subject = ? AND competency = ?");
                    $update->bind_param("siiss", $level, $student_id, $teacher_id, $subject, $competency);
                    if ($update->execute()) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    // Insert new
                    $stmt = $conn->prepare("INSERT INTO assessments (student_id, teacher_id, subject, competency, performance_level, assessment_date) 
                                          VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $stmt->bind_param("iisss", $student_id, $teacher_id, $subject, $competency, $level);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "✅ $success_count assessment(s) saved successfully!";
        if ($failed_count > 0) {
            $message .= " ⚠️ $failed_count failed.";
        }
    } else {
        $error = "❌ Failed to save assessments.";
    }
}

// Handle sending bulk SMS to all parents
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_bulk_sms'])) {
    $sms_sent = 0;
    $sms_failed = 0;
    
    foreach ($_POST['bulk_performance'] as $student_id => $subjects) {
        // Get parent phone
        $parent_query = "SELECT s.name as student_name, u.phone, u.full_name as parent_name 
                         FROM students s
                         LEFT JOIN users u ON s.parent_id = u.id
                         WHERE s.id = $student_id";
        $parent_result = $conn->query($parent_query);
        
        if ($parent_result->num_rows > 0) {
            $parent = $parent_result->fetch_assoc();
            if (!empty($parent['phone'])) {
                // Build message with all subjects
                $subject_list = [];
                foreach ($subjects as $subject => $level) {
                    if (!empty($level)) {
                        $level_text = '';
                        switch($level) {
                            case 'EE': $level_text = 'Exceeding'; break;
                            case 'ME': $level_text = 'Meeting'; break;
                            case 'AE': $level_text = 'Approaching'; break;
                            case 'BE': $level_text = 'Below'; break;
                        }
                        $subject_list[] = "$subject: $level_text";
                    }
                }
                
                $sms_message = "CBC Assessment Report for {$parent['student_name']} - $selected_term $selected_exam:\n" . implode(", ", $subject_list);
                
                if (sendSMS($parent['phone'], $sms_message)) {
                    $sms_sent++;
                } else {
                    $sms_failed++;
                }
            } else {
                $sms_failed++;
            }
        }
    }
    
    if ($sms_sent > 0) {
        $sms_result = "✅ $sms_sent SMS(s) sent successfully!";
        if ($sms_failed > 0) {
            $sms_result .= " ⚠️ $sms_failed failed (no phone number).";
        }
    } else {
        $sms_result = "❌ No SMS sent. Parents may not have phone numbers registered.";
    }
}
?>

<style>
    @media print {
        .no-print { display: none; }
        .print-only { display: block; }
        .master-table { border-collapse: collapse; width: 100%; font-size: 10px; }
        .master-table th, .master-table td { border: 1px solid #000; padding: 4px; }
    }
    .print-only { display: none; }
    .master-table { width: 100%; border-collapse: collapse; }
    .master-table th, .master-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
    .master-table th { background: #00a651; color: white; position: sticky; top: 0; }
    .subject-column { min-width: 100px; }
    .student-name { font-weight: bold; background: #f8f9fa; }
    select { padding: 4px; border-radius: 4px; border: 1px solid #ccc; }
    .container { overflow-x: auto; }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Master Assessment Tool - Combine All Subjects</h3>
    
    <?php if ($message): ?>
        <div class="no-print" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="no-print" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($sms_result): ?>
        <div class="no-print" style="background: #cce5ff; color: #004085; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $sms_result ?></div>
    <?php endif; ?>
    
    <!-- Grade Selection -->
    <div class="no-print" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="font-weight: bold;">Select Your Class:</label>
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
                    <button type="submit" style="background: #00a651; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        Load Students & Subjects
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($selected_grade && $students->num_rows > 0): ?>
        <!-- Assessment Form -->
        <form method="POST" id="assessmentForm">
            <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div>
                    <label>Term:</label>
                    <select name="term" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="Term 1" <?= $selected_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= $selected_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= $selected_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                <div>
                    <label>Exam Type:</label>
                    <select name="exam_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="Mid Term" <?= $selected_exam == 'Mid Term' ? 'selected' : '' ?>>Mid Term</option>
                        <option value="End Term" <?= $selected_exam == 'End Term' ? 'selected' : '' ?>>End Term</option>
                    </select>
                </div>
                <div>
                    <button type="button" onclick="window.print()" style="background: #2196F3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                        🖨️ Print Assessment Sheet
                    </button>
                </div>
                <div>
                    <button type="submit" name="save_assessments" style="background: #00a651; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        💾 Save All Assessments
                    </button>
                </div>
                <div>
                    <button type="button" onclick="confirmBulkSMS()" style="background: #FF9800; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        📱 Send Bulk SMS to Parents
                    </button>
                </div>
            </div>
            
            <div class="container" style="overflow-x: auto; max-height: 70vh; overflow-y: auto;">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th style="position: sticky; left: 0; background: #00a651; z-index: 10; min-width: 150px;">Student Name</th>
                            <th style="position: sticky; left: 150px; background: #00a651; z-index: 10; min-width: 100px;">Admission No</th>
                            <?php foreach($all_subjects as $subject): ?>
                                <th class="subject-column"><?= $subject ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $students->data_seek(0);
                        while($student = $students->fetch_assoc()): 
                            // Get existing assessments for this student
                            $existing = [];
                            $existing_query = $conn->prepare("SELECT subject, performance_level FROM assessments 
                                                              WHERE student_id = ? AND teacher_id = ? AND competency = ?");
                            $competency = "$selected_term - $selected_exam";
                            $existing_query->bind_param("iis", $student['id'], $teacher_id, $competency);
                            $existing_query->execute();
                            $existing_result = $existing_query->get_result();
                            while($row = $existing_result->fetch_assoc()) {
                                $existing[$row['subject']] = $row['performance_level'];
                            }
                        ?>
                            <tr>
                                <td style="position: sticky; left: 0; background: white; z-index: 5;" class="student-name">
                                    <?= htmlspecialchars($student['name']) ?>
                                </td>
                                <td style="position: sticky; left: 150px; background: white; z-index: 5;">
                                    <?= $student['admission_no'] ?>
                                </td>
                                <?php foreach($all_subjects as $subject): ?>
                                    <td>
                                        <select name="performance[<?= $student['id'] ?>][<?= $subject ?>]" style="width: 100%; min-width: 100px;">
                                            <option value="">-- Select --</option>
                                            <option value="EE" <?= (isset($existing[$subject]) && $existing[$subject] == 'EE') ? 'selected' : '' ?>>EE - Exceeding</option>
                                            <option value="ME" <?= (isset($existing[$subject]) && $existing[$subject] == 'ME') ? 'selected' : '' ?>>ME - Meeting</option>
                                            <option value="AE" <?= (isset($existing[$subject]) && $existing[$subject] == 'AE') ? 'selected' : '' ?>>AE - Approaching</option>
                                            <option value="BE" <?= (isset($existing[$subject]) && $existing[$subject] == 'BE') ? 'selected' : '' ?>>BE - Below</option>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Hidden inputs for bulk SMS -->
            <div id="bulkSmsData" style="display:none;"></div>
        </form>
        
        <!-- Print View -->
        <div class="print-only" style="margin-top: 30px;">
            <h2 style="text-align: center;"><?= $selected_grade ?> Assessment Report</h2>
            <p style="text-align: center;">Term: <?= $selected_term ?> | Exam: <?= $selected_exam ?> | Date: <?= date('d/m/Y') ?></p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 5px; border: 1px solid #000;">Student Name</th>
                        <th style="padding: 5px; border: 1px solid #000;">Admission No</th>
                        <?php foreach($all_subjects as $subject): ?>
                            <th style="padding: 5px; border: 1px solid #000;"><?= $subject ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $students->data_seek(0);
                    while($student = $students->fetch_assoc()): 
                    ?>
                        <tr>
                            <td style="padding: 4px; border: 1px solid #000;"><?= htmlspecialchars($student['name']) ?></td>
                            <td style="padding: 4px; border: 1px solid #000;"><?= $student['admission_no'] ?></td>
                            <?php foreach($all_subjects as $subject): ?>
                                <td style="padding: 4px; border: 1px solid #000; text-align: center;">___________</td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
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

<!-- Bulk SMS Modal -->
<div id="bulkSmsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; width:500px; max-width:90%;">
        <h3 style="color:#00a651; margin-bottom:20px;">📱 Send Bulk SMS to Parents</h3>
        <p>This will send assessment results to all parents whose children are in this class.</p>
        <p><strong>Class:</strong> <?= $selected_grade ?></p>
        <p><strong>Term/Exam:</strong> <?= $selected_term ?> - <?= $selected_exam ?></p>
        
        <form method="POST" id="bulkSmsForm">
            <input type="hidden" name="grade" value="<?= $selected_grade ?>">
            <input type="hidden" name="term" value="<?= $selected_term ?>">
            <input type="hidden" name="exam_type" value="<?= $selected_exam ?>">
            <div id="bulkSubjects"></div>
            
            <div style="margin-top:20px;">
                <button type="submit" name="send_bulk_sms" style="background:#00a651; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer;">
                    Send Bulk SMS
                </button>
                <button type="button" onclick="closeBulkSmsModal()" style="background:#666; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmBulkSMS() {
    // Collect all assessment data
    var form = document.getElementById('assessmentForm');
    var formData = new FormData(form);
    
    // Create hidden inputs for bulk SMS form
    var bulkDiv = document.getElementById('bulkSubjects');
    bulkDiv.innerHTML = '';
    
    for (var pair of formData.entries()) {
        if (pair[0].startsWith('performance')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = pair[0];
            input.value = pair[1];
            bulkDiv.appendChild(input);
        }
    }
    
    document.getElementById('bulkSmsModal').style.display = 'flex';
    document.getElementById('bulkSmsModal').style.justifyContent = 'center';
    document.getElementById('bulkSmsModal').style.alignItems = 'center';
}

function closeBulkSmsModal() {
    document.getElementById('bulkSmsModal').style.display = 'none';
}
</script>

</body>
</html>