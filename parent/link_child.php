<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$parent_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Process linking request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_child'])) {
    $admission_no = strtoupper(trim($_POST['admission_no']));
    
    // Find student with this admission number
    $find_student = $conn->prepare("SELECT id, name, grade, parent_id FROM students WHERE admission_no = ?");
    $find_student->bind_param("s", $admission_no);
    $find_student->execute();
    $result = $find_student->get_result();
    
    if ($result->num_rows === 0) {
        $error = "❌ Invalid Admission Number. Please check and try again.";
    } else {
        $student = $result->fetch_assoc();
        
        if ($student['parent_id'] !== null && $student['parent_id'] > 0) {
            $error = "⚠️ This student is already linked to another parent. Contact administrator if this is incorrect.";
        } else {
            // Link the student
            $link_student = $conn->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
            $link_student->bind_param("ii", $parent_id, $student['id']);
            
            if ($link_student->execute()) {
                $message = "✅ Successfully linked to {$student['name']} (Grade {$student['grade']})!";
            } else {
                $error = "❌ Failed to link. Please try again.";
            }
        }
    }
}

// Get linked children
$get_children = $conn->prepare("SELECT id, name, grade, admission_no FROM students WHERE parent_id = ? ORDER BY name");
$get_children->bind_param("i", $parent_id);
$get_children->execute();
$children = $get_children->get_result();
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">🔗 Link Your Child Using Admission Number</h3>
    
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
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <p><strong>📝 How to link your child:</strong></p>
        <ul style="margin-left: 20px; line-height: 1.8;">
            <li>Enter your child's <strong>Admission Number</strong> (found on their school ID or report card)</li>
            <li>Example: <strong>ADM00001</strong> or the number given by the school</li>
            <li>Click "Link Child" to connect to your account</li>
        </ul>
    </div>
    
    <form method="POST" style="margin-top: 20px;">
        <label style="font-weight: bold; display: block; margin-bottom: 10px;">Child's Admission Number:</label>
        <input type="text" name="admission_no" required placeholder="Enter Admission Number (e.g., ADM00001)" 
               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; text-transform: uppercase;">
        <button type="submit" name="link_child" style="background: #00a651; color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;">
            🔗 Link Child to My Account
        </button>
    </form>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
    <h3 style="color: #00a651; margin-bottom: 20px;">👨‍👩‍👧‍👦 My Linked Children</h3>
    
    <?php if ($children->num_rows > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
            <?php while($child = $children->fetch_assoc()): ?>
                <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #00a651;">
                    <h4 style="color: #00a651; margin: 0 0 10px 0;"><?= htmlspecialchars($child['name']) ?></h4>
                    <p style="margin: 5px 0;">📚 Grade: <?= $child['grade'] ?></p>
                    <p style="margin: 5px 0;">🆔 Admission No: <?= htmlspecialchars($child['admission_no']) ?></p>
                    <a href="child_progress.php?student_id=<?= $child['id'] ?>" 
                       style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: #00a651; color: white; text-decoration: none; border-radius: 5px;">
                        View Progress 📊
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 20px;">No children linked yet. Enter your child's Admission Number above.</p>
    <?php endif; ?>
</div>

</div>
</body>
</html>