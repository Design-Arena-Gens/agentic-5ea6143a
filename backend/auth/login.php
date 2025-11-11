<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /backend/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email format.';
    } else {
        try {
            $user = db()->fetchOne(
                'SELECT id, email, password, full_name, role, status FROM users WHERE email = :email',
                [':email' => $email]
            );

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account is not active. Please contact support.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // Update last login
                    db()->update(
                        'users',
                        ['last_login' => date('Y-m-d H:i:s')],
                        'id = :id',
                        [':id' => $user['id']]
                    );

                    // Log activity
                    db()->insert('activity_logs', [
                        'user_id' => $user['id'],
                        'action' => 'login',
                        'description' => 'User logged in',
                        'ip_address' => get_client_ip(),
                        'user_agent' => get_user_agent()
                    ]);

                    // Redirect
                    header('Location: /backend/admin/index.php');
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';

                // Log failed attempt
                db()->insert('activity_logs', [
                    'action' => 'login_failed',
                    'description' => "Failed login attempt for email: $email",
                    'ip_address' => get_client_ip(),
                    'user_agent' => get_user_agent()
                ]);
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Core Official</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .login-container h1 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .login-container p { color: #7f8c8d; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; transition: border 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 0.75rem; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5568d3; }
        .error { background: #fee; color: #c33; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #fcc; }
        .info { background: #e7f3ff; color: #0066cc; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #b3d9ff; }
        .register-link { text-align: center; margin-top: 1rem; color: #7f8c8d; }
        .register-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Welcome Back</h1>
        <p>Login to Core Official Admin Panel</p>

        <?php if (isset($_GET['expired'])): ?>
            <div class="info">Your session has expired. Please login again.</div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="info">Registration successful! Please login.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
