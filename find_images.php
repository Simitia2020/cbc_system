<?php
echo "<h2>Searching for slide1.jpg, slide2.jpg, slide3.jpg</h2>";

// Search in different possible locations
$search_paths = [
    __DIR__ . "/images/",
    __DIR__ . "/",
    __DIR__ . "/assets/",
    __DIR__ . "/uploads/",
    "C:/xampp/htdocs/cbc-system/",
    "C:/xampp/htdocs/"
];

foreach ($search_paths as $path) {
    echo "<h3>Checking: $path</h3>";
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if (strpos($file, 'slide') !== false && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "<div style='background:#d4edda; padding:10px; margin:5px; border-radius:5px;'>";
                echo "✅ FOUND: <strong>$file</strong> at: $path$file<br>";
                echo "<img src='$path$file' width='200' style='margin-top:10px; border:2px solid green;'>";
                echo "</div>";
            }
        }
    } else {
        echo "❌ Directory does not exist: $path<br>";
    }
}

// Also search recursively in entire htdocs
echo "<h3>Searching entire htdocs folder (this may take a moment)...</h3>";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("C:/xampp/htdocs/"));
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/slide[0-9]+\.(jpg|jpeg|png|gif)$/i', $file->getFilename())) {
        echo "<div style='background:#d4edda; padding:10px; margin:5px;'>";
        echo "✅ FOUND: " . $file->getFilename() . " at: " . $file->getPathname() . "<br>";
        echo "<img src='" . str_replace("C:/xampp/htdocs/", "/", $file->getPathname()) . "' width='200'>";
        echo "</div>";
    }
}
?>