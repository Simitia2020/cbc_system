
<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM teacher_assignments WHERE id=$id")) {
        $message = "✅ Assignment removed successfully!";
    } else {
        $error = "❌ Failed to remove assignment.";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $grade = $_POST['grade'];
    $subject = trim($_POST['subject']);
    
    // Check if assignment already exists
    $check = $conn->query("SELECT id FROM teacher_assignments WHERE teacher_id=$teacher_id AND grade='$grade' AND subject='$subject'");
    if ($check && $check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, grade, subject) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $teacher_id, $grade, $subject);
        if ($stmt->execute()) {
            $message = "✅ Assignment added successfully!";
        } else {
            $error = "❌ Failed to add assignment.";
        }
    } else {
        $error = "⚠️ This assignment already exists!";
    }
}

// Get all teachers
$teachers = $conn->query("SELECT id, full_name, email FROM users WHERE role='teacher' ORDER BY full_name");

// Get all assignments for display
$assignments = $conn->query("SELECT ta.*, u.full_name as teacher_name 
                             FROM teacher_assignments ta 
                             JOIN users u ON ta.teacher_id = u.id 
                             ORDER BY u.full_name, ta.grade, ta.subject");

$all_grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];

// Common subjects by level
$primary_subjects = ['Mathematics', 'English', 'Kiswahili', 'Environmental Activities', 'Science and Technology', 'Social Studies', 'Agriculture', 'Religious Education', 'Creative Arts', 'Physical and Health Education'];
$junior_subjects = ['English', 'Mathematics', 'Kiswahili', 'Integrated Science', 'Social Studies', 'Agriculture', 'Religious Education', 'Pre-Technical Studies', 'Home Science', 'Performing Arts', 'Visual Arts'];
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍🏫 Assign Teacher to Grade & Subject</h3>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Select Teacher</label>
        <select name="teacher_id" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Choose Teacher --</option>
            <?php if ($teachers && $teachers->num_rows > 0): ?>
                <?php while($teacher = $teachers->fetch_assoc()): ?>
                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['full_name']) ?> (<?= $teacher['email'] ?>)</option>
                <?php endwhile; ?>
            <?php else: ?>
                <option value="">No teachers found. Please add teachers first.</option>
            <?php endif; ?>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Grade</label>
        <select name="grade" id="grade" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Choose Grade --</option>
            <?php foreach($all_grades as $grade): ?>
                <option value="<?= $grade ?>"><?= $grade ?></option>
            <?php endforeach; ?>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Subject</label>
        <input type="text" name="subject" list="subjects" required placeholder="e.g., Mathematics, English" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        <datalist id="subjects">
            <optgroup label="Primary School (Grades 1-6)">
                <?php foreach($primary_subjects as $subject): ?>
                    <option value="<?= $subject ?>">
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Junior Secondary (Grades 7-9)">
                <?php foreach($junior_subjects as $subject): ?>
                    <option value="<?= $subject ?>">
                <?php endforeach; ?>
            </optgroup>
        </datalist>
        
        <button type="submit" name="assign" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;">
            ➕ Assign Teacher
        </button>
    </form>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📋 Current Teacher Assignments</h3>
    
    <?php if ($assignments && $assignments->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Teacher</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $assignments->fetch_assoc()): ?>
                        <tr>
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
        <p style="color: #666; text-align: center; padding: 40px;">No teacher assignments yet.</p>
    <?php endif; ?>
</div>

</div>
</body>
</html>