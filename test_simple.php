<!DOCTYPE html>
<html>
<head>
    <title>Test Images</title>
</head>
<body>
    <h2>Testing Images from images folder</h2>
    
    <?php
    $images = ['slide1.jpg', 'slide2.jpg', 'slide3.jpg'];
    
    foreach ($images as $img) {
        $path = "images/" . $img;
        echo "<div style='margin:20px; display:inline-block;'>";
        echo "<img src='$path' width='200' style='border:2px solid red;'>";
        echo "<br>Path: $path";
        echo "<br>File exists: " . (file_exists($path) ? "YES" : "NO");
        echo "</div>";
    }
    ?>
</body>
</html>