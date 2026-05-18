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

// Handle delete
if (isset($_GET['delete'])) {
    $material_id = intval($_GET['delete']);
    
    // Get file path to delete
    $get_file = $conn->prepare("SELECT file_path FROM learning_materials WHERE id = ? AND teacher_id = ?");
    $get_file->bind_param("ii", $material_id, $teacher_id);
    $get_file->execute();
    $file = $get_file->get_result()->fetch_assoc();
    
    if ($file && $file['file_path']) {
        $full_path = "../" . $file['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    $delete = $conn->prepare("DELETE FROM learning_materials WHERE id = ? AND teacher_id = ?");
    $delete->bind_param("ii", $material_id, $teacher_id);
    if ($delete->execute()) {
        $message = "✅ Material deleted successfully!";
    } else {
        $error = "❌ Failed to delete material.";
    }
}

// Get materials for this teacher
$materials = $conn->prepare("SELECT * FROM learning_materials WHERE teacher_id = ? ORDER BY created_at DESC");
$materials->bind_param("i", $teacher_id);
$materials->execute();
$result = $materials->get_result();
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📚 My Learning Materials</h3>
    
    <div style="margin-bottom: 20px;">
        <a href="upload_material.php" style="background: #00a651; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">
            + Upload New Material
        </a>
    </div>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Grade</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Title</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Type</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['grade'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['subject']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['title']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?= $row['material_type'] == 'note' ? '📄 Note' : '📺 YouTube' ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="edit_material.php?id=<?= $row['id'] ?>" 
                                   style="background: #2196F3; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; display: inline-block;">
                                    ✏️ Edit
                                </a>
                                <a href="?delete=<?= $row['id'] ?>" 
                                   onclick="return confirm('Delete this material?')" 
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
        <div style="background: #e8f5e9; padding: 40px; border-radius: 8px; text-align: center;">
            <p style="color: #00a651; font-size: 18px;">📭 No materials uploaded yet.</p>
            <a href="upload_material.php" style="background: #00a651; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 15px;">
                Upload Your First Material
            </a>
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>