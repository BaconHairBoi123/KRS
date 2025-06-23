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
    if ($_POST['action'] == 'add_mahasiswa') {
        try {
            $conn->beginTransaction();
            
            // Check if username, email, or NIM already exists
            $check_query = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':username', $_POST['username']);
            $check_stmt->bindParam(':email', $_POST['email']);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Username atau email sudah digunakan");
            }
            
            // Check if NIM already exists
            $nim_query = "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim";
            $nim_stmt = $conn->prepare($nim_query);
            $nim_stmt->bindParam(':nim', $_POST['nim']);
            $nim_stmt->execute();
            
            if ($nim_stmt->fetchColumn() > 0) {
                throw new Exception("NIM sudah terdaftar");
            }
            
            // Insert ke tabel users
            $user_query = "INSERT INTO users (username, password, email, role, status) VALUES (:username, :password, :email, 'mahasiswa', 'aktif')";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bindParam(':username', $_POST['username']);
            $user_stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
            $user_stmt->bindParam(':email', $_POST['email']);
            $user_stmt->execute();
            
            $user_id = $conn->lastInsertId();
            
            // Insert ke tabel mahasiswa
            $mahasiswa_query = "INSERT INTO mahasiswa (user_id, nim, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telepon, program_studi, angkatan, semester_aktif, kelompok_ukt) 
                               VALUES (:user_id, :nim, :nama_lengkap, :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :no_telepon, :program_studi, :angkatan, :semester_aktif, :kelompok_ukt)";
            $mahasiswa_stmt = $conn->prepare($mahasiswa_query);
            $mahasiswa_stmt->bindParam(':user_id', $user_id);
            $mahasiswa_stmt->bindParam(':nim', $_POST['nim']);
            $mahasiswa_stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
            $mahasiswa_stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
            $mahasiswa_stmt->bindParam(':tanggal_lahir', $_POST['tanggal_lahir'] ?: null);
            $mahasiswa_stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
            $mahasiswa_stmt->bindParam(':alamat', $_POST['alamat']);
            $mahasiswa_stmt->bindParam(':no_telepon', $_POST['no_telepon']);
            $mahasiswa_stmt->bindParam(':program_studi', $_POST['program_studi']);
            $mahasiswa_stmt->bindParam(':angkatan', $_POST['angkatan']);
            $mahasiswa_stmt->bindParam(':semester_aktif', $_POST['semester_aktif']);
            $mahasiswa_stmt->bindParam(':kelompok_ukt', $_POST['kelompok_ukt']);
            $mahasiswa_stmt->execute();
            
            $conn->commit();
            $success = "Data mahasiswa berhasil ditambahkan";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal menambahkan mahasiswa: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'edit_mahasiswa') {
        try {
            $conn->beginTransaction();
            
            // Update users table
            $user_query = "UPDATE users SET username = :username, email = :email, status = :status WHERE id = :user_id";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bindParam(':username', $_POST['username']);
            $user_stmt->bindParam(':email', $_POST['email']);
            $user_stmt->bindParam(':status', $_POST['status']);
            $user_stmt->bindParam(':user_id', $_POST['user_id']);
            $user_stmt->execute();
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $pass_query = "UPDATE users SET password = :password WHERE id = :user_id";
                $pass_stmt = $conn->prepare($pass_query);
                $pass_stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                $pass_stmt->bindParam(':user_id', $_POST['user_id']);
                $pass_stmt->execute();
            }
            
            // Update mahasiswa table
            $mahasiswa_query = "UPDATE mahasiswa SET 
                               nim = :nim, nama_lengkap = :nama_lengkap, tempat_lahir = :tempat_lahir, 
                               tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin, 
                               alamat = :alamat, no_telepon = :no_telepon, program_studi = :program_studi, 
                               angkatan = :angkatan, semester_aktif = :semester_aktif, kelompok_ukt = :kelompok_ukt,
                               status_mahasiswa = :status_mahasiswa
                               WHERE id = :mahasiswa_id";
            $mahasiswa_stmt = $conn->prepare($mahasiswa_query);
            $mahasiswa_stmt->bindParam(':nim', $_POST['nim']);
            $mahasiswa_stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
            $mahasiswa_stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
            $mahasiswa_stmt->bindParam(':tanggal_lahir', $_POST['tanggal_lahir'] ?: null);
            $mahasiswa_stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
            $mahasiswa_stmt->bindParam(':alamat', $_POST['alamat']);
            $mahasiswa_stmt->bindParam(':no_telepon', $_POST['no_telepon']);
            $mahasiswa_stmt->bindParam(':program_studi', $_POST['program_studi']);
            $mahasiswa_stmt->bindParam(':angkatan', $_POST['angkatan']);
            $mahasiswa_stmt->bindParam(':semester_aktif', $_POST['semester_aktif']);
            $mahasiswa_stmt->bindParam(':kelompok_ukt', $_POST['kelompok_ukt']);
            $mahasiswa_stmt->bindParam(':status_mahasiswa', $_POST['status_mahasiswa']);
            $mahasiswa_stmt->bindParam(':mahasiswa_id', $_POST['mahasiswa_id']);
            $mahasiswa_stmt->execute();
            
            $conn->commit();
            $success = "Data mahasiswa berhasil diupdate";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal mengupdate mahasiswa: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_mahasiswa') {
        try {
            $query = "DELETE FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_POST['user_id']);
            $stmt->execute();
            
            $success = "Data mahasiswa berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus mahasiswa: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$program_studi = $_GET['program_studi'] ?? '';
$angkatan = $_GET['angkatan'] ?? '';
$status = $_GET['status'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.nama_lengkap LIKE :search OR m.nim LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($program_studi)) {
    $where_conditions[] = "m.program_studi = :program_studi";
    $params[':program_studi'] = $program_studi;
}

if (!empty($angkatan)) {
    $where_conditions[] = "m.angkatan = :angkatan";
    $params[':angkatan'] = $angkatan;
}

if (!empty($status)) {
    $where_conditions[] = "m.status_mahasiswa = :status";
    $params[':status'] = $status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM mahasiswa m JOIN users u ON m.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get mahasiswa data
$query = "SELECT m.*, u.username, u.email, u.status as user_status 
          FROM mahasiswa m 
          JOIN users u ON m.user_id = u.id 
          $where_clause 
          ORDER BY m.nama_lengkap 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$mahasiswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$prodi_query = "SELECT DISTINCT program_studi FROM mahasiswa ORDER BY program_studi";
$prodi_stmt = $conn->prepare($prodi_query);
$prodi_stmt->execute();
$prodi_list = $prodi_stmt->fetchAll(PDO::FETCH_COLUMN);

$angkatan_query = "SELECT DISTINCT angkatan FROM mahasiswa ORDER BY angkatan DESC";
$angkatan_stmt = $conn->prepare($angkatan_query);
$angkatan_stmt->execute();
$angkatan_list = $angkatan_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mahasiswa - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme-toggle.css" rel="stylesheet">
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
        .btn-gradient {
            background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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
                        <h2 class="text-lg font-bold text-gray-800">Admin KRS</h2>
                        <p class="text-xs text-gray-500">Panel Administrator</p>
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
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kelola Data</p>
                    </div>
                    
                    <a href="mahasiswa-admin.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola Mahasiswa</span>
                    </a>

                    <a href="dosen-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                        <span>Kelola Dosen</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</p>
                    </div>

                    <a href="absensi-input.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-check w-5 mr-3"></i>
                        <span>Input Absensi</span>
                    </a>

                    <a href="absensi-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Absensi</span>
                    </a>

                    <a href="ukt-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Kelola UKT</span>
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
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $_SESSION['nama_lengkap']; ?></p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Kelola Data Mahasiswa</h1>
                            <p class="text-gray-600">Manajemen data mahasiswa sistem KRS</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <button onclick="openAddModal()" class="btn-gradient px-6 py-3 text-white rounded-xl">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Mahasiswa
                            </button>
                        </div>
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
                            <input type="text" name="search" placeholder="Cari nama, NIM, email..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="form-input w-full">
                        </div>
                        <div>
                            <select name="program_studi" class="form-input w-full">
                                <option value="">Semua Program Studi</option>
                                <?php foreach ($prodi_list as $prodi): ?>
                                    <option value="<?php echo $prodi; ?>" <?php echo ($program_studi == $prodi) ? 'selected' : ''; ?>>
                                        <?php echo $prodi; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="angkatan" class="form-input w-full">
                                <option value="">Semua Angkatan</option>
                                <?php foreach ($angkatan_list as $thn): ?>
                                    <option value="<?php echo $thn; ?>" <?php echo ($angkatan == $thn) ? 'selected' : ''; ?>>
                                        <?php echo $thn; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="status" class="form-input w-full">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo ($status == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="cuti" <?php echo ($status == 'cuti') ? 'selected' : ''; ?>>Cuti</option>
                                <option value="lulus" <?php echo ($status == 'lulus') ? 'selected' : ''; ?>>Lulus</option>
                                <option value="dropout" <?php echo ($status == 'dropout') ? 'selected' : ''; ?>>Dropout</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-gradient px-4 py-2 text-white rounded-lg flex-1">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="mahasiswa-admin.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Data Mahasiswa (<?php echo $total_records; ?> total)
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Studi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Angkatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($mahasiswa_list as $mhs): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-user text-purple-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $mhs['nama_lengkap']; ?></div>
                                            <div class="text-sm text-gray-500">NIM: <?php echo $mhs['nim']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $mhs['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $mhs['program_studi']; ?></div>
                                    <div class="text-sm text-gray-500">Kelompok UKT: <?php echo $mhs['kelompok_ukt']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $mhs['angkatan']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $mhs['semester_aktif']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($mhs['status_mahasiswa']) {
                                            case 'aktif': echo 'bg-green-100 text-green-800'; break;
                                            case 'cuti': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'lulus': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'dropout': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($mhs['status_mahasiswa']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editMahasiswa(<?php echo htmlspecialchars(json_encode($mhs)); ?>)" 
                                            class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMahasiswa(<?php echo $mhs['user_id']; ?>, '<?php echo $mhs['nama_lengkap']; ?>')" 
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
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> - <?php echo min($page * $limit, $total_records); ?> dari <?php echo $total_records; ?> data
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&program_studi=<?php echo urlencode($program_studi); ?>&angkatan=<?php echo urlencode($angkatan); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&program_studi=<?php echo urlencode($program_studi); ?>&angkatan=<?php echo urlencode($angkatan); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&program_studi=<?php echo urlencode($program_studi); ?>&angkatan=<?php echo urlencode($angkatan); ?>&status=<?php echo urlencode($status); ?>" 
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
    <div id="mahasiswaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Mahasiswa</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="mahasiswaForm">
                <input type="hidden" name="action" id="formAction" value="add_mahasiswa">
                <input type="hidden" name="user_id" id="userId">
                <input type="hidden" name="mahasiswa_id" id="mahasiswaId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Kolom Kiri -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Akun</h4>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" id="username" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" id="email" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Password <span id="passwordNote">(kosongkan jika tidak ingin mengubah)</span></label>
                            <input type="password" name="password" id="password" class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status Akun</label>
                            <select name="status" id="userStatus" class="form-input w-full">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
                        </div>

                        <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mt-6">Informasi Akademik</h4>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIM *</label>
                            <input type="text" name="nim" id="nim" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Program Studi *</label>
                            <select name="program_studi" id="programStudi" required class="form-input w-full">
                                <option value="">Pilih Program Studi</option>
                                <option value="Informatika">Informatika</option>
                                <option value="Sistem Informasi">Sistem Informasi</option>
                                <option value="Teknik Komputer">Teknik Komputer</option>
                                <option value="Manajemen Informatika">Manajemen Informatika</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Angkatan *</label>
                                <select name="angkatan" id="angkatan" required class="form-input w-full">
                                    <option value="">Pilih Angkatan</option>
                                    <?php 
                                    $current_year = date('Y');
                                    for ($year = $current_year; $year >= $current_year - 10; $year--): 
                                    ?>
                                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Semester Aktif</label>
                                <input type="number" name="semester_aktif" id="semesterAktif" min="1" max="14" value="1" class="form-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kelompok UKT</label>
                                <select name="kelompok_ukt" id="kelompokUkt" class="form-input w-full">
                                    <option value="1">Kelompok 1</option>
                                    <option value="2">Kelompok 2</option>
                                    <option value="3">Kelompok 3</option>
                                    <option value="4">Kelompok 4</option>
                                    <option value="5">Kelompok 5</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status Mahasiswa</label>
                                <select name="status_mahasiswa" id="statusMahasiswa" class="form-input w-full">
                                    <option value="aktif">Aktif</option>
                                    <option value="cuti">Cuti</option>
                                    <option value="lulus">Lulus</option>
                                    <option value="dropout">Dropout</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Informasi Pribadi</h4>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" id="namaLengkap" required class="form-input w-full">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="tempatLahir" class="form-input w-full">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="tanggalLahir" class="form-input w-full">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="jenisKelamin" class="form-input w-full">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon</label>
                            <input type="tel" name="no_telepon" id="noTelepon" class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat</label>
                            <textarea name="alamat" id="alamat" rows="4" class="form-input w-full"></textarea>
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
                            class="flex-1 py-3 px-4 btn-gradient text-white rounded-lg">
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
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Mahasiswa</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus mahasiswa <strong id="deleteName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_mahasiswa">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
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

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Mahasiswa';
            document.getElementById('formAction').value = 'add_mahasiswa';
            document.getElementById('submitText').textContent = 'Tambah';
            document.getElementById('passwordNote').textContent = '*';
            document.getElementById('password').required = true;
            document.getElementById('mahasiswaForm').reset();
            document.getElementById('mahasiswaModal').classList.remove('hidden');
            document.getElementById('mahasiswaModal').classList.add('flex');
        }
        
        function editMahasiswa(data) {
            document.getElementById('modalTitle').textContent = 'Edit Mahasiswa';
            document.getElementById('formAction').value = 'edit_mahasiswa';
            document.getElementById('submitText').textContent = 'Update';
            document.getElementById('passwordNote').textContent = '(kosongkan jika tidak ingin mengubah)';
            document.getElementById('password').required = false;
            
            // Fill form with data
            document.getElementById('userId').value = data.user_id;
            document.getElementById('mahasiswaId').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('email').value = data.email;
            document.getElementById('userStatus').value = data.user_status;
            document.getElementById('nim').value = data.nim;
            document.getElementById('namaLengkap').value = data.nama_lengkap;
            document.getElementById('tempatLahir').value = data.tempat_lahir || '';
            document.getElementById('tanggalLahir').value = data.tanggal_lahir || '';
            document.getElementById('jenisKelamin').value = data.jenis_kelamin || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('noTelepon').value = data.no_telepon || '';
            document.getElementById('programStudi').value = data.program_studi;
            document.getElementById('angkatan').value = data.angkatan;
            document.getElementById('semesterAktif').value = data.semester_aktif;
            document.getElementById('kelompokUkt').value = data.kelompok_ukt;
            document.getElementById('statusMahasiswa').value = data.status_mahasiswa;
            
            document.getElementById('mahasiswaModal').classList.remove('hidden');
            document.getElementById('mahasiswaModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('mahasiswaModal').classList.add('hidden');
            document.getElementById('mahasiswaModal').classList.remove('flex');
        }
        
        function deleteMahasiswa(userId, name) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }
        
        // Close modals when clicking outside
        document.getElementById('mahasiswaModal').addEventListener('click', function(e) {
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
