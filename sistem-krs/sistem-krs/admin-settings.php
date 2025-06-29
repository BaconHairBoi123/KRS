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

// Handle settings update
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_settings') {
        try {
            $settings = [
                'app_name' => $_POST['app_name'],
                'university_name' => $_POST['university_name'],
                'semester_aktif' => $_POST['semester_aktif'],
                'tahun_ajaran' => $_POST['tahun_ajaran'],
                'krs_open' => $_POST['krs_open'],
                'max_sks' => $_POST['max_sks'],
                'min_sks' => $_POST['min_sks']
            ];
            
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) 
                         ON DUPLICATE KEY UPDATE setting_value = :value";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                $stmt->execute();
            }
            
            $success = "Pengaturan berhasil disimpan";
        } catch (Exception $e) {
            $error = "Gagal menyimpan pengaturan: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'backup_database') {
        try {
            // Simple backup functionality
            $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_path = __DIR__ . '/backups/' . $backup_file;
            
            // Create backups directory if not exists
            if (!is_dir(__DIR__ . '/backups/')) {
                mkdir(__DIR__ . '/backups/', 0755, true);
            }
            
            // Execute mysqldump command (requires mysqldump to be available)
            $command = "mysqldump --host=" . DB_HOST . " --user=" . DB_USER . " --password=" . DB_PASS . " " . DB_NAME . " > " . $backup_path;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $success = "Backup database berhasil dibuat: " . $backup_file;
            } else {
                $error = "Gagal membuat backup database";
            }
        } catch (Exception $e) {
            $error = "Gagal membuat backup: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'clear_cache') {
        try {
            // Clear session cache
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            
            // Clear any temporary files if needed
            $temp_dir = __DIR__ . '/temp/';
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            
            $success = "Cache berhasil dibersihkan";
        } catch (Exception $e) {
            $error = "Gagal membersihkan cache: " . $e->getMessage();
        }
    }
}

// Get current settings
$current_settings = [];
$query = "SELECT setting_key, setting_value FROM system_settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($settings_data as $setting) {
    $current_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Default values if not set
$defaults = [
    'app_name' => 'Sistem KRS',
    'university_name' => 'Universitas Touhou Indonesia',
    'semester_aktif' => 'Ganjil',
    'tahun_ajaran' => '2024/2025',
    'krs_open' => '0',
    'max_sks' => '24',
    'min_sks' => '12'
];

foreach ($defaults as $key => $value) {
    if (!isset($current_settings[$key])) {
        $current_settings[$key] = $value;
    }
}

// Get system info
$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => $conn->query('SELECT VERSION()')->fetchColumn(),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Get recent backups
$backup_files = [];
$backup_dir = __DIR__ . '/backups/';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . 'backup_*.sql');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    // Sort by date descending
    usort($backup_files, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    $backup_files = array_slice($backup_files, 0, 10); // Show only last 10 backups
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - <?php echo APP_NAME; ?></title>
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

                    <a href="admin-settings.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Sistem</h1>
                            <p class="text-gray-600">Konfigurasi dan maintenance sistem</p>
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- General Settings -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Umum</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Aplikasi</label>
                                    <input type="text" name="app_name"
                                        value="<?php echo htmlspecialchars($current_settings['app_name']); ?>"
                                        class="form-input w-full" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama
                                        Universitas</label>
                                    <input type="text" name="university_name"
                                        value="<?php echo htmlspecialchars($current_settings['university_name']); ?>"
                                        class="form-input w-full" required>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Semester
                                            Aktif</label>
                                        <select name="semester_aktif" class="form-input w-full">
                                            <option value="Ganjil"
                                                <?php echo ($current_settings['semester_aktif'] == 'Ganjil') ? 'selected' : ''; ?>>
                                                Ganjil</option>
                                            <option value="Genap"
                                                <?php echo ($current_settings['semester_aktif'] == 'Genap') ? 'selected' : ''; ?>>
                                                Genap</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun
                                            Ajaran</label>
                                        <input type="text" name="tahun_ajaran"
                                            value="<?php echo htmlspecialchars($current_settings['tahun_ajaran']); ?>"
                                            class="form-input w-full" placeholder="2024/2025" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status KRS</label>
                                    <select name="krs_open" class="form-input w-full">
                                        <option value="0"
                                            <?php echo ($current_settings['krs_open'] == '0') ? 'selected' : ''; ?>>
                                            Ditutup</option>
                                        <option value="1"
                                            <?php echo ($current_settings['krs_open'] == '1') ? 'selected' : ''; ?>>
                                            Dibuka</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Minimum
                                            SKS</label>
                                        <input type="number" name="min_sks"
                                            value="<?php echo htmlspecialchars($current_settings['min_sks']); ?>"
                                            class="form-input w-full" min="1" max="30" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Maksimum
                                            SKS</label>
                                        <input type="number" name="max_sks"
                                            value="<?php echo htmlspecialchars($current_settings['max_sks']); ?>"
                                            class="form-input w-full" min="1" max="30" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit"
                                    class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Maintenance -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Maintenance Sistem</h3>

                        <div class="space-y-4">
                            <!-- Backup Database -->
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-800">Backup Database</h4>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit"
                                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-download mr-2"></i>
                                            Backup
                                        </button>
                                    </form>
                                </div>
                                <p class="text-sm text-gray-600">Buat backup database untuk keamanan data</p>
                            </div>

                            <!-- Clear Cache -->
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-800">Bersihkan Cache</h4>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit"
                                            class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                                            <i class="fas fa-broom mr-2"></i>
                                            Clear Cache
                                        </button>
                                    </form>
                                </div>
                                <p class="text-sm text-gray-600">Hapus file cache dan session temporary</p>
                            </div>

                            <!-- Recent Backups -->
                            <?php if (!empty($backup_files)): ?>
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium text-gray-800 mb-3">Backup Terbaru</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <?php foreach ($backup_files as $backup): ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <div>
                                            <div class="font-medium text-gray-800"><?php echo $backup['name']; ?></div>
                                            <div class="text-gray-500"><?php echo $backup['date']; ?> -
                                                <?php echo number_format($backup['size'] / 1024, 2); ?> KB</div>
                                        </div>
                                        <a href="backups/<?php echo $backup['name']; ?>" download
                                            class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card lg:col-span-2">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Sistem</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">PHP Version</div>
                                <div class="font-semibold text-gray-800"><?php echo $system_info['php_version']; ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Database Version</div>
                                <div class="font-semibold text-gray-800"><?php echo $system_info['database_version']; ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Memory Limit</div>
                                <div class="font-semibold text-gray-800"><?php echo $system_info['memory_limit']; ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Max Upload Size</div>
                                <div class="font-semibold text-gray-800">
                                    <?php echo $system_info['upload_max_filesize']; ?></div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Post Max Size</div>
                                <div class="font-semibold text-gray-800"><?php echo $system_info['post_max_size']; ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Max Execution Time</div>
                                <div class="font-semibold text-gray-800">
                                    <?php echo $system_info['max_execution_time']; ?>s</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                                <div class="text-sm text-gray-600">Server Software</div>
                                <div class="font-semibold text-gray-800"><?php echo $system_info['server_software']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>