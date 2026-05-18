<?php
session_start();
include("../config/db.php");

$admin_authenticated = isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'admin';
$login_error = '';
$message = '';
$error = '';

if (!$admin_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login_input === '' || $password === '') {
        $login_error = 'Please enter your admin credentials.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE (national_id = ? OR email = ?) AND role = 'admin' LIMIT 1");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (($user['status'] ?? '') !== 'approved') {
                $login_error = 'Admin account is not approved.';
            } elseif (!password_verify($password, $user['password'])) {
                $login_error = 'Invalid admin password.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['national_id'] = $user['national_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                header("Location: admin.php");
                exit();
            }
        } else {
            $login_error = 'Admin account not found.';
        }
    }
}

if (!$admin_authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - CBC Kenya</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #145a32, #0b8f47);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .card {
                width: 100%;
                max-width: 460px;
                background: #fff;
                border-radius: 14px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
                overflow: hidden;
            }
            .head {
                background: #00a651;
                color: #fff;
                padding: 28px;
                text-align: center;
            }
            .head h1 { font-size: 30px; margin-bottom: 6px; }
            .head p { font-size: 14px; opacity: 0.95; }
            .body { padding: 24px; }
            label { display: block; margin: 8px 0; color: #333; font-weight: 600; }
            input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ccc;
                border-radius: 8px;
                margin-bottom: 14px;
                font-size: 15px;
            }
            button {
                width: 100%;
                border: 0;
                border-radius: 8px;
                background: #00a651;
                color: #fff;
                padding: 13px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
            }
            .error {
                background: #f8d7da;
                color: #842029;
                border: 1px solid #f5c2c7;
                border-radius: 8px;
                padding: 10px;
                margin-bottom: 12px;
            }
            .hint { margin-top: 14px; font-size: 13px; color: #555; text-align: center; }
            .hint a { color: #0b8f47; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="head">
                <h1>Admin Portal</h1>
                <p>CBC Kenya Management</p>
            </div>
            <div class="body">
                <?php if ($login_error): ?>
                    <div class="error"><?= htmlspecialchars($login_error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="admin_login" value="1">
                    <label>Admin National ID or Email</label>
                    <input type="text" name="login_input" required>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <button type="submit">Login as Admin</button>
                </form>

                <div class="hint">Not an admin? <a href="../index.php">Go to main login</a></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

include("../includes/sidebar.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $target_user_id = intval($_POST['user_id']);
    $admin_id = intval($_SESSION['user_id']);
    $action = $_POST['action'];

    if ($target_user_id > 0) {
        if ($action === 'approve') {
            $update = $conn->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
            $update->bind_param("ii", $admin_id, $target_user_id);
            if ($update->execute()) {
                $message = "User approved successfully.";
            } else {
                $error = "Failed to approve user.";
            }
        } elseif ($action === 'reject') {
            $update = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            $update->bind_param("i", $target_user_id);
            if ($update->execute()) {
                $message = "User rejected successfully.";
            } else {
                $error = "Failed to reject user.";
            }
        }
    }
}

$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch_assoc()['count'];
$total_parents = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='parent'")->fetch_assoc()['count'];
$total_assessments = $conn->query("SELECT COUNT(*) as count FROM assessments")->fetch_assoc()['count'];
$total_assignments = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments")->fetch_assoc()['count'];
$pending_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='pending'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='rejected'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='approved'")->fetch_assoc()['count'];

$pending_users = $conn->query("SELECT id, full_name, national_id, email, role, created_at FROM users WHERE status='pending' ORDER BY created_at DESC");
?>

<!-- Main Content Area -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #00a651, #008c44); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_students ?></h2>
        <p>Total Students</p>
    </div>

    <div style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_teachers ?></h2>
        <p>Total Teachers</p>
    </div>

    <div style="background: linear-gradient(135deg, #FF9800, #F57C00); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_parents ?></h2>
        <p>Total Parents</p>
    </div>

    <div style="background: linear-gradient(135deg, #9C27B0, #7B1FA2); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_assessments ?></h2>
        <p>Assessments Recorded</p>
    </div>

    <div style="background: linear-gradient(135deg, #f44336, #d32f2f); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $total_assignments ?></h2>
        <p>Teacher Assignments</p>
    </div>

    <div style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 25px; border-radius: 12px;">
        <h2 style="font-size: 36px;"><?= $pending_count ?></h2>
        <p>Pending Approvals</p>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
        <a href="admin_add_user.php" style="display: block; padding: 15px; background: #00a651; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            Add User/Student
        </a>
        <a href="view_users.php" style="display: block; padding: 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            View All Users
        </a>
        <a href="assign_teacher.php" style="display: block; padding: 15px; background: #FF9800; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            Assign Teacher to Grade
        </a>
        <a href="teacher_assignments.php" style="display: block; padding: 15px; background: #9C27B0; color: white; text-decoration: none; border-radius: 8px; text-align: center;">
            View All Assignments
        </a>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 25px;">
    <h3 style="color: #00a651; margin-bottom: 20px;">Pending User Approvals</h3>

    <div style="display: grid; grid-template-columns: repeat(3, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: #fff3cd; padding: 12px; border-radius: 8px; text-align: center;">
            <strong style="display: block; color: #856404;"><?= $pending_count ?></strong>
            <span>Pending</span>
        </div>
        <div style="background: #d4edda; padding: 12px; border-radius: 8px; text-align: center;">
            <strong style="display: block; color: #155724;"><?= $approved_count ?></strong>
            <span>Approved</span>
        </div>
        <div style="background: #f8d7da; padding: 12px; border-radius: 8px; text-align: center;">
            <strong style="display: block; color: #721c24;"><?= $rejected_count ?></strong>
            <span>Rejected</span>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($pending_users && $pending_users->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #00a651; color: #fff;">
                        <th style="padding: 10px; border: 1px solid #ddd;">Registered</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Name</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">National ID</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Email</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Role</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $pending_users->fetch_assoc()): ?>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d M Y H:i', strtotime($user['created_at'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['full_name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['national_id']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($user['email']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <form method="POST" style="display: inline-block; margin-right: 6px;" onsubmit="return confirm('Approve this user?');">
                                    <input type="hidden" name="user_id" value="<?= intval($user['id']) ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" style="background: #00a651; color: #fff; border: 0; border-radius: 5px; padding: 6px 10px; cursor: pointer;">Approve</button>
                                </form>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reject this user?');">
                                    <input type="hidden" name="user_id" value="<?= intval($user['id']) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" style="background: #f44336; color: #fff; border: 0; border-radius: 5px; padding: 6px 10px; cursor: pointer;">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; color: #1b5e20;">
            No pending users to approve right now.
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>
