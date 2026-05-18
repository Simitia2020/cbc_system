<?php
session_start();
include("config/db.php");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['login_input']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE (national_id = ? OR email = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            if ($user['status'] == NULL || $user['status'] == '') {
                $update = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                $user['status'] = 'approved';
            }
            
            if ($user['status'] == 'pending') {
                $error = "⏳ Your account is pending admin approval.";
            } elseif ($user['status'] == 'rejected') {
                $error = "❌ Your account has been rejected.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['national_id'] = $user['national_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                if ($user['role'] == 'admin') {
                    header("Location: dashboard/admin.php");
                } elseif ($user['role'] == 'teacher') {
                    header("Location: dashboard/teacher.php");
                } elseif ($user['role'] == 'parent') {
                    header("Location: dashboard/parent.php");
                }
                exit();
            }
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "❌ User not found! Check your National ID or Email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBC Kenya - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a8a4a 0%, #0d5c2e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #00a651, #008c44);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .kenya-flag {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 15px 0;
        }
        .flag-stripe {
            width: 40px;
            height: 5px;
            border-radius: 2px;
        }
        .stripe-black { background: #000; }
        .stripe-red { background: #B30000; }
        .stripe-green { background: #006600; }
        .stripe-white { background: #fff; }
        .content {
            padding: 30px;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #00a651;
            box-shadow: 0 0 0 3px rgba(0,166,81,0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00a651, #008c44);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #008c44, #006633);
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #f44336;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .signup-link a {
            color: #00a651;
            text-decoration: none;
            font-weight: bold;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        .info {
            background: #e8f5e9;
            color: #00a651;
            padding: 12px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
        }
        label {
            font-weight: 600;
            display: block;
            margin-top: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="kenya-flag">
                <div class="flag-stripe stripe-black"></div>
                <div class="flag-stripe stripe-red"></div>
                <div class="flag-stripe stripe-green"></div>
                <div class="flag-stripe stripe-white"></div>
                <div class="flag-stripe stripe-black"></div>
                <div class="flag-stripe stripe-red"></div>
                <div class="flag-stripe stripe-green"></div>
            </div>
            <h1>🇰🇪 CBC Kenya</h1>
            <p>Competency Based Curriculum Management System</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label>📧 National ID or Email</label>
                <input type="text" name="login_input" placeholder="Enter your National ID or Email" required autofocus>
                
                <label>🔒 Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
                
                <button type="submit">🔐 Login to Dashboard</button>
            </form>
            
            <div class="signup-link">
                Don't have an account? <a href="signup.php">📝 Sign Up here</a>
            </div>
            
            <div class="info">
                <strong>📌 CBC Kenya System</strong><br>
                Teachers & Parents: Login using your <strong>National ID Number</strong><br>
                📧 You can also login using your email address<br>
                🆕 New users can Sign Up above (requires admin approval)
            </div>
        </div>
    </div>
</body>
</html>