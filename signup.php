<?php
session_start();
include("config/db.php");

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters!";
    } elseif (strlen($national_id) < 5) {
        $error = "❌ Please enter a valid National ID!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Please enter a valid email address!";
    } else {
        $check_national = $conn->prepare("SELECT id FROM users WHERE national_id = ?");
        $check_national->bind_param("s", $national_id);
        $check_national->execute();
        
        if ($check_national->get_result()->num_rows > 0) {
            $error = "❌ National ID already registered! Please login.";
        } else {
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error = "❌ Email already registered! Please login.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $status = 'pending';
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, national_id, email, role, password, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $full_name, $national_id, $email, $role, $hashed_password, $status);
                
                if ($stmt->execute()) {
                    $success = "✅ Registration submitted! Your account is pending admin approval.";
                    $full_name = $national_id = $email = '';
                } else {
                    $error = "❌ Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBC Kenya - Sign Up</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a8a4a 0%, #0d5c2e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
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
        input, select {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input:focus, select:focus {
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
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #00a651;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a {
            color: #00a651;
            text-decoration: none;
            font-weight: bold;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        label {
            font-weight: 600;
            display: block;
            margin-top: 10px;
            color: #333;
        }
        .role-info {
            font-size: 12px;
            color: #666;
            margin-top: -5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
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
            <h1>📝 Create Account</h1>
            <p>Join the CBC Kenya Education System</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label>👤 Full Name</label>
                <input type="text" name="full_name" required placeholder="Enter your full name">
                
                <label>🆔 National ID Number</label>
                <input type="text" name="national_id" required placeholder="e.g., 12345678">
                <div class="role-info">You will use this to login</div>
                
                <label>📧 Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
                
                <label>👔 I am a:</label>
                <select name="role" required>
                    <option value="teacher">👨‍🏫 Teacher</option>
                    <option value="parent">👨‍👩‍👧‍👦 Parent</option>
                </select>
                <div class="role-info">Select your role in the CBC system</div>
                
                <label>🔒 Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters">
                
                <label>🔒 Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter your password">
                
                <button type="submit">📝 Register Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="index.php">🔐 Login here</a>
            </div>
        </div>
    </div>
</body>
</html>