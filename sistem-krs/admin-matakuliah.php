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
    if ($_POST['action'] == 'add_matakuliah') {
        try {
            $query = "INSERT INTO mata_kuliah (kode_matakuliah, nama_matakuliah, sks, semester, jurusan, program_studi, deskripsi, prasyarat, status) 
                     VALUES (:kode, :nama, :sks, :semester, :jurusan, :program_studi, :deskripsi, :prasyarat, :status)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kode', $_POST['kode_matakuliah']);
            $stmt->bindParam(':nama', $_POST['nama_matakuliah']);
            $stmt->bindParam(':sks', $_POST['sks']);
            $stmt->bindParam(':semester', $_POST['semester']);
            $stmt->bindParam(':jurusan', $_POST['jurusan']);
            $stmt->bindParam(':program_studi', $_POST['program_studi']);
            $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
            $stmt->bindParam(':prasyarat', $_POST['prasyarat']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->execute();
            
            $success = "Mata kuliah berhasil ditambahkan";
        } catch (Exception $e) {
            $error = "Gagal menambahkan mata kuliah: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'edit_matakuliah') {
        try {
            $query = "UPDATE mata_kuliah SET 
                     kode_matakuliah = :kode, nama_matakuliah = :nama, sks = :sks, 
                     semester = :semester, jurusan = :jurusan, program_studi = :program_studi,
                     deskripsi = :deskripsi, prasyarat = :prasyarat, status = :status
                     WHERE id_matakuliah = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kode', $_POST['kode_matakuliah']);
            $stmt->bindParam(':nama', $_POST['nama_matakuliah']);
            $stmt->bindParam(':sks', $_POST['sks']);
            $stmt->bindParam(':semester', $_POST['semester']);
            $stmt->bindParam(':jurusan', $_POST['jurusan']);
            $stmt->bindParam(':program_studi', $_POST['program_studi']);
            $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
            $stmt->bindParam(':prasyarat', $_POST['prasyarat']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->bindParam(':id', $_POST['matakuliah_id']);
            $stmt->execute();
            
            $success = "Mata kuliah berhasil diupdate";
        } catch (Exception $e) {
            $error = "Gagal mengupdate mata kuliah: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_matakuliah') {
        try {
            $query = "DELETE FROM mata_kuliah WHERE id_matakuliah = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_POST['matakuliah_id']);
            $stmt->execute();
            
            $success = "Mata kuliah berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus mata kuliah: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$jurusan = $_GET['jurusan'] ?? '';
$program_studi = $_GET['program_studi'] ?? '';
$semester = $_GET['semester'] ?? '';
$status = $_GET['status'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(kode_matakuliah LIKE :search OR nama_matakuliah LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($jurusan)) {
    $where_conditions[] = "jurusan = :jurusan";
    $params[':jurusan'] = $jurusan;
}

if (!empty($program_studi)) {
    $where_conditions[] = "program_studi = :program_studi";
    $params[':program_studi'] = $program_studi;
}

if (!empty($semester)) {
    $where_conditions[] = "semester = :semester";
    $params[':semester'] = $semester;
}

if (!empty($status)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM mata_kuliah $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get mata kuliah data
$query = "SELECT * FROM mata_kuliah $where_clause ORDER BY jurusan, program_studi, semester, nama_matakuliah LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$matakuliah_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$jurusan_query = "SELECT DISTINCT jurusan FROM mata_kuliah ORDER BY jurusan";
$jurusan_stmt = $conn->prepare($jurusan_query);
$jurusan_stmt->execute();
$jurusan_list = $jurusan_stmt->fetchAll(PDO::FETCH_COLUMN);

$prodi_query = "SELECT DISTINCT program_studi FROM mata_kuliah ORDER BY program_studi";
$prodi_stmt = $conn->prepare($prodi_query);
$prodi_stmt->execute();
$prodi_list = $prodi_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mata Kuliah - <?php echo APP_NAME; ?></title>
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

                    <a href="mahasiswa-admin.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Data Mahasiswa</span>
                    </a>

                    <a href="dosen-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Data Dosen</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="admin-matakuliah.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Manajemen Mata Kuliah</h1>
                            <p class="text-gray-600">Kelola mata kuliah dan kurikulum</p>
                        </div>
                        <button onclick="openAddModal()"
                            class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Mata Kuliah
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
                            <input type="text" name="search" placeholder="Cari kode/nama mata kuliah..."
                                value="<?php echo htmlspecialchars($search); ?>" class="form-input w-full">
                        </div>
                        <div>
                            <select name="jurusan" class="form-input w-full">
                                <option value="">Semua Jurusan</option>
                                <?php foreach ($jurusan_list as $jur): ?>
                                <option value="<?php echo $jur; ?>" <?php echo ($jurusan == $jur) ? 'selected' : ''; ?>>
                                    <?php echo $jur; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
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
                        <div>
                            <select name="semester" class="form-input w-full">
                                <option value="">Semua Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($semester == $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
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
                            <a href="admin-matakuliah.php"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Mata Kuliah Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Daftar Mata Kuliah (<?php echo $total_records; ?> total)
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
                                    Mata Kuliah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Studi</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    SKS</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Semester</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($matakuliah_list as $mk): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-book text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $mk['nama_matakuliah']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $mk['kode_matakuliah']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $mk['program_studi']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $mk['jurusan']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        <?php echo $mk['sks']; ?> SKS
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $mk['semester']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $mk['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($mk['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editMatakuliah(<?php echo htmlspecialchars(json_encode($mk)); ?>)"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        onclick="deleteMatakuliah(<?php echo $mk['id_matakuliah']; ?>, '<?php echo $mk['nama_matakuliah']; ?>')"
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&semester=<?php echo urlencode($semester); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&semester=<?php echo urlencode($semester); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&semester=<?php echo urlencode($semester); ?>&status=<?php echo urlencode($status); ?>"
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
    <div id="matakuliahModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Mata Kuliah</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" id="matakuliahForm">
                <input type="hidden" name="action" id="formAction" value="add_matakuliah">
                <input type="hidden" name="matakuliah_id" id="matakuliahId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Mata Kuliah *</label>
                            <input type="text" name="kode_matakuliah" id="kodeMatakuliah" required
                                class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Mata Kuliah *</label>
                            <input type="text" name="nama_matakuliah" id="namaMatakuliah" required
                                class="form-input w-full">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">SKS *</label>
                                <select name="sks" id="sks" required class="form-input w-full">
                                    <option value="">Pilih SKS</option>
                                    <option value="1">1 SKS</option>
                                    <option value="2">2 SKS</option>
                                    <option value="3">3 SKS</option>
                                    <option value="4">4 SKS</option>
                                    <option value="6">6 SKS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Semester *</label>
                                <select name="semester" id="semester" required class="form-input w-full">
                                    <option value="">Pilih Semester</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jurusan *</label>
                            <select name="jurusan" id="jurusanSelect" required class="form-input w-full">
                                <option value="">Pilih Jurusan</option>
                                <option value="Akuntansi">Akuntansi</option>
                                <option value="Administrasi Bisnis">Administrasi Bisnis</option>
                                <option value="Pariwisata">Pariwisata</option>
                                <option value="Teknik Sipil">Teknik Sipil</option>
                                <option value="Teknik Mesin">Teknik Mesin</option>
                                <option value="Teknik Elektro">Teknik Elektro</option>
                                <option value="Teknologi Informasi">Teknologi Informasi</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Program Studi *</label>
                            <select name="program_studi" id="programStudiSelect" required class="form-input w-full">
                                <option value="">Pilih Program Studi</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="statusSelect" class="form-input w-full">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Prasyarat</label>
                            <input type="text" name="prasyarat" id="prasyarat" class="form-input w-full"
                                placeholder="Contoh: Algoritma Pemrograman">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" rows="6" class="form-input w-full"
                                placeholder="Deskripsi mata kuliah..."></textarea>
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
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Mata Kuliah</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus mata kuliah <strong
                        id="deleteName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_matakuliah">
                    <input type="hidden" name="matakuliah_id" id="deleteMatakuliahId">

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
    // Program studi options
    const programStudiOptions = {
    'Akuntansi': [
        'D2 Administrasi Perpajakan',
        'D3 Akuntansi',
        'D4 Akuntansi Manajerial',
        'D4 Akuntansi Perpajakan'
    ],
    'Administrasi Bisnis': [
        'D2 Manajemen Operasional Bisnis Digital',
        'D3 Administrasi Bisnis',
        'D4 Manajemen Bisnis Internasional',
        'D4 Bisnis Digital',
        'D4 Bahasa Inggris untuk Komunikasi Bisnis & Profesional'
    ],
    'Pariwisata': [
        'S2 Terapan Perencanaan Pariwisata',
        'D4 Manajemen Bisnis Pariwisata',
        'D3 Perhotelan',
        'D3 Usaha Perjalanan Wisata'
    ],
    'Teknik Sipil': [
        'D2 Fondasi, Beton, & Pengaspalan Jalan',
        'D3 Teknik Sipil',
        'D4 Manajemen Proyek Konstruksi',
        'D4 Teknologi Rekayasa Konstruksi Bangunan Gedung',
        'D4 Teknologi Rekayasa Konstruksi Bangunan Air'
    ],
    'Teknik Mesin': [
        'D2 Teknik Manufaktur Mesin',
        'D3 Teknik Mesin',
        'D3 Teknik Pendingin dan Tata Udara',
        'D4 Teknologi Rekayasa Utilitas',
        'D4 Rekayasa Perancangan Mekanik'
    ],
    'Teknik Elektro': [
        'D2 Instalasi dan Pemeliharaan Kabel Bertegangan Rendah',
        'D3 Teknik Listrik',
        'D4 Teknik Otomasi',
        'D4 Teknologi Rekayasa Energi Terbarukan'
    ],
    'Teknologi Informasi': [
        'D2 Administrasi Jaringan Komputer',
        'D3 Manajemen Informatika',
        'D4 Teknologi Rekayasa Perangkat Lunak'
    ]
};

    document.getElementById('jurusanSelect').addEventListener('change', function() {
        const jurusan = this.value;
        const programStudiSelect = document.getElementById('programStudiSelect');

        // Clear existing options
        programStudiSelect.innerHTML = '<option value="">Pilih Program Studi</option>';

        if (jurusan && programStudiOptions[jurusan]) {
            programStudiOptions[jurusan].forEach(function(prodi) {
                const option = document.createElement('option');
                option.value = prodi;
                option.textContent = prodi;
                programStudiSelect.appendChild(option);
            });
        }
    });

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Mata Kuliah';
        document.getElementById('formAction').value = 'add_matakuliah';
        document.getElementById('submitText').textContent = 'Tambah';
        document.getElementById('matakuliahForm').reset();
        document.getElementById('matakuliahModal').classList.remove('hidden');
        document.getElementById('matakuliahModal').classList.add('flex');
    }

    function editMatakuliah(data) {
        document.getElementById('modalTitle').textContent = 'Edit Mata Kuliah';
        document.getElementById('formAction').value = 'edit_matakuliah';
        document.getElementById('submitText').textContent = 'Update';

        // Fill form with data
        document.getElementById('matakuliahId').value = data.id_matakuliah;
        document.getElementById('kodeMatakuliah').value = data.kode_matakuliah;
        document.getElementById('namaMatakuliah').value = data.nama_matakuliah;
        document.getElementById('sks').value = data.sks;
        document.getElementById('semester').value = data.semester;
        document.getElementById('jurusanSelect').value = data.jurusan;

        // Trigger change event to populate program studi
        const event = new Event('change');
        document.getElementById('jurusanSelect').dispatchEvent(event);

        setTimeout(() => {
            document.getElementById('programStudiSelect').value = data.program_studi;
        }, 100);

        document.getElementById('statusSelect').value = data.status;
        document.getElementById('prasyarat').value = data.prasyarat || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';

        document.getElementById('matakuliahModal').classList.remove('hidden');
        document.getElementById('matakuliahModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('matakuliahModal').classList.add('hidden');
        document.getElementById('matakuliahModal').classList.remove('flex');
    }

    function deleteMatakuliah(id, name) {
        document.getElementById('deleteMatakuliahId').value = id;
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    // Close modals when clicking outside
    document.getElementById('matakuliahModal').addEventListener('click', function(e) {
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
