<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

// Get admin statistics
$stats = [];

// Total Mahasiswa
$mahasiswa_query = "SELECT COUNT(*) as total FROM mahasiswa";
$mahasiswa_stmt = $conn->prepare($mahasiswa_query);
$mahasiswa_stmt->execute();
$stats['mahasiswa'] = $mahasiswa_stmt->fetchColumn();

// Total Dosen
$dosen_query = "SELECT COUNT(*) as total FROM dosen";
$dosen_stmt = $conn->prepare($dosen_query);
$dosen_stmt->execute();
$stats['dosen'] = $dosen_stmt->fetchColumn();

// Total Mata Kuliah
$mk_query = "SELECT COUNT(*) as total FROM mata_kuliah";
$mk_stmt = $conn->prepare($mk_query);
$mk_stmt->execute();
$stats['mata_kuliah'] = $mk_stmt->fetchColumn();

// Total Kelas Aktif
$kelas_query = "SELECT COUNT(*) as total FROM kelas";
$kelas_stmt = $conn->prepare($kelas_query);
$kelas_stmt->execute();
$stats['kelas'] = $kelas_stmt->fetchColumn();

// Recent activities
$activities = [
    ['type' => 'user', 'message' => 'Mahasiswa baru terdaftar: John Doe', 'time' => '2 jam lalu'],
    ['type' => 'course', 'message' => 'Mata kuliah baru ditambahkan: Pemrograman Mobile', 'time' => '1 hari lalu'],
    ['type' => 'schedule', 'message' => 'Jadwal semester baru telah disusun', 'time' => '2 hari lalu'],
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
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
        .user-info-box {
            border: 2px solid rgba(255, 255, 255, 0.3);
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
                        <h2 class="text-lg font-bold text-gray-800">Admin Panel</h2>
                        <p class="text-xs text-gray-500">Universitas Touhou Indonesia</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-2">
                    <div class="px-3 py-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
                    </div>
                    
                    <a href="dashboard-admin.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Manajemen Pengguna</p>
                    </div>
                    
                    <a href="admin-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola Mahasiswa</span>
                    </a>

                    <a href="admin-dosen.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Kelola Dosen</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="admin-matakuliah.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Mata Kuliah</span>
                    </a>

                    <a href="admin-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Penjadwalan</span>
                    </a>

                    <a href="admin-krs.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-list w-5 mr-3"></i>
                        <span>Manajemen KRS</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Laporan</p>
                    </div>

                    <a href="admin-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Akademik</span>
                    </a>

                    <a href="admin-sistem.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Pengaturan Sistem</span>
                    </a>
                    
                    <a href="profil.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-user w-5 mr-3"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3 user-info-box">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500">Administrator</p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Dashboard Administrator</h1>
                            <p class="text-gray-600">Selamat datang, <?php echo $userData['nama_lengkap']; ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <?php echo date('d F Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Mahasiswa -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Mahasiswa</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['mahasiswa']; ?></h3>
                            <p class="text-xs text-gray-500">Mahasiswa Aktif</p>
                        </div>
                        <div class="icon-shape bg-gradient-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Dosen -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Dosen</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['dosen']; ?></h3>
                            <p class="text-xs text-gray-500">Dosen Aktif</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>

                <!-- Mata Kuliah -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mata Kuliah</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['mata_kuliah']; ?></h3>
                            <p class="text-xs text-gray-500">Total Mata Kuliah</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <!-- Kelas Aktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Kelas Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['kelas']; ?></h3>
                            <p class="text-xs text-gray-500">Semester Ini</p>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <a href="admin-mahasiswa.php" class="flex flex-col items-center p-4 bg-gradient-primary rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user-plus text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Tambah Mahasiswa</span>
                        </a>
                        
                        <a href="admin-dosen.php" class="flex flex-col items-center p-4 bg-gradient-success rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chalkboard-teacher text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Tambah Dosen</span>
                        </a>
                        
                        <a href="admin-matakuliah.php" class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-book text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Mata Kuliah</span>
                        </a>
                        
                        <a href="admin-jadwal.php" class="flex flex-col items-center p-4 bg-gradient-warning rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-calendar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Jadwal</span>
                        </a>

                        <a href="admin-krs.php" class="flex flex-col items-center p-4 bg-purple-500 rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-clipboard-list text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Kelola KRS</span>
                        </a>

                        <a href="admin-laporan.php" class="flex flex-col items-center p-4 bg-indigo-500 rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Laporan</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sistem Status -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-server text-green-500 mr-2"></i>
                            Status Sistem
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="font-medium text-gray-800">Database Server</span>
                                </div>
                                <span class="text-sm text-green-600 font-medium">Online</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="font-medium text-gray-800">Web Server</span>
                                </div>
                                <span class="text-sm text-green-600 font-medium">Online</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span class="font-medium text-gray-800">Periode KRS</span>
                                </div>
                                <span class="text-sm text-blue-600 font-medium">Aktif</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span class="font-medium text-gray-800">Backup Terakhir</span>
                                </div>
                                <span class="text-sm text-yellow-600 font-medium">2 jam lalu</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aktivitas Terbaru -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-bell text-orange-500 mr-2"></i>
                            Aktivitas Terbaru
                        </h3>
                        <div class="space-y-3">
                            <?php foreach ($activities as $activity): ?>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <?php if ($activity['type'] == 'user'): ?>
                                        <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                                    <?php elseif ($activity['type'] == 'course'): ?>
                                        <i class="fas fa-book text-green-600 text-sm"></i>
                                    <?php else: ?>
                                        <i class="fas fa-calendar text-purple-600 text-sm"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800"><?php echo $activity['message']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $activity['time']; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
