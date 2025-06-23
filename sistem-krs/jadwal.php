<?php
require_once 'config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get mahasiswa ID
$query = "SELECT id FROM mahasiswa WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
$mahasiswa_id = $mahasiswa['id'];

// Get jadwal kuliah mahasiswa
$query = "SELECT jk.*, mk.kode_mk, mk.nama_mk, mk.sks, d.nama_lengkap as dosen,
               jk.hari, jk.jam_mulai, jk.jam_selesai, jk.ruang
        FROM krs k
        JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
        JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
        JOIN dosen d ON jk.dosen_id = d.id
        WHERE k.mahasiswa_id = :mahasiswa_id AND k.status = 'approved'
        ORDER BY 
            CASE jk.hari 
                WHEN 'Senin' THEN 1
                WHEN 'Selasa' THEN 2
                WHEN 'Rabu' THEN 3
                WHEN 'Kamis' THEN 4
                WHEN 'Jumat' THEN 5
                WHEN 'Sabtu' THEN 6
                WHEN 'Minggu' THEN 7
            END,
            jk.jam_mulai";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$jadwal_kuliah = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by day
$jadwal_per_hari = [];
foreach ($jadwal_kuliah as $jadwal) {
    $jadwal_per_hari[$jadwal['hari']][] = $jadwal;
}

$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kuliah - <?php echo APP_NAME; ?></title>
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
        .schedule-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
        }
        .time-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
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
                    
                    <a href="krs.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>
                    
                    <a href="jadwal.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Jadwal Kuliah</h1>
                            <p class="text-gray-600">Semester Genap 2023/2024</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-100 to-purple-100 rounded-xl">
                                <i class="fas fa-calendar text-blue-600"></i>
                                <span class="text-sm font-medium text-blue-800">Total: <?php echo count($jadwal_kuliah); ?> Mata Kuliah</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($jadwal_kuliah)): ?>
            <!-- Empty State -->
            <div class="card">
                <div class="p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-times text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Belum Ada Jadwal</h3>
                    <p class="text-gray-600 mb-6">Anda belum memiliki jadwal kuliah. Silakan isi KRS terlebih dahulu.</p>
                    <a href="krs.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-book mr-2"></i>
                        Isi KRS Sekarang
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Schedule Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($hari_list as $hari): ?>
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-day text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800"><?php echo $hari; ?></h3>
                                <p class="text-sm text-gray-500">
                                    <?php echo isset($jadwal_per_hari[$hari]) ? count($jadwal_per_hari[$hari]) : 0; ?> mata kuliah
                                </p>
                            </div>
                        </div>

                        <?php if (isset($jadwal_per_hari[$hari])): ?>
                            <div class="space-y-3">
                                <?php foreach ($jadwal_per_hari[$hari] as $jadwal): ?>
                                <div class="schedule-card">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs font-semibold bg-white bg-opacity-20 px-2 py-1 rounded">
                                                    <?php echo $jadwal['kode_mk']; ?>
                                                </span>
                                                <span class="text-xs font-semibold bg-white bg-opacity-20 px-2 py-1 rounded">
                                                    <?php echo $jadwal['sks']; ?> SKS
                                                </span>
                                            </div>
                                            <h4 class="font-semibold text-white mb-1"><?php echo $jadwal['nama_mk']; ?></h4>
                                            <p class="text-sm text-white text-opacity-90"><?php echo $jadwal['dosen']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="time-badge">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo formatWaktu($jadwal['jam_mulai']); ?> - <?php echo formatWaktu($jadwal['jam_selesai']); ?>
                                        </div>
                                        <div class="time-badge">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo $jadwal['ruang']; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-calendar-times text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-500 text-sm">Tidak ada jadwal</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Weekly Summary -->
            <div class="card mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Mingguan</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl">
                            <div class="text-2xl font-bold text-blue-600"><?php echo count($jadwal_kuliah); ?></div>
                            <div class="text-sm text-blue-600">Total Mata Kuliah</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl">
                            <div class="text-2xl font-bold text-green-600"><?php echo array_sum(array_column($jadwal_kuliah, 'sks')); ?></div>
                            <div class="text-sm text-green-600">Total SKS</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl">
                            <div class="text-2xl font-bold text-purple-600"><?php echo count(array_filter($jadwal_per_hari)); ?></div>
                            <div class="text-sm text-purple-600">Hari Aktif</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl">
                            <div class="text-2xl font-bold text-orange-600"><?php echo count(array_unique(array_column($jadwal_kuliah, 'dosen'))); ?></div>
                            <div class="text-sm text-orange-600">Dosen Pengampu</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
