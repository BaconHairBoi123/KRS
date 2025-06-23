<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Get user profile data
if (getUserRole() == 'mahasiswa') {
    $query = "SELECT m.*, u.username, u.email 
              FROM mahasiswa m 
              JOIN users u ON m.user_id = u.id 
              WHERE m.user_id = :user_id";
} elseif (getUserRole() == 'dosen') {
    $query = "SELECT d.*, u.username, u.email 
              FROM dosen d 
              JOIN users u ON d.user_id = u.id 
              WHERE d.user_id = :user_id";
} else {
    $query = "SELECT a.*, u.username, u.email 
              FROM administrator a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.user_id = :user_id";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_profile') {
        try {
            $conn->beginTransaction();
            
            // Update users table
            $user_query = "UPDATE users SET email = :email WHERE id = :user_id";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bindParam(':email', $_POST['email']);
            $user_stmt->bindParam(':user_id', $_SESSION['user_id']);
            $user_stmt->execute();
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $pass_query = "UPDATE users SET password = :password WHERE id = :user_id";
                $pass_stmt = $conn->prepare($pass_query);
                $pass_stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                $pass_stmt->bindParam(':user_id', $_SESSION['user_id']);
                $pass_stmt->execute();
            }
            
            // Update profile table based on role
            if (getUserRole() == 'mahasiswa') {
                $profile_query = "UPDATE mahasiswa SET 
                                 nama_lengkap = :nama_lengkap, tempat_lahir = :tempat_lahir, 
                                 tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin,
                                 alamat = :alamat, no_telepon = :no_telepon
                                 WHERE user_id = :user_id";
            } elseif (getUserRole() == 'dosen') {
                $profile_query = "UPDATE dosen SET 
                                 nama_lengkap = :nama_lengkap, tempat_lahir = :tempat_lahir, 
                                 tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin,
                                 alamat = :alamat, no_telepon = :no_telepon
                                 WHERE user_id = :user_id";
            }
            
            if (isset($profile_query)) {
                $profile_stmt = $conn->prepare($profile_query);
                $profile_stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
                $profile_stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
                $profile_stmt->bindParam(':tanggal_lahir', $_POST['tanggal_lahir'] ?: null);
                $profile_stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
                $profile_stmt->bindParam(':alamat', $_POST['alamat']);
                $profile_stmt->bindParam(':no_telepon', $_POST['no_telepon']);
                $profile_stmt->bindParam(':user_id', $_SESSION['user_id']);
                $profile_stmt->execute();
            }
            
            $conn->commit();
            $success = "Profil berhasil diperbarui";
            
            // Refresh profile data
            $stmt->execute();
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme-toggle.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(310deg, #f0f2f5 0%, #fcfcfc 100%);
            font-family: 'Open Sans', sans-serif;
        }
        .sidebar-soft {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(42px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .nav-link-soft {
            border-radius: 0.5rem;
            margin: 0.125rem 0.5rem;
            padding: 0.65rem 1rem;
            transition: all 0.15s ease-in;
        }
        .nav-link-soft:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .nav-link-soft.active {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
            color: white;
            box-shadow: 0 4px 7px -1px rgba(0, 0, 0, 0.11);
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05);
            border: 0;
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
        .btn-gradient {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 p-4">
            <div class="sidebar-soft h-full p-4">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Sistem KRS</h2>
                        <p class="text-xs text-gray-500">Universitas Indonesia</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-2">
                    <div class="px-3 py-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
                    </div>
                    
                    <a href="dashboard.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <?php if (getUserRole() == 'mahasiswa'): ?>
                    <a href="krs.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>
                    
                    <a href="jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Keuangan</p>
                    </div>

                    <a href="ukt-pembayaran.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-credit-card w-5 mr-3"></i>
                        <span>Pembayaran UKT</span>
                    </a>

                    <a href="ukt-riwayat.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-history w-5 mr-3"></i>
                        <span>Riwayat Pembayaran</span>
                    </a>
                    <?php endif; ?>

                    <?php if (getUserRole() == 'admin'): ?>
                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kelola Data</p>
                    </div>
                    
                    <a href="mahasiswa-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola Mahasiswa</span>
                    </a>

                    <a href="dosen-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Kelola Dosen</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Administrasi</p>
                    </div>

                    <a href="absensi-input.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-check w-5 mr-3"></i>
                        <span>Input Absensi</span>
                    </a>

                    <a href="absensi-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Absensi</span>
                    </a>

                    <a href="ukt-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Kelola UKT</span>
                    </a>
                    <?php endif; ?>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>
                    
                    <a href="absensi-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-user-check w-5 mr-3"></i>
                        <span>Absensi</span>
                    </a>
                    
                    <a href="profil.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-user w-5 mr-3"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $_SESSION['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $_SESSION['nomor_induk']; ?></p>
                            </div>
                            <a href="logout.php" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4">
            <!-- Header -->
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Profil Pengguna</h1>
                            <p class="text-gray-600">Kelola informasi profil Anda</p>
                        </div>
                        <div class="theme-toggle-container"></div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                        <p class="text-green-700"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="card">
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Akun</h3>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" 
                                           class="form-input w-full bg-gray-100" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" 
                                           required class="form-input w-full">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                                    <input type="password" name="password" class="form-input w-full" 
                                           placeholder="Kosongkan jika tidak ingin mengubah">
                                </div>

                                <?php if (getUserRole() == 'mahasiswa'): ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">NIM</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profile['nim']); ?>" 
                                           class="form-input w-full bg-gray-100" readonly>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Program Studi</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profile['program_studi']); ?>" 
                                           class="form-input w-full bg-gray-100" readonly>
                                </div>
                                <?php elseif (getUserRole() == 'dosen'): ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">NIP</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profile['nip']); ?>" 
                                           class="form-input w-full bg-gray-100" readonly>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Fakultas</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profile['fakultas']); ?>" 
                                           class="form-input w-full bg-gray-100" readonly>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Pribadi</h3>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($profile['nama_lengkap']); ?>" 
                                           required class="form-input w-full">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($profile['tempat_lahir'] ?? ''); ?>" 
                                               class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" value="<?php echo $profile['tanggal_lahir'] ?? ''; ?>" 
                                               class="form-input w-full">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-input w-full">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" <?php echo ($profile['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo ($profile['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon</label>
                                    <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($profile['no_telepon'] ?? ''); ?>" 
                                           class="form-input w-full">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat</label>
                                    <textarea name="alamat" rows="4" class="form-input w-full"><?php echo htmlspecialchars($profile['alamat'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end mt-6 pt-6 border-t">
                            <button type="submit" class="btn-gradient px-8 py-3 text-white rounded-xl">
                                <i class="fas fa-save mr-2"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
