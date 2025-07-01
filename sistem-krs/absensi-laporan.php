<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Only allow admin or dosen
if (!in_array(getUserRole(), ['admin', 'dosen'])) {
    header('Location: dashboard-soft.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$mata_kuliah_filter = $_GET['mata_kuliah'] ?? '';
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-t');
$status_filter = $_GET['status'] ?? '';

// Get mata kuliah list
$mk_query = "SELECT DISTINCT jk.id, mk.nama_mata_kuliah, mk.kode_mata_kuliah 
             FROM jadwal_kuliah jk 
             JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id 
             ORDER BY mk.nama_mata_kuliah";
$mk_stmt = $conn->prepare($mk_query);
$mk_stmt->execute();
$mata_kuliah_list = $mk_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query for laporan
$laporan_query = "SELECT 
    jp.tanggal_pertemuan,
    jp.pertemuan_ke,
    jp.waktu_mulai,
    jp.waktu_selesai,
    jp.materi,
    jp.ruangan,
    mk.nama_mata_kuliah,
    mk.kode_mata_kuliah,
    m.nama_lengkap,
    m.nim,
    COALESCE(a.status_kehadiran, 'belum_absen') as status_kehadiran,
    a.waktu_absen,
    a.keterangan
FROM jadwal_pertemuan jp
JOIN jadwal_kuliah jk ON jp.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
JOIN krs k ON jk.id = k.jadwal_kuliah_id AND k.status = 'approved'
JOIN mahasiswa m ON k.mahasiswa_id = m.id
LEFT JOIN absensi a ON (jp.id = a.jadwal_pertemuan_id AND m.id = a.mahasiswa_id)
WHERE jp.tanggal_pertemuan BETWEEN :tanggal_dari AND :tanggal_sampai";

$params = [
    ':tanggal_dari' => $tanggal_dari,
    ':tanggal_sampai' => $tanggal_sampai
];

if ($mata_kuliah_filter) {
    $laporan_query .= " AND jk.id = :mata_kuliah";
    $params[':mata_kuliah'] = $mata_kuliah_filter;
}

if ($status_filter) {
    if ($status_filter == 'belum_absen') {
        $laporan_query .= " AND a.status_kehadiran IS NULL";
    } else {
        $laporan_query .= " AND a.status_kehadiran = :status";
        $params[':status'] = $status_filter;
    }
}

$laporan_query .= " ORDER BY jp.tanggal_pertemuan DESC, jp.waktu_mulai, m.nama_lengkap";

$laporan_stmt = $conn->prepare($laporan_query);
$laporan_stmt->execute($params);
$laporan_data = $laporan_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
    SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
    SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) as total_izin,
    SUM(CASE WHEN a.status_kehadiran = 'alfa' THEN 1 ELSE 0 END) as total_alfa,
    SUM(CASE WHEN a.status_kehadiran = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat,
    SUM(CASE WHEN a.status_kehadiran IS NULL THEN 1 ELSE 0 END) as total_belum_absen
FROM jadwal_pertemuan jp
JOIN jadwal_kuliah jk ON jp.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
JOIN krs k ON jk.id = k.jadwal_kuliah_id AND k.status = 'approved'
JOIN mahasiswa m ON k.mahasiswa_id = m.id
LEFT JOIN absensi a ON (jp.id = a.jadwal_pertemuan_id AND m.id = a.mahasiswa_id)
WHERE jp.tanggal_pertemuan BETWEEN :tanggal_dari AND :tanggal_sampai";

if ($mata_kuliah_filter) {
    $stats_query .= " AND jk.id = :mata_kuliah";
}

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute($params);
$statistics = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme-toggle.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(310deg, #f0f2f5 0%, #fcfcfc 100%);
            font-family: 'Open Sans', sans-serif;
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
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-hadir { background: #d1fae5; color: #065f46; }
        .status-sakit { background: #fef3c7; color: #92400e; }
        .status-izin { background: #dbeafe; color: #1e40af; }
        .status-alfa { background: #fee2e2; color: #991b1b; }
        .status-terlambat { background: #fed7aa; color: #9a3412; }
        .status-belum_absen { background: #f3f4f6; color: #374151; }
        
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%); }
        .bg-gradient-info { background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%); }
        .bg-gradient-warning { background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%); }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-4">
        <!-- Header -->
        <div class="card mb-6 no-print">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Laporan Absensi</h1>
                        <p class="text-gray-600">Laporan kehadiran mahasiswa</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="theme-toggle-container"></div>
                        <button onclick="window.print()" class="px-4 py-2 bg-gradient-info text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-print mr-2"></i>Cetak
                        </button>
                        <a href="dashboard-soft.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-6 no-print">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Kuliah</label>
                        <select name="mata_kuliah" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Semua Mata Kuliah</option>
                            <?php foreach ($mata_kuliah_list as $mk): ?>
                            <option value="<?php echo $mk['id']; ?>" <?php echo $mata_kuliah_filter == $mk['id'] ? 'selected' : ''; ?>>
                                <?php echo $mk['kode_mata_kuliah'] . ' - ' . $mk['nama_mata_kuliah']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Dari</label>
                        <input type="date" name="tanggal_dari" value="<?php echo $tanggal_dari; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Sampai</label>
                        <input type="date" name="tanggal_sampai" value="<?php echo $tanggal_sampai; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="hadir" <?php echo $status_filter == 'hadir' ? 'selected' : ''; ?>>Hadir</option>
                            <option value="sakit" <?php echo $status_filter == 'sakit' ? 'selected' : ''; ?>>Sakit</option>
                            <option value="izin" <?php echo $status_filter == 'izin' ? 'selected' : ''; ?>>Izin</option>
                            <option value="alfa" <?php echo $status_filter == 'alfa' ? 'selected' : ''; ?>>Alfa</option>
                            <option value="terlambat" <?php echo $status_filter == 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                            <option value="belum_absen" <?php echo $status_filter == 'belum_absen' ? 'selected' : ''; ?>>Belum Absen</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-gradient-primary text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4 mb-6">
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-800"><?php echo $statistics['total_records']; ?></div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $statistics['total_hadir']; ?></div>
                    <div class="text-sm text-gray-600">Hadir</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $statistics['total_sakit']; ?></div>
                    <div class="text-sm text-gray-600">Sakit</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $statistics['total_izin']; ?></div>
                    <div class="text-sm text-gray-600">Izin</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $statistics['total_alfa']; ?></div>
                    <div class="text-sm text-gray-600">Alfa</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo $statistics['total_terlambat']; ?></div>
                    <div class="text-sm text-gray-600">Terlambat</div>
                </div>
            </div>
            <div class="stats-card">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600"><?php echo $statistics['total_belum_absen']; ?></div>
                    <div class="text-sm text-gray-600">Belum Absen</div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card mb-6 no-print">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Kehadiran</h3>
                <div class="w-full h-64">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Laporan Absensi</h3>
                    <div class="text-sm text-gray-600">
                        Periode: <?php echo date('d/m/Y', strtotime($tanggal_dari)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_sampai)); ?>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Tanggal</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Pertemuan</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">NIM</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama Mahasiswa</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Waktu Absen</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($laporan_data)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p>Tidak ada data absensi yang ditemukan.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($laporan_data as $data): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <?php echo date('d/m/Y', strtotime($data['tanggal_pertemuan'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800"><?php echo $data['nama_mata_kuliah']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $data['kode_mata_kuliah']; ?></div>
                                </td>
                                <td class="text-center py-3 px-4"><?php echo $data['pertemuan_ke']; ?></td>
                                <td class="py-3 px-4 font-medium"><?php echo $data['nim']; ?></td>
                                <td class="py-3 px-4"><?php echo $data['nama_lengkap']; ?></td>
                                <td class="text-center py-3 px-4">
                                    <span class="status-badge status-<?php echo $data['status_kehadiran']; ?>">
                                        <?php 
                                        $status_text = [
                                            'hadir' => 'Hadir',
                                            'sakit' => 'Sakit',
                                            'izin' => 'Izin',
                                            'alfa' => 'Alfa',
                                            'terlambat' => 'Terlambat',
                                            'belum_absen' => 'Belum Absen'
                                        ];
                                        echo $status_text[$data['status_kehadiran']];
                                        ?>
                                    </span>
                                </td>
                                <td class="text-center py-3 px-4">
                                    <?php if ($data['waktu_absen']): ?>
                                        <div class="text-sm"><?php echo date('H:i', strtotime($data['waktu_absen'])); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($data['waktu_absen'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-sm text-gray-600"><?php echo $data['keterangan'] ?: '-'; ?></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        // Create attendance chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alfa', 'Terlambat', 'Belum Absen'],
                datasets: [{
                    data: [
                        <?php echo $statistics['total_hadir']; ?>,
                        <?php echo $statistics['total_sakit']; ?>,
                        <?php echo $statistics['total_izin']; ?>,
                        <?php echo $statistics['total_alfa']; ?>,
                        <?php echo $statistics['total_terlambat']; ?>,
                        <?php echo $statistics['total_belum_absen']; ?>
                    ],
                    backgroundColor: [
                        '#10b981', // green - hadir
                        '#f59e0b', // yellow - sakit
                        '#3b82f6', // blue - izin
                        '#ef4444', // red - alfa
                        '#f97316', // orange - terlambat
                        '#6b7280'  // gray - belum absen
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
    </script>
</body>
</html>
