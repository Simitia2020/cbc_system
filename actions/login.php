<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            // Store user session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: ../dashboard/admin.php");
            } 
            elseif ($user['role'] == 'teacher') {
                header("Location: ../dashboard/teacher.php");
            } 
            elseif ($user['role'] == 'parent') {
                header("Location: ../dashboard/parent.php");
            } 
            else {
                header("Location: ../dashboard/student.php");
            }
            exit();
        }
    }
    
    // If login fails
    echo "Invalid username or password.<br><br>";
    echo "<a href='../index.php'>← Try Login Again</a>";
}
?>