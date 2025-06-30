<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Get user data based on role
$database = new Database();
$conn = $database->getConnection();

$stats = [];
$userData = getUserData();
$userRole = getUserRole();

if ($userRole == 'mahasiswa') {
    // Get mahasiswa stats
    $query = "SELECT COUNT(*) as total_mk, COALESCE(SUM(3), 0) as total_sks FROM krs WHERE id_mahasiswa = :user_id AND status_krs = 'Aktif'";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $mahasiswa_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_sks' => $mahasiswa_data['total_sks'] ?? 0,
        'total_mk' => $mahasiswa_data['total_mk'] ?? 0,
        'nama' => $userData['nama_lengkap'],
        'nim' => $userData['nomor_induk']
    ];
} elseif ($userRole == 'dosen') {
    // Get dosen stats
    $stats_query = "SELECT 
        COUNT(DISTINCT k.id_kelas) as total_kelas,
        COUNT(DISTINCT krs.id_mahasiswa) as total_mahasiswa,
        COUNT(DISTINCT mk.id_matakuliah) as total_matakuliah
    FROM kelas k
    LEFT JOIN krs ON k.id_kelas = krs.id_kelas AND krs.status_krs = 'Aktif'
    LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
    WHERE k.id_dosen = :dosen_id";

    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bindValue(':dosen_id', $_SESSION['user_id']);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($userRole == 'admin') {
    // Get admin stats
    $mahasiswa_query = "SELECT COUNT(*) as total FROM mahasiswa";
    $mahasiswa_stmt = $conn->prepare($mahasiswa_query);
    $mahasiswa_stmt->execute();
    $stats['mahasiswa'] = $mahasiswa_stmt->fetchColumn();

    $dosen_query = "SELECT COUNT(*) as total FROM dosen";
    $dosen_stmt = $conn->prepare($dosen_query);
    $dosen_stmt->execute();
    $stats['dosen'] = $dosen_stmt->fetchColumn();

    $mk_query = "SELECT COUNT(*) as total FROM mata_kuliah";
    $mk_stmt = $conn->prepare($mk_query);
    $mk_stmt->execute();
    $stats['mata_kuliah'] = $mk_stmt->fetchColumn();

    $kelas_query = "SELECT COUNT(*) as total FROM kelas";
    $kelas_stmt = $conn->prepare($kelas_query);
    $kelas_stmt->execute();
    $stats['kelas'] = $kelas_stmt->fetchColumn();
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

    .bg-gradient-primary {
        background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%);
    }

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
                        <h2 class="text-lg font-bold text-gray-800">
                            <?php if ($userRole == 'admin'): ?>
                            Admin Panel
                            <?php elseif ($userRole == 'dosen'): ?>
                            Portal Dosen
                            <?php else: ?>
                            Sistem KRS
                            <?php endif; ?>
                        </h2>
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

                    <?php if ($userRole == 'mahasiswa'): ?>
                    <a href="krs-dashboard.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>

                    <a href="jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($userRole == 'dosen'): ?>
                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="dosen-mahasiswa.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Daftar Mahasiswa</span>
                    </a>

                    <a href="dosen-jadwal.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Mengajar</span>
                    </a>

                    <a href="dosen-absensi.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-check w-5 mr-3"></i>
                        <span>Kelola Absensi</span>
                    </a>

                    <a href="dosen-nilai.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-star w-5 mr-3"></i>
                        <span>Input Nilai</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Laporan</p>
                    </div>

                    <a href="dosen-laporan.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Akademik</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($userRole == 'admin'): ?>
                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Manajemen Pengguna</p>
                    </div>

                    <a href="admin-mahasiswa.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="admin-matakuliah.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Mata Kuliah</span>
                    </a>

                    <a href="admin-jadwal.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="admin-laporan.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Akademik</span>
                    </a>

                    <a href="admin-sistem.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Pengaturan Sistem</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($userRole == 'mahasiswa'): ?>
                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-semester.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-line w-5 mr-3"></i>
                        <span>Absen Semester</span>
                    </a>
                    <?php endif; ?>

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
                                <?php if ($userRole == 'admin'): ?>
                                <i class="fas fa-user-shield text-white text-sm"></i>
                                <?php elseif ($userRole == 'dosen'): ?>
                                <i class="fas fa-chalkboard-teacher text-white text-sm"></i>
                                <?php else: ?>
                                <i class="fas fa-user text-white text-sm"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php 
                                    if ($userRole == 'admin') echo 'Administrator';
                                    elseif ($userRole == 'dosen') echo 'Dosen';
                                    else echo $userData['nomor_induk'];
                                    ?>
                                </p>
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
                            <h1 class="text-2xl font-bold text-gray-800">
                                Dashboard
                                <?php 
                                if ($userRole == 'admin') echo 'Administrator';
                                elseif ($userRole == 'dosen') echo 'Dosen';
                                else echo 'Mahasiswa';
                                ?>
                            </h1>
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
            <?php if ($userRole == 'mahasiswa'): ?>
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
                            <h3 class="text-2xl font-bold text-gray-800">
                                <?php echo getCurrentAcademicYear()['display']; ?></h3>
                            <p class="text-xs text-gray-500">Tahun Akademik</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($userRole == 'dosen'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Kelas -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Kelas</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_kelas'] ?: 0; ?></h3>
                            <p class="text-xs text-gray-500">Kelas Aktif</p>
                        </div>
                        <div class="icon-shape bg-gradient-primary">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Mahasiswa -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Mahasiswa</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_mahasiswa'] ?: 0; ?>
                            </h3>
                            <p class="text-xs text-gray-500">Mahasiswa Aktif</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <!-- Mata Kuliah -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mata Kuliah</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_matakuliah'] ?: 0; ?>
                            </h3>
                            <p class="text-xs text-gray-500">Mata Kuliah Diampu</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Hari Ini -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Jadwal Hari Ini</p>
                            <h3 class="text-2xl font-bold text-gray-800">3</h3>
                            <p class="text-xs text-gray-500">Kelas Mengajar</p>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($userRole == 'admin'): ?>
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
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php if ($userRole == 'mahasiswa'): ?>
                        <a href="krs-dashboard.php"
                            class="flex flex-col items-center p-4 bg-gradient-primary rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-book text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Isi KRS</span>
                        </a>

                        <a href="jadwal.php"
                            class="flex flex-col items-center p-4 bg-gradient-success rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-calendar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Jadwal</span>
                        </a>

                        <a href="absensi-semester.php"
                            class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chart-line text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Absen Semester</span>
                        </a>

                        <a href="profil.php"
                            class="flex flex-col items-center p-4 bg-gradient-warning rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Profil</span>
                        </a>
                        <?php elseif ($userRole == 'dosen'): ?>
                        <a href="dosen-absensi.php"
                            class="flex flex-col items-center p-4 bg-gradient-primary rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-clipboard-check text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Input Absensi</span>
                        </a>

                        <a href="dosen-nilai.php"
                            class="flex flex-col items-center p-4 bg-gradient-success rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-star text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Input Nilai</span>
                        </a>

                        <a href="dosen-mahasiswa.php"
                            class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-users text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Lihat Mahasiswa</span>
                        </a>

                        <a href="dosen-laporan.php"
                            class="flex flex-col items-center p-4 bg-gradient-warning rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Laporan</span>
                        </a>
                        <?php elseif ($userRole == 'admin'): ?>
                        <a href="admin-mahasiswa.php"
                            class="flex flex-col items-center p-4 bg-gradient-primary rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user-plus text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Tambah Mahasiswa</span>
                        </a>

                        <a href="admin-dosen.php"
                            class="flex flex-col items-center p-4 bg-gradient-success rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chalkboard-teacher text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Tambah Dosen</span>
                        </a>

                        <a href="admin-matakuliah.php"
                            class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-book text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Mata Kuliah</span>
                        </a>

                        <a href="admin-jadwal.php"
                            class="flex flex-col items-center p-4 bg-gradient-warning rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-calendar text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Jadwal</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">🎉 Selamat Datang di Sistem KRS!</h3>
                    <p class="text-gray-600 mb-4">
                        Sistem ini berhasil berjalan dengan baik. Anda dapat mulai menggunakan fitur-fitur yang
                        tersedia.
                    </p>

                    <?php if ($userRole == 'mahasiswa'): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <h4 class="text-blue-800 font-semibold mb-2">Panduan untuk Mahasiswa:</h4>
                        <ul class="text-blue-700 text-sm space-y-1">
                            <li>• Gunakan menu "Pengisian KRS" untuk mengambil mata kuliah</li>
                            <li>• Lihat jadwal kuliah Anda di menu "Jadwal Kuliah"</li>
                            <li>• Pantau kehadiran semester di menu "Absen Semester"</li>
                            <li>• Perbarui profil Anda di menu "Profil"</li>
                        </ul>
                    </div>
                    <?php elseif ($userRole == 'dosen'): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                        <h4 class="text-green-800 font-semibold mb-2">Panduan untuk Dosen:</h4>
                        <ul class="text-green-700 text-sm space-y-1">
                            <li>• Kelola daftar mahasiswa di kelas Anda</li>
                            <li>• Input dan kelola absensi mahasiswa</li>
                            <li>• Input nilai UTS, UAS, tugas, dan kuis</li>
                            <li>• Lihat dan download laporan akademik</li>
                        </ul>
                    </div>
                    <?php elseif ($userRole == 'admin'): ?>
                    <div class="bg-purple-50 border-l-4 border-purple-400 p-4 rounded">
                        <h4 class="text-purple-800 font-semibold mb-2">Panduan untuk Administrator:</h4>
                        <ul class="text-purple-700 text-sm space-y-1">
                            <li>• Kelola data mahasiswa dan dosen</li>
                            <li>• Atur mata kuliah dan penjadwalan</li>
                            <li>• Pantau sistem KRS mahasiswa</li>
                            <li>• Generate laporan akademik lengkap</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>