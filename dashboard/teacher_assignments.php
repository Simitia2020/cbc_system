<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get all assignments grouped by teacher
$assignments = $conn->query("SELECT ta.*, u.full_name as teacher_name 
                             FROM teacher_assignments ta 
                             JOIN users u ON ta.teacher_id = u.id 
                             ORDER BY u.full_name, ta.grade, ta.subject");
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Teacher Assignments List</h3>
    
    <a href="assign_teacher.php" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #00a651; color: white; text-decoration: none; border-radius: 6px;">
        ➕ Add New Assignment
    </a>
    
    <?php if ($assignments && $assignments->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Teacher</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Assigned Date</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $assignments->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['teacher_name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['grade'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['subject']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['assigned_date'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="assign_teacher.php?delete=<?= $row['id'] ?>" onclick="return confirm('Remove this assignment?')" style="color: #f44336; text-decoration: none;">🗑️ Remove</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 40px;">No teacher assignments yet. Click "Add New Assignment" to begin.</p>
    <?php endif; ?>
</div>

</div>
</body>
</html>