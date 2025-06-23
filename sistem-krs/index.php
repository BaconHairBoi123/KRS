<?php

// index.php - Entry point for the application

// Start the session
session_start();

// Include necessary files
require_once 'config.php';
require_once 'functions.php';

// Check if the user is already logged in
if (is_logged_in()) {
    // Redirect to the dashboard
    redirect('dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate username and password
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Authenticate user
        $user = authenticate_user($username, $password);

        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to the dashboard
            redirect('dashboard.php');
        } else {
            // Invalid credentials
            $error = 'Invalid username or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h2>Login</h2>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>

</body>
</html>
<?php
// Update redirect destination from login-soft.php to login.php
function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
