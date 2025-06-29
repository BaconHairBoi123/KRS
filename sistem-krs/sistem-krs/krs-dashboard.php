<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_kelas') {
        $id_kelas = $_POST['id_kelas'];
        
        try {
            // Check if already enrolled
            $check_query = "SELECT COUNT(*) FROM krs WHERE id_mahasiswa = :id_mahasiswa AND id_kelas = :id_kelas";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            $check_stmt->bindValue(':id_kelas', $id_kelas);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Anda sudah mengambil kelas ini");
            }
            
            // Insert KRS
            $insert_query = "INSERT INTO krs (id_mahasiswa, id_kelas, tanggal_ambil, status_krs) 
                            VALUES (:id_mahasiswa, :id_kelas, NOW(), 'Aktif')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            $insert_stmt->bindValue(':id_kelas', $id_kelas);
            
            if ($insert_stmt->execute()) {
                $success = "Mata kuliah berhasil ditambahkan ke KRS";
            } else {
                throw new Exception("Gagal menambahkan mata kuliah");
            }
        } catch (Exception $e) {
            $error = "Gagal menambahkan mata kuliah: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'remove_kelas') {
        $id_krs = $_POST['id_krs'];
        
        try {
            $delete_query = "DELETE FROM krs WHERE id_krs = :id_krs AND id_mahasiswa = :id_mahasiswa";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindValue(':id_krs', $id_krs);
            $delete_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            
            if ($delete_stmt->execute()) {
                $success = "Mata kuliah berhasil dihapus dari KRS";
            } else {
                throw new Exception("Gagal menghapus mata kuliah");
            }
        } catch (Exception $e) {
            $error = "Gagal menghapus mata kuliah: " . $e->getMessage();
        }
    }
}

// Get student's current KRS
$krs_query = "SELECT krs.*, 
                     'Mata Kuliah' as nama_matakuliah,
                     'MK001' as kode_matakuliah,
                     3 as sks,
                     'Dosen Pengajar' as nama_dosen,
                     'S.Kom., M.Kom.' as gelar,
                     kl.nama_kelas
              FROM krs 
              JOIN kelas kl ON krs.id_kelas = kl.id_kelas
              WHERE krs.id_mahasiswa = :id_mahasiswa AND krs.status_krs = 'Aktif'";

$krs_stmt = $conn->prepare($krs_query);
$krs_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
$krs_stmt->execute();
$current_krs = $krs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total SKS
$total_sks = count($current_krs) * 3; // Simplified calculation

// Get available classes
$available_query = "SELECT kl.*, 
                           'Mata Kuliah' as nama_matakuliah,
                           'MK001' as kode_matakuliah,
                           3 as sks,
                           1 as semester,
                           'Dosen Pengajar' as nama_dosen,
                           'S.Kom., M.Kom.' as gelar,
                           30 as kapasitas,
                           0 as terisi
                    FROM kelas kl
                    WHERE kl.id_kelas NOT IN (
                        SELECT COALESCE(k.id_kelas, 0) FROM krs k WHERE k.id_mahasiswa = :id_mahasiswa AND k.status_krs = 'Aktif'
                    )
                    ORDER BY kl.nama_kelas
                    LIMIT 10";

$available_stmt = $conn->prepare($available_query);
$available_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
$available_stmt->execute();
$available_classes = $available_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current semester info
$semester_query = "SELECT * FROM tahun_akademik WHERE status = 'Aktif' LIMIT 1";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->execute();
$active_semester = $semester_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard KRS - <?php echo APP_NAME; ?></title>
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

    .bg-gradient-primary {
        background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%);
    }

    .user-info-box {
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 15px rgba(79, 172, 254, 0.3);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
    }

    .btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 15px rgba(255, 107, 107, 0.3);
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .alert-success {
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        color: #276749;
    }

    .alert-error {
        background: #fed7d7;
        border: 1px solid #feb2b2;
        color: #c53030;
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
                        <h2 class="text-lg font-bold text-gray-800">Sistem KRS</h2>
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

                    <a href="krs-dashboard.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>

                    <a href="jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>

                    <a href="absensi-semester.php"
                        class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-line w-5 mr-3"></i>
                        <span>Absen Semester</span>
                    </a>

                    <a href="profil.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-user w-5 mr-3"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3 user-info-box">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $userData['nomor_induk']; ?></p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Dashboard Pengisian KRS</h1>
                            <p class="text-gray-600">Kelola Kartu Rencana Studi Anda</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <?php if ($active_semester): ?>
                            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <?php echo $active_semester['tahun_akademik']; ?> - Semester
                                <?php echo $active_semester['semester_akademik']; ?>
                            </div>
                            <?php else: ?>
                            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Semester Aktif
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total MK -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Mata Kuliah Diambil</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo count($current_krs); ?></h3>
                            <p class="text-xs text-gray-500">Mata Kuliah</p>
                        </div>
                        <div class="icon-shape bg-gradient-primary">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <!-- Total SKS -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total SKS</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_sks; ?></h3>
                            <p class="text-xs text-gray-500">SKS</p>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>

                <!-- Status KRS -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Status KRS</p>
                            <h3 class="text-lg font-bold text-green-600">Aktif</h3>
                            <p class="text-xs text-gray-500">Periode Pengisian</p>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Batas SKS -->
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Batas Maksimal</p>
                            <h3 class="text-2xl font-bold text-gray-800">24</h3>
                            <p class="text-xs text-gray-500">SKS</p>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current KRS -->
            <?php if (!empty($current_krs)): ?>
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-list-check text-blue-500 mr-2"></i>
                        Mata Kuliah yang Diambil
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kode</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">SKS</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kelas</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Dosen</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_krs as $krs): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-blue-600">
                                        <?php echo $krs['kode_matakuliah']; ?></td>
                                    <td class="py-3 px-4"><?php echo $krs['nama_matakuliah']; ?></td>
                                    <td class="text-center py-3 px-4">
                                        <span
                                            class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm"><?php echo $krs['sks']; ?></span>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $krs['nama_kelas']; ?></td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div><?php echo $krs['nama_dosen']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $krs['gelar']; ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_kelas">
                                            <input type="hidden" name="id_krs" value="<?php echo $krs['id_krs']; ?>">
                                            <button type="submit" class="btn btn-danger"
                                                onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Available Classes -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                        Kelas yang Tersedia
                    </h3>
                    <?php if (empty($available_classes)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-info-circle text-4xl text-gray-400 mb-4"></i>
                        <h4 class="text-lg font-semibold text-gray-600 mb-2">Tidak Ada Kelas Tersedia</h4>
                        <p class="text-gray-500">Semua kelas sudah diambil atau belum ada kelas yang dibuka.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kode</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Mata Kuliah</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">SKS</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kelas</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Dosen</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Kapasitas</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_classes as $class): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-blue-600">
                                        <?php echo $class['kode_matakuliah']; ?></td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div><?php echo $class['nama_matakuliah']; ?></div>
                                            <div class="text-sm text-gray-500">Semester
                                                <?php echo $class['semester']; ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <span
                                            class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm"><?php echo $class['sks']; ?></span>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $class['nama_kelas']; ?></td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div><?php echo $class['nama_dosen']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $class['gelar']; ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <span
                                            class="text-sm"><?php echo $class['terisi']; ?>/<?php echo $class['kapasitas']; ?></span>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="add_kelas">
                                            <input type="hidden" name="id_kelas"
                                                value="<?php echo $class['id_kelas']; ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Ambil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>