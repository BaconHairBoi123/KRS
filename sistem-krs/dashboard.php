<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Get user data based on role
$database = new Database();
$conn = $database->getConnection();

$stats = [];
$userData = getUserData();

if (getUserRole() == 'mahasiswa') {
    // Get mahasiswa stats using correct database structure
    $query = "SELECT m.*, 
                     COUNT(k.id_krs) as total_mk,
                     SUM(mk.sks) as total_sks
              FROM mahasiswa m
              LEFT JOIN krs k ON m.id_mahasiswa = k.id_mahasiswa AND k.status_krs = 'Aktif'
              LEFT JOIN kelas kl ON k.id_kelas = kl.id_kelas
              LEFT JOIN mata_kuliah mk ON kl.id_matakuliah = mk.id_matakuliah
              WHERE m.id_mahasiswa = :user_id
              GROUP BY m.id_mahasiswa";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $mahasiswa_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_sks' => $mahasiswa_data['total_sks'] ?? 0,
        'total_mk' => $mahasiswa_data['total_mk'] ?? 0,
        'nama' => $mahasiswa_data['nama'] ?? $userData['nama'],
        'nim' => $mahasiswa_data['nim'] ?? $userData['nim']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05);
            border: 0;
            position: relative;
            overflow: hidden;
        }
        .stats-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
        }
        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%); }
        .bg-gradient-info { background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%); }
        .bg-gradient-warning { background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%); }
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
                        <p class="text-xs text-gray-500">Universitas Touhou Indonesia</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-2">
                    <div class="px-3 py-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
                    </div>
                    
                    <a href="dashboard.php" class="nav-link-soft active flex items-center text-white">
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
                    <?php endif; ?>

                    <?php if (getUserRole() == 'admin'): ?>
                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Administrasi</p>
                    </div>

                    <a href="mahasiswa-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola Mahasiswa</span>
                    </a>

                    <a href="dosen-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Kelola Dosen</span>
                    </a>
                    <?php endif; ?>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-semester.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-line w-5 mr-3"></i>
                        <span>Absen Semester</span>
                    </a>
                    
                    <a href="profil.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $userData['nomor_induk']; ?></p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                            <p class="text-gray-600">Selamat datang, <?php echo $userData['nama_lengkap']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (getUserRole() == 'mahasiswa'): ?>
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Total SKS -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total SKS Diambil</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_sks']; ?></h3>
                            <p class="text-xs text-gray-500">SKS</p>
                        </div>
                        <div class="icon-shape bg-gradient-primary">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <!-- Total MK -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mata Kuliah Diambil</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_mk']; ?></h3>
                            <p class="text-xs text-gray-500">Mata Kuliah</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>

                <!-- Semester Aktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Semester Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo getCurrentAcademicYear()['display']; ?></h3>
                            <p class="text-xs text-gray-500">Tahun Akademik</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Welcome Message -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">🎉 Selamat Datang di Sistem KRS!</h3>
                    <p class="text-gray-600 mb-4">
                        Sistem ini berhasil berjalan dengan baik. Anda dapat mulai menggunakan fitur-fitur yang tersedia.
                    </p>
                    
                    <?php if (getUserRole() == 'mahasiswa'): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="krs.php" class="flex flex-col items-center p-4 bg-gradient-primary rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-book text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Isi KRS</span>
                        </a>
                        
                        <a href="jadwal.php" class="flex flex-col items-center p-4 bg-gradient-success rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-calendar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Jadwal</span>
                        </a>
                        
                        <a href="absensi-semester.php" class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chart-line text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Absen Semester</span>
                        </a>
                        
                        <a href="profil.php" class="flex flex-col items-center p-4 bg-gradient-warning rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Profil</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
