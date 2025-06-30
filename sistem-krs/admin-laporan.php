<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get statistics for dashboard
$stats = [];

// Total mahasiswa
$query = "SELECT COUNT(*) as total FROM mahasiswa WHERE status = 'aktif'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_mahasiswa'] = $stmt->fetchColumn();

// Total dosen
$query = "SELECT COUNT(*) as total FROM dosen WHERE status = 'aktif'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_dosen'] = $stmt->fetchColumn();

// Total mata kuliah
$query = "SELECT COUNT(*) as total FROM mata_kuliah WHERE status = 'aktif'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_matakuliah'] = $stmt->fetchColumn();

// Total kelas
$query = "SELECT COUNT(*) as total FROM kelas WHERE status = 'aktif'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_kelas'] = $stmt->fetchColumn();

// KRS statistics
$query = "SELECT 
            COUNT(*) as total_krs,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
          FROM krs";
$stmt = $conn->prepare($query);
$stmt->execute();
$krs_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Mahasiswa per program studi
$query = "SELECT program_studi, COUNT(*) as jumlah 
          FROM mahasiswa 
          WHERE status = 'aktif' 
          GROUP BY program_studi 
          ORDER BY jumlah DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$mahasiswa_per_prodi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mata kuliah per semester
$query = "SELECT semester, COUNT(*) as jumlah 
          FROM mata_kuliah 
          WHERE status = 'aktif' 
          GROUP BY semester 
          ORDER BY semester";
$stmt = $conn->prepare($query);
$stmt->execute();
$matakuliah_per_semester = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 mata kuliah dengan KRS terbanyak
$query = "SELECT mk.nama_matakuliah, mk.kode_matakuliah, COUNT(krs.id_krs) as jumlah_krs
          FROM mata_kuliah mk
          LEFT JOIN kelas k ON mk.id_matakuliah = k.id_matakuliah
          LEFT JOIN krs ON k.id_kelas = krs.id_kelas
          WHERE mk.status = 'aktif'
          GROUP BY mk.id_matakuliah
          ORDER BY jumlah_krs DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$top_matakuliah = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dosen dengan mahasiswa wali terbanyak
$query = "SELECT d.nama_dosen, d.program_studi, COUNT(m.id_mahasiswa) as jumlah_mahasiswa
          FROM dosen d
          LEFT JOIN mahasiswa m ON d.id_dosen = m.dosen_wali
          WHERE d.status = 'aktif'
          GROUP BY d.id_dosen
          ORDER BY jumlah_mahasiswa DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$top_dosen_wali = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mahasiswa per angkatan
$query = "SELECT angkatan, COUNT(*) as jumlah 
          FROM mahasiswa 
          WHERE status = 'aktif' 
          GROUP BY angkatan 
          ORDER BY angkatan DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$mahasiswa_per_angkatan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Sistem - <?php echo APP_NAME; ?></title>
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

    .bg-gradient-primary {
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
                        <h2 class="text-lg font-bold text-gray-800">Admin Panel</h2>
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
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Manajemen Pengguna</p>
                    </div>

                    <a href="admin-users.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users-cog w-5 mr-3"></i>
                        <span>Kelola Pengguna</span>
                    </a>

                    <a href="admin-mahasiswa.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Data Mahasiswa</span>
                    </a>

                    <a href="admin-dosen.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Data Dosen</span>
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
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</p>
                    </div>

                    <a href="admin-laporan.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan</span>
                    </a>

                    <a href="admin-settings.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Pengaturan</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $_SESSION['nama']; ?>
                                </p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Laporan Sistem</h1>
                            <p class="text-gray-600">Analisis dan statistik sistem akademik</p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="exportReport('pdf')"
                                class="bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition-colors">
                                <i class="fas fa-file-pdf mr-2"></i>
                                Export PDF
                            </button>
                            <button onclick="exportReport('excel')"
                                class="bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 transition-colors">
                                <i class="fas fa-file-excel mr-2"></i>
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overview Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Mahasiswa</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($stats['total_mahasiswa']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Dosen</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($stats['total_dosen']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-book text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Mata Kuliah</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($stats['total_matakuliah']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-door-open text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Kelas</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($stats['total_kelas']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KRS Statistics -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik KRS</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                <?php echo number_format($krs_stats['total_krs']); ?></div>
                            <div class="text-sm text-gray-600">Total KRS</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600">
                                <?php echo number_format($krs_stats['pending']); ?></div>
                            <div class="text-sm text-gray-600">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">
                                <?php echo number_format($krs_stats['disetujui']); ?></div>
                            <div class="text-sm text-gray-600">Disetujui</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">
                                <?php echo number_format($krs_stats['ditolak']); ?></div>
                            <div class="text-sm text-gray-600">Ditolak</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Mahasiswa per Program Studi -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Mahasiswa per Program Studi</h3>
                        <canvas id="prodiChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Mahasiswa per Angkatan -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Mahasiswa per Angkatan</h3>
                        <canvas id="angkatanChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Top Mata Kuliah -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Mata Kuliah (KRS Terbanyak)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mata
                                            Kuliah</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Jumlah KRS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($top_matakuliah as $mk): ?>
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $mk['nama_matakuliah']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $mk['kode_matakuliah']; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900"><?php echo $mk['jumlah_krs']; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Dosen Wali -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Dosen Wali (Mahasiswa Terbanyak)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Dosen</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Mahasiswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($top_dosen_wali as $dosen): ?>
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $dosen['nama_dosen']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $dosen['program_studi']; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <?php echo $dosen['jumlah_mahasiswa']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mata Kuliah per Semester -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Mata Kuliah per Semester</h3>
                    <canvas id="semesterChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Chart colors
    const colors = [
        '#7928ca', '#ff0080', '#00d4ff', '#00c851', '#ffbb33',
        '#ff4444', '#aa66cc', '#33b5e5', '#99cc00', '#ff8800'
    ];

    // Mahasiswa per Program Studi Chart
    const prodiData = <?php echo json_encode($mahasiswa_per_prodi); ?>;
    const prodiCtx = document.getElementById('prodiChart').getContext('2d');
    new Chart(prodiCtx, {
        type: 'doughnut',
        data: {
            labels: prodiData.map(item => item.program_studi),
            datasets: [{
                data: prodiData.map(item => item.jumlah),
                backgroundColor: colors.slice(0, prodiData.length),
                borderWidth: 2,
                borderColor: '#fff'
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

    // Mahasiswa per Angkatan Chart
    const angkatanData = <?php echo json_encode($mahasiswa_per_angkatan); ?>;
    const angkatanCtx = document.getElementById('angkatanChart').getContext('2d');
    new Chart(angkatanCtx, {
        type: 'bar',
        data: {
            labels: angkatanData.map(item => item.angkatan),
            datasets: [{
                label: 'Jumlah Mahasiswa',
                data: angkatanData.map(item => item.jumlah),
                backgroundColor: '#7928ca',
                borderColor: '#7928ca',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Mata Kuliah per Semester Chart
    const semesterData = <?php echo json_encode($matakuliah_per_semester); ?>;
    const semesterCtx = document.getElementById('semesterChart').getContext('2d');
    new Chart(semesterCtx, {
        type: 'line',
        data: {
            labels: semesterData.map(item => 'Semester ' + item.semester),
            datasets: [{
                label: 'Jumlah Mata Kuliah',
                data: semesterData.map(item => item.jumlah),
                borderColor: '#ff0080',
                backgroundColor: 'rgba(255, 0, 128, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    function exportReport(format) {
        if (format === 'pdf') {
            alert('Export PDF akan segera tersedia');
        } else if (format === 'excel') {
            alert('Export Excel akan segera tersedia');
        }
    }
    </script>
</body>

</html>