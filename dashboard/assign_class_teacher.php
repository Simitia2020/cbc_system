<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_class_teacher'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $grade = $_POST['grade'];
    $academic_year = $_POST['academic_year'];
    
    // Check if already assigned
    $check = $conn->prepare("SELECT id FROM class_teachers WHERE teacher_id = ? AND grade = ? AND academic_year = ?");
    $check->bind_param("iss", $teacher_id, $grade, $academic_year);
    $check->execute();
    
    if ($check->get_result()->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO class_teachers (teacher_id, grade, academic_year) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $teacher_id, $grade, $academic_year);
        if ($stmt->execute()) {
            $message = "✅ Class teacher assigned successfully!";
        } else {
            $error = "❌ Failed to assign class teacher.";
        }
    } else {
        $error = "⚠️ This teacher is already assigned as class teacher for this grade this year.";
    }
}

// Handle removal
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $conn->query("DELETE FROM class_teachers WHERE id = $id");
    $message = "✅ Class teacher removed successfully!";
}

// Get all teachers
$teachers = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'teacher' ORDER BY full_name");

// Get all class teacher assignments
$class_teachers = $conn->query("SELECT ct.*, u.full_name as teacher_name 
                                FROM class_teachers ct 
                                JOIN users u ON ct.teacher_id = u.id 
                                ORDER BY ct.grade, ct.academic_year DESC");

$grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];
$current_year = date('Y');
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍🏫 Assign Class Teacher</h3>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Select Teacher:</label>
        <select name="teacher_id" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
            <option value="">-- Choose Teacher --</option>
            <?php while($teacher = $teachers->fetch_assoc()): ?>
                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['full_name']) ?> (<?= $teacher['email'] ?>)</option>
            <?php endwhile; ?>
        </select>
        
        <label>Grade:</label>
        <select name="grade" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
            <option value="">-- Choose Grade --</option>
            <?php foreach($grades as $grade): ?>
                <option value="<?= $grade ?>"><?= $grade ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Academic Year:</label>
        <select name="academic_year" required style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;">
            <option value="<?= $current_year ?>"><?= $current_year ?></option>
            <option value="<?= $current_year + 1 ?>"><?= $current_year + 1 ?></option>
        </select>
        
        <button type="submit" name="assign_class_teacher" style="background:#00a651; color:white; padding:12px 25px; border:none; border-radius:6px; cursor:pointer; margin-top:15px;">
            ➕ Assign as Class Teacher
        </button>
    </form>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Current Class Teachers</h3>
    
    <?php if ($class_teachers->num_rows > 0): ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#00a651; color:white;">
                    <th style="padding:10px;">Teacher</th>
                    <th style="padding:10px;">Grade</th>
                    <th style="padding:10px;">Academic Year</th>
                    <th style="padding:10px;">Assigned Date</th>
                    <th style="padding:10px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($ct = $class_teachers->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:10px;"><?= htmlspecialchars($ct['teacher_name']) ?></td>
                        <td style="padding:10px;"><?= $ct['grade'] ?></td>
                        <td style="padding:10px;"><?= $ct['academic_year'] ?></td>
                        <td style="padding:10px;"><?= $ct['assigned_date'] ?></td>
                        <td style="padding:10px;">
                            <a href="?remove=<?= $ct['id'] ?>" onclick="return confirm('Remove this class teacher?')" style="color:#f44336; text-decoration:none;">Remove</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
         </table>
    <?php else: ?>
        <p>No class teachers assigned yet.</p>
    <?php endif; ?>
</div>

</body>
</html>