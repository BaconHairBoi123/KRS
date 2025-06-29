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
    if ($_POST['action'] == 'add_kelas') {
        try {
            $query = "INSERT INTO kelas (nama_kelas, id_matakuliah, id_dosen, kapasitas, semester, tahun_ajaran, hari, jam_mulai, jam_selesai, ruangan, status) 
                     VALUES (:nama_kelas, :id_matakuliah, :id_dosen, :kapasitas, :semester, :tahun_ajaran, :hari, :jam_mulai, :jam_selesai, :ruangan, :status)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama_kelas', $_POST['nama_kelas']);
            $stmt->bindParam(':id_matakuliah', $_POST['id_matakuliah']);
            $stmt->bindParam(':id_dosen', $_POST['id_dosen']);
            $stmt->bindParam(':kapasitas', $_POST['kapasitas']);
            $stmt->bindParam(':semester', $_POST['semester']);
            $stmt->bindParam(':tahun_ajaran', $_POST['tahun_ajaran']);
            $stmt->bindParam(':hari', $_POST['hari']);
            $stmt->bindParam(':jam_mulai', $_POST['jam_mulai']);
            $stmt->bindParam(':jam_selesai', $_POST['jam_selesai']);
            $stmt->bindParam(':ruangan', $_POST['ruangan']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->execute();
            
            $success = "Kelas berhasil ditambahkan";
        } catch (Exception $e) {
            $error = "Gagal menambahkan kelas: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'edit_kelas') {
        try {
            $query = "UPDATE kelas SET 
                     nama_kelas = :nama_kelas, id_matakuliah = :id_matakuliah, id_dosen = :id_dosen,
                     kapasitas = :kapasitas, semester = :semester, tahun_ajaran = :tahun_ajaran,
                     hari = :hari, jam_mulai = :jam_mulai, jam_selesai = :jam_selesai,
                     ruangan = :ruangan, status = :status
                     WHERE id_kelas = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama_kelas', $_POST['nama_kelas']);
            $stmt->bindParam(':id_matakuliah', $_POST['id_matakuliah']);
            $stmt->bindParam(':id_dosen', $_POST['id_dosen']);
            $stmt->bindParam(':kapasitas', $_POST['kapasitas']);
            $stmt->bindParam(':semester', $_POST['semester']);
            $stmt->bindParam(':tahun_ajaran', $_POST['tahun_ajaran']);
            $stmt->bindParam(':hari', $_POST['hari']);
            $stmt->bindParam(':jam_mulai', $_POST['jam_mulai']);
            $stmt->bindParam(':jam_selesai', $_POST['jam_selesai']);
            $stmt->bindParam(':ruangan', $_POST['ruangan']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->bindParam(':id', $_POST['kelas_id']);
            $stmt->execute();
            
            $success = "Kelas berhasil diupdate";
        } catch (Exception $e) {
            $error = "Gagal mengupdate kelas: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_kelas') {
        try {
            $query = "DELETE FROM kelas WHERE id_kelas = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_POST['kelas_id']);
            $stmt->execute();
            
            $success = "Kelas berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus kelas: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$semester = $_GET['semester'] ?? '';
$tahun_ajaran = $_GET['tahun_ajaran'] ?? '';
$hari = $_GET['hari'] ?? '';
$status = $_GET['status'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(k.nama_kelas LIKE :search OR mk.nama_matakuliah LIKE :search OR d.nama_dosen LIKE :search OR k.ruangan LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($semester)) {
    $where_conditions[] = "k.semester = :semester";
    $params[':semester'] = $semester;
}

if (!empty($tahun_ajaran)) {
    $where_conditions[] = "k.tahun_ajaran = :tahun_ajaran";
    $params[':tahun_ajaran'] = $tahun_ajaran;
}

if (!empty($hari)) {
    $where_conditions[] = "k.hari = :hari";
    $params[':hari'] = $hari;
}

if (!empty($status)) {
    $where_conditions[] = "k.status = :status";
    $params[':status'] = $status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM kelas k 
                LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
                LEFT JOIN dosen d ON k.id_dosen = d.id_dosen
                $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get kelas data
$query = "SELECT k.*, mk.nama_matakuliah, mk.kode_matakuliah, mk.sks, d.nama_dosen,
                 COUNT(krs.id_krs) as jumlah_mahasiswa
          FROM kelas k 
          LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
          LEFT JOIN dosen d ON k.id_dosen = d.id_dosen
          LEFT JOIN krs ON k.id_kelas = krs.id_kelas
          $where_clause 
          GROUP BY k.id_kelas
          ORDER BY k.hari, k.jam_mulai 
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get mata kuliah list
$mk_query = "SELECT id_matakuliah, nama_matakuliah, kode_matakuliah, sks FROM mata_kuliah WHERE status = 'aktif' ORDER BY nama_matakuliah";
$mk_stmt = $conn->prepare($mk_query);
$mk_stmt->execute();
$matakuliah_list = $mk_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get dosen list
$dosen_query = "SELECT id_dosen, nama_dosen, program_studi FROM dosen WHERE status = 'aktif' ORDER BY nama_dosen";
$dosen_stmt = $conn->prepare($dosen_query);
$dosen_stmt->execute();
$dosen_list = $dosen_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$semester_query = "SELECT DISTINCT semester FROM kelas ORDER BY semester";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->execute();
$semester_list = $semester_stmt->fetchAll(PDO::FETCH_COLUMN);

$tahun_query = "SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC";
$tahun_stmt = $conn->prepare($tahun_query);
$tahun_stmt->execute();
$tahun_list = $tahun_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjadwalan Perkuliahan - <?php echo APP_NAME; ?></title>
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

                    <a href="admin-jadwal.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Penjadwalan Perkuliahan</h1>
                            <p class="text-gray-600">Kelola jadwal kuliah dan assign dosen pengajar</p>
                        </div>
                        <button onclick="openAddModal()"
                            class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Kelas
                        </button>
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
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div>
                            <input type="text" name="search" placeholder="Cari kelas, mata kuliah, dosen..."
                                value="<?php echo htmlspecialchars($search); ?>" class="form-input w-full">
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
                        <div>
                            <select name="hari" class="form-input w-full">
                                <option value="">Semua Hari</option>
                                <option value="Senin" <?php echo ($hari == 'Senin') ? 'selected' : ''; ?>>Senin</option>
                                <option value="Selasa" <?php echo ($hari == 'Selasa') ? 'selected' : ''; ?>>Selasa
                                </option>
                                <option value="Rabu" <?php echo ($hari == 'Rabu') ? 'selected' : ''; ?>>Rabu</option>
                                <option value="Kamis" <?php echo ($hari == 'Kamis') ? 'selected' : ''; ?>>Kamis</option>
                                <option value="Jumat" <?php echo ($hari == 'Jumat') ? 'selected' : ''; ?>>Jumat</option>
                                <option value="Sabtu" <?php echo ($hari == 'Sabtu') ? 'selected' : ''; ?>>Sabtu</option>
                            </select>
                        </div>
                        <div>
                            <select name="status" class="form-input w-full">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo ($status == 'aktif') ? 'selected' : ''; ?>>Aktif
                                </option>
                                <option value="nonaktif" <?php echo ($status == 'nonaktif') ? 'selected' : ''; ?>>
                                    Non-aktif</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex-1">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="admin-jadwal.php"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kelas Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Daftar Kelas (<?php echo $total_records; ?> total)
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
                                    Kelas</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mata Kuliah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dosen</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jadwal</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kapasitas</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($kelas_list as $kelas): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-users text-indigo-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $kelas['nama_kelas']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $kelas['semester']; ?> -
                                                <?php echo $kelas['tahun_ajaran']; ?></div>
                                            <div class="text-sm text-gray-500">Ruang: <?php echo $kelas['ruangan']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $kelas['nama_matakuliah']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $kelas['kode_matakuliah']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $kelas['sks']; ?> SKS</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $kelas['nama_dosen'] ?: 'Belum ditentukan'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $kelas['hari']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $kelas['jam_mulai']; ?> -
                                        <?php echo $kelas['jam_selesai']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $kelas['jumlah_mahasiswa']; ?> /
                                        <?php echo $kelas['kapasitas']; ?></div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-blue-600 h-2 rounded-full"
                                            style="width: <?php echo ($kelas['kapasitas'] > 0) ? ($kelas['jumlah_mahasiswa'] / $kelas['kapasitas'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $kelas['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($kelas['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editKelas(<?php echo htmlspecialchars(json_encode($kelas)); ?>)"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        onclick="deleteKelas(<?php echo $kelas['id_kelas']; ?>, '<?php echo $kelas['nama_kelas']; ?>')"
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>&hari=<?php echo urlencode($hari); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>&hari=<?php echo urlencode($hari); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester); ?>&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>&hari=<?php echo urlencode($hari); ?>&status=<?php echo urlencode($status); ?>"
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

    <!-- Add/Edit Modal -->
    <div id="kelasModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Kelas</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" id="kelasForm">
                <input type="hidden" name="action" id="formAction" value="add_kelas">
                <input type="hidden" name="kelas_id" id="kelasId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kelas *</label>
                            <input type="text" name="nama_kelas" id="nama_kelas" required class="form-input w-full"
                                placeholder="Contoh: A, B, C">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mata Kuliah *</label>
                            <select name="id_matakuliah" id="id_matakuliah" required class="form-input w-full">
                                <option value="">Pilih Mata Kuliah</option>
                                <?php foreach ($matakuliah_list as $mk): ?>
                                <option value="<?php echo $mk['id_matakuliah']; ?>">
                                    <?php echo $mk['kode_matakuliah']; ?> - <?php echo $mk['nama_matakuliah']; ?>
                                    (<?php echo $mk['sks']; ?> SKS)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dosen Pengajar</label>
                            <select name="id_dosen" id="id_dosen" class="form-input w-full">
                                <option value="">Pilih Dosen</option>
                                <?php foreach ($dosen_list as $dosen): ?>
                                <option value="<?php echo $dosen['id_dosen']; ?>">
                                    <?php echo $dosen['nama_dosen']; ?> - <?php echo $dosen['program_studi']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kapasitas *</label>
                                <input type="number" name="kapasitas" id="kapasitas" required class="form-input w-full"
                                    value="40" min="1">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" class="form-input w-full">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non-aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Semester *</label>
                                <select name="semester" id="semester" required class="form-input w-full">
                                    <option value="">Pilih Semester</option>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Ajaran *</label>
                                <input type="text" name="tahun_ajaran" id="tahun_ajaran" required
                                    class="form-input w-full" placeholder="2024/2025">
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Hari *</label>
                            <select name="hari" id="hari" required class="form-input w-full">
                                <option value="">Pilih Hari</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Mulai *</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" required class="form-input w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Selesai *</label>
                                <input type="time" name="jam_selesai" id="jam_selesai" required
                                    class="form-input w-full">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ruangan *</label>
                            <input type="text" name="ruangan" id="ruangan" required class="form-input w-full"
                                placeholder="Contoh: R101, Lab Komputer 1">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-3 mt-6 pt-6 border-t">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Kelas</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus kelas <strong id="deleteName"></strong>?
                    Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_kelas">
                    <input type="hidden" name="kelas_id" id="deleteKelasId">

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
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Kelas';
        document.getElementById('formAction').value = 'add_kelas';
        document.getElementById('submitText').textContent = 'Tambah';
        document.getElementById('kelasForm').reset();
        document.getElementById('kelasModal').classList.remove('hidden');
        document.getElementById('kelasModal').classList.add('flex');
    }

    function editKelas(data) {
        document.getElementById('modalTitle').textContent = 'Edit Kelas';
        document.getElementById('formAction').value = 'edit_kelas';
        document.getElementById('submitText').textContent = 'Update';

        // Fill form with data
        document.getElementById('kelasId').value = data.id_kelas;
        document.getElementById('nama_kelas').value = data.nama_kelas;
        document.getElementById('id_matakuliah').value = data.id_matakuliah;
        document.getElementById('id_dosen').value = data.id_dosen || '';
        document.getElementById('kapasitas').value = data.kapasitas;
        document.getElementById('semester').value = data.semester;
        document.getElementById('tahun_ajaran').value = data.tahun_ajaran;
        document.getElementById('hari').value = data.hari;
        document.getElementById('jam_mulai').value = data.jam_mulai;
        document.getElementById('jam_selesai').value = data.jam_selesai;
        document.getElementById('ruangan').value = data.ruangan;
        document.getElementById('status').value = data.status;

        document.getElementById('kelasModal').classList.remove('hidden');
        document.getElementById('kelasModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('kelasModal').classList.add('hidden');
        document.getElementById('kelasModal').classList.remove('flex');
    }

    function deleteKelas(id, name) {
        document.getElementById('deleteKelasId').value = id;
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    // Close modals when clicking outside
    document.getElementById('kelasModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
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