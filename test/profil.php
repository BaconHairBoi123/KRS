<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Get user profile data based on role
if (getUserRole() == 'mahasiswa') {
    $query = "SELECT * FROM mahasiswa WHERE id_mahasiswa = :user_id";
} elseif (getUserRole() == 'dosen') {
    $query = "SELECT * FROM dosen WHERE id_dosen = :user_id";
} else {
    $query = "SELECT * FROM admin WHERE id_admin = :user_id";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_profile') {
        try {
            // Update password if provided
            if (!empty($_POST['password'])) {
                if (getUserRole() == 'mahasiswa') {
                    $pass_query = "UPDATE mahasiswa SET password = ? WHERE id_mahasiswa = ?";
                } elseif (getUserRole() == 'dosen') {
                    $pass_query = "UPDATE dosen SET password = ? WHERE id_dosen = ?";
                } else {
                    $pass_query = "UPDATE admin SET password = ? WHERE id_admin = ?";
                }
                
                $pass_stmt = $conn->prepare($pass_query);
                $pass_stmt->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
            }
            
            // Update profile table based on role
            if (getUserRole() == 'mahasiswa') {
                $profile_query = "UPDATE mahasiswa SET 
                                 nama = ?, tanggal_lahir = ?, 
                                 jenis_kelamin = ?, alamat = ?, 
                                 nomor_telepon = ?, email = ?
                                 WHERE id_mahasiswa = ?";
                $profile_stmt = $conn->prepare($profile_query);
                $profile_stmt->execute([
                    $_POST['nama_lengkap'],
                    $_POST['tanggal_lahir'] ?: null,
                    $_POST['jenis_kelamin'],
                    $_POST['alamat'],
                    $_POST['no_telepon'],
                    $_POST['email'],
                    $_SESSION['user_id']
                ]);
            } elseif (getUserRole() == 'dosen') {
                $profile_query = "UPDATE dosen SET 
                                 nama_dosen = ?, email = ?,
                                 nomor_telepon = ?, alamat = ?
                                 WHERE id_dosen = ?";
                $profile_stmt = $conn->prepare($profile_query);
                $profile_stmt->execute([
                    $_POST['nama_lengkap'],
                    $_POST['email'],
                    $_POST['no_telepon'],
                    $_POST['alamat'],
                    $_SESSION['user_id']
                ]);
            }
            
            $success = "Profil berhasil diperbarui";
            
            // Refresh profile data
            $stmt->execute();
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'upload_photo') {
        try {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['photo']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $upload_dir = 'uploads/photos/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_filename = $_SESSION['user_id'] . '_' . time() . '.' . $filetype;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        // Update database with photo path - check if column exists first
                        try {
                            if (getUserRole() == 'mahasiswa') {
                                $photo_query = "UPDATE mahasiswa SET foto = ? WHERE id_mahasiswa = ?";
                            } elseif (getUserRole() == 'dosen') {
                                $photo_query = "UPDATE dosen SET foto = ? WHERE id_dosen = ?";
                            }
                            
                            if (isset($photo_query)) {
                                $photo_stmt = $conn->prepare($photo_query);
                                $photo_stmt->execute([$upload_path, $_SESSION['user_id']]);
                                
                                $success = "Foto profil berhasil diupload";
                                
                                // Refresh profile data
                                $stmt->execute();
                                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                            }
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                                $error = "Fitur upload foto belum tersedia. Kolom foto belum ada di database.";
                            } else {
                                throw $e;
                            }
                        }
                    } else {
                        $error = "Gagal mengupload foto";
                    }
                } else {
                    $error = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF";
                }
            } else {
                $error = "Pilih file foto terlebih dahulu";
            }
        } catch (Exception $e) {
            $error = "Gagal mengupload foto: " . $e->getMessage();
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
        .bg-gradient-primary {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
        }
        .user-info-box {
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e2e8f0;
        }
        .photo-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .photo-upload-area:hover {
            border-color: #7928ca;
            background: rgba(121, 40, 202, 0.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 p-4">
            <div class="sidebar-soft h-full p-4 relative">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Sistem KRS</h2>
                        <p class="text-xs text-gray-500">Universitas Touhou Indonesia</p>
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
                    <a href="krs-dashboard.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>
                    
                    <a href="jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>
                    <?php endif; ?>

                    <?php if (getUserRole() == 'dosen'): ?>
                    <a href="dosen-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Mengajar</span>
                    </a>
                    
                    <a href="dosen-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Daftar Mahasiswa</span>
                    </a>
                    
                    <a href="dosen-nilai.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-list w-5 mr-3"></i>
                        <span>Input Nilai</span>
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
                    <?php endif; ?>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-semester.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-line w-5 mr-3"></i>
                        <span>Absen Semester</span>
                    </a>
                    
                    <a href="profil.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-user w-5 mr-3"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3 user-info-box">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($profile['foto']) && file_exists($profile['foto'])): ?>
                                <img src="<?php echo $profile['foto']; ?>" alt="Foto Profil" class="w-8 h-8 rounded-lg object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo getUserData()['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo getUserData()['nomor_induk']; ?></p>
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
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-primary rounded-xl flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Profil Pengguna</h1>
                                <p class="text-gray-600">Kelola informasi profil Anda</p>
                            </div>
                        </div>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Photo Upload -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Foto Profil</h3>
                        <div class="text-center">
                            <?php if (!empty($profile['foto']) && file_exists($profile['foto'])): ?>
                                <img src="<?php echo $profile['foto']; ?>" alt="Foto Profil" class="photo-preview mx-auto mb-4">
                            <?php else: ?>
                                <div class="photo-preview mx-auto mb-4 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_photo">
                                <div class="photo-upload-area mb-4">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600 mb-2">Pilih foto profil</p>
                                    <input type="file" name="photo" accept="image/*" class="form-input" required>
                                </div>
                                <button type="submit" class="btn-gradient text-white px-4 py-2 rounded-lg font-semibold w-full">
                                    <i class="fas fa-upload mr-2"></i>
                                    Upload Foto
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="p-6">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Left Column -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Akun</h3>
                                        
                                        <?php if (getUserRole() == 'mahasiswa'): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIM</label>
                                            <input type="text" value="<?php echo htmlspecialchars($profile['nim']); ?>" 
                                                   class="form-input w-full bg-gray-100" readonly>
                                        </div>
                                        <?php elseif (getUserRole() == 'dosen'): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIDN</label>
                                            <input type="text" value="<?php echo htmlspecialchars($profile['nidn']); ?>" 
                                                   class="form-input w-full bg-gray-100" readonly>
                                        </div>
                                        <?php endif; ?>

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
                                    </div>

                                    <!-- Right Column -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Pribadi</h3>
                                        
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                            <input type="text" name="nama_lengkap" 
                                                   value="<?php echo htmlspecialchars($profile['nama'] ?? $profile['nama_dosen'] ?? ''); ?>" 
                                                   required class="form-input w-full">
                                        </div>

                                        <?php if (getUserRole() == 'mahasiswa'): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                                            <input type="date" name="tanggal_lahir" value="<?php echo $profile['tanggal_lahir'] ?? ''; ?>" 
                                                   class="form-input w-full">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                                            <select name="jenis_kelamin" class="form-input w-full">
                                                <option value="">Pilih Jenis Kelamin</option>
                                                <option value="L" <?php echo ($profile['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                                <option value="P" <?php echo ($profile['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                                            </select>
                                        </div>
                                        <?php endif; ?>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat</label>
                                            <textarea name="alamat" rows="3" class="form-input w-full"><?php echo htmlspecialchars($profile['alamat'] ?? ''); ?></textarea>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon</label>
                                            <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($profile['nomor_telepon'] ?? ''); ?>" 
                                                   class="form-input w-full">
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-8 pt-6 border-t">
                                    <button type="submit" class="btn-gradient text-white px-8 py-3 rounded-lg font-semibold">
                                        <i class="fas fa-save mr-2"></i>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
