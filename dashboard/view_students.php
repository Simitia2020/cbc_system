<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle delete student
if (isset($_GET['delete'])) {
    $student_id = intval($_GET['delete']);
    
    // Check if student has assessments
    $check = $conn->query("SELECT id FROM assessments WHERE student_id = $student_id LIMIT 1");
    if ($check->num_rows > 0) {
        $error = "❌ Cannot delete student. They have assessment records.";
    } else {
        $delete = $conn->prepare("DELETE FROM students WHERE id = ?");
        $delete->bind_param("i", $student_id);
        if ($delete->execute()) {
            $message = "✅ Student deleted successfully!";
        } else {
            $error = "❌ Failed to delete student.";
        }
    }
}

// Get all students
$students = $conn->query("SELECT s.*, u.full_name as parent_name 
                          FROM students s
                          LEFT JOIN users u ON s.parent_id = u.id
                          ORDER BY s.grade, s.name");
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍🎓 All Students</h3>
    
    <div style="margin-bottom: 20px;">
        <a href="admin_add_student.php" style="background: #00a651; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">
            + Add New Student
        </a>
    </div>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($students->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Name</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Admission No</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Parent</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $student['id'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($student['name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $student['grade'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><code><?= $student['admission_no'] ?></code></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?= $student['parent_name'] ? htmlspecialchars($student['parent_name']) : '<span style="color:orange;">Not linked</span>' ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="edit_student.php?id=<?= $student['id'] ?>" 
                                   style="background: #2196F3; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; display: inline-block;">
                                    ✏️ Edit
                                </a>
                                <a href="?delete=<?= $student['id'] ?>" 
                                   onclick="return confirm('Delete <?= addslashes($student['name']) ?>?')" 
                                   style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No students added yet.</p>
    <?php endif; ?>
</div>

</body>
</html>