<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'dosen') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

// Get dosen's schedule (simplified data for demo)
$schedules = [
    [
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'mata_kuliah' => 'Pemrograman Web',
        'kelas' => 'A',
        'ruang' => '201',
        'sks' => 3
    ],
    [
        'hari' => 'Senin',
        'jam_mulai' => '10:30',
        'jam_selesai' => '12:30',
        'mata_kuliah' => 'Basis Data',
        'kelas' => 'B',
        'ruang' => '105',
        'sks' => 3
    ],
    [
        'hari' => 'Selasa',
        'jam_mulai' => '13:30',
        'jam_selesai' => '15:30',
        'mata_kuliah' => 'Algoritma Pemrograman',
        'kelas' => 'C',
        'ruang' => '301',
        'sks' => 3
    ],
    [
        'hari' => 'Rabu',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'mata_kuliah' => 'Pemrograman Web',
        'kelas' => 'B',
        'ruang' => '202',
        'sks' => 3
    ],
    [
        'hari' => 'Kamis',
        'jam_mulai' => '10:30',
        'jam_selesai' => '12:30',
        'mata_kuliah' => 'Basis Data',
        'kelas' => 'A',
        'ruang' => '106',
        'sks' => 3
    ]
];

// Group schedules by day
$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$grouped_schedules = [];
foreach ($days as $day) {
    $grouped_schedules[$day] = array_filter($schedules, function($schedule) use ($day) {
        return $schedule['hari'] === $day;
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar - <?php echo APP_NAME; ?></title>
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
        .user-info-box {
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
        .schedule-card {
            border-left: 4px solid #4facfe;
            transition: all 0.3s ease;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
                        <h2 class="text-lg font-bold text-gray-800">Portal Dosen</h2>
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

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>
                    
                    <a href="dosen-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Daftar Mahasiswa</span>
                    </a>

                    <a href="dosen-jadwal.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Mengajar</span>
                    </a>

                    <a href="dosen-absensi.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="dosen-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Akademik</span>
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
                                <i class="fas fa-chalkboard-teacher text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500">Dosen</p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Jadwal Mengajar</h1>
                            <p class="text-gray-600">Jadwal kuliah Anda minggu ini</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                                <i class="fas fa-calendar-week mr-2"></i>
                                Minggu Ini
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-blue-500 text-white p-6 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Total Kelas</h3>
                            <p class="text-2xl font-bold"><?php echo count($schedules); ?></p>
                        </div>
                        <i class="fas fa-chalkboard text-3xl opacity-80"></i>
                    </div>
                </div>
                
                <div class="bg-green-500 text-white p-6 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Total SKS</h3>
                            <p class="text-2xl font-bold"><?php echo array_sum(array_column($schedules, 'sks')); ?></p>
                        </div>
                        <i class="fas fa-book text-3xl opacity-80"></i>
                    </div>
                </div>
                
                <div class="bg-purple-500 text-white p-6 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Hari Mengajar</h3>
                            <p class="text-2xl font-bold"><?php echo count(array_unique(array_column($schedules, 'hari'))); ?></p>
                        </div>
                        <i class="fas fa-calendar-day text-3xl opacity-80"></i>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                        Jadwal Mingguan
                    </h3>
                    
                    <div class="space-y-6">
                        <?php foreach ($days as $day): ?>
                        <div>
                            <h4 class="text-md font-semibold text-gray-700 mb-3 flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                <?php echo $day; ?>
                            </h4>
                            
                            <?php if (empty($grouped_schedules[$day])): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center text-gray-500">
                                <i class="fas fa-calendar-times text-2xl mb-2"></i>
                                <p>Tidak ada jadwal mengajar</p>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($grouped_schedules[$day] as $schedule): ?>
                                <div class="schedule-card bg-white p-4 rounded-lg border shadow-sm">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h5 class="font-semibold text-gray-800"><?php echo $schedule['mata_kuliah']; ?></h5>
                                            <p class="text-sm text-gray-600">Kelas <?php echo $schedule['kelas']; ?></p>
                                        </div>
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                            <?php echo $schedule['sks']; ?> SKS
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <i class="fas fa-clock w-4 mr-2"></i>
                                            <span><?php echo $schedule['jam_mulai']; ?> - <?php echo $schedule['jam_selesai']; ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                                            <span>Ruang <?php echo $schedule['ruang']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 pt-3 border-t flex gap-2">
                                        <button onclick="viewDetail('<?php echo $schedule['mata_kuliah']; ?>', '<?php echo $schedule['kelas']; ?>')" 
                                                class="flex-1 bg-blue-500 text-white px-3 py-2 rounded text-sm hover:bg-blue-600 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>
                                            Detail
                                        </button>
                                        <button onclick="inputAbsensi('<?php echo $schedule['mata_kuliah']; ?>', '<?php echo $schedule['kelas']; ?>')" 
                                                class="flex-1 bg-green-500 text-white px-3 py-2 rounded text-sm hover:bg-green-600 transition-colors">
                                            <i class="fas fa-clipboard-check mr-1"></i>
                                            Absensi
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewDetail(mataKuliah, kelas) {
            alert(`Detail kelas ${mataKuliah} - Kelas ${kelas}`);
            // Redirect to detail page or show modal
        }

        function inputAbsensi(mataKuliah, kelas) {
            window.location.href = `dosen-absensi.php?mk=${encodeURIComponent(mataKuliah)}&kelas=${kelas}`;
        }
    </script>
</body>
</html>
