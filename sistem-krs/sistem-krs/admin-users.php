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
    if ($_POST['action'] == 'reset_password') {
        try {
            $user_id = $_POST['user_id'];
            $user_type = $_POST['user_type'];
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            if ($user_type == 'mahasiswa') {
                $query = "UPDATE mahasiswa SET password = :password WHERE id_mahasiswa = :user_id";
            } elseif ($user_type == 'dosen') {
                $query = "UPDATE dosen SET password = :password WHERE id_dosen = :user_id";
            } elseif ($user_type == 'admin') {
                $query = "UPDATE admin SET password = :password WHERE id_admin = :user_id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $new_password);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $success = "Password berhasil direset";
        } catch (Exception $e) {
            $error = "Gagal reset password: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'toggle_status') {
        try {
            $user_id = $_POST['user_id'];
            $user_type = $_POST['user_type'];
            $new_status = $_POST['new_status'];
            
            if ($user_type == 'mahasiswa') {
                $query = "UPDATE mahasiswa SET status = :status WHERE id_mahasiswa = :user_id";
            } elseif ($user_type == 'dosen') {
                $query = "UPDATE dosen SET status = :status WHERE id_dosen = :user_id";
            }
            
            if (isset($query)) {
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $new_status);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $success = "Status pengguna berhasil diubah";
            }
        } catch (Exception $e) {
            $error = "Gagal mengubah status: " . $e->getMessage();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build combined query for all user types
$users_list = [];

// Get mahasiswa
if (empty($role_filter) || $role_filter == 'mahasiswa') {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(nim LIKE :search OR nama LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT id_mahasiswa as user_id, nim as identifier, nama as nama_lengkap, email, 
                     'mahasiswa' as user_type, status, program_studi, created_at
              FROM mahasiswa $where_clause";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $mahasiswa_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users_list = array_merge($users_list, $mahasiswa_data);
}

// Get dosen
if (empty($role_filter) || $role_filter == 'dosen') {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(nidn LIKE :search OR nama_dosen LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT id_dosen as user_id, nidn as identifier, nama_dosen as nama_lengkap, email, 
                     'dosen' as user_type, status, program_studi, created_at
              FROM dosen $where_clause";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $dosen_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users_list = array_merge($users_list, $dosen_data);
}

// Get admin
if (empty($role_filter) || $role_filter == 'admin') {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE :search OR nama_admin LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT id_admin as user_id, username as identifier, nama_admin as nama_lengkap, email, 
                     'admin' as user_type, 'aktif' as status, 'Admin' as program_studi, created_at
              FROM admin $where_clause";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $admin_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users_list = array_merge($users_list, $admin_data);
}

// Sort by created_at
usort($users_list, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Apply pagination
$total_records = count($users_list);
$total_pages = ceil($total_records / $limit);
$users_list = array_slice($users_list, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - <?php echo APP_NAME; ?></title>
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

                    <a href="admin-users.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h1>
                            <p class="text-gray-600">Kelola akun pengguna sistem</p>
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
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input type="text" name="search" placeholder="Cari nama, username, email..."
                                value="<?php echo htmlspecialchars($search); ?>" class="form-input w-full">
                        </div>
                        <div>
                            <select name="role" class="form-input w-full">
                                <option value="">Semua Role</option>
                                <option value="mahasiswa"
                                    <?php echo ($role_filter == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                                <option value="dosen" <?php echo ($role_filter == 'dosen') ? 'selected' : ''; ?>>Dosen
                                </option>
                                <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Admin
                                </option>
                            </select>
                        </div>
                        <div>
                            <select name="status" class="form-input w-full">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo ($status_filter == 'aktif') ? 'selected' : ''; ?>>Aktif
                                </option>
                                <option value="nonaktif"
                                    <?php echo ($status_filter == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex-1">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="admin-users.php"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Daftar Pengguna (<?php echo $total_records; ?> total)
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
                                    Pengguna</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Studi</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users_list as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <?php if ($user['user_type'] == 'mahasiswa'): ?>
                                            <i class="fas fa-user text-blue-600"></i>
                                            <?php elseif ($user['user_type'] == 'dosen'): ?>
                                            <i class="fas fa-chalkboard-teacher text-green-600"></i>
                                            <?php else: ?>
                                            <i class="fas fa-user-shield text-purple-600"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $user['nama_lengkap']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $user['identifier']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $user['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($user['user_type']) {
                                            case 'mahasiswa': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'dosen': echo 'bg-green-100 text-green-800'; break;
                                            case 'admin': echo 'bg-purple-100 text-purple-800'; break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $user['program_studi']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $user['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <button
                                            onclick="resetPassword(<?php echo $user['user_id']; ?>, '<?php echo $user['user_type']; ?>', '<?php echo $user['nama_lengkap']; ?>')"
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['user_type'] != 'admin'): ?>
                                        <button
                                            onclick="toggleStatus(<?php echo $user['user_id']; ?>, '<?php echo $user['user_type']; ?>', '<?php echo $user['status']; ?>', '<?php echo $user['nama_lengkap']; ?>')"
                                            class="text-orange-600 hover:text-orange-900">
                                            <i
                                                class="fas fa-toggle-<?php echo $user['status'] == 'aktif' ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <?php endif; ?>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>"
                                class="px-3 py-2 <?php echo ($i == $page) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>"
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

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Reset Password</h3>
                <p class="text-gray-600 mb-6">Reset password untuk <strong id="resetUserName"></strong></p>

                <form method="POST" id="resetPasswordForm">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <input type="hidden" name="user_type" id="resetUserType">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closeResetPasswordModal()"
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-key mr-2"></i>
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div id="toggleStatusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-toggle-on text-orange-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Ubah Status</h3>
                <p class="text-gray-600 mb-6">Ubah status untuk <strong id="statusUserName"></strong></p>

                <form method="POST" id="toggleStatusForm">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <input type="hidden" name="user_type" id="statusUserType">
                    <input type="hidden" name="new_status" id="newStatus">

                    <div class="flex gap-3">
                        <button type="button" onclick="closeToggleStatusModal()"
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            <i class="fas fa-toggle-on mr-2"></i>
                            <span id="statusButtonText">Ubah Status</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function resetPassword(userId, userType, userName) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetUserType').value = userType;
        document.getElementById('resetUserName').textContent = userName;
        document.getElementById('resetPasswordModal').classList.remove('hidden');
        document.getElementById('resetPasswordModal').classList.add('flex');
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
        document.getElementById('resetPasswordModal').classList.remove('flex');
    }

    function toggleStatus(userId, userType, currentStatus, userName) {
        document.getElementById('statusUserId').value = userId;
        document.getElementById('statusUserType').value = userType;
        document.getElementById('statusUserName').textContent = userName;

        const newStatus = currentStatus === 'aktif' ? 'nonaktif' : 'aktif';
        document.getElementById('newStatus').value = newStatus;
        document.getElementById('statusButtonText').textContent = newStatus === 'aktif' ? 'Aktifkan' : 'Nonaktifkan';

        document.getElementById('toggleStatusModal').classList.remove('hidden');
        document.getElementById('toggleStatusModal').classList.add('flex');
    }

    function closeToggleStatusModal() {
        document.getElementById('toggleStatusModal').classList.add('hidden');
        document.getElementById('toggleStatusModal').classList.remove('flex');
    }

    // Close modals when clicking outside
    document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeResetPasswordModal();
        }
    });

    document.getElementById('toggleStatusModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeToggleStatusModal();
        }
    });
    </script>
</body>

</html>