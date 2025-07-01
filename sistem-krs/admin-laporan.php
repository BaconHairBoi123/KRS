<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Get filter parameters
$semester = $_GET['semester'] ?? '';
$tahun_ajaran = $_GET['tahun_ajaran'] ?? '';
$program_studi = $_GET['program_studi'] ?? '';
$report_type = $_GET['report_type'] ?? 'krs';

// Get unique values for filters
$semester_query = "SELECT DISTINCT semester FROM krs ORDER BY semester";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->execute();
$semester_list = $semester_stmt->fetchAll(PDO::FETCH_COLUMN);

$tahun_query = "SELECT DISTINCT tahun_ajaran FROM krs ORDER BY tahun_ajaran DESC";
$tahun_stmt = $conn->prepare($tahun_query);
$tahun_stmt->execute();
$tahun_list = $tahun_stmt->fetchAll(PDO::FETCH_COLUMN);

$prodi_query = "SELECT DISTINCT program_studi FROM mahasiswa ORDER BY program_studi";
$prodi_stmt = $conn->prepare($prodi_query);
$prodi_stmt->execute();
$prodi_list = $prodi_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build where conditions
$where_conditions = [];
$params = [];

if (!empty($semester)) {
    $where_conditions[] = "krs.semester = :semester";
    $params[':semester'] = $semester;
}

if (!empty($tahun_ajaran)) {
    $where_conditions[] = "krs.tahun_ajaran = :tahun_ajaran";
    $params[':tahun_ajaran'] = $tahun_ajaran;
}

if (!empty($program_studi)) {
    $where_conditions[] = "m.program_studi = :program_studi";
    $params[':program_studi'] = $program_studi;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Generate reports based on type
$report_data = [];

if ($report_type == 'krs') {
    // KRS Summary Report
    $query = "SELECT 
                m.program_studi,
                COUNT(DISTINCT m.id_mahasiswa) as total_mahasiswa,
                COUNT(krs.id_krs) as total_krs,
                SUM(CASE WHEN krs.status_krs = 'disetujui' THEN 1 ELSE 0 END) as krs_disetujui,
                SUM(CASE WHEN krs.status_krs = 'pending' THEN 1 ELSE 0 END) as krs_pending,
                SUM(CASE WHEN krs.status_krs = 'ditolak' THEN 1 ELSE 0 END) as krs_ditolak,
                SUM(CASE WHEN krs.status_krs = 'disetujui' THEN mk.sks ELSE 0 END) as total_sks
              FROM krs 
              LEFT JOIN mahasiswa m ON krs.id_mahasiswa = m.id_mahasiswa
              LEFT JOIN kelas k ON krs.id_kelas = k.id_kelas
              LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
              $where_clause
              GROUP BY m.program_studi
              ORDER BY m.program_studi";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($report_type == 'mata_kuliah') {
    // Mata Kuliah Popularity Report
    $query = "SELECT 
                mk.kode_matakuliah,
                mk.nama_matakuliah,
                mk.sks,
                COUNT(krs.id_krs) as total_pengambil,
                SUM(CASE WHEN krs.status_krs = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
                SUM(CASE WHEN krs.status_krs = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN krs.status_krs = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
                d.nama_dosen
              FROM krs 
              LEFT JOIN mahasiswa m ON krs.id_mahasiswa = m.id_mahasiswa
              LEFT JOIN kelas k ON krs.id_kelas = k.id_kelas
              LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
              LEFT JOIN dosen d ON k.id_dosen = d.id_dosen
              $where_clause
              GROUP BY mk.id_matakuliah, k.id_dosen
              ORDER BY total_pengambil DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($report_type == 'mahasiswa') {
    // Student KRS Report
    $query = "SELECT 
                m.nim,
                m.nama,
                m.program_studi,
                COUNT(krs.id_krs) as total_mata_kuliah,
                SUM(CASE WHEN krs.status_krs = 'disetujui' THEN mk.sks ELSE 0 END) as total_sks,
                SUM(CASE WHEN krs.status_krs = 'disetujui' THEN 1 ELSE 0 END) as mk_disetujui,
                SUM(CASE WHEN krs.status_krs = 'pending' THEN 1 ELSE 0 END) as mk_pending,
                SUM(CASE WHEN krs.status_krs = 'ditolak' THEN 1 ELSE 0 END) as mk_ditolak
              FROM krs 
              LEFT JOIN mahasiswa m ON krs.id_mahasiswa = m.id_mahasiswa
              LEFT JOIN kelas k ON krs.id_kelas = k.id_kelas
              LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
              $where_clause
              GROUP BY m.id_mahasiswa
              ORDER BY m.nama";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($report_type == 'krs') {
        fputcsv($output, ['Program Studi', 'Total Mahasiswa', 'Total KRS', 'Disetujui', 'Pending', 'Ditolak', 'Total SKS']);
        foreach ($report_data as $row) {
            fputcsv($output, [
                $row['program_studi'],
                $row['total_mahasiswa'],
                $row['total_krs'],
                $row['krs_disetujui'],
                $row['krs_pending'],
                $row['krs_ditolak'],
                $row['total_sks']
            ]);
        }
    } elseif ($report_type == 'mata_kuliah') {
        fputcsv($output, ['Kode MK', 'Nama Mata Kuliah', 'SKS', 'Total Pengambil', 'Disetujui', 'Pending', 'Ditolak', 'Dosen']);
        foreach ($report_data as $row) {
            fputcsv($output, [
                $row['kode_matakuliah'],
                $row['nama_matakuliah'],
                $row['sks'],
                $row['total_pengambil'],
                $row['disetujui'],
                $row['pending'],
                $row['ditolak'],
                $row['nama_dosen']
            ]);
        }
    } elseif ($report_type == 'mahasiswa') {
        fputcsv($output, ['NIM', 'Nama', 'Program Studi', 'Total MK', 'Total SKS', 'Disetujui', 'Pending', 'Ditolak']);
        foreach ($report_data as $row) {
            fputcsv($output, [
                $row['nim'],
                $row['nama'],
                $row['program_studi'],
                $row['total_mata_kuliah'],
                $row['total_sks'],
                $row['mk_disetujui'],
                $row['mk_pending'],
                $row['mk_ditolak']
            ]);
        }
    }
    
    fclose($output);
    exit;
}
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
    <style>
    body {
        background: linear-gradient(310deg, #f0f2f5 0%, #fcfcfc 100%);
        font-family: 'Open Sans', sans-serif;
    }

    .absolute.bottom-4.left-4.right-4 {
        z-index: 1;
    }

    .nav-link-soft {
        z-index: 2;
    }

    l .sidebar-soft {
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

    .form-input {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        border-color: #7928ca;
        box-shadow: 0 0 0 3px rgba(121, 40, 202, 0.1);
        outline: none;
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
                <div class="bg-white bg-opacity-50 rounded-xl p-3">
                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $_SESSION['nama']; ?>
                                </p>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                        </div>

                        <div>
                            <a href="logout.php" class="text-red-500 hover:text-red-700 text-lg">
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
                            <p class="text-gray-600">Generate dan export laporan sistem akademik</p>
                        </div>
                        <?php if (!empty($report_data)): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                            class="bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Export CSV
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Laporan</label>
                            <select name="report_type" class="form-input w-full">
                                <option value="krs" <?php echo ($report_type == 'krs') ? 'selected' : ''; ?>>Laporan KRS
                                </option>
                                <option value="mata_kuliah"
                                    <?php echo ($report_type == 'mata_kuliah') ? 'selected' : ''; ?>>Laporan Mata Kuliah
                                </option>
                                <option value="mahasiswa"
                                    <?php echo ($report_type == 'mahasiswa') ? 'selected' : ''; ?>>
                                    Laporan Mahasiswa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Semester</label>
                            <select name="semester" class="form-input w-full">
                                <option value="">Semua Semester</option>
                                <?php foreach ($semester_list as $sem): ?>
                                <option value="<?php echo $sem; ?>"
                                    <?php echo ($semester == $sem) ? 'selected' : ''; ?>>
                                    <?php echo $sem; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Ajaran</label>
                            <select name="tahun_ajaran" class="form-input w-full">
                                <option value="">Semua Tahun Ajaran</option>
                                <?php foreach ($tahun_list as $tahun): ?>
                                <option value="<?php echo $tahun; ?>"
                                    <?php echo ($tahun_ajaran == $tahun) ? 'selected' : ''; ?>>
                                    <?php echo $tahun; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Program Studi</label>
                            <select name="program_studi" class="form-input w-full">
                                <option value="">Semua Program Studi</option>
                                <?php foreach ($prodi_list as $prodi): ?>
                                <option value="<?php echo $prodi; ?>"
                                    <?php echo ($program_studi == $prodi) ? 'selected' : ''; ?>>
                                    <?php echo $prodi; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 w-full">
                                <i class="fas fa-chart-bar mr-2"></i>Generate Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Results -->
            <?php if (!empty($report_data)): ?>
            <div class="card">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php 
                        if ($report_type == 'krs') echo 'Laporan Ringkasan KRS';
                        elseif ($report_type == 'mata_kuliah') echo 'Laporan Mata Kuliah';
                        else echo 'Laporan Mahasiswa';
                        ?>
                        (<?php echo count($report_data); ?> data)
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        <?php if ($semester): ?>Semester: <?php echo $semester; ?> | <?php endif; ?>
                        <?php if ($tahun_ajaran): ?>Tahun Ajaran: <?php echo $tahun_ajaran; ?> | <?php endif; ?>
                        <?php if ($program_studi): ?>Program Studi: <?php echo $program_studi; ?> | <?php endif; ?>
                        Generated: <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($report_type == 'krs'): ?>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Studi</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Mahasiswa</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total KRS</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Disetujui</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pending</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ditolak</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total SKS</th>
                                <?php elseif ($report_type == 'mata_kuliah'): ?>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kode MK</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Mata Kuliah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    SKS</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Pengambil</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Disetujui</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pending</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ditolak</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dosen</th>
                                <?php else: ?>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    NIM</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Studi</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total MK</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total SKS</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Disetujui</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pending</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ditolak</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($report_data as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <?php if ($report_type == 'krs'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $row['program_studi']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['total_mahasiswa']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['total_krs']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                    <?php echo $row['krs_disetujui']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-semibold">
                                    <?php echo $row['krs_pending']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                    <?php echo $row['krs_ditolak']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    <?php echo $row['total_sks']; ?></td>
                                <?php elseif ($report_type == 'mata_kuliah'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $row['kode_matakuliah']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['nama_matakuliah']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['sks']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    <?php echo $row['total_pengambil']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                    <?php echo $row['disetujui']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-semibold">
                                    <?php echo $row['pending']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                    <?php echo $row['ditolak']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['nama_dosen'] ?: 'Belum ditentukan'; ?></td>
                                <?php else: ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $row['nim']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['nama']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['program_studi']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['total_mata_kuliah']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    <?php echo $row['total_sks']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                    <?php echo $row['mk_disetujui']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-semibold">
                                    <?php echo $row['mk_pending']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                    <?php echo $row['mk_ditolak']; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-bar text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Data Laporan</h3>
                    <p class="text-gray-600 mb-6">Pilih filter dan klik "Generate Laporan" untuk melihat data</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>