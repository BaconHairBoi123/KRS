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
    if ($_POST['action'] == 'add_dosen') {
        try {
            // Check if NIDN already exists
            $check_query = "SELECT nidn FROM dosen WHERE nidn = :nidn";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':nidn', $_POST['nidn']);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                $error = "NIDN sudah terdaftar";
            } else {
                $query = "INSERT INTO dosen (nidn, nama_dosen, email, password, nomor_telepon, gelar, jurusan, program_studi, bidang_keahlian, status) 
                         VALUES (:nidn, :nama_dosen, :email, :password, :nomor_telepon, :gelar, :jurusan, :program_studi, :bidang_keahlian, :status)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nidn', $_POST['nidn']);
                $stmt->bindParam(':nama_dosen', $_POST['nama_dosen']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                $stmt->bindParam(':nomor_telepon', $_POST['nomor_telepon']);
                $stmt->bindParam(':gelar', $_POST['gelar']);
                $stmt->bindParam(':jurusan', $_POST['jurusan']);
                $stmt->bindParam(':program_studi', $_POST['program_studi']);
                $stmt->bindParam(':bidang_keahlian', $_POST['bidang_keahlian']);
                $stmt->bindParam(':status', $_POST['status']);
                $stmt->execute();
                
                $success = "Dosen berhasil ditambahkan";
            }
        } catch (Exception $e) {
            $error = "Gagal menambahkan dosen: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'edit_dosen') {
        try {
            $query = "UPDATE dosen SET 
                     nama_dosen = :nama_dosen, email = :email, nomor_telepon = :nomor_telepon,
                     gelar = :gelar, jurusan = :jurusan, program_studi = :program_studi,
                     bidang_keahlian = :bidang_keahlian, status = :status
                     WHERE id_dosen = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama_dosen', $_POST['nama_dosen']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':nomor_telepon', $_POST['nomor_telepon']);
            $stmt->bindParam(':gelar', $_POST['gelar']);
            $stmt->bindParam(':jurusan', $_POST['jurusan']);
            $stmt->bindParam(':program_studi', $_POST['program_studi']);
            $stmt->bindParam(':bidang_keahlian', $_POST['bidang_keahlian']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->bindParam(':id', $_POST['dosen_id']);
            $stmt->execute();
            
            $success = "Data dosen berhasil diupdate";
        } catch (Exception $e) {
            $error = "Gagal mengupdate dosen: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_dosen') {
        try {
            $query = "DELETE FROM dosen WHERE id_dosen = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_POST['dosen_id']);
            $stmt->execute();
            
            $success = "Dosen berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus dosen: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'assign_matakuliah') {
        try {
            // Delete existing assignments for this dosen
            $delete_query = "DELETE FROM dosen_matakuliah WHERE id_dosen = :dosen_id";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindParam(':dosen_id', $_POST['dosen_id']);
            $delete_stmt->execute();
            
            // Insert new assignments
            if (!empty($_POST['matakuliah_ids'])) {
                $insert_query = "INSERT INTO dosen_matakuliah (id_dosen, id_matakuliah) VALUES (:dosen_id, :matakuliah_id)";
                $insert_stmt = $conn->prepare($insert_query);
                
                foreach ($_POST['matakuliah_ids'] as $matakuliah_id) {
                    $insert_stmt->bindParam(':dosen_id', $_POST['dosen_id']);
                    $insert_stmt->bindParam(':matakuliah_id', $matakuliah_id);
                    $insert_stmt->execute();
                }
            }
            
            $success = "Mata kuliah berhasil diassign ke dosen";
        } catch (Exception $e) {
            $error = "Gagal assign mata kuliah: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$jurusan = $_GET['jurusan'] ?? '';
$program_studi = $_GET['program_studi'] ?? '';
$status = $_GET['status'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.nidn LIKE :search OR d.nama_dosen LIKE :search OR d.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($jurusan)) {
    $where_conditions[] = "d.jurusan = :jurusan";
    $params[':jurusan'] = $jurusan;
}

if (!empty($program_studi)) {
    $where_conditions[] = "d.program_studi = :program_studi";
    $params[':program_studi'] = $program_studi;
}

if (!empty($status)) {
    $where_conditions[] = "d.status = :status";
    $params[':status'] = $status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM dosen d $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get dosen data with mahasiswa count
$query = "SELECT d.*, 
                 COUNT(m.id_mahasiswa) as jumlah_mahasiswa_wali,
                 GROUP_CONCAT(mk.nama_matakuliah SEPARATOR ', ') as mata_kuliah_diampu
          FROM dosen d 
          LEFT JOIN mahasiswa m ON d.id_dosen = m.dosen_wali
          LEFT JOIN dosen_matakuliah dm ON d.id_dosen = dm.id_dosen
          LEFT JOIN mata_kuliah mk ON dm.id_matakuliah = mk.id_matakuliah
          $where_clause 
          GROUP BY d.id_dosen
          ORDER BY d.nama_dosen 
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dosen_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get mata kuliah list for assignment
$mk_query = "SELECT id_matakuliah, nama_matakuliah, kode_matakuliah, program_studi FROM mata_kuliah WHERE status = 'aktif' ORDER BY program_studi, nama_matakuliah";
$mk_stmt = $conn->prepare($mk_query);
$mk_stmt->execute();
$matakuliah_list = $mk_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$jurusan_query = "SELECT DISTINCT jurusan FROM dosen ORDER BY jurusan";
$jurusan_stmt = $conn->prepare($jurusan_query);
$jurusan_stmt->execute();
$jurusan_list = $jurusan_stmt->fetchAll(PDO::FETCH_COLUMN);

$prodi_query = "SELECT DISTINCT program_studi FROM dosen ORDER BY program_studi";
$prodi_stmt = $conn->prepare($prodi_query);
$prodi_stmt->execute();
$prodi_list = $prodi_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Dosen - <?php echo APP_NAME; ?></title>
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

                    <a href="admin-dosen.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Manajemen Data Dosen</h1>
                            <p class="text-gray-600">Kelola data dosen dan pengampu mata kuliah</p>
                        </div>
                        <button onclick="openAddModal()"
                            class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Dosen
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
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <input type="text" name="search" placeholder="Cari NIDN, nama, email..."
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
                            <a href="admin-dosen.php"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Dosen Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Daftar Dosen (<?php echo $total_records; ?> total)
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
                                    Dosen</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Studi</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mata Kuliah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mahasiswa Wali</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dosen_list as $dosen): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-chalkboard-teacher text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $dosen['nama_dosen']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $dosen['nidn']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $dosen['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $dosen['program_studi']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $dosen['jurusan']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $dosen['gelar']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        <?php echo $dosen['mata_kuliah_diampu'] ?: 'Belum ada'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        <?php echo $dosen['jumlah_mahasiswa_wali']; ?> mahasiswa
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $dosen['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($dosen['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editDosen(<?php echo htmlspecialchars(json_encode($dosen)); ?>)"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        onclick="assignMatakuliah(<?php echo $dosen['id_dosen']; ?>, '<?php echo $dosen['nama_dosen']; ?>')"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-book"></i>
                                    </button>
                                    <button
                                        onclick="deleteDosen(<?php echo $dosen['id_dosen']; ?>, '<?php echo $dosen['nama_dosen']; ?>')"
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&status=<?php echo urlencode($status); ?>"
                                class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&jurusan=<?php echo urlencode($jurusan); ?>&program_studi=<?php echo urlencode($program_studi); ?>&status=<?php echo urlencode($status); ?>"
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
    <div id="dosenModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-3xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Dosen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" id="dosenForm">
                <input type="hidden" name="action" id="formAction" value="add_dosen">
                <input type="hidden" name="dosen_id" id="dosenId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIDN *</label>
                            <input type="text" name="nidn" id="nidn" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama_dosen" id="nama_dosen" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" id="email" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" id="password" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gelar</label>
                            <input type="text" name="gelar" id="gelar" class="form-input w-full"
                                placeholder="S.Kom., M.Kom.">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon</label>
                            <input type="tel" name="nomor_telepon" id="nomor_telepon" class="form-input w-full">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jurusan *</label>
                            <select name="jurusan" id="jurusanSelect" required class="form-input w-full">
                                <option value="">Pilih Jurusan</option>
                                <option value="Teknik Informatika">Teknik Informatika</option>
                                <option value="Teknik Sipil">Teknik Sipil</option>
                                <option value="Ekonomi">Ekonomi</option>
                                <option value="Hukum">Hukum</option>
                                <option value="Kedokteran">Kedokteran</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Program Studi *</label>
                            <select name="program_studi" id="programStudiSelect" required class="form-input w-full">
                                <option value="">Pilih Program Studi</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Bidang Keahlian</label>
                            <input type="text" name="bidang_keahlian" id="bidang_keahlian" class="form-input w-full"
                                placeholder="Pemrograman Web, Database">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="status" class="form-input w-full">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
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

    <!-- Assign Mata Kuliah Modal -->
    <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Assign Mata Kuliah</h3>
                <button onclick="closeAssignModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" id="assignForm">
                <input type="hidden" name="action" value="assign_matakuliah">
                <input type="hidden" name="dosen_id" id="assignDosenId">

                <div class="mb-4">
                    <p class="text-gray-600">Assign mata kuliah untuk: <strong id="assignDosenName"></strong></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                    <?php foreach ($matakuliah_list as $mk): ?>
                    <div class="border rounded-lg p-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="matakuliah_ids[]" value="<?php echo $mk['id_matakuliah']; ?>"
                                class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div>
                                <div class="font-medium text-gray-900"><?php echo $mk['nama_matakuliah']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $mk['kode_matakuliah']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $mk['program_studi']; ?></div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-3 mt-6 pt-6 border-t">
                    <button type="button" onclick="closeAssignModal()"
                        class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-book mr-2"></i>
                        Assign Mata Kuliah
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
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Dosen</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus dosen <strong id="deleteName"></strong>?
                    Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_dosen">
                    <input type="hidden" name="dosen_id" id="deleteDosenId">

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
        'Teknik Informatika': ['Teknik Informatika'],
        'Teknik Sipil': ['Teknik Sipil'],
        'Ekonomi': ['Ekonomi'],
        'Hukum': ['Hukum'],
        'Kedokteran': ['Kedokteran']
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
        document.getElementById('modalTitle').textContent = 'Tambah Dosen';
        document.getElementById('formAction').value = 'add_dosen';
        document.getElementById('submitText').textContent = 'Tambah';
        document.getElementById('dosenForm').reset();
        document.getElementById('password').required = true;
        document.getElementById('dosenModal').classList.remove('hidden');
        document.getElementById('dosenModal').classList.add('flex');
    }

    function editDosen(data) {
        document.getElementById('modalTitle').textContent = 'Edit Dosen';
        document.getElementById('formAction').value = 'edit_dosen';
        document.getElementById('submitText').textContent = 'Update';

        // Fill form with data
        document.getElementById('dosenId').value = data.id_dosen;
        document.getElementById('nidn').value = data.nidn;
        document.getElementById('nidn').readOnly = true; // NIDN shouldn't be changed
        document.getElementById('nama_dosen').value = data.nama_dosen;
        document.getElementById('email').value = data.email;
        document.getElementById('password').required = false;
        document.getElementById('gelar').value = data.gelar;
        document.getElementById('nomor_telepon').value = data.nomor_telepon;
        document.getElementById('jurusanSelect').value = data.jurusan;

        // Trigger change event to populate program studi
        const event = new Event('change');
        document.getElementById('jurusanSelect').dispatchEvent(event);

        setTimeout(() => {
            document.getElementById('programStudiSelect').value = data.program_studi;
        }, 100);

        document.getElementById('bidang_keahlian').value = data.bidang_keahlian;
        document.getElementById('status').value = data.status;

        document.getElementById('dosenModal').classList.remove('hidden');
        document.getElementById('dosenModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('dosenModal').classList.add('hidden');
        document.getElementById('dosenModal').classList.remove('flex');
        document.getElementById('nidn').readOnly = false;
    }

    function assignMatakuliah(dosenId, dosenName) {
        document.getElementById('assignDosenId').value = dosenId;
        document.getElementById('assignDosenName').textContent = dosenName;

        // Load current assignments
        fetch(`get-dosen-matakuliah.php?dosen_id=${dosenId}`)
            .then(response => response.json())
            .then(data => {
                // Uncheck all checkboxes first
                document.querySelectorAll('input[name="matakuliah_ids[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });

                // Check assigned mata kuliah
                data.forEach(mkId => {
                    const checkbox = document.querySelector(`input[value="${mkId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            })
            .catch(error => console.error('Error:', error));

        document.getElementById('assignModal').classList.remove('hidden');
        document.getElementById('assignModal').classList.add('flex');
    }

    function closeAssignModal() {
        document.getElementById('assignModal').classList.add('hidden');
        document.getElementById('assignModal').classList.remove('flex');
    }

    function deleteDosen(id, name) {
        document.getElementById('deleteDosenId').value = id;
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    // Close modals when clicking outside
    document.getElementById('dosenModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('assignModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAssignModal();
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