<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Get user details
$user_query = $conn->prepare("SELECT id, full_name, national_id, email, role FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

if (!$user) {
    header("Location: view_users.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    // Check if national ID already exists for another user
    $check_id = $conn->prepare("SELECT id FROM users WHERE national_id = ? AND id != ?");
    $check_id->bind_param("si", $national_id, $user_id);
    $check_id->execute();
    if ($check_id->get_result()->num_rows > 0) {
        $error = "❌ National ID already exists for another user!";
    } else {
        // Update user
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET full_name = ?, national_id = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $update->bind_param("sssssi", $full_name, $national_id, $email, $role, $hashed_password, $user_id);
        } else {
            $update = $conn->prepare("UPDATE users SET full_name = ?, national_id = ?, email = ?, role = ? WHERE id = ?");
            $update->bind_param("ssssi", $full_name, $national_id, $email, $role, $user_id);
        }
        
        if ($update->execute()) {
            $message = "✅ User updated successfully!";
            $user_query->execute();
            $user = $user_query->get_result()->fetch_assoc();
        } else {
            $error = "❌ Failed to update user.";
        }
    }
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">✏️ Edit User</h3>
    
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
        <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">National ID Number</label>
        <input type="text" name="national_id" required value="<?= htmlspecialchars($user['national_id']) ?>" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Role</label>
        <select name="role" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="teacher" <?= $user['role'] == 'teacher' ? 'selected' : '' ?>>Teacher</option>
            <option value="parent" <?= $user['role'] == 'parent' ? 'selected' : '' ?>>Parent</option>
        </select>
        
        <label style="font-weight: bold; display: block; margin-top: 15px;">Password (Leave blank to keep current)</label>
        <input type="password" name="password" placeholder="Enter new password to change" 
               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px;">
        
        <div style="margin-top: 20px;">
            <button type="submit" name="update_user" style="background: #00a651; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer;">
                💾 Save Changes
            </button>
            <a href="view_users.php" style="background: #666; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin-left: 10px;">
                ← Cancel
            </a>
        </div>
    </form>
</div>

</div>
</body>
</html>