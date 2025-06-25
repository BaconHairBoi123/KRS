<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_POST) {
    $auth = new Auth();
    $nim = $_POST['nim'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($nim, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'NIM atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        ::-webkit-scrollbar {
            display: none;
        }

        * {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        
        .video-section {
            flex: 0 0 50%;
            width: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .video-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            padding: 2rem;
        }
        
        .university-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .login-section {
            flex: 0 0 50%;
            width: 50%;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            color: #718096;
            font-weight: 400;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #2d3748;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }
        
        .form-input::placeholder {
            color: #a0aec0;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .register-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #00f2fe;
        }
        
        .error-alert {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                height: 100vh;
            }
            
            .video-section {
                flex: 0 0 40vh;
                height: 40vh;
                width: 100%;
            }
            
            .login-section {
                flex: 0 0 60vh;
                height: 60vh;
                width: 100%;
                padding: 1rem;
            }
            
            .login-form-container {
                padding: 2rem;
                max-height: 55vh;
                overflow-y: auto;
            }
            
            .welcome-text h1 {
                font-size: 2rem;
                margin-bottom: 0.25rem;
            }
            
            .welcome-text p {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .register-link {
                margin-top: 1rem;
                padding-top: 1rem;
            }
            
            .register-link p {
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
            }
            
            .video-content {
                padding: 1rem;
            }
            
            .video-content h2 {
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            
            .video-content p {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .video-content .flex {
                flex-direction: column;
                gap: 1rem;
            }
            
            .video-content .text-center {
                margin-bottom: 0.5rem;
            }
            
            .video-content .text-2xl {
                font-size: 1.25rem;
            }
            
            .video-content .text-sm {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .video-section {
                flex: 0 0 35vh;
                height: 35vh;
            }
            
            .login-section {
                flex: 0 0 65vh;
                height: 65vh;
                padding: 0.75rem;
            }
            
            .login-form-container {
                padding: 1.5rem;
                max-height: 60vh;
                border-radius: 15px;
            }
            
            .welcome-text h1 {
                font-size: 1.75rem;
            }
            
            .form-input {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .submit-btn {
                padding: 0.875rem;
                font-size: 0.9rem;
            }
            
            .video-content h2 {
                font-size: 1.5rem;
            }
            
            .video-content p {
                font-size: 0.875rem;
            }
            
            .video-content .text-2xl {
                font-size: 1rem;
            }
            
            .video-content .text-sm {
                font-size: 0.7rem;
            }
            
            .video-content .flex {
                gap: 0.75rem;
            }
        }

        @media (max-width: 768px) and (orientation: landscape) {
            .login-container {
                flex-direction: row;
            }
            
            .video-section {
                flex: 0 0 45%;
                height: 100vh;
                width: 45%;
            }
            
            .login-section {
                flex: 0 0 55%;
                height: 100vh;
                width: 55%;
                padding: 1rem;
            }
            
            .login-form-container {
                max-height: 85vh;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Video Section -->
        <div class="video-section">
            <video class="university-video" autoplay muted loop>
                <source src="assets/videos/university-intro.mp4" type="video/mp4">
            </video>
            <div class="video-overlay"></div>
            <div class="video-content">
                <div class="mb-8">
                    <i class="fas fa-university text-6xl mb-4 opacity-80"></i>
                    <h2 class="text-4xl font-bold mb-4">Universitas Touhou Indonesia</h2>
                    <p class="text-xl opacity-90 max-w-md mx-auto">
                        Bergabunglah dengan ribuan mahasiswa dalam perjalanan pendidikan digital yang inovatif dan modern.
                    </p>
                </div>
                <div class="flex justify-center space-x-8 text-sm opacity-75">
                    <div class="text-center">
                        <div class="text-2xl font-bold">15,000+</div>
                        <div>Mahasiswa Aktif</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">500+</div>
                        <div>Dosen Berkualitas</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">50+</div>
                        <div>Program Studi</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Section -->
        <div class="login-section">
            <div class="login-form-container">
                <div class="welcome-text">
                    <h1>WELCOME</h1>
                    <p>Masuk ke Sistem Akademik</p>
                </div>

                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST">
                    <div class="form-group">
                        <input type="text" 
                               name="nim" 
                               class="form-input" 
                               placeholder="NIM" 
                               required
                               value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <input type="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="Password" 
                               required>
                    </div>

                    <button type="submit" class="submit-btn">
                        SUBMIT
                    </button>
                </form>

                <!-- Register Link -->
                <div class="register-link">
                    <p class="text-gray-600 mb-2">Belum punya akun?</p>
                    <a href="register.php">
                        <i class="fas fa-user-plus mr-2"></i>
                        Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nimInput = document.querySelector('input[name="nim"]');
            if (nimInput) {
                nimInput.focus();
            }
            
            const errorAlert = document.querySelector('.error-alert');
            if (errorAlert) {
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.name === 'nim') {
                    document.querySelector('input[name="password"]').focus();
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
