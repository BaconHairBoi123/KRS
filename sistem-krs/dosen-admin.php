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
            $conn->beginTransaction();
            
            // Check if username, email, or NIP already exists
            $check_query = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':username', $_POST['username']);
            $check_stmt->bindParam(':email', $_POST['email']);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Username atau email sudah digunakan");
            }
            
            // Check if NIP already exists
            $nip_query = "SELECT COUNT(*) FROM dosen WHERE nip = :nip";
            $nip_stmt = $conn->prepare($nip_query);
            $nip_stmt->bindParam(':nip', $_POST['nip']);
            $nip_stmt->execute();
            
            if ($nip_stmt->fetchColumn() > 0) {
                throw new Exception("NIP sudah terdaftar");
            }
            
            // Insert ke tabel users
            $user_query = "INSERT INTO users (username, password, email, role, status) VALUES (:username, :password, :email, 'dosen', 'aktif')";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bindParam(':username', $_POST['username']);
            $user_stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
            $user_stmt->bindParam(':email', $_POST['email']);
            $user_stmt->execute();
            
            $user_id = $conn->lastInsertId();
            
            // Insert ke tabel dosen
            $dosen_query = "INSERT INTO dosen (user_id, nip, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telepon, fakultas, program_studi, jabatan, pendidikan_terakhir) 
                           VALUES (:user_id, :nip, :nama_lengkap, :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :no_telepon, :fakultas, :program_studi, :jabatan, :pendidikan_terakhir)";
            $dosen_stmt = $conn->prepare($dosen_query);
            $dosen_stmt->bindParam(':user_id', $user_id);
            $dosen_stmt->bindParam(':nip', $_POST['nip']);
            $dosen_stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
            $dosen_stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
            $dosen_stmt->bindParam(':tanggal_lahir', $_POST['tanggal_lahir'] ?: null);
            $dosen_stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
            $dosen_stmt->bindParam(':alamat', $_POST['alamat']);
            $dosen_stmt->bindParam(':no_telepon', $_POST['no_telepon']);
            $dosen_stmt->bindParam(':fakultas', $_POST['fakultas']);
            $dosen_stmt->bindParam(':program_studi', $_POST['program_studi']);
            $dosen_stmt->bindParam(':jabatan', $_POST['jabatan']);
            $dosen_stmt->bindParam(':pendidikan_terakhir', $_POST['pendidikan_terakhir']);
            $dosen_stmt->execute();
            
            $conn->commit();
            $success = "Data dosen berhasil ditambahkan";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal menambahkan dosen: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'edit_dosen') {
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
            
            // Update dosen table
            $dosen_query = "UPDATE dosen SET 
                           nip = :nip, nama_lengkap = :nama_lengkap, tempat_lahir = :tempat_lahir, 
                           tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin, 
                           alamat = :alamat, no_telepon = :no_telepon, fakultas = :fakultas, 
                           program_studi = :program_studi, jabatan = :jabatan, pendidikan_terakhir = :pendidikan_terakhir
                           WHERE id = :dosen_id";
            $dosen_stmt = $conn->prepare($dosen_query);
            $dosen_stmt->bindParam(':nip', $_POST['nip']);
            $dosen_stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
            $dosen_stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
            $dosen_stmt->bindParam(':tanggal_lahir', $_POST['tanggal_lahir'] ?: null);
            $dosen_stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
            $dosen_stmt->bindParam(':alamat', $_POST['alamat']);
            $dosen_stmt->bindParam(':no_telepon', $_POST['no_telepon']);
            $dosen_stmt->bindParam(':fakultas', $_POST['fakultas']);
            $dosen_stmt->bindParam(':program_studi', $_POST['program_studi']);
            $dosen_stmt->bindParam(':jabatan', $_POST['jabatan']);
            $dosen_stmt->bindParam(':pendidikan_terakhir', $_POST['pendidikan_terakhir']);
            $dosen_stmt->bindParam(':dosen_id', $_POST['dosen_id']);
            $dosen_stmt->execute();
            
            $conn->commit();
            $success = "Data dosen berhasil diupdate";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal mengupdate dosen: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'delete_dosen') {
        try {
            $query = "DELETE FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_POST['user_id']);
            $stmt->execute();
            
            $success = "Data dosen berhasil dihapus";
        } catch (Exception $e) {
            $error = "Gagal menghapus dosen: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$fakultas = $_GET['fakultas'] ?? '';
$program_studi = $_GET['program_studi'] ?? '';
$jabatan = $_GET['jabatan'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.nama_lengkap LIKE :search OR d.nip LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($fakultas)) {
    $where_conditions[] = "d.fakultas = :fakultas";
    $params[':fakultas'] = $fakultas;
}

if (!empty($program_studi)) {
    $where_conditions[] = "d.program_studi = :program_studi";
    $params[':program_studi'] = $program_studi;
}

if (!empty($jabatan)) {
    $where_conditions[] = "d.jabatan = :jabatan";
    $params[':jabatan'] = $jabatan;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM dosen d JOIN users u ON d.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get dosen data
$query = "SELECT d.*, u.username, u.email, u.status as user_status 
          FROM dosen d 
          JOIN users u ON d.user_id = u.id 
          $where_clause 
          ORDER BY d.nama_lengkap 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dosen_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$fakultas_query = "SELECT DISTINCT fakultas FROM dosen ORDER BY fakultas";
$fakultas_stmt = $conn->prepare($fakultas_query);
$fakultas_stmt->execute();
$fakultas_list = $fakultas_stmt->fetchAll(PDO::FETCH_COLUMN);

$prodi_query = "SELECT DISTINCT program_studi FROM dosen ORDER BY program_studi";
$prodi_stmt = $conn->prepare($prodi_query);
$prodi_stmt->execute();
$prodi_list = $prodi_stmt->fetchAll(PDO::FETCH_COLUMN);

$jabatan_query = "SELECT DISTINCT jabatan FROM dosen ORDER BY jabatan";
$jabatan_stmt = $conn->prepare($jabatan_query);
$jabatan_stmt->execute();
$jabatan_list = $jabatan_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen - <?php echo APP_NAME; ?></title>
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
                    
                    <a href="mahasiswa-admin.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola Mahasiswa</span>
                    </a>

                    <a href="dosen-admin.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Kelola Data Dosen</h1>
                            <p class="text-gray-600">Manajemen data dosen sistem KRS</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <button onclick="openAddModal()" class="btn-gradient px-6 py-3 text-white rounded-xl">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Dosen
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
                            <input type="text" name="search" placeholder="Cari nama, NIP, email..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="form-input w-full">
                        </div>
                        <div>
                            <select name="fakultas" class="form-input w-full">
                                <option value="">Semua Fakultas</option>
                                <?php foreach ($fakultas_list as $fak): ?>
                                    <option value="<?php echo $fak; ?>" <?php echo ($fakultas == $fak) ? 'selected' : ''; ?>>
                                        <?php echo $fak; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                            <select name="jabatan" class="form-input w-full">
                                <option value="">Semua Jabatan</option>
                                <?php foreach ($jabatan_list as $jab): ?>
                                    <option value="<?php echo $jab; ?>" <?php echo ($jabatan == $jab) ? 'selected' : ''; ?>>
                                        <?php echo $jab; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-gradient px-4 py-2 text-white rounded-lg flex-1">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="dosen-admin.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
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
                            Data Dosen (<?php echo $total_records; ?> total)
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fakultas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Studi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendidikan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dosen_list as $dsn): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-chalkboard-teacher text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $dsn['nama_lengkap']; ?></div>
                                            <div class="text-sm text-gray-500">NIP: <?php echo $dsn['nip']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $dsn['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $dsn['fakultas']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $dsn['program_studi']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $dsn['jabatan']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $dsn['pendidikan_terakhir']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editDosen(<?php echo htmlspecialchars(json_encode($dsn)); ?>)" 
                                            class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteDosen(<?php echo $dsn['user_id']; ?>, '<?php echo $dsn['nama_lengkap']; ?>')" 
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&fakultas=<?php echo urlencode($fakultas); ?>&program_studi=<?php echo urlencode($program_studi); ?>&jabatan=<?php echo urlencode($jabatan); ?>" 
                                   class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&fakultas=<?php echo urlencode($fakultas); ?>&program_studi=<?php echo urlencode($program_studi); ?>&jabatan=<?php echo urlencode($jabatan); ?>" 
                                   class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&fakultas=<?php echo urlencode($fakultas); ?>&program_studi=<?php echo urlencode($program_studi); ?>&jabatan=<?php echo urlencode($jabatan); ?>" 
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
        <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Dosen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="dosenForm">
                <input type="hidden" name="action" id="formAction" value="add_dosen">
                <input type="hidden" name="user_id" id="userId">
                <input type="hidden" name="dosen_id" id="dosenId">
                
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIP *</label>
                            <input type="text" name="nip" id="nip" required class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Fakultas *</label>
                            <select name="fakultas" id="fakultasSelect" required class="form-input w-full">
                                <option value="">Pilih Fakultas</option>
                                <option value="Teknik">Fakultas Teknik</option>
                                <option value="Ekonomi">Fakultas Ekonomi</option>
                                <option value="Hukum">Fakultas Hukum</option>
                                <option value="Kedokteran">Fakultas Kedokteran</option>
                                <option value="MIPA">Fakultas MIPA</option>
                            </select>
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

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jabatan</label>
                            <select name="jabatan" id="jabatan" class="form-input w-full">
                                <option value="">Pilih Jabatan</option>
                                <option value="Asisten Ahli">Asisten Ahli</option>
                                <option value="Lektor">Lektor</option>
                                <option value="Lektor Kepala">Lektor Kepala</option>
                                <option value="Guru Besar">Guru Besar</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pendidikan Terakhir</label>
                            <select name="pendidikan_terakhir" id="pendidikanTerakhir" class="form-input w-full">
                                <option value="">Pilih Pendidikan</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
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
                <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Dosen</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus dosen <strong id="deleteName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_dosen">
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
            document.getElementById('modalTitle').textContent = 'Tambah Dosen';
            document.getElementById('formAction').value = 'add_dosen';
            document.getElementById('submitText').textContent = 'Tambah';
            document.getElementById('passwordNote').textContent = '*';
            document.getElementById('password').required = true;
            document.getElementById('dosenForm').reset();
            document.getElementById('dosenModal').classList.remove('hidden');
            document.getElementById('dosenModal').classList.add('flex');
        }
        
        function editDosen(data) {
            document.getElementById('modalTitle').textContent = 'Edit Dosen';
            document.getElementById('formAction').value = 'edit_dosen';
            document.getElementById('submitText').textContent = 'Update';
            document.getElementById('passwordNote').textContent = '(kosongkan jika tidak ingin mengubah)';
            document.getElementById('password').required = false;
            
            // Fill form with data
            document.getElementById('userId').value = data.user_id;
            document.getElementById('dosenId').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('email').value = data.email;
            document.getElementById('userStatus').value = data.user_status;
            document.getElementById('nip').value = data.nip;
            document.getElementById('namaLengkap').value = data.nama_lengkap;
            document.getElementById('tempatLahir').value = data.tempat_lahir || '';
            document.getElementById('tanggalLahir').value = data.tanggal_lahir || '';
            document.getElementById('jenisKelamin').value = data.jenis_kelamin || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('noTelepon').value = data.no_telepon || '';
            document.getElementById('fakultasSelect').value = data.fakultas;
            document.getElementById('programStudi').value = data.program_studi;
            document.getElementById('jabatan').value = data.jabatan;
            document.getElementById('pendidikanTerakhir').value = data.pendidikan_terakhir;
            
            document.getElementById('dosenModal').classList.remove('hidden');
            document.getElementById('dosenModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('dosenModal').classList.add('hidden');
            document.getElementById('dosenModal').classList.remove('flex');
        }
        
        function deleteDosen(userId, name) {
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
        document.getElementById('dosenModal').addEventListener('click', function(e) {
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
