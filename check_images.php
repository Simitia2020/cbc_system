<?php
echo "<h2>Checking Images in /images folder</h2>";
$images_dir = "images/";

if (is_dir($images_dir)) {
    $files = scandir($images_dir);
    echo "<h3>Files found in images folder:</h3>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo "<li>";
                echo "<img src='$images_dir$file' width='150' style='margin:10px; border:2px solid green;'>";
                echo "<br><strong>Filename:</strong> $file";
                echo "<br><strong>Path:</strong> $images_dir$file";
                echo "</li>";
            } else {
                echo "<li>📄 $file (not an image)</li>";
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Images folder does not exist at: " . realpath($images_dir) . "</p>";
    echo "<p>Please create folder: C:\\xampp\\htdocs\\cbc-system\\images\\</p>";
}
?>