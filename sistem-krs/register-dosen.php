<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$success = '';
$error = '';

if ($_POST) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Validate required fields
        $required_fields = ['nidn', 'nama_dosen', 'email', 'password', 'jurusan', 'program_studi'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $error = 'Harap lengkapi semua field yang wajib diisi.';
        } else {
            // Check if NIDN or email already exists
            $check_query = "SELECT nidn, email FROM dosen WHERE nidn = :nidn OR email = :email";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindValue(':nidn', $_POST['nidn']);
            $check_stmt->bindValue(':email', $_POST['email']);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                $error = 'NIDN atau email sudah terdaftar.';
            } else {
                // Insert new dosen
                $insert_query = "INSERT INTO dosen (nidn, nama_dosen, email, password, nomor_telepon, gelar, jurusan, program_studi, bidang_keahlian) 
                               VALUES (:nidn, :nama_dosen, :email, :password, :nomor_telepon, :gelar, :jurusan, :program_studi, :bidang_keahlian)";
                
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindValue(':nidn', $_POST['nidn']);
                $insert_stmt->bindValue(':nama_dosen', $_POST['nama_dosen']);
                $insert_stmt->bindValue(':email', $_POST['email']);
                $insert_stmt->bindValue(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                $insert_stmt->bindValue(':nomor_telepon', $_POST['nomor_telepon'] ?? null);
                $insert_stmt->bindValue(':gelar', $_POST['gelar'] ?? null);
                $insert_stmt->bindValue(':jurusan', $_POST['jurusan']);
                $insert_stmt->bindValue(':program_studi', $_POST['program_studi']);
                $insert_stmt->bindValue(':bidang_keahlian', $_POST['bidang_keahlian'] ?? null);
                
                if ($insert_stmt->execute()) {
                    $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                } else {
                    $error = 'Registrasi gagal. Silakan coba lagi.';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Program studi berdasarkan jurusan
$program_studi_options = [
    'Teknik Informatika' => [
        'Teknik Informatika'
    ],
    'Teknik Sipil' => [
        'Teknik Sipil'
    ],
    'Ekonomi' => [
        'Ekonomi'
    ],
    'Hukum' => [
        'Hukum'
    ],
    'Kedokteran' => [
        'Kedokteran'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Dosen - <?php echo APP_NAME; ?></title>
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
        max-width: 700px;
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
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-card">
            <div class="header">
                <h1>Registrasi Dosen</h1>
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
                            <label class="form-label">NIDN *</label>
                            <input type="text" name="nidn" class="form-input" placeholder="Masukkan NIDN" required
                                value="<?php echo htmlspecialchars($_POST['nidn'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama_dosen" class="form-input" placeholder="Masukkan nama lengkap"
                                required value="<?php echo htmlspecialchars($_POST['nama_dosen'] ?? ''); ?>">
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
                            <label class="form-label">Gelar</label>
                            <input type="text" name="gelar" class="form-input" placeholder="Contoh: S.Kom., M.Kom."
                                value="<?php echo htmlspecialchars($_POST['gelar'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
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
                            <label class="form-label">Bidang Keahlian</label>
                            <input type="text" name="bidang_keahlian" class="form-input"
                                placeholder="Contoh: Pemrograman Web, Database"
                                value="<?php echo htmlspecialchars($_POST['bidang_keahlian'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" name="nomor_telepon" class="form-input"
                                placeholder="Masukkan nomor telepon"
                                value="<?php echo htmlspecialchars($_POST['nomor_telepon'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
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