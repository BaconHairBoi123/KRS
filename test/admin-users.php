<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

// Handle actions
if ($_POST) {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        switch ($_POST['action']) {
            case 'toggle_status':
                $userId = $_POST['user_id'];
                $userType = $_POST['user_type'];
                $newStatus = $_POST['new_status'];
                
                $table = ($userType == 'mahasiswa') ? 'mahasiswa' : 'dosen';
                $idField = ($userType == 'mahasiswa') ? 'id_mahasiswa' : 'id_dosen';
                
                $query = "UPDATE {$table} SET status = :status WHERE {$idField} = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':status', $newStatus);
                $stmt->bindValue(':id', $userId);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Status berhasil diubah';
                } else {
                    $response['message'] = 'Gagal mengubah status';
                }
                break;
                
            case 'delete_user':
                $userId = $_POST['user_id'];
                $userType = $_POST['user_type'];
                
                $table = ($userType == 'mahasiswa') ? 'mahasiswa' : 'dosen';
                $idField = ($userType == 'mahasiswa') ? 'id_mahasiswa' : 'id_dosen';
                
                // Check if user has related data
                if ($userType == 'mahasiswa') {
                    $checkQuery = "SELECT COUNT(*) FROM krs WHERE id_mahasiswa = :id";
                } else {
                    $checkQuery = "SELECT COUNT(*) FROM kelas WHERE id_dosen = :id";
                }
                
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bindValue(':id', $userId);
                $checkStmt->execute();
                $relatedCount = $checkStmt->fetchColumn();
                
                if ($relatedCount > 0) {
                    $response['message'] = 'Tidak dapat menghapus pengguna yang memiliki data terkait';
                } else {
                    $query = "DELETE FROM {$table} WHERE {$idField} = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(':id', $userId);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Pengguna berhasil dihapus';
                    } else {
                        $response['message'] = 'Gagal menghapus pengguna';
                    }
                }
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];

if ($filter_type != 'all') {
    // This will be handled in UNION query
}

if ($filter_status != 'all') {
    $whereConditions[] = "status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($search)) {
    $whereConditions[] = "(nama LIKE :search OR nomor_induk LIKE :search OR email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get users with pagination
if ($filter_type == 'mahasiswa') {
    $query = "SELECT 
        id_mahasiswa as id, 
        nim as nomor_induk, 
        nama, 
        email, 
        jurusan,
        program_studi,
        status,
        'mahasiswa' as user_type,
        foto
    FROM mahasiswa 
    {$whereClause}
    ORDER BY nama ASC 
    LIMIT :limit OFFSET :offset";
} elseif ($filter_type == 'dosen') {
    $query = "SELECT 
        id_dosen as id, 
        nidn as nomor_induk, 
        nama_dosen as nama, 
        email, 
        jurusan,
        program_studi,
        status,
        'dosen' as user_type,
        foto
    FROM dosen 
    {$whereClause}
    ORDER BY nama ASC 
    LIMIT :limit OFFSET :offset";
} else {
    // Get both mahasiswa and dosen
    $query = "
    (SELECT 
        id_mahasiswa as id, 
        nim as nomor_induk, 
        nama, 
        email, 
        jurusan,
        program_studi,
        status,
        'mahasiswa' as user_type,
        foto
    FROM mahasiswa 
    " . ($whereClause ? str_replace('nama', 'nama', $whereClause) : '') . ")
    UNION ALL
    (SELECT 
        id_dosen as id, 
        nidn as nomor_induk, 
        nama_dosen as nama, 
        email, 
        jurusan,
        program_studi,
        status,
        'dosen' as user_type,
        foto
    FROM dosen 
    " . ($whereClause ? str_replace('nama', 'nama_dosen', $whereClause) : '') . ")
    ORDER BY nama ASC 
    LIMIT :limit OFFSET :offset";
}

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
if ($filter_type == 'mahasiswa') {
    $countQuery = "SELECT COUNT(*) FROM mahasiswa {$whereClause}";
} elseif ($filter_type == 'dosen') {
    $countQuery = "SELECT COUNT(*) FROM dosen {$whereClause}";
} else {
    $countQuery = "SELECT 
        (SELECT COUNT(*) FROM mahasiswa " . ($whereClause ? str_replace('nama', 'nama', $whereClause) : '') . ") +
        (SELECT COUNT(*) FROM dosen " . ($whereClause ? str_replace('nama', 'nama_dosen', $whereClause) : '') . ") as total";
}

$countStmt = $conn->prepare($countQuery);
foreach ($params as $key => $value) {
    if ($key != ':limit' && $key != ':offset') {
        $countStmt->bindValue($key, $value);
    }
}
$countStmt->execute();
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get statistics
$mahasiswaCount = $conn->query("SELECT COUNT(*) FROM mahasiswa WHERE status = 'aktif'")->fetchColumn();
$dosenCount = $conn->query("SELECT COUNT(*) FROM dosen WHERE status = 'aktif'")->fetchColumn();
$mahasiswaInactive = $conn->query("SELECT COUNT(*) FROM mahasiswa WHERE status = 'nonaktif'")->fetchColumn();
$dosenInactive = $conn->query("SELECT COUNT(*) FROM dosen WHERE status = 'nonaktif'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - <?php echo APP_NAME; ?></title>
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
        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%); }
        .bg-gradient-info { background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%); }
        .bg-gradient-warning { background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%); }
        .user-info-box {
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 p-4">
            <div class="sidebar-soft h-full p-4 relative">
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
                <nav class="space-y-2" style="padding-bottom: 120px;">
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
                    
                    <a href="admin-users.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-users-cog w-5 mr-3"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                    
                    <a href="admin-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="admin-matakuliah.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Mata Kuliah</span>
                    </a>

                    <a href="admin-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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

                    <a href="admin-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan</span>
                    </a>

                    <a href="admin-settings.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-cog w-5 mr-3"></i>
                        <span>Pengaturan</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3 user-info-box">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                            <a href="logout.php" class="text-red-500 hover:text-red-700" title="Logout">
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
                            <h1 class="text-2xl font-bold text-gray-800">Kelola Pengguna</h1>
                            <p class="text-gray-600">Manajemen pengguna sistem (Mahasiswa & Dosen)</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>
                                Tambah Mahasiswa
                            </a>
                            <a href="register-dosen.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-chalkboard-teacher mr-2"></i>
                                Tambah Dosen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Mahasiswa Aktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mahasiswa Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $mahasiswaCount; ?></h3>
                            <p class="text-xs text-gray-500">Pengguna</p>
                        </div>
                        <div class="icon-shape bg-gradient-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <!-- Dosen Aktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Dosen Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $dosenCount; ?></h3>
                            <p class="text-xs text-gray-500">Pengguna</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>

                <!-- Mahasiswa Nonaktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mahasiswa Nonaktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $mahasiswaInactive; ?></h3>
                            <p class="text-xs text-gray-500">Pengguna</p>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>
                </div>

                <!-- Dosen Nonaktif -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Dosen Nonaktif</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $dosenInactive; ?></h3>
                            <p class="text-xs text-gray-500">Pengguna</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="p-6">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-64">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pencarian</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Cari nama, NIM/NIDN, atau email..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Pengguna</label>
                            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="mahasiswa" <?php echo $filter_type == 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                <option value="dosen" <?php echo $filter_type == 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="aktif" <?php echo $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="admin-users.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Pengguna</h3>
                        <p class="text-sm text-gray-600">Total: <?php echo $totalUsers; ?> pengguna</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM/NIDN</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurusan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-users text-4xl mb-2"></i>
                                        <p>Tidak ada pengguna ditemukan</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                            <?php if (!empty($user['foto']) && file_exists('uploads/photos/' . $user['foto'])): ?>
                                                <img src="uploads/photos/<?php echo htmlspecialchars($user['foto']); ?>" 
                                                     alt="Foto <?php echo htmlspecialchars($user['nama']); ?>" 
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-gray-400"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['nomor_induk']); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['jurusan']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['program_studi']); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $user['user_type'] == 'mahasiswa' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                            <i class="fas <?php echo $user['user_type'] == 'mahasiswa' ? 'fa-user' : 'fa-chalkboard-teacher'; ?> mr-1"></i>
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $user['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <i class="fas <?php echo $user['status'] == 'aktif' ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center space-x-2">
                                            <!-- Toggle Status -->
                                            <button onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>', '<?php echo $user['status'] == 'aktif' ? 'nonaktif' : 'aktif'; ?>')"
                                                    class="text-sm px-2 py-1 rounded <?php echo $user['status'] == 'aktif' ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?> transition-colors"
                                                    title="<?php echo $user['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                <i class="fas <?php echo $user['status'] == 'aktif' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                            </button>
                                            
                                            <!-- Delete -->
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>', '<?php echo htmlspecialchars($user['nama']); ?>')"
                                                    class="text-sm px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200 transition-colors"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-6">
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $totalUsers); ?> dari <?php echo $totalUsers; ?> pengguna
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(userId, userType, newStatus) {
            if (confirm(`Apakah Anda yakin ingin ${newStatus === 'aktif' ? 'mengaktifkan' : 'menonaktifkan'} pengguna ini?`)) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('user_id', userId);
                formData.append('user_type', userType);
                formData.append('new_status', newStatus);
                
                fetch('admin-users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengubah status');
                });
            }
        }
        
        function deleteUser(userId, userType, userName) {
            if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${userName}"?\n\nTindakan ini tidak dapat dibatalkan!`)) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                formData.append('user_type', userType);
                
                fetch('admin-users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus pengguna');
                });
            }
        }
    </script>
</body>
</html>
