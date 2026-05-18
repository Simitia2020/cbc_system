<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$parent_id = $_SESSION['user_id'];

// Get ONLY children of THIS SPECIFIC parent
$children_query = "SELECT id, name, grade, admission_no 
                   FROM students 
                   WHERE parent_id = $parent_id 
                   ORDER BY name";
$children_result = $conn->query($children_query);

$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// If no student selected and there are children, select the first one
if ($selected_student_id == 0 && $children_result && $children_result->num_rows > 0) {
    $children_result->data_seek(0);
    $first_child = $children_result->fetch_assoc();
    $selected_student_id = $first_child['id'];
    $children_result->data_seek(0);
}

// Get the selected student's details - ONLY if it belongs to this parent
$selected_student = null;
if ($selected_student_id > 0) {
    $student_query = "SELECT id, name, grade, admission_no 
                      FROM students 
                      WHERE id = $selected_student_id AND parent_id = $parent_id";
    $student_result = $conn->query($student_query);
    if ($student_result && $student_result->num_rows > 0) {
        $selected_student = $student_result->fetch_assoc();
    }
}

// Get assessments ONLY for the selected student
$assessments = [];
if ($selected_student) {
    $assessments_query = "SELECT * FROM assessments 
                          WHERE student_id = {$selected_student['id']} 
                          ORDER BY assessment_date DESC";
    $assessments_result = $conn->query($assessments_query);
    if ($assessments_result) {
        while($row = $assessments_result->fetch_assoc()) {
            $assessments[] = $row;
        }
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📊 My Child's Progress</h3>
    
    <?php if ($children_result && $children_result->num_rows > 0): ?>
        <!-- Child Selector - Only shows THIS parent's children -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <label style="font-weight: bold; display: block; margin-bottom: 10px;">Select Your Child:</label>
            <select id="child_select" onchange="window.location.href='child_progress.php?student_id='+this.value" 
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                <option value="">-- Select Your Child --</option>
                <?php 
                $children_result->data_seek(0);
                while($child = $children_result->fetch_assoc()): 
                ?>
                    <option value="<?= $child['id'] ?>" <?= ($selected_student_id == $child['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($child['name']) ?> - Grade <?= $child['grade'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <?php if ($selected_student): ?>
            <!-- Student Info -->
            <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <h2 style="margin: 0 0 10px 0;"><?= htmlspecialchars($selected_student['name']) ?></h2>
                <p style="margin: 5px 0;">📚 Grade: <?= $selected_student['grade'] ?></p>
                <?php if(isset($selected_student['admission_no']) && !empty($selected_student['admission_no'])): ?>
                    <p style="margin: 5px 0;">🆔 Admission No: <?= $selected_student['admission_no'] ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Assessments Table -->
            <h4 style="color: #00a651; margin-bottom: 15px;">📝 Assessment Results</h4>
            
            <?php if (count($assessments) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #00a651; color: white;">
                                <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
                                <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                                <th style="padding: 12px; border: 1px solid #ddd;">Term/Exam</th>
                                <th style="padding: 12px; border: 1px solid #ddd;">Performance</th>
                                <th style="padding: 12px; border: 1px solid #ddd;">Meaning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assessments as $assessment): ?>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d M Y', strtotime($assessment['assessment_date'])) ?></td>
                                    <td style="padding: 10px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($assessment['subject']) ?></strong></td>
                                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($assessment['competency']) ?></td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <strong style="
                                            <?php 
                                            $level = $assessment['performance_level'];
                                            if($level == 'EE') echo 'color: #00a651; font-size: 18px;';
                                            elseif($level == 'ME') echo 'color: #2196F3; font-size: 18px;';
                                            elseif($level == 'AE') echo 'color: #FF9800; font-size: 18px;';
                                            else echo 'color: #f44336; font-size: 18px;';
                                            ?>
                                        "><?= $level ?></strong>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php
                                        $level = $assessment['performance_level'];
                                        if($level == 'EE') echo '🎉 Exceeding Expectations';
                                        elseif($level == 'ME') echo '✅ Meeting Expectations';
                                        elseif($level == 'AE') echo '⚠️ Approaching Expectations';
                                        else echo '🔴 Below Expectations';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Performance Summary -->
                <?php
                $summary = ['EE' => 0, 'ME' => 0, 'AE' => 0, 'BE' => 0];
                foreach($assessments as $assessment) {
                    $summary[$assessment['performance_level']]++;
                }
                ?>
                <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="color: #00a651; margin-bottom: 15px;">📊 Performance Summary</h4>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div style="flex: 1; text-align: center;">
                            <div style="background: #00a651; color: white; padding: 15px; border-radius: 8px;">
                                <strong>EE</strong>
                                <p style="font-size: 24px; margin: 10px 0;"><?= $summary['EE'] ?></p>
                                <small>Exceeding</small>
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <div style="background: #2196F3; color: white; padding: 15px; border-radius: 8px;">
                                <strong>ME</strong>
                                <p style="font-size: 24px; margin: 10px 0;"><?= $summary['ME'] ?></p>
                                <small>Meeting</small>
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <div style="background: #FF9800; color: white; padding: 15px; border-radius: 8px;">
                                <strong>AE</strong>
                                <p style="font-size: 24px; margin: 10px 0;"><?= $summary['AE'] ?></p>
                                <small>Approaching</small>
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <div style="background: #f44336; color: white; padding: 15px; border-radius: 8px;">
                                <strong>BE</strong>
                                <p style="font-size: 24px; margin: 10px 0;"><?= $summary['BE'] ?></p>
                                <small>Below</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
                    📭 No assessments recorded yet for your child.
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
                ⚠️ Please select your child to view their progress.
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; text-align: center;">
            ❌ No children linked to your account. 
            <br><br>
            <strong>Please contact the school administrator to link your children to your account.</strong>
            <br><br>
            <small>Your Parent ID: <?= $parent_id ?></small>
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>