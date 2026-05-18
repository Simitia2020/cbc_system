<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$parent_id = $_SESSION['user_id'];
$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$selected_grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Get parent's children
$children_query = "SELECT id, name, grade FROM students WHERE parent_id = $parent_id ORDER BY name";
$children = $conn->query($children_query);

// If no student selected and there are children, select the first one
if ($selected_student_id == 0 && $children->num_rows > 0) {
    $first_child = $children->fetch_assoc();
    $selected_student_id = $first_child['id'];
    $selected_grade = $first_child['grade'];
    $children->data_seek(0);
}

// Get unique subjects for the selected grade
$subjects = [];
if ($selected_grade) {
    $subjects_query = "SELECT DISTINCT subject FROM learning_materials WHERE grade = '$selected_grade' ORDER BY subject";
    $subjects_result = $conn->query($subjects_query);
    while($subj = $subjects_result->fetch_assoc()) {
        $subjects[] = $subj['subject'];
    }
}

// Get learning materials
$materials_query = "SELECT * FROM learning_materials WHERE 1=1";
if ($selected_grade) {
    $materials_query .= " AND grade = '$selected_grade'";
}
if ($selected_subject) {
    $materials_query .= " AND subject = '$selected_subject'";
}
$materials_query .= " ORDER BY created_at DESC";
$materials = $conn->query($materials_query);
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📚 Learning Materials</h3>
    
    <!-- Child Selector -->
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <label style="font-weight: bold; display: block; margin-bottom: 10px;">Select Your Child:</label>
        <select onchange="window.location.href='view_materials.php?student_id='+this.value+'&grade='+this.options[this.selectedIndex].getAttribute('data-grade')" 
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">-- Select Child --</option>
            <?php while($child = $children->fetch_assoc()): ?>
                <option value="<?= $child['id'] ?>" data-grade="<?= $child['grade'] ?>" <?= ($selected_student_id == $child['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($child['name']) ?> - Grade <?= $child['grade'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    
    <?php if ($selected_grade): ?>
        <!-- Filter Section -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: center;">
                <div>
                    <label style="font-weight: bold;">Filter by Subject:</label>
                    <select onchange="window.location.href='view_materials.php?student_id=<?= $selected_student_id ?>&grade=<?= $selected_grade ?>&subject='+this.value" 
                            style="width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- All Subjects --</option>
                        <?php foreach($subjects as $subject): ?>
                            <option value="<?= $subject ?>" <?= ($selected_subject == $subject) ? 'selected' : '' ?>>
                                <?= $subject ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <a href="view_materials.php?student_id=<?= $selected_student_id ?>&grade=<?= $selected_grade ?>" 
                       style="background: #00a651; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 28px;">
                        Clear Filter
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Materials Display -->
        <h4 style="color: #00a651; margin-bottom: 15px;">📖 Available Learning Materials</h4>
        
        <?php if ($materials && $materials->num_rows > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                <?php while($material = $materials->fetch_assoc()): ?>
                    <div style="background: #f8f9fa; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <div style="background: #00a651; color: white; padding: 15px;">
                            <h4 style="margin: 0;"><?= htmlspecialchars($material['title']) ?></h4>
                            <small><?= $material['subject'] ?> | <?= date('d M Y', strtotime($material['created_at'])) ?></small>
                        </div>
                        <div style="padding: 15px;">
                            <p style="color: #666; margin-bottom: 15px;"><?= nl2br(htmlspecialchars($material['content'])) ?></p>
                            
                            <?php if ($material['material_type'] == 'note' && $material['file_path']): ?>
                                <a href="../<?= $material['file_path'] ?>" target="_blank" 
                                   style="display: inline-block; background: #2196F3; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;">
                                    📄 Download Notes
                                </a>
                                
                            <?php elseif ($material['material_type'] == 'youtube' && $material['youtube_url']): ?>
                                <?php
                                // Extract YouTube video ID
                                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $material['youtube_url'], $matches);
                                $video_id = $matches[1] ?? '';
                                ?>
                                <?php if ($video_id): ?>
                                    <div style="margin-bottom: 10px;">
                                        <iframe width="100%" height="200" src="https://www.youtube.com/embed/<?= $video_id ?>" 
                                                frameborder="0" allowfullscreen style="border-radius: 5px;"></iframe>
                                    </div>
                                <?php endif; ?>
                                <a href="<?= $material['youtube_url'] ?>" target="_blank" 
                                   style="display: inline-block; background: #FF0000; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;">
                                    📺 Watch on YouTube
                                </a>
                                
                            <?php elseif ($material['material_type'] == 'video' && $material['file_path']): ?>
                                <video width="100%" controls style="border-radius: 5px; margin-bottom: 10px;">
                                    <source src="../<?= $material['file_path'] ?>" type="video/mp4">
                                    Your browser does not support video.
                                </video>
                                <a href="../<?= $material['file_path'] ?>" download 
                                   style="display: inline-block; background: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;">
                                    ⬇️ Download Video
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 40px; border-radius: 8px; text-align: center;">
                📭 No learning materials available for <?= $selected_grade ?> <?= $selected_subject ? " - $selected_subject" : '' ?>.
                <br><br>
                <small>Check back later for new materials from teachers.</small>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="background: #f8d7da; color: #721c24; padding: 40px; border-radius: 8px; text-align: center;">
            ⚠️ No children linked to your account. Please link your child first.
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>