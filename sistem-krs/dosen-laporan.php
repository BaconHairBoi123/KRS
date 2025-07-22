<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'dosen') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

// Sample data for demo
$classes = [
    ['id' => 1, 'nama' => 'Pemrograman Web - Kelas A', 'total_mahasiswa' => 25],
    ['id' => 2, 'nama' => 'Basis Data - Kelas B', 'total_mahasiswa' => 30],
    ['id' => 3, 'nama' => 'Algoritma - Kelas C', 'total_mahasiswa' => 28]
];

$attendance_summary = [
    'total_pertemuan' => 14,
    'rata_kehadiran' => 85.5,
    'mahasiswa_aktif' => 83,
    'total_mahasiswa' => 83
];

$grade_distribution = [
    'A' => 15,
    'A-' => 12,
    'B+' => 18,
    'B' => 20,
    'B-' => 10,
    'C+' => 5,
    'C' => 3,
    'C-' => 0,
    'D' => 0,
    'E' => 0
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

                    <a href="dosen-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="dosen-laporan.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Laporan Akademik</h1>
                            <p class="text-gray-600">Rekap kehadiran dan nilai per kelas</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <button onclick="downloadReport('pdf')" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                                <i class="fas fa-file-pdf mr-2"></i>
                                Download PDF
                            </button>
                            <button onclick="downloadReport('excel')" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                <i class="fas fa-file-excel mr-2"></i>
                                Download Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Kelas</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo count($classes); ?></h3>
                            <p class="text-xs text-gray-500">Kelas Aktif</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chalkboard text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Mahasiswa</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $attendance_summary['total_mahasiswa']; ?></h3>
                            <p class="text-xs text-gray-500">Mahasiswa Aktif</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Rata-rata Kehadiran</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $attendance_summary['rata_kehadiran']; ?>%</h3>
                            <p class="text-xs text-gray-500">Semester Ini</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Pertemuan</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $attendance_summary['total_pertemuan']; ?></h3>
                            <p class="text-xs text-gray-500">Pertemuan</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Grade Distribution Chart -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-pie text-blue-500 mr-2"></i>
                            Distribusi Nilai
                        </h3>
                        <div class="h-64">
                            <canvas id="gradeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Attendance Chart -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                            Kehadiran per Kelas
                        </h3>
                        <div class="h-64">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Reports -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">
                        <i class="fas fa-list-alt text-purple-500 mr-2"></i>
                        Laporan per Kelas
                    </h3>
                    
                    <div class="space-y-6">
                        <?php foreach ($classes as $class): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800"><?php echo $class['nama']; ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo $class['total_mahasiswa']; ?> mahasiswa</p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="viewClassDetail(<?php echo $class['id']; ?>)" 
                                            class="bg-blue-500 text-white px-3 py-2 rounded text-sm hover:bg-blue-600 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        Detail
                                    </button>
                                    <button onclick="downloadClassReport(<?php echo $class['id']; ?>)" 
                                            class="bg-green-500 text-white px-3 py-2 rounded text-sm hover:bg-green-600 transition-colors">
                                        <i class="fas fa-download mr-1"></i>
                                        Download
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <div class="text-sm text-blue-600 font-medium">Kehadiran</div>
                                    <div class="text-xl font-bold text-blue-800">87%</div>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg">
                                    <div class="text-sm text-green-600 font-medium">Rata-rata Nilai</div>
                                    <div class="text-xl font-bold text-green-800">82.5</div>
                                </div>
                                <div class="bg-yellow-50 p-3 rounded-lg">
                                    <div class="text-sm text-yellow-600 font-medium">Lulus</div>
                                    <div class="text-xl font-bold text-yellow-800"><?php echo $class['total_mahasiswa'] - 2; ?></div>
                                </div>
                                <div class="bg-red-50 p-3 rounded-lg">
                                    <div class="text-sm text-red-600 font-medium">Tidak Lulus</div>
                                    <div class="text-xl font-bold text-red-800">2</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Grade Distribution Chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($grade_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($grade_distribution)); ?>,
                    backgroundColor: [
                        '#10b981', '#059669', '#3b82f6', '#2563eb',
                        '#1d4ed8', '#f59e0b', '#d97706', '#b45309',
                        '#ef4444', '#991b1b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: ['Pemrograman Web A', 'Basis Data B', 'Algoritma C'],
                datasets: [{
                    label: 'Kehadiran (%)',
                    data: [87, 92, 85],
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        function downloadReport(format) {
            alert(`Download laporan dalam format ${format.toUpperCase()} akan segera tersedia`);
        }

        function viewClassDetail(classId) {
            alert(`Menampilkan detail kelas ID: ${classId}`);
        }

        function downloadClassReport(classId) {
            alert(`Download laporan kelas ID: ${classId} akan segera tersedia`);
        }
    </script>
</body>
</html>
