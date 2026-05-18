<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Get student details
$student_query = $conn->prepare("SELECT * FROM students WHERE id = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student = $student_query->get_result()->fetch_assoc();

if (!$student) {
    header("Location: view_students.php");
    exit();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $name = trim($_POST['name']);
    $grade = $_POST['grade'];
    $admission_no = trim($_POST['admission_no']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    $update = $conn->prepare("UPDATE students SET name = ?, grade = ?, admission_no = ?, parent_id = ? WHERE id = ?");
    $update->bind_param("sssii", $name, $grade, $admission_no, $parent_id, $student_id);
    
    if ($update->execute()) {
        $message = "✅ Student updated successfully!";
        // Refresh data
        $student_query->execute();
        $student = $student_query->get_result()->fetch_assoc();
    } else {
        $error = "❌ Failed to update student.";
    }
}

$parents = $conn->query("SELECT id, full_name, national_id FROM users WHERE role = 'parent' ORDER BY full_name");
$grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">✏️ Edit Student</h3>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Student Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($student['name']) ?>" 
               style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
        
        <label>Grade</label>
        <select name="grade" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
            <?php foreach($grades as $grade): ?>
                <option value="<?= $grade ?>" <?= ($student['grade'] == $grade) ? 'selected' : '' ?>><?= $grade ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Admission Number</label>
        <input type="text" name="admission_no" required value="<?= htmlspecialchars($student['admission_no']) ?>" 
               style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
        
        <label>Link to Parent</label>
        <select name="parent_id" style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
            <option value="">-- No Parent --</option>
            <?php while($parent = $parents->fetch_assoc()): ?>
                <option value="<?= $parent['id'] ?>" <?= ($student['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($parent['full_name']) ?> (<?= $parent['national_id'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        
        <div style="margin-top: 20px;">
            <button type="submit" name="update_student" style="background:#00a651; color:white; padding:12px 25px; border:none; border-radius:6px;">💾 Save Changes</button>
            <a href="view_students.php" style="background:#666; color:white; padding:12px 25px; text-decoration:none; border-radius:6px; margin-left:10px;">← Cancel</a>
        </div>
    </form>
</div>

</body>
</html>