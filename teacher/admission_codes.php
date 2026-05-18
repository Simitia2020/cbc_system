<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get students in teacher's assigned grades
$students_query = "SELECT s.id, s.name, s.grade, s.admission_no, s.parent_id,
                   CASE WHEN s.parent_id IS NULL OR s.parent_id = 0 THEN 'Not Linked' ELSE 'Linked' END as status
                   FROM students s
                   JOIN teacher_assignments ta ON ta.grade = s.grade
                   WHERE ta.teacher_id = $teacher_id
                   GROUP BY s.id
                   ORDER BY s.grade, s.name";
$students = $conn->query($students_query);
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍👩‍👧‍👦 Student Admission Numbers</h3>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p><strong>📌 Instructions:</strong> Share these Admission Numbers with parents so they can link their children to their accounts.</p>
    </div>
    
    <?php if ($students && $students->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Student Name</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Admission Number</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Parent Link Status</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($student['name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $student['grade'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <code style="background: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-size: 14px;">
                                    <?= $student['admission_no'] ?: 'Not generated' ?>
                                </code>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php if($student['status'] == 'Linked'): ?>
                                    <span style="color: #00a651;">✅ Linked to Parent</span>
                                <?php else: ?>
                                    <span style="color: #FF9800;">⚠️ Not Linked</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php if($student['admission_no']): ?>
                                    <button onclick="copyAdmission('<?= $student['admission_no'] ?>')" 
                                            style="background: #2196F3; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;">
                                        📋 Copy Admission No
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">No students found in your assigned grades.</p>
    <?php endif; ?>
</div>

<script>
function copyAdmission(admission) {
    navigator.clipboard.writeText(admission);
    alert('Admission Number ' + admission + ' copied! Share it with the parent.');
}
</script>

</div>
</body>
</html>