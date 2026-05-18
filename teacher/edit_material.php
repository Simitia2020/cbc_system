<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Get material details
$get_material = $conn->prepare("SELECT * FROM learning_materials WHERE id = ? AND teacher_id = ?");
$get_material->bind_param("ii", $material_id, $teacher_id);
$get_material->execute();
$material = $get_material->get_result()->fetch_assoc();

if (!$material) {
    header("Location: manage_materials.php");
    exit();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_material'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $youtube_url = trim($_POST['youtube_url']);
    
    $update = $conn->prepare("UPDATE learning_materials SET title = ?, content = ?, youtube_url = ? WHERE id = ? AND teacher_id = ?");
    $update->bind_param("sssii", $title, $content, $youtube_url, $material_id, $teacher_id);
    
    if ($update->execute()) {
        $message = "✅ Material updated successfully!";
        // Refresh data
        $get_material->execute();
        $material = $get_material->get_result()->fetch_assoc();
    } else {
        $error = "❌ Failed to update material.";
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">✏️ Edit Learning Material</h3>
    
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
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p><strong>Grade:</strong> <?= $material['grade'] ?></p>
        <p><strong>Subject:</strong> <?= htmlspecialchars($material['subject']) ?></p>
        <p><strong>Type:</strong> <?= ucfirst($material['material_type']) ?></p>
    </div>
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Title</label>
        <input type="text" name="title" required value="<?= htmlspecialchars($material['title']) ?>" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Description / Notes</label>
        <textarea name="content" rows="5" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;"><?= htmlspecialchars($material['content']) ?></textarea>
        
        <?php if ($material['material_type'] == 'youtube'): ?>
            <label style="font-weight: bold; display: block; margin-top: 15px;">YouTube URL</label>
            <input type="url" name="youtube_url" value="<?= htmlspecialchars($material['youtube_url']) ?>" 
                   style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        <?php endif; ?>
        
        <?php if ($material['material_type'] == 'note' && $material['file_path']): ?>
            <div style="background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0;">
                📎 Current file: <a href="../<?= $material['file_path'] ?>" target="_blank">Download</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <button type="submit" name="update_material" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer;">
                💾 Save Changes
            </button>
            <a href="manage_materials.php" style="background: #666; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin-left: 10px;">
                ← Cancel
            </a>
        </div>
    </form>
</div>

</div>
</body>
</html>