<?php 

session_start();
require_once 'config/db.php';

$error = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch the user from the database
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify user exists and password matches
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // 'owner' or 'staff'

        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Store Checker System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            padding: 20px;
        }
        .login-container { 
            background: white; 
            padding: 2.5rem; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 420px;
            text-align: center;
        }
        .login-header {
            margin-bottom: 2rem;
        }
        .login-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }
        .login-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.95rem;
        }
        .logo-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        .form-group { 
            margin-bottom: 1.25rem; 
            text-align: left;
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 500;
            color: #475569;
            font-size: 0.9rem;
        }
        input[type="text"], input[type="password"] { 
            width: 100%; 
            padding: 0.875rem 1rem; 
            box-sizing: border-box;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button { 
            width: 100%; 
            padding: 1rem; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        button:active {
            transform: translateY(0);
        }
        .error { 
            background: #fef2f2;
            color: #dc2626;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            border: 1px solid #fecaca;
        }
        .footer-text {
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <span class="logo-icon">🏪</span>
            <h2>Welcome Back!</h2>
            <p>Sign in to access your store dashboard</p>
        </div>
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit">Sign In →</button>
        </form>
        <p class="footer-text">Smart Store Checker System © 2024</p>
    </div>
</body>
</html>