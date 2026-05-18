<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $grade = $_POST['grade'];
    $admission_no = trim($_POST['admission_no']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    // Validate
    if (empty($name)) {
        $error = "❌ Student name is required!";
    } elseif (empty($admission_no)) {
        $error = "❌ Admission number is required!";
    } else {
        // Check if admission number already exists
        $check = $conn->prepare("SELECT id FROM students WHERE admission_no = ?");
        $check->bind_param("s", $admission_no);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "❌ Admission number already exists!";
        } else {
            // Generate linking code
            $linking_code = "CBC" . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            
            // Insert student - removed linking_code if column doesn't exist
            $stmt = $conn->prepare("INSERT INTO students (name, grade, admission_no, parent_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $grade, $admission_no, $parent_id);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                // Update with linking code separately
                $conn->query("UPDATE students SET linking_code = 'CBC" . str_pad($new_id, 5, '0', STR_PAD_LEFT) . "' WHERE id = $new_id");
                
                $message = "✅ Student added successfully!<br>
                           📛 Name: $name<br>
                           📚 Grade: $grade<br>
                           🆔 Admission No: $admission_no<br>
                           🔑 Linking Code: CBC" . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            } else {
                $error = "❌ Failed to add student: " . $conn->error;
            }
        }
    }
}

// Get all parents for dropdown
$parents = $conn->query("SELECT id, full_name, national_id, email FROM users WHERE role = 'parent' ORDER BY full_name");

$grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍🎓 Add New Student</h3>
    
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
        <label style="font-weight: bold; display: block; margin-top: 15px;">Student Full Name</label>
        <input type="text" name="name" required placeholder="e.g., John Doe" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Grade</label>
        <select name="grade" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Select Grade --</option>
            <?php foreach($grades as $grade): ?>
                <option value="<?= $grade ?>"><?= $grade ?></option>
            <?php endforeach; ?>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Admission Number</label>
        <input type="text" name="admission_no" required placeholder="e.g., ADM001 or 2024001" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        <small style="color: #666;">Unique number for identification</small>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Link to Parent (Optional)</label>
        <select name="parent_id" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- No Parent Linked --</option>
            <?php while($parent = $parents->fetch_assoc()): ?>
                <option value="<?= $parent['id'] ?>">
                    <?= htmlspecialchars($parent['full_name']) ?> (<?= $parent['national_id'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <small style="color: #666;">Parent can also link later using admission number</small>
        
        <button type="submit" name="add_student" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; margin-top: 20px;">
            ➕ Add Student
        </button>
    </form>
</div>

</div>
</body>
</html>