<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';

// Handle linking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link'])) {
    $student_id = intval($_POST['student_id']);
    $parent_id = intval($_POST['parent_id']);
    
    $update = $conn->query("UPDATE students SET parent_id = $parent_id WHERE id = $student_id");
    if ($update) {
        $message = "✅ Student linked to parent successfully!";
    } else {
        $message = "❌ Failed to link student to parent.";
    }
}

// Get all students without parents (or all students)
$students = $conn->query("SELECT id, name, grade, parent_id FROM students ORDER BY grade, name");

// Get all parents
$parents = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'parent' ORDER BY full_name");
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">🔗 Link Parent to Child</h3>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Select Student</label>
        <select name="student_id" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Choose Student --</option>
            <?php while($student = $students->fetch_assoc()): ?>
                <option value="<?= $student['id'] ?>">
                    <?= htmlspecialchars($student['name']) ?> - Grade <?= $student['grade'] ?>
                    <?= $student['parent_id'] ? '(Already linked to parent ID: ' . $student['parent_id'] . ')' : '' ?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Select Parent</label>
        <select name="parent_id" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Choose Parent --</option>
            <?php while($parent = $parents->fetch_assoc()): ?>
                <option value="<?= $parent['id'] ?>">
                    <?= htmlspecialchars($parent['full_name']) ?> (<?= $parent['email'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        
        <button type="submit" name="link" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;">
            🔗 Link Student to Parent
        </button>
    </form>
</div>

</div>
</body>
</html>