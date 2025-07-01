<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Only allow mahasiswa
if (getUserRole() != 'mahasiswa') {
    header('Location: dashboard-soft.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Get mahasiswa ID
$mahasiswa_query = "SELECT id FROM mahasiswa WHERE user_id = :user_id";
$mahasiswa_stmt = $conn->prepare($mahasiswa_query);
$mahasiswa_stmt->bindParam(':user_id', $_SESSION['user_id']);
$mahasiswa_stmt->execute();
$mahasiswa_id = $mahasiswa_stmt->fetchColumn();

// Get filter parameters
$mata_kuliah_filter = $_GET['mata_kuliah'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

// Get absensi summary
$summary_query = "SELECT * FROM v_absensi_summary WHERE mahasiswa_id = :mahasiswa_id";
$params = [':mahasiswa_id' => $mahasiswa_id];

if ($mata_kuliah_filter) {
    $summary_query .= " AND jadwal_kuliah_id = :mata_kuliah";
    $params[':mata_kuliah'] = $mata_kuliah_filter;
}

$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->execute($params);
$absensi_summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get detailed absensi
$detail_query = "SELECT 
    jp.tanggal_pertemuan,
    jp.pertemuan_ke,
    jp.waktu_mulai,
    jp.waktu_selesai,
    jp.materi,
    jp.ruangan,
    mk.nama_mata_kuliah,
    mk.kode_mata_kuliah,
    COALESCE(a.status_kehadiran, 'belum_absen') as status_kehadiran,
    a.waktu_absen,
    a.keterangan
FROM krs k
JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
JOIN jadwal_pertemuan jp ON jk.id = jp.jadwal_kuliah_id
LEFT JOIN absensi a ON (jp.id = a.jadwal_pertemuan_id AND k.mahasiswa_id = a.mahasiswa_id)
WHERE k.mahasiswa_id = :mahasiswa_id AND k.status = 'approved'";

if ($mata_kuliah_filter) {
    $detail_query .= " AND jk.id = :mata_kuliah";
}

$detail_query .= " ORDER BY jp.tanggal_pertemuan DESC, jp.waktu_mulai";

$detail_stmt = $conn->prepare($detail_query);
$detail_stmt->execute($params);
$absensi_detail = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get mata kuliah list for filter
$mk_query = "SELECT DISTINCT jk.id, mk.nama_mata_kuliah, mk.kode_mata_kuliah 
             FROM krs k 
             JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id 
             JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id 
             WHERE k.mahasiswa_id = :mahasiswa_id AND k.status = 'approved'
             ORDER BY mk.nama_mata_kuliah";
$mk_stmt = $conn->prepare($mk_query);
$mk_stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$mk_stmt->execute();
$mata_kuliah_list = $mk_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall statistics
$total_pertemuan = 0;
$total_hadir = 0;
$total_sakit = 0;
$total_izin = 0;
$total_alfa = 0;
$total_terlambat = 0;

foreach ($absensi_summary as $summary) {
    $total_pertemuan += $summary['total_pertemuan'];
    $total_hadir += $summary['hadir'];
    $total_sakit += $summary['sakit'];
    $total_izin += $summary['izin'];
    $total_alfa += $summary['alfa'];
    $total_terlambat += $summary['terlambat'];
}

$overall_percentage = $total_pertemuan > 0 ? (($total_hadir + $total_terlambat) / $total_pertemuan) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Mahasiswa - <?php echo APP_NAME; ?></title>
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
                    
                    <a href="dashboard-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="krs-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>

                    <a href="jadwal-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-mahasiswa.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-user-check w-5 mr-3"></i>
                        <span>Absensi</span>
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
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Profil</p>
                    </div>
                    
                    <a href="profil-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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
                            <h1 class="text-2xl font-bold text-gray-800">Absensi Mahasiswa</h1>
                            <p class="text-gray-600">Rekap kehadiran dan statistik absensi</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                        </div>
                    </div>
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
                        <div class="text-2xl font-bold <?php echo $overall_percentage >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($overall_percentage, 1); ?>%
                        </div>
                        <div class="text-sm text-gray-600">Kehadiran</div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-6">
                <div class="p-6">
                    <form method="GET" class="flex gap-4 items-end">
                        <div class="flex-1">
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
                        <button type="submit" class="px-6 py-2 bg-gradient-primary text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="absensi-mahasiswa.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Summary per Mata Kuliah -->
            <?php if (!empty($absensi_summary)): ?>
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan per Mata Kuliah</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Total Pertemuan</th>
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
                                        <div class="font-medium text-gray-800"><?php echo $summary['nama_mata_kuliah']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $summary['kode_mata_kuliah']; ?></div>
                                    </td>
                                    <td class="text-center py-3 px-4"><?php echo $summary['total_pertemuan']; ?></td>
                                    <td class="text-center py-3 px-4 text-green-600 font-semibold"><?php echo $summary['hadir']; ?></td>
                                    <td class="text-center py-3 px-4 text-yellow-600 font-semibold"><?php echo $summary['sakit']; ?></td>
                                    <td class="text-center py-3 px-4 text-blue-600 font-semibold"><?php echo $summary['izin']; ?></td>
                                    <td class="text-center py-3 px-4 text-red-600 font-semibold"><?php echo $summary['alfa']; ?></td>
                                    <td class="text-center py-3 px-4 text-orange-600 font-semibold"><?php echo $summary['terlambat']; ?></td>
                                    <td class="text-center py-3 px-4">
                                        <span class="font-semibold <?php echo $summary['persentase_kehadiran'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
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
                </div>
            </div>
            <?php endif; ?>

            <!-- Detail Absensi -->
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Detail Absensi</h3>
                        <button onclick="exportAbsensi()" class="px-4 py-2 bg-gradient-success text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tanggal</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Pertemuan</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Waktu</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Materi</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Waktu Absen</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($absensi_detail as $detail): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <?php echo date('d/m/Y', strtotime($detail['tanggal_pertemuan'])); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-800"><?php echo $detail['nama_mata_kuliah']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $detail['kode_mata_kuliah']; ?></div>
                                    </td>
                                    <td class="text-center py-3 px-4"><?php echo $detail['pertemuan_ke']; ?></td>
                                    <td class="text-center py-3 px-4">
                                        <div class="text-sm">
                                            <?php echo date('H:i', strtotime($detail['waktu_mulai'])); ?> - 
                                            <?php echo date('H:i', strtotime($detail['waktu_selesai'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500"><?php echo $detail['ruangan']; ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-800"><?php echo $detail['materi'] ?: '-'; ?></div>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <span class="status-badge status-<?php echo $detail['status_kehadiran']; ?>">
                                            <?php 
                                            $status_text = [
                                                'hadir' => 'Hadir',
                                                'sakit' => 'Sakit',
                                                'izin' => 'Izin',
                                                'alfa' => 'Alfa',
                                                'terlambat' => 'Terlambat',
                                                'belum_absen' => 'Belum Absen'
                                            ];
                                            echo $status_text[$detail['status_kehadiran']];
                                            ?>
                                        </span>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <?php if ($detail['waktu_absen']): ?>
                                            <div class="text-sm"><?php echo date('H:i', strtotime($detail['waktu_absen'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($detail['waktu_absen'])); ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-600"><?php echo $detail['keterangan'] ?: '-'; ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function exportAbsensi() {
            // Simple export to CSV
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = 'absensi_' + new Date().toISOString().slice(0, 10) + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
