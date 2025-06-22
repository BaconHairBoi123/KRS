<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Get user data based on role
$database = new Database();
$conn = $database->getConnection();

$stats = [];
if (getUserRole() == 'mahasiswa') {
    // Get mahasiswa stats
    $query = "SELECT m.*, 
                     COUNT(k.id) as total_mk,
                     SUM(mk.sks) as total_sks
              FROM mahasiswa m
              LEFT JOIN krs k ON m.id = k.mahasiswa_id AND k.status = 'approved'
              LEFT JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
              LEFT JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
              WHERE m.user_id = :user_id
              GROUP BY m.id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $mahasiswa_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_sks' => $mahasiswa_data['total_sks'] ?? 0,
        'total_mk' => $mahasiswa_data['total_mk'] ?? 0,
        'ipk' => $mahasiswa_data['ipk'] ?? 0,
        'semester' => $mahasiswa_data['semester_aktif'] ?? 1
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
                        <p class="text-xs text-gray-500">Universitas Indonesia</p>
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
                    
                    <a href="absensi-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-user-check w-5 mr-3"></i>
                        <span>Absensi</span>
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
                            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                            <p class="text-gray-600">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <button class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-bell text-gray-500"></i>
                                <span class="text-sm text-gray-700">Notifikasi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (getUserRole() == 'mahasiswa'): ?>
            <!-- Get UKT status -->
            <?php
            $ukt_query = "SELECT 
                COUNT(CASE WHEN ut.status_tagihan = 'lunas' THEN 1 END) as lunas,
                COUNT(CASE WHEN ut.status_tagihan = 'belum_bayar' THEN 1 END) as belum_bayar,
                SUM(CASE WHEN ut.status_tagihan = 'belum_bayar' THEN ut.total_tagihan ELSE 0 END) as total_tagihan
                FROM ukt_tagihan ut 
                WHERE ut.mahasiswa_id = (SELECT id FROM mahasiswa WHERE user_id = :user_id)";
            $ukt_stmt = $conn->prepare($ukt_query);
            $ukt_stmt->bindParam(':user_id', $_SESSION['user_id']);
            $ukt_stmt->execute();
            $ukt_status = $ukt_stmt->fetch(PDO::FETCH_ASSOC);
            ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
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
                            <p class="text-sm font-medium text-gray-600 mb-1">Mata Kuliah Terdaftar</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_mk']; ?></h3>
                            <p class="text-xs text-gray-500">MK</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>

                <!-- IPK -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">IPK Sementara</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['ipk'], 2); ?></h3>
                            <p class="text-xs text-green-500">+0.12</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>

                <!-- Semester -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Semester Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['semester']; ?></h3>
                            <p class="text-xs text-gray-500">Semester</p>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <!-- Add UKT Status Card -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Status UKT</p>
                            <h3 class="text-lg font-bold text-gray-800">
                                <?php echo $ukt_status['belum_bayar'] > 0 ? 'Belum Lunas' : 'Lunas'; ?>
                            </h3>
                            <?php if ($ukt_status['belum_bayar'] > 0): ?>
                                <p class="text-xs text-red-500">Rp <?php echo number_format($ukt_status['total_tagihan'], 0, ',', '.'); ?></p>
                            <?php else: ?>
                                <p class="text-xs text-green-500">Semua lunas</p>
                            <?php endif; ?>
                        </div>
                        <div class="icon-shape <?php echo $ukt_status['belum_bayar'] > 0 ? 'bg-gradient-warning' : 'bg-gradient-success'; ?>">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                </div>

                <!-- Absensi Summary Card -->
                <?php
                // Get average attendance
                $absensi_query = "SELECT AVG(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) * 100 as avg_kehadiran
                                 FROM krs k
                                 JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
                                 LEFT JOIN jadwal_pertemuan jp ON jk.id = jp.jadwal_kuliah_id
                                 LEFT JOIN absensi a ON (a.jadwal_pertemuan_id = jp.id AND a.mahasiswa_id = k.mahasiswa_id)
                                 WHERE k.mahasiswa_id = (SELECT id FROM mahasiswa WHERE user_id = :user_id) AND k.status = 'approved'";
                $absensi_stmt = $conn->prepare($absensi_query);
                $absensi_stmt->bindParam(':user_id', $_SESSION['user_id']);
                $absensi_stmt->execute();
                $avg_kehadiran = $absensi_stmt->fetchColumn() ?: 0;
                ?>
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Rata-rata Kehadiran</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($avg_kehadiran, 1); ?>%</h3>
                            <p class="text-xs <?php echo ($avg_kehadiran >= 75) ? 'text-green-500' : 'text-red-500'; ?>">
                                <?php echo ($avg_kehadiran >= 75) ? 'Memenuhi syarat' : 'Perlu ditingkatkan'; ?>
                            </p>
                        </div>
                        <div class="icon-shape <?php echo ($avg_kehadiran >= 75) ? 'bg-gradient-success' : 'bg-gradient-warning'; ?>">
                            <i class="fas fa-user-check"></i>
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
                        
                        <a href="transkrip.php" class="flex flex-col items-center p-4 bg-gradient-info rounded-xl text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <span class="text-sm font-medium">Transkrip</span>
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
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
