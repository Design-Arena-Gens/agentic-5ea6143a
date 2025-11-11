<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /backend/admin/index.php');
    exit;
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Validate inputs
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!validate_email($email)) {
            $errors[] = 'Invalid email format.';
        }

        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($full_name) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (!validate_password($password)) {
            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        // Check if email already exists
        if (empty($errors)) {
            try {
                $existing = db()->fetchOne(
                    'SELECT id FROM users WHERE email = :email',
                    [':email' => $email]
                );

                if ($existing) {
                    $errors[] = 'Email already registered.';
                }
            } catch (Exception $e) {
                error_log('Registration check error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }

        // Create user
        if (empty($errors) && empty($error)) {
            try {
                $user_id = db()->insert('users', [
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'full_name' => $full_name,
                    'role' => 'user',
                    'status' => 'active'
                ]);

                // Log activity
                db()->insert('activity_logs', [
                    'user_id' => $user_id,
                    'action' => 'register',
                    'description' => 'New user registered',
                    'ip_address' => get_client_ip(),
                    'user_agent' => get_user_agent()
                ]);

                // Redirect to login
                header('Location: login.php?registered=1');
                exit;
            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = 'An error occurred during registration. Please try again later.';
            }
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Core Official</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .register-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 450px; }
        .register-container h1 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .register-container p { color: #7f8c8d; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; transition: border 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .form-group small { display: block; margin-top: 0.25rem; color: #7f8c8d; font-size: 0.85rem; }
        .btn { width: 100%; padding: 0.75rem; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5568d3; }
        .error { background: #fee; color: #c33; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #fcc; line-height: 1.6; }
        .login-link { text-align: center; margin-top: 1rem; color: #7f8c8d; }
        .login-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Create Account</h1>
        <p>Register for Core Official Admin Panel</p>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small>At least 8 characters with uppercase, lowercase, and number</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
