<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $url = trim($_POST['url']);
    $subject = trim($_POST['subject']);

    $stmt = $conn->prepare("INSERT INTO resources (title, description, type, url, subject, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $title, $description, $type, $url, $subject, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo "Resource added successfully!";
    }
}
?>

<h2>Add Revision Material / Video</h2>
<form method="POST">
    Title: <input type="text" name="title" required><br>
    Description: <textarea name="description"></textarea><br>
    Type: 
    <select name="type">
        <option value="video">Video</option>
        <option value="note">Notes</option>
        <option value="assignment">Assignment</option>
    </select><br>
    URL/Link: <input type="text" name="url" required><br>
    Subject: <input type="text" name="subject"><br>
    <button type="submit">Upload to System</button>
</form>