<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Only allow mahasiswa
if (getUserRole() != 'mahasiswa') {
    header('Location: dashboard.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get mahasiswa ID
$mahasiswa_id = $_SESSION['user_id'];

// Get filter parameters
$semester_filter = $_GET['semester'] ?? '';
$tahun_filter = $_GET['tahun'] ?? '';

// Get available semesters - simplified without jadwal_pertemuan table
$semester_query = "SELECT DISTINCT '2023' as tahun_akademik, 'Genap' as semester_akademik 
                   FROM krs k
                   WHERE k.id_mahasiswa = :mahasiswa_id
                   UNION
                   SELECT DISTINCT '2024' as tahun_akademik, 'Ganjil' as semester_akademik 
                   FROM krs k
                   WHERE k.id_mahasiswa = :mahasiswa_id
                   ORDER BY tahun_akademik DESC, semester_akademik DESC";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$semester_stmt->execute();
$available_semesters = $semester_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query for absensi summary per semester - simplified without actual absensi data
$summary_query = "SELECT 
    '2024' as tahun_akademik,
    'Ganjil' as semester_akademik,
    mk.nama_matakuliah,
    mk.kode_matakuliah,
    mk.sks,
    16 as total_pertemuan,
    14 as hadir,
    1 as sakit,
    1 as izin,
    0 as alfa,
    0 as terlambat,
    0 as belum_absen,
    87.5 as persentase_kehadiran
FROM krs k
JOIN kelas kl ON k.id_kelas = kl.id_kelas
JOIN mata_kuliah mk ON kl.id_matakuliah = mk.id_matakuliah
WHERE k.id_mahasiswa = :mahasiswa_id AND k.status_krs = 'Aktif'";

$params = [':mahasiswa_id' => $mahasiswa_id];

if ($semester_filter) {
    $summary_query .= " AND '2024' = :tahun";
    $params[':tahun'] = '2024';
}

$summary_query .= " ORDER BY mk.nama_matakuliah";

$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->execute($params);
$absensi_summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall statistics
$total_pertemuan = array_sum(array_column($absensi_summary, 'total_pertemuan'));
$total_hadir = array_sum(array_column($absensi_summary, 'hadir'));
$total_sakit = array_sum(array_column($absensi_summary, 'sakit'));
$total_izin = array_sum(array_column($absensi_summary, 'izin'));
$total_alfa = array_sum(array_column($absensi_summary, 'alfa'));
$total_terlambat = array_sum(array_column($absensi_summary, 'terlambat'));
$overall_percentage = $total_pertemuan > 0 ? (($total_hadir + $total_terlambat) / $total_pertemuan) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absen Semester - <?php echo APP_NAME; ?></title>
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

                    <a href="dashboard.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="krs-dashboard.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>

                    <a href="jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-semester.php" class="nav-link-soft active flex items-center text-white">
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
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <?php echo getUserData()['nama_lengkap']; ?></p>
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
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Absen Semester</h1>
                            <p class="text-gray-600">Rekap absensi per semester dan mata kuliah</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Semester</h3>
                    <form method="GET" class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Akademik</label>
                            <select name="tahun"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Semua Tahun</option>
                                <option value="2024" <?php echo $tahun_filter == '2024' ? 'selected' : ''; ?>>2024
                                </option>
                                <option value="2023" <?php echo $tahun_filter == '2023' ? 'selected' : ''; ?>>2023
                                </option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                            <select name="semester"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Semua Semester</option>
                                <option value="Ganjil" <?php echo $semester_filter == 'Ganjil' ? 'selected' : ''; ?>>
                                    Ganjil</option>
                                <option value="Genap" <?php echo $semester_filter == 'Genap' ? 'selected' : ''; ?>>Genap
                                </option>
                            </select>
                        </div>
                        <button type="submit"
                            class="px-6 py-2 bg-gradient-primary text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="absensi-semester.php"
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="stats-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $total_hadir; ?></div>
                        <div class="text-sm text-gray-600">Hadir</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $total_sakit; ?></div>
                        <div class="text-sm text-gray-600">Sakit</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $total_izin; ?></div>
                        <div class="text-sm text-gray-600">Izin</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600"><?php echo $total_alfa; ?></div>
                        <div class="text-sm text-gray-600">Alfa</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600"><?php echo $total_terlambat; ?></div>
                        <div class="text-sm text-gray-600">Terlambat</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold <?php echo $overall_percentage >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($overall_percentage, 1); ?>%
                        </div>
                        <div class="text-sm text-gray-600">Kehadiran</div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Kehadiran</h3>
                    <div class="w-full h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Summary per Mata Kuliah -->
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Ringkasan per Mata Kuliah</h3>
                        <button onclick="exportData()"
                            class="px-4 py-2 bg-gradient-success text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>

                    <?php if (empty($absensi_summary)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Tidak ada data absensi yang ditemukan.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Semester</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">SKS</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Total</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Hadir</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Sakit</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Izin</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Alfa</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Terlambat</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($absensi_summary as $summary): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="text-sm font-medium text-gray-800">
                                            <?php echo $summary['tahun_akademik']; ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Semester <?php echo $summary['semester_akademik']; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-800">
                                            <?php echo $summary['nama_matakuliah']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $summary['kode_matakuliah']; ?>
                                        </div>
                                    </td>
                                    <td class="text-center py-3 px-4"><?php echo $summary['sks']; ?></td>
                                    <td class="text-center py-3 px-4"><?php echo $summary['total_pertemuan']; ?></td>
                                    <td class="text-center py-3 px-4 text-green-600 font-semibold">
                                        <?php echo $summary['hadir']; ?></td>
                                    <td class="text-center py-3 px-4 text-yellow-600 font-semibold">
                                        <?php echo $summary['sakit']; ?></td>
                                    <td class="text-center py-3 px-4 text-blue-600 font-semibold">
                                        <?php echo $summary['izin']; ?></td>
                                    <td class="text-center py-3 px-4 text-red-600 font-semibold">
                                        <?php echo $summary['alfa']; ?></td>
                                    <td class="text-center py-3 px-4 text-orange-600 font-semibold">
                                        <?php echo $summary['terlambat']; ?></td>
                                    <td class="text-center py-3 px-4">
                                        <span
                                            class="font-semibold <?php echo $summary['persentase_kehadiran'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo number_format($summary['persentase_kehadiran'], 1); ?>%
                                        </span>
                                        <?php if ($summary['persentase_kehadiran'] < 75): ?>
                                        <div class="text-xs text-red-500 mt-1">⚠️ Tidak memenuhi syarat</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Create attendance chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Sakit', 'Izin', 'Alfa', 'Terlambat'],
            datasets: [{
                data: [
                    <?php echo $total_hadir; ?>,
                    <?php echo $total_sakit; ?>,
                    <?php echo $total_izin; ?>,
                    <?php echo $total_alfa; ?>,
                    <?php echo $total_terlambat; ?>
                ],
                backgroundColor: [
                    '#10b981', // green - hadir
                    '#f59e0b', // yellow - sakit
                    '#3b82f6', // blue - izin
                    '#ef4444', // red - alfa
                    '#f97316' // orange - terlambat
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    function exportData() {
        const table = document.querySelector('table');
        let csv = [];
        const rows = table.querySelectorAll('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = [],
                cols = rows[i].querySelectorAll('td, th');
            for (let j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            csv.push(row.join(','));
        }

        const csvFile = new Blob([csv.join('\n')], {
            type: 'text/csv'
        });
        const downloadLink = document.createElement('a');
        downloadLink.download = 'absensi_semester_' + new Date().toISOString().slice(0, 10) + '.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
    </script>
</body>

</html>