<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$success = '';
$error = '';

if ($_POST) {
    $auth = new Auth();
    
    // Validate required fields
    $required_fields = ['nim', 'nama', 'email', 'password', 'jurusan', 'program_studi'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'Harap lengkapi semua field yang wajib diisi.';
    } else {
        $data = [
            'nim' => $_POST['nim'],
            'nama' => $_POST['nama'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'tanggal_lahir' => $_POST['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $_POST['jenis_kelamin'] ?? null,
            'alamat' => $_POST['alamat'] ?? null,
            'nomor_telepon' => $_POST['nomor_telepon'] ?? null,
            'jurusan' => $_POST['jurusan'],
            'program_studi' => $_POST['program_studi'],
            'angkatan' => $_POST['angkatan'] ?? date('Y'),
            'semester_aktif' => 1,
            'kelompok_ukt' => $_POST['kelompok_ukt'] ?? 1
        ];
        
        if ($auth->register($data)) {
            $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
        } else {
            $error = 'Registrasi gagal. NIM atau email mungkin sudah terdaftar.';
        }
    }
}

// Program studi berdasarkan jurusan
$program_studi_options = [
    'Akuntansi' => [
        'D2 Administrasi Perpajakan',
        'D3 Akuntansi',
        'D4 Akuntansi Manajerial',
        'D4 Akuntansi Perpajakan'
    ],
    'Administrasi Bisnis' => [
        'D2 Manajemen Operasional Bisnis Digital',
        'D3 Administrasi Bisnis',
        'D4 Manajemen Bisnis Internasional',
        'D4 Bisnis Digital',
        'D4 Bahasa Inggris untuk Komunikasi Bisnis & Profesional'
    ],
    'Pariwisata' => [
        'S2 Terapan Perencanaan Pariwisata',
        'D4 Manajemen Bisnis Pariwisata',
        'D3 Perhotelan',
        'D3 Usaha Perjalanan Wisata'
    ],
    'Teknik Sipil' => [
        'D2 Fondasi, Beton, & Pengaspalan Jalan',
        'D3 Teknik Sipil',
        'D4 Manajemen Proyek Konstruksi',
        'D4 Teknologi Rekayasa Konstruksi Bangunan Gedung',
        'D4 Teknologi Rekayasa Konstruksi Bangunan Air'
    ],
    'Teknik Mesin' => [
        'D2 Teknik Manufaktur Mesin',
        'D3 Teknik Mesin',
        'D3 Teknik Pendingin dan Tata Udara',
        'D4 Teknologi Rekayasa Utilitas',
        'D4 Rekayasa Perancangan Mekanik'
    ],
    'Teknik Elektro' => [
        'D2 Instalasi dan Pemeliharaan Kabel Bertegangan Rendah',
        'D3 Teknik Listrik',
        'D4 Teknik Otomasi',
        'D4 Teknologi Rekayasa Energi Terbarukan'
    ],
    'Teknologi Informasi' => [
        'D2 Administrasi Jaringan Komputer',
        'D3 Manajemen Informatika',
        'D4 Teknologi Rekayasa Perangkat Lunak'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Mahasiswa - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    .register-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
    }

    .header p {
        color: #718096;
        font-size: 1.1rem;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
        color: #2d3748;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #4facfe;
        box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
    }

    .form-input::placeholder {
        color: #a0aec0;
    }

    .submit-btn {
        width: 100%;
        padding: 15px;
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

    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        color: #276749;
    }

    .alert-error {
        background: #fed7d7;
        border: 1px solid #feb2b2;
        color: #c53030;
    }

    .login-link {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e2e8f0;
    }

    .login-link a {
        color: #4facfe;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .login-link a:hover {
        color: #00f2fe;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }

        .register-card {
            padding: 30px 20px;
        }

        .header h1 {
            font-size: 2rem;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-card">
            <div class="header">
                <h1>Registrasi Mahasiswa</h1>
                <p>Daftar untuk mengakses Sistem Akademik</p>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST">
                <div class="grid-2">
                    <!-- Left Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">NIM *</label>
                            <input type="text" name="nim" class="form-input" placeholder="Masukkan NIM" required
                                value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-input" placeholder="Masukkan nama lengkap"
                                required value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" placeholder="Masukkan email" required
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-input" placeholder="Masukkan password"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jurusan *</label>
                            <select name="jurusan" class="form-select" required id="jurusan">
                                <option value="">Pilih Jurusan</option>
                                <?php foreach ($program_studi_options as $jurusan => $prodi_list): ?>
                                <option value="<?php echo $jurusan; ?>"
                                    <?php echo ($_POST['jurusan'] ?? '') == $jurusan ? 'selected' : ''; ?>>
                                    <?php echo $jurusan; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Program Studi *</label>
                            <select name="program_studi" class="form-select" required id="program_studi">
                                <option value="">Pilih Program Studi</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Angkatan *</label>
                            <select name="angkatan" class="form-select" required>
                                <option value="">Pilih Angkatan</option>
                                <?php 
                                $current_year = date('Y');
                                for ($year = $current_year; $year >= $current_year - 5; $year--): 
                                ?>
                                <option value="<?php echo $year; ?>"
                                    <?php echo ($_POST['angkatan'] ?? $current_year) == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-input"
                                value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L"
                                    <?php echo ($_POST['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki
                                </option>
                                <option value="P"
                                    <?php echo ($_POST['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" name="nomor_telepon" class="form-input"
                                placeholder="Masukkan nomor telepon"
                                value="<?php echo htmlspecialchars($_POST['nomor_telepon'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kelompok UKT</label>
                            <select name="kelompok_ukt" class="form-select">
                                <option value="1"
                                    <?php echo ($_POST['kelompok_ukt'] ?? '1') == '1' ? 'selected' : ''; ?>>Kelompok 1
                                </option>
                                <option value="2"
                                    <?php echo ($_POST['kelompok_ukt'] ?? '') == '2' ? 'selected' : ''; ?>>Kelompok 2
                                </option>
                                <option value="3"
                                    <?php echo ($_POST['kelompok_ukt'] ?? '') == '3' ? 'selected' : ''; ?>>Kelompok 3
                                </option>
                                <option value="4"
                                    <?php echo ($_POST['kelompok_ukt'] ?? '') == '4' ? 'selected' : ''; ?>>Kelompok 4
                                </option>
                                <option value="5"
                                    <?php echo ($_POST['kelompok_ukt'] ?? '') == '5' ? 'selected' : ''; ?>>Kelompok 5
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-input" rows="4"
                                placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus mr-2"></i>
                    DAFTAR SEKARANG
                </button>
            </form>

            <!-- Login Link -->
            <div class="login-link">
                <p class="text-gray-600 mb-2">Sudah punya akun?</p>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Masuk ke Sistem
                </a>
            </div>
        </div>
    </div>

    <script>
    // Program studi options
    const programStudiOptions = <?php echo json_encode($program_studi_options); ?>;

    document.getElementById('jurusan').addEventListener('change', function() {
        const jurusan = this.value;
        const programStudiSelect = document.getElementById('program_studi');

        // Clear existing options
        programStudiSelect.innerHTML = '<option value="">Pilih Program Studi</option>';

        if (jurusan && programStudiOptions[jurusan]) {
            programStudiOptions[jurusan].forEach(function(prodi) {
                const option = document.createElement('option');
                option.value = prodi;
                option.textContent = prodi;
                programStudiSelect.appendChild(option);
            });
        }
    });

    // Initialize program studi if jurusan is already selected
    document.addEventListener('DOMContentLoaded', function() {
        const jurusan = document.getElementById('jurusan').value;
        const selectedProdi = '<?php echo $_POST['program_studi'] ?? ''; ?>';

        if (jurusan) {
            const event = new Event('change');
            document.getElementById('jurusan').dispatchEvent(event);

            // Set selected program studi
            setTimeout(function() {
                if (selectedProdi) {
                    document.getElementById('program_studi').value = selectedProdi;
                }
            }, 100);
        }
    });
    </script>
</body>

</html>
