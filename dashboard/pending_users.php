<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Approve user
if (isset($_GET['approve'])) {
    $user_id = intval($_GET['approve']);
    $admin_id = $_SESSION['user_id'];
    
    $update = $conn->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
    $update->bind_param("ii", $admin_id, $user_id);
    
    if ($update->execute()) {
        $message = "✅ User approved successfully!";
    } else {
        $error = "❌ Failed to approve user.";
    }
}

// Reject user
if (isset($_GET['reject'])) {
    $user_id = intval($_GET['reject']);
    
    $update = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
    $update->bind_param("i", $user_id);
    
    if ($update->execute()) {
        $message = "⚠️ User rejected.";
    } else {
        $error = "❌ Failed to reject user.";
    }
}

// Get pending users
$pending_query = "SELECT id, full_name, national_id, email, role, created_at 
                  FROM users 
                  WHERE status = 'pending' 
                  ORDER BY created_at DESC";
$pending_users = $conn->query($pending_query);

// Get approved users count
$approved_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'approved'")->fetch_assoc()['count'];
$pending_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'rejected'")->fetch_assoc()['count'];
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">⏳ Pending User Approvals</h3>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
            <h3 style="color: #00a651; margin: 0;"><?= $approved_count ?></h3>
            <p style="margin: 5px 0 0;">Approved Users</p>
        </div>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
            <h3 style="color: #FF9800; margin: 0;"><?= $pending_count ?></h3>
            <p style="margin: 5px 0 0;">Pending Approval</p>
        </div>
        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
            <h3 style="color: #f44336; margin: 0;"><?= $rejected_count ?></h3>
            <p style="margin: 5px 0 0;">Rejected</p>
        </div>
    </div>
    
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
    
    <?php if ($pending_users && $pending_users->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: white;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Registered On</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Full Name</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">National ID</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Email</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Role</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $pending_users->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['full_name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><code><?= htmlspecialchars($user['national_id']) ?></code></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['email']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <span style="background: <?= $user['role'] == 'teacher' ? '#2196F3' : '#FF9800' ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="?approve=<?= $user['id'] ?>" 
                                   onclick="return confirm('Approve <?= addslashes($user['full_name']) ?>?')" 
                                   style="background: #00a651; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; display: inline-block;">
                                    ✅ Approve
                                </a>
                                <a href="?reject=<?= $user['id'] ?>" 
                                   onclick="return confirm('Reject <?= addslashes($user['full_name']) ?>?')" 
                                   style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                    ❌ Reject
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: #e8f5e9; padding: 40px; border-radius: 8px; text-align: center;">
            <p style="color: #00a651; font-size: 18px;">✅ No pending user approvals!</p>
            <p style="color: #666;">All users have been processed.</p>
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>