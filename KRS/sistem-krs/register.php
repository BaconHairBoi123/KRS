<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_POST) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $nim = $_POST['nim'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';
    $program_studi = $_POST['program_studi'] ?? '';
    $angkatan = $_POST['angkatan'] ?? '';
    
    // Validasi
    if (empty($password) || empty($email) || empty($nim) || empty($nama_lengkap)) {
        $error = 'NIM, Email, Nama Lengkap, dan Password wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        $auth = new Auth();
        $data = [
            'password' => $password,
            'email' => $email,
            'nim' => $nim,
            'nama_lengkap' => $nama_lengkap,
            'tempat_lahir' => $tempat_lahir,
            'tanggal_lahir' => $tanggal_lahir,
            'jenis_kelamin' => $jenis_kelamin,
            'alamat' => $alamat,
            'no_telepon' => $no_telepon,
            'program_studi' => $program_studi,
            'angkatan' => $angkatan
        ];
        
        if ($auth->register($data)) {
            $success = 'Registrasi berhasil! Silakan login dengan NIM dan password Anda.';
        } else {
            $error = 'Registrasi gagal! Email atau NIM mungkin sudah digunakan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Mahasiswa - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme-toggle.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 900px;
            padding: 3rem;
        }
        
        .form-input, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.875rem 1.125rem;
            transition: all 0.3s ease;
            background: white;
            width: 100%;
        }
        
        .form-input:focus, .form-select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
            outline: none;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="theme-toggle-container"></div>
    
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 btn-gradient rounded-2xl mb-4">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Registrasi Mahasiswa Baru</h1>
                <p class="text-gray-600">Daftarkan diri Anda untuk mengakses Sistem KRS</p>
            </div>

            <!-- Alert Messages -->
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

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo $success; ?></p>
                            <p class="text-sm text-green-600 mt-2">
                                <a href="login.php" class="font-semibold hover:underline">Klik di sini untuk login</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Kolom Kiri -->
                    <div class="space-y-6">
                        <h3 class="section-title">Informasi Akun</h3>
                        
                        <div>
                            <label for="nim" class="block text-sm font-semibold text-gray-700 mb-2">NIM *</label>
                            <input type="text" id="nim" name="nim" required
                                   class="form-input" placeholder="Nomor Induk Mahasiswa"
                                   value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input type="email" id="email" name="email" required
                                   class="form-input" placeholder="contoh@email.com"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                            <input type="password" id="password" name="password" required
                                   class="form-input" placeholder="Minimal 6 karakter">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="form-input" placeholder="Ulangi password">
                        </div>

                        <h3 class="section-title">Informasi Akademik</h3>
                        
                        <div>
                            <label for="program_studi" class="block text-sm font-semibold text-gray-700 mb-2">Program Studi *</label>
                            <select id="program_studi" name="program_studi" required class="form-select">
                                <option value="">Pilih Program Studi</option>
                                <option value="Informatika" <?php echo ($_POST['program_studi'] ?? '') == 'Informatika' ? 'selected' : ''; ?>>Informatika</option>
                                <option value="Sistem Informasi" <?php echo ($_POST['program_studi'] ?? '') == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                                <option value="Teknik Komputer" <?php echo ($_POST['program_studi'] ?? '') == 'Teknik Komputer' ? 'selected' : ''; ?>>Teknik Komputer</option>
                                <option value="Manajemen Informatika" <?php echo ($_POST['program_studi'] ?? '') == 'Manajemen Informatika' ? 'selected' : ''; ?>>Manajemen Informatika</option>
                            </select>
                        </div>

                        <div>
                            <label for="angkatan" class="block text-sm font-semibold text-gray-700 mb-2">Angkatan *</label>
                            <select id="angkatan" name="angkatan" required class="form-select">
                                <option value="">Pilih Angkatan</option>
                                <?php 
                                $current_year = date('Y');
                                for ($year = $current_year; $year >= $current_year - 10; $year--): 
                                ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($_POST['angkatan'] ?? '') == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="space-y-6">
                        <h3 class="section-title">Informasi Pribadi</h3>
                        
                        <div>
                            <label for="nama_lengkap" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" required
                                   class="form-input" placeholder="Nama lengkap sesuai KTP"
                                   value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="tempat_lahir" class="block text-sm font-semibold text-gray-700 mb-2">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir"
                                   class="form-input" placeholder="Kota tempat lahir"
                                   value="<?php echo htmlspecialchars($_POST['tempat_lahir'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="jenis_kelamin" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                            <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo ($_POST['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($_POST['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label for="no_telepon" class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon</label>
                            <input type="tel" id="no_telepon" name="no_telepon"
                                   class="form-input" placeholder="08xxxxxxxxxx"
                                   value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="alamat" class="block text-sm font-semibold text-gray-700 mb-2">Alamat</label>
                            <textarea id="alamat" name="alamat" rows="4"
                                      class="form-input" placeholder="Alamat lengkap"><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" 
                            class="btn-gradient flex-1 text-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Daftar Sekarang
                    </button>
                    
                    <a href="login.php" 
                       class="flex-1 py-3 px-6 text-center border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Login
                    </a>
                </div>
            </form>

            <!-- Info -->
            <div class="mt-8 p-4 bg-blue-50 rounded-xl">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">📝 Catatan Penting:</h4>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• Field yang bertanda (*) wajib diisi</li>
                    <li>• NIM akan digunakan sebagai username untuk login</li>
                    <li>• Password minimal 6 karakter</li>
                    <li>• NIM harus sesuai dengan data resmi universitas</li>
                    <li>• Setelah registrasi berhasil, login menggunakan NIM dan password</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
