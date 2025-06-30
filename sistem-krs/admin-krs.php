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

// Handle actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'toggle_krs_period') {
        try {
            $new_status = $_POST['krs_status'];
            $query = "UPDATE system_settings SET setting_value = :value WHERE setting_key = 'krs_open'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':value', $new_status);
            $stmt->execute();
            
            $success = $new_status == '1' ? "Periode KRS dibuka" : "Periode KRS ditutup";
        } catch (Exception $e) {
            $error = "Gagal mengubah status KRS: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'approve_krs') {
        try {
            $krs_id = $_POST['krs_id'];
            $query = "UPDATE krs SET status = 'disetujui' WHERE id_krs = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $krs_id);
            $stmt->execute();
            
            $success = "KRS berhasil disetujui";
        } catch (Exception $e) {
            $error = "Gagal menyetujui KRS: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'reject_krs') {
        try {
            $krs_id = $_POST['krs_id'];
            $query = "UPDATE krs SET status = 'ditolak' WHERE id_krs = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $krs_id);
            $stmt->execute();
            
            $success = "KRS berhasil ditolak";
        } catch (Exception $e) {
            $error = "Gagal menolak KRS: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_krs') {
        try {
            $krs_id = $_POST['krs_id'];
            $query = "DELETE FROM krs WHERE id_krs = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $krs_id);
            $stmt->execute();
            
            $success = "KRS berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus KRS: " . $e->getMessage();
        }
    }
}

// Get KRS status
$krs_status_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'krs_open'";
$krs_status_stmt = $conn->prepare($krs_status_query);
$krs_status_stmt->execute();
$krs_open = $krs_status_stmt->fetchColumn() ?: '0';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$semester = $_GET['semester'] ?? '';
$tahun_ajaran = $_GET['tahun_ajaran'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.nim LIKE :search OR m.nama LIKE :search OR mk.nama_matakuliah LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status)) {
    $where_conditions[] = "krs.status = :status";
    $params[':status'] = $status;
}

if (!empty($semester)) {
    $where_conditions[] = "krs.semester = :semester";
    $params[':semester'] = $semester;
}

if (!empty($tahun_ajaran)) {
    $where_conditions[] = "krs.tahun_ajaran = :tahun_ajaran";
    $params[':tahun_ajaran'] = $tahun_ajaran;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM krs 
                LEFT JOIN mahasiswa m ON krs.id_mahasiswa = m.id_mahasiswa
                LEFT JOIN kelas k ON krs.id_kelas = k.id_kelas
                LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
                $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get KRS data
$query = "SELECT krs.*, m.nim, m.nama as nama_mahasiswa, m.program_studi,
                 mk.nama_matakuliah, mk.kode_matakuliah, mk.sks,
                 k.nama_kelas, d.nama_dosen
          FROM krs 
          LEFT JOIN mahasiswa m ON krs.id_mahasiswa = m.id_mahasiswa
          LEFT JOIN kelas k ON krs.id_kelas = k.id_kelas
          LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
          LEFT JOIN dosen d ON k.id_dosen = d.id_dosen
          $where_clause 
          ORDER BY krs.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$krs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_krs,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
                    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                FROM krs";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get unique values for filters
$semester_query = "SELECT DISTINCT semester FROM krs ORDER BY semester";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->execute();
$semester_list = $semester_stmt->fetchAll(PDO::FETCH_COLUMN);

$tahun_query = "SELECT DISTINCT tahun_ajaran FROM krs ORDER BY tahun_ajaran DESC";
$tahun_stmt = $conn->prepare($tahun_query);
$tahun_stmt->execute();
$tahun_list = $tahun_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen KRS - <?php echo APP_NAME; ?></title>
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

                    <a href="admin-krs.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-clipboard-list w-5 mr-3"></i>
                        <span>Manajemen KRS</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</p>
                    </div>

                    <a href="admin-laporan.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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
                            <h1 class="text-2xl font-bold text-gray-800">Manajemen KRS</h1>
                            <p class="text-gray-600">Kelola periode KRS dan persetujuan mahasiswa</p>
                        </div>
                        <div class="flex gap-3">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_krs_period">
                                <input type="hidden" name="krs_status"
                                    value="<?php echo $krs_open == '1' ? '0' : '1'; ?>">
                                <button type="submit"
                                    class="<?php echo $krs_open == '1' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white px-6 py-3 rounded-xl transition-colors">
                                    <i class="fas fa-<?php echo $krs_open == '1' ? 'lock' : 'unlock'; ?> mr-2"></i>
                                    <?php echo $krs_open == '1' ? 'Tutup KRS' : 'Buka KRS'; ?>
                                </button>
                            </form>
                            <button onclick="exportKRS()"
                                class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Export KRS
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total KRS</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_krs']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Disetujui</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['disetujui']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-times text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ditolak</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['ditolak']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KRS Status Alert -->
            <div class="mb-6">
                <div
                    class="<?php echo $krs_open == '1' ? 'bg-green-50 border-green-400' : 'bg-red-50 border-red-400'; ?> border-l-4 p-4 rounded-lg">
                    <div class="flex">
                        <i
                            class="fas fa-<?php echo $krs_open == '1' ? 'unlock text-green-400' : 'lock text-red-400'; ?> mr-3 mt-0.5"></i>
                        <p class="<?php echo $krs_open == '1' ? 'text-green-700' : 'text-red-700'; ?>">
                            Periode KRS saat ini:
                            <strong><?php echo $krs_open == '1' ? 'DIBUKA' : 'DITUTUP'; ?></strong>
                            <?php if ($krs_open == '1'): ?>
                            - Mahasiswa dapat mengisi dan mengubah KRS
                            <?php else: ?>
                            - Mahasiswa tidak dapat mengisi atau mengubah KRS
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                    <p class="text-green-700"><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                    <p class="text-red-700"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <input type="text" name="search" placeholder="Cari NIM, nama, mata kuliah..."
                                value="<?php echo htmlspecialchars($search); ?>" class="form-input w-full">
                        </div>
                        <div>
                            <select name="status" class="form-input w-full">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending
                                </option>
                                <option value="disetujui" <?php echo ($status == 'disetujui') ? 'selected' : ''; ?>>
                                    Disetujui</option>
                                <option value="ditolak" <?php echo ($status == 'ditolak') ? 'selected' : ''; ?>>Ditolak
                                </option>
                            </select>
                        </div>
                        <div>
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
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex-1">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="admin-krs.php"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- KRS Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Daftar KRS (<?php echo $total_records; ?> total)
                        </h3>
                        <div class="text-sm text-gray-600">
                            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mahasiswa</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mata Kuliah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kelas</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dosen</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($krs_list as $krs): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $krs['nama_mahasiswa']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $krs['nim']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $krs['program_studi']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $krs['nama_matakuliah']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $krs['kode_matakuliah']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $krs['sks']; ?> SKS</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $krs['nama_kelas']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $krs['semester']; ?> -
                                        <?php echo $krs['tahun_ajaran']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $krs['nama_dosen'] ?: 'Belum ditentukan'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($krs['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'disetujui': echo 'bg-green-100 text-green-800'; break;
                                            case 'ditolak': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($krs['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <?php if ($krs['status'] == 'pending'): ?>
                                        <button
                                            onclick="approveKRS(<?php echo $krs['id_krs']; ?>, '<?php echo $krs['nama_mahasiswa']; ?>', '<?php echo $krs['nama_matakuliah']; ?>')"
                                            class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button
                                            onclick="rejectKRS(<?php echo $krs['id_krs']; ?>, '<?php echo $krs['nama_mahasiswa']; ?>', '<?php echo $krs['nama_matakuliah']; ?>')"
                                            class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button
                                            onclick="deleteKRS(<?php echo $krs['id_krs']; ?>, '<?php echo $krs['nama_mahasiswa']; ?>', '<?php echo $krs['nama_matakuliah']; ?>')"
                                            class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> -
                            <?php echo min($page * $limit, $total_records); ?> dari <?php echo $total_records; ?> data
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>"
                                class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Setujui KRS</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menyetujui KRS <strong
                        id="approveMahasiswa"></strong> untuk mata kuliah <strong id="approveMatakuliah"></strong>?</p>

                <form method="POST" id="approveForm">
                    <input type="hidden" name="action" value="approve_krs">
                    <input type="hidden" name="krs_id" id="approveKrsId">

                    <div class="flex gap-3">
                        <button type="button" onclick="closeApproveModal()"
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-2"></i>
                            Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-times text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Tolak KRS</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menolak KRS <strong id="rejectMahasiswa"></strong>
                    untuk mata kuliah <strong id="rejectMatakuliah"></strong>?</p>

                <form method="POST" id="rejectForm">
                    <input type="hidden" name="action" value="reject_krs">
                    <input type="hidden" name="krs_id" id="rejectKrsId">

                    <div class="flex gap-3">
                        <button type="button" onclick="closeRejectModal()"
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus KRS</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus KRS <strong
                        id="deleteMahasiswa"></strong> untuk mata kuliah <strong id="deleteMatakuliah"></strong>?
                    Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_krs">
                    <input type="hidden" name="krs_id" id="deleteKrsId">

                    <div class="flex gap-3">
                        <button type="button" onclick="closeDeleteModal()"
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function approveKRS(krsId, mahasiswa, matakuliah) {
        document.getElementById('approveKrsId').value = krsId;
        document.getElementById('approveMahasiswa').textContent = mahasiswa;
        document.getElementById('approveMatakuliah').textContent = matakuliah;
        document.getElementById('approveModal').classList.remove('hidden');
        document.getElementById('approveModal').classList.add('flex');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
        document.getElementById('approveModal').classList.remove('flex');
    }

    function rejectKRS(krsId, mahasiswa, matakuliah) {
        document.getElementById('rejectKrsId').value = krsId;
        document.getElementById('rejectMahasiswa').textContent = mahasiswa;
        document.getElementById('rejectMatakuliah').textContent = matakuliah;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectModal').classList.remove('flex');
    }

    function deleteKRS(krsId, mahasiswa, matakuliah) {
        document.getElementById('deleteKrsId').value = krsId;
        document.getElementById('deleteMahasiswa').textContent = mahasiswa;
        document.getElementById('deleteMatakuliah').textContent = matakuliah;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    function exportKRS() {
        // Implement export functionality
        alert('Fitur export akan segera tersedia');
    }

    // Close modals when clicking outside
    document.getElementById('approveModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeApproveModal();
        }
    });

    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    </script>
</body>

</html>