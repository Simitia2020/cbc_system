<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Don't allow admin to delete themselves
    if ($user_id == $_SESSION['user_id']) {
        $error = "❌ You cannot delete your own account!";
    } else {
        // Check if user has related records
        $check_students = $conn->query("SELECT id FROM students WHERE parent_id = $user_id LIMIT 1");
        $check_assessments = $conn->query("SELECT id FROM assessments WHERE teacher_id = $user_id LIMIT 1");
        
        if ($check_students->num_rows > 0 || $check_assessments->num_rows > 0) {
            $error = "❌ Cannot delete this user. They have related records (students linked or assessments made).";
        } else {
            $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete->bind_param("i", $user_id);
            if ($delete->execute()) {
                $message = "✅ User deleted successfully!";
            } else {
                $error = "❌ Failed to delete user.";
            }
        }
    }
}

// Get all users with their roles
$users_query = "SELECT id, full_name, national_id, email, role, created_at FROM users ORDER BY role, full_name";
$users = $conn->query($users_query);
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">👥 Manage Users</h3>
    
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
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #00a651; color: white;">
                    <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Full Name</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">National ID</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Email</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Role</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Created</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $users->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $user['id'] ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <?= htmlspecialchars($user['full_name']) ?>
                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                <span style="background: #00a651; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">You</span>
                            <?php endif; ?>
                        </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <code><?= htmlspecialchars($user['national_id']) ?></code>
                        </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['email']) ?></span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <span style="background: 
                                <?= $user['role'] == 'admin' ? '#f44336' : ($user['role'] == 'teacher' ? '#2196F3' : '#FF9800') ?>; 
                                color: white; padding: 4px 8px; border-radius: 4px;">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                        <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" 
                               style="background: #2196F3; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; display: inline-block;">
                                ✏️ Edit
                            </a>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?= $user['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to delete <?= addslashes($user['full_name']) ?>? This action cannot be undone.')" 
                                   style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                    🗑️ Delete
                                </a>
                            <?php endif; ?>
                        </span>
                    </span>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="admin_add_user.php" style="display: inline-block; padding: 10px 20px; background: #00a651; color: white; text-decoration: none; border-radius: 6px;">
                + Add New User
            </a>
        </div>
    </div>

</div>
</body>
</html>