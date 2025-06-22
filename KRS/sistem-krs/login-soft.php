<?php
// Gunakan path absolut untuk menghindari masalah path
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard-soft.php');
}

$error = '';

if ($_POST) {
    $auth = new Auth();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        redirect('dashboard-soft.php');
    } else {
        $error = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
            min-height: 100vh;
            font-family: 'Open Sans', sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .btn-gradient {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .form-input {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: #7928ca;
            box-shadow: 0 0 0 3px rgba(121, 40, 202, 0.1);
            outline: none;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <div class="login-card rounded-3xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 btn-gradient rounded-2xl mb-4">
                    <i class="fas fa-graduation-cap text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistem KRS</h1>
                <p class="text-gray-600">Masuk ke akun Anda</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <input type="text" id="username" name="username" required
                           class="form-input w-full" placeholder="Masukkan username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                           class="form-input w-full" placeholder="Masukkan password">
                </div>

                <button type="submit" 
                        class="btn-gradient w-full py-3 px-4 text-white font-semibold rounded-xl">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Masuk
                </button>
            </form>

            <!-- Demo Info -->
            <div class="mt-8 p-4 bg-blue-50 rounded-xl">
                <h4 class="text-sm font-semibold text-blue-800 mb-3">🔑 Demo Login:</h4>
                <div class="text-xs text-blue-700 space-y-2">
                    <div class="flex justify-between">
                        <span><strong>Mahasiswa:</strong></span>
                        <span>mahasiswa1 / password</span>
                    </div>
                    <div class="flex justify-between">
                        <span><strong>Dosen:</strong></span>
                        <span>dosen1 / password</span>
                    </div>
                    <div class="flex justify-between">
                        <span><strong>Admin:</strong></span>
                        <span>admin / password</span>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="mt-4 p-3 bg-gray-100 rounded-lg text-xs">
                <strong>Debug Info:</strong><br>
                Current Directory: <?php echo __DIR__; ?><br>
                Config Path: <?php echo __DIR__ . '/config/config.php'; ?><br>
                Auth Path: <?php echo __DIR__ . '/includes/auth.php'; ?><br>
                Config Exists: <?php echo file_exists(__DIR__ . '/config/config.php') ? 'YES' : 'NO'; ?><br>
                Auth Exists: <?php echo file_exists(__DIR__ . '/includes/auth.php') ? 'YES' : 'NO'; ?>
            </div>
        </div>
    </div>
</body>
</html>
