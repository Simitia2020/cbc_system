<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_material'])) {
    $grade = $_POST['grade'];
    $subject = trim($_POST['subject']);
    $title = trim($_POST['title']);
    $material_type = $_POST['material_type'];
    $content = trim($_POST['content']);
    $youtube_url = trim($_POST['youtube_url']);
    
    $file_path = '';
    
    // Handle file upload for notes
    if ($material_type == 'note' && isset($_FILES['material_file']) && $_FILES['material_file']['error'] == 0) {
        $upload_dir = "../uploads/materials/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['material_file']['name']));
        $file_path = "uploads/materials/" . $filename;
        
        if (move_uploaded_file($_FILES['material_file']['tmp_name'], "../" . $file_path)) {
            // File uploaded successfully
        } else {
            $error = "❌ Failed to upload file.";
        }
    }
    
    if (empty($error)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO learning_materials (teacher_id, grade, subject, title, material_type, content, file_path, youtube_url) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $teacher_id, $grade, $subject, $title, $material_type, $content, $file_path, $youtube_url);
        
        if ($stmt->execute()) {
            $message = "✅ Material uploaded successfully!";
            // Clear form data after successful upload
            $_POST = array();
        } else {
            $error = "❌ Database error: " . $conn->error;
        }
    }
}

// Get teacher's assigned grades
$grades_result = $conn->query("SELECT DISTINCT grade FROM teacher_assignments WHERE teacher_id = $teacher_id ORDER BY grade");
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📤 Upload Learning Material</h3>
    
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
    
    <form method="POST" enctype="multipart/form-data">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Grade</label>
        <select name="grade" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Select Grade --</option>
            <?php while($row = $grades_result->fetch_assoc()): ?>
                <option value="<?= $row['grade'] ?>"><?= $row['grade'] ?></option>
            <?php endwhile; ?>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Subject</label>
        <input type="text" name="subject" required placeholder="e.g., Mathematics, English" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Title</label>
        <input type="text" name="title" required placeholder="e.g., Algebra Notes, Science Video" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Material Type</label>
        <select name="material_type" id="material_type" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="note">📄 Note / Document</option>
            <option value="youtube">📺 YouTube Video</option>
        </select>
        
        <div id="file_div">
            <label style="font-weight: bold; display: block; margin-top: 15px;">Upload File (PDF, DOC, PPT, TXT)</label>
            <input type="file" name="material_file" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        
        <div id="youtube_div" style="display: none;">
            <label style="font-weight: bold; display: block; margin-top: 15px;">YouTube URL</label>
            <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." 
                   style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Description / Notes</label>
        <textarea name="content" rows="4" placeholder="Enter description or additional notes..." 
                  style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;"></textarea>
        
        <button type="submit" name="upload_material" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;">
            📤 Upload Material
        </button>
    </form>
</div>

<script>
document.getElementById('material_type').addEventListener('change', function() {
    if(this.value == 'youtube') {
        document.getElementById('file_div').style.display = 'none';
        document.getElementById('youtube_div').style.display = 'block';
    } else {
        document.getElementById('file_div').style.display = 'block';
        document.getElementById('youtube_div').style.display = 'none';
    }
});
</script>

</div>
</body>
</html>