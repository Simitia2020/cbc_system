<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get students in teacher's assigned grades
$students = $conn->query("SELECT DISTINCT s.id, s.name, s.grade, s.admission_no, s.parent_id 
                          FROM students s
                          JOIN teacher_assignments ta ON ta.grade = s.grade
                          WHERE ta.teacher_id = $teacher_id
                          ORDER BY s.grade, s.name");
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651;">🔑 Student Admission Numbers</h3>
    <p>Share these admission numbers with parents so they can link their children.</p>
    
    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <tr style="background:#00a651; color:white;">
            <th style="padding:10px;">Student Name</th>
            <th style="padding:10px;">Grade</th>
            <th style="padding:10px;">Admission Number</th>
            <th style="padding:10px;">Status</th>
        </tr>
        <?php while($student = $students->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #ddd;">
                <td style="padding:10px;"><?= $student['name'] ?></td>
                <td style="padding:10px;"><?= $student['grade'] ?></td>
                <td style="padding:10px;"><code><?= $student['admission_no'] ?: 'Not set' ?></code></td>
                <td style="padding:10px;">
                    <?= $student['parent_id'] ? '<span style="color:green;">✅ Linked</span>' : '<span style="color:orange;">⚠️ Not Linked</span>' ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>