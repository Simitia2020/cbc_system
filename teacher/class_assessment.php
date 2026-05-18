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

// Get teacher's assigned grades
$grades_result = $conn->query("SELECT DISTINCT grade FROM teacher_assignments WHERE teacher_id = $teacher_id ORDER BY grade");
$selected_grade = isset($_GET['grade']) ? $_GET['grade'] : (isset($_POST['grade']) ? $_POST['grade'] : '');
$selected_term = isset($_POST['term']) ? $_POST['term'] : 'Term 1';
$selected_exam = isset($_POST['exam_type']) ? $_POST['exam_type'] : 'Mid Term';

// Get students for selected grade
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
    
    foreach ($_POST['performance'] as $student_id => $subjects) {
        foreach ($subjects as $subject => $level) {
            if (!empty($level)) {
                $competency = "$selected_term - $selected_exam";
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
        $message = "✅ $success_count assessment(s) saved successfully!";
    } else {
        $error = "❌ Failed to save assessments.";
    }
}

// Handle SMS sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_sms'])) {
    $student_id = intval($_POST['student_id']);
    $subject = $_POST['sms_subject'];
    $performance_level = $_POST['sms_performance'];
    
    // Get student and parent details
    $parent_query = "SELECT s.name as student_name, u.phone, u.full_name as parent_name 
                     FROM students s
                     LEFT JOIN users u ON s.parent_id = u.id
                     WHERE s.id = $student_id";
    $parent_result = $conn->query($parent_query);
    
    if ($parent_result->num_rows > 0) {
        $parent = $parent_result->fetch_assoc();
        if (!empty($parent['phone'])) {
            $sms_message = formatAssessmentMessage($parent['student_name'], $subject, $performance_level, $selected_term, $selected_exam);
            if (sendSMS($parent['phone'], $sms_message)) {
                $sms_result = "✅ SMS sent to {$parent['parent_name']} at {$parent['phone']}";
            } else {
                $sms_result = "❌ Failed to send SMS";
            }
        } else {
            $sms_result = "❌ No phone number registered for parent of {$parent['student_name']}";
        }
    }
}

// Get subjects for this teacher
$subjects_query = "SELECT DISTINCT subject FROM teacher_assignments WHERE teacher_id = $teacher_id AND grade = '$selected_grade'";
$subjects = $conn->query($subjects_query);
$subject_list = [];
while($subj = $subjects->fetch_assoc()) {
    $subject_list[] = $subj['subject'];
}
?>

<!-- Main Content Area -->
<style>
    @media print {
        .no-print { display: none; }
        .print-only { display: block; }
        .assessment-table { border-collapse: collapse; width: 100%; }
        .assessment-table th, .assessment-table td { border: 1px solid #000; padding: 8px; }
    }
    .print-only { display: none; }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Class Assessment Tool</h3>
    
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
                    <label style="font-weight: bold;">Select Grade:</label>
                    <select name="grade" required style="width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Choose Grade --</option>
                        <?php while($grade = $grades_result->fetch_assoc()): ?>
                            <option value="<?= $grade['grade'] ?>" <?= ($selected_grade == $grade['grade']) ? 'selected' : '' ?>>
                                <?= $grade['grade'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" style="background: #00a651; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        Load Students
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($selected_grade && $students->num_rows > 0): ?>
        <!-- Assessment Form -->
        <form method="POST" id="assessmentForm">
            <div class="no-print" style="margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label>Term:</label>
                        <select name="term" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="Term 1" <?= $selected_term == 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                            <option value="Term 2" <?= $selected_term == 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                            <option value="Term 3" <?= $selected_term == 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                        </select>
                    </div>
                    <div>
                        <label>Exam Type:</label>
                        <select name="exam_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="Mid Term" <?= $selected_exam == 'Mid Term' ? 'selected' : '' ?>>Mid Term</option>
                            <option value="End Term" <?= $selected_exam == 'End Term' ? 'selected' : '' ?>>End Term</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" onclick="window.print()" style="background: #2196F3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                            🖨️ Print Assessment
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="assessment-table-container" style="overflow-x: auto;">
                <table class="assessment-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #00a651; color: white;">
                            <th style="padding: 12px; border: 1px solid #ddd;">Student Name</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Admission No</th>
                            <?php foreach($subject_list as $subject): ?>
                                <th style="padding: 12px; border: 1px solid #ddd;"><?= $subject ?></th>
                            <?php endforeach; ?>
                            <th class="no-print" style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <?= htmlspecialchars($student['name']) ?>
                                    <input type="hidden" name="performance[<?= $student['id'] ?>][student_name]" value="<?= $student['name'] ?>">
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?= $student['admission_no'] ?></td>
                                <?php foreach($subject_list as $subject): ?>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <select name="performance[<?= $student['id'] ?>][<?= $subject ?>]" style="width: 100%; padding: 5px;">
                                            <option value="">-- Select --</option>
                                            <option value="EE">EE - Exceeding</option>
                                            <option value="ME">ME - Meeting</option>
                                            <option value="AE">AE - Approaching</option>
                                            <option value="BE">BE - Below</option>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                                <td class="no-print" style="padding: 10px; border: 1px solid #ddd;">
                                    <button type="button" onclick="showSMSModal(<?= $student['id'] ?>, '<?= addslashes($student['name']) ?>')" 
                                            style="background: #FF9800; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;">
                                        📱 Send SMS
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="no-print" style="margin-top: 20px;">
                <button type="submit" name="save_assessments" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer;">
                    💾 Save All Assessments
                </button>
            </div>
        </form>
        
        <!-- Print View (Hidden normally, shows when printing) -->
        <div class="print-only" style="margin-top: 30px;">
            <h2 style="text-align: center;"><?= $selected_grade ?> Assessment Report</h2>
            <p style="text-align: center;">Term: <?= $selected_term ?> | Exam: <?= $selected_exam ?></p>
            <p style="text-align: center;">Date: <?= date('d/m/Y') ?></p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 10px; border: 1px solid #000;">Student Name</th>
                        <th style="padding: 10px; border: 1px solid #000;">Admission No</th>
                        <?php foreach($subject_list as $subject): ?>
                            <th style="padding: 10px; border: 1px solid #000;"><?= $subject ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $students->data_seek(0);
                    while($student = $students->fetch_assoc()): 
                    ?>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #000;"><?= htmlspecialchars($student['name']) ?></td>
                            <td style="padding: 8px; border: 1px solid #000;"><?= $student['admission_no'] ?></td>
                            <?php foreach($subject_list as $subject): ?>
                                <td style="padding: 8px; border: 1px solid #000; text-align: center;">___________</td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="margin-top: 50px;">
                <p>____________________</p>
                <p>Teacher's Signature</p>
            </div>
        </div>
        
    <?php elseif ($selected_grade): ?>
        <div class="no-print" style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
            ⚠️ No students found in <?= $selected_grade ?>.
        </div>
    <?php endif; ?>
</div>

<!-- SMS Modal -->
<div id="smsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; width:400px; max-width:90%;">
        <h3 style="color:#00a651; margin-bottom:20px;">Send Assessment via SMS</h3>
        <form method="POST">
            <input type="hidden" name="student_id" id="sms_student_id">
            <label>Subject:</label>
            <select name="sms_subject" id="sms_subject" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
                <option value="">-- Select Subject --</option>
                <?php foreach($subject_list as $subject): ?>
                    <option value="<?= $subject ?>"><?= $subject ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Performance Level:</label>
            <select name="sms_performance" id="sms_performance" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
                <option value="">-- Select Level --</option>
                <option value="EE">EE - Exceeding Expectations (85-100%)</option>
                <option value="ME">ME - Meeting Expectations (70-84%)</option>
                <option value="AE">AE - Approaching Expectations (50-69%)</option>
                <option value="BE">BE - Below Expectations (0-49%)</option>
            </select>
            
            <div style="margin-top:20px;">
                <button type="submit" name="send_sms" style="background:#00a651; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer;">Send SMS</button>
                <button type="button" onclick="closeSMSModal()" style="background:#666; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; margin-left:10px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showSMSModal(studentId, studentName) {
    document.getElementById('sms_student_id').value = studentId;
    document.getElementById('smsModal').style.display = 'flex';
    document.getElementById('smsModal').style.justifyContent = 'center';
    document.getElementById('smsModal').style.alignItems = 'center';
}

function closeSMSModal() {
    document.getElementById('smsModal').style.display = 'none';
}
</script>

</div>
</body>
</html>