<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Validate national ID
    if (empty($national_id)) {
        $error = "❌ National ID is required!";
    } elseif ($role != 'admin' && strlen($national_id) < 5) {
        $error = "❌ Please enter a valid National ID!";
    } else {
        // Check if national ID already exists
        $check_id = $conn->prepare("SELECT id FROM users WHERE national_id = ?");
        $check_id->bind_param("s", $national_id);
        $check_id->execute();
        if ($check_id->get_result()->num_rows > 0) {
            $error = "❌ National ID already exists!";
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (full_name, national_id, email, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $national_id, $email, $role, $password);
            
            if ($stmt->execute()) {
                $message = "✅ User added successfully! They can login with National ID: $national_id";
            } else {
                $error = "❌ Failed to add user.";
            }
        }
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👤 Add New User</h3>
    
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
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-top: 15px;">Full Name</label>
        <input type="text" name="full_name" required 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">National ID Number</label>
        <input type="text" name="national_id" required placeholder="e.g., 12345678" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        <small style="color: #666;">Teachers and parents will use this to login</small>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Email (Optional)</label>
        <input type="email" name="email" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Role</label>
        <select name="role" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="admin">Admin</option>
            <option value="teacher">Teacher</option>
            <option value="parent">Parent</option>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Password</label>
        <input type="password" name="password" required 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <button type="submit" name="add_user" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px;">
            ➕ Add User
        </button>
    </form>
</div>

</div>
</body>
</html>