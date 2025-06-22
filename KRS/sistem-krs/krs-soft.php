<?php
require_once 'config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard-soft.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get mahasiswa ID
$query = "SELECT id FROM mahasiswa WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
$mahasiswa_id = $mahasiswa['id'];

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_course') {
        $jadwal_id = $_POST['jadwal_id'];
        $semester_tahun = '2024/1';
        
        try {
            $query = "INSERT INTO krs (mahasiswa_id, jadwal_kuliah_id, semester_tahun, status) 
                     VALUES (:mahasiswa_id, :jadwal_id, :semester_tahun, 'draft')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $stmt->bindParam(':jadwal_id', $jadwal_id);
            $stmt->bindParam(':semester_tahun', $semester_tahun);
            $stmt->execute();
            
            $success = "Mata kuliah berhasil ditambahkan ke KRS";
        } catch (Exception $e) {
            $error = "Gagal menambahkan mata kuliah: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'remove_course') {
        $krs_id = $_POST['krs_id'];
        
        try {
            $query = "DELETE FROM krs WHERE id = :krs_id AND mahasiswa_id = :mahasiswa_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':krs_id', $krs_id);
            $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $stmt->execute();
            
            $success = "Mata kuliah berhasil dihapus dari KRS";
        } catch (Exception $e) {
            $error = "Gagal menghapus mata kuliah: " . $e->getMessage();
        }
    }
}

// Get selected courses
$query = "SELECT k.*, mk.kode_mk, mk.nama_mk, mk.sks, d.nama_lengkap as dosen,
                 jk.hari, jk.jam_mulai, jk.jam_selesai, jk.ruang
          FROM krs k
          JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
          JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
          JOIN dosen d ON jk.dosen_id = d.id
          WHERE k.mahasiswa_id = :mahasiswa_id AND k.semester_tahun = '2024/1'";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$selected_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total SKS
$total_sks = array_sum(array_column($selected_courses, 'sks'));

// Get available courses
$selected_ids = array_column($selected_courses, 'jadwal_kuliah_id');
$selected_ids_str = empty($selected_ids) ? '0' : implode(',', $selected_ids);

$query = "SELECT jk.*, mk.kode_mk, mk.nama_mk, mk.sks, d.nama_lengkap as dosen,
                 (SELECT COUNT(*) FROM krs WHERE jadwal_kuliah_id = jk.id AND semester_tahun = '2024/1') as terisi
          FROM jadwal_kuliah jk
          JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
          JOIN dosen d ON jk.dosen_id = d.id
          WHERE jk.semester_tahun = '2024/1' AND jk.id NOT IN ($selected_ids_str)
          ORDER BY mk.kode_mk";

$stmt = $conn->prepare($query);
$stmt->execute();
$available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengisian KRS - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/soft-ui-custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(310deg, #f0f2f5 0%, #fcfcfc 100%);
            font-family: 'Open Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar (same structure as dashboard) -->
        <div class="w-64 p-4">
            <div class="sidebar-soft h-full p-4">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Sistem KRS</h2>
                        <p class="text-xs text-gray-500">Universitas Indonesia</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-2">
                    <div class="px-3 py-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
                    </div>
                    
                    <a href="dashboard-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="krs-soft.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>
                    
                    <a href="jadwal-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4">
            <!-- Header -->
            <div class="card mb-6">
                <div class="card-header p-6 pb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Pengisian KRS</h1>
                            <p class="text-gray-600">Semester Genap 2023/2024</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-100 to-pink-100 rounded-xl">
                                <i class="fas fa-book text-purple-600"></i>
                                <span class="text-sm font-medium text-purple-800">Total SKS: <?php echo $total_sks; ?>/24</span>
                            </div>
                            <button class="btn-gradient-primary px-6 py-2 <?php echo empty($selected_courses) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo empty($selected_courses) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save mr-2"></i>
                                Simpan KRS
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                        <p class="text-green-700"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Selected Courses -->
            <?php if (!empty($selected_courses)): ?>
            <div class="card mb-6">
                <div class="card-header p-6 pb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Mata Kuliah Terpilih</h3>
                    <p class="text-sm text-gray-600">Daftar mata kuliah yang akan diambil semester ini</p>
                </div>
                <div class="p-6 pt-0">
                    <div class="space-y-4">
                        <?php foreach ($selected_courses as $course): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-100 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="badge-soft bg-blue-100 text-blue-800"><?php echo $course['kode_mk']; ?></span>
                                        <span class="font-semibold text-gray-800"><?php echo $course['nama_mk']; ?></span>
                                        <span class="badge-soft bg-purple-100 text-purple-800"><?php echo $course['sks']; ?> SKS</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-user text-xs"></i>
                                            <span><?php echo $course['dosen']; ?></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-clock text-xs"></i>
                                            <span><?php echo $course['hari']; ?> <?php echo formatWaktu($course['jam_mulai']); ?>-<?php echo formatWaktu($course['jam_selesai']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-map-marker-alt text-xs"></i>
                                            <span><?php echo $course['ruang']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="remove_course">
                                    <input type="hidden" name="krs_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="w-10 h-10 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition-colors"
                                            onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Available Courses -->
            <div class="card">
                <div class="card-header p-6 pb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Mata Kuliah Tersedia</h3>
                    <p class="text-sm text-gray-600">Pilih mata kuliah yang ingin diambil</p>
                </div>
                <div class="p-6 pt-0">
                    <div class="overflow-x-auto">
                        <table class="table-soft w-full">
                            <thead>
                                <tr>
                                    <th class="text-left">Kode</th>
                                    <th class="text-left">Mata Kuliah</th>
                                    <th class="text-left">SKS</th>
                                    <th class="text-left">Dosen</th>
                                    <th class="text-left">Jadwal</th>
                                    <th class="text-left">Ruang</th>
                                    <th class="text-left">Kuota</th>
                                    <th class="text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_courses as $course): ?>
                                <?php 
                                $can_select = ($total_sks + $course['sks'] <= 24) && ($course['terisi'] < $course['kuota']);
                                $is_full = $course['terisi'] >= $course['kuota'];
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <span class="badge-soft bg-gray-100 text-gray-800"><?php echo $course['kode_mk']; ?></span>
                                    </td>
                                    <td>
                                        <div class="font-medium text-gray-900"><?php echo $course['nama_mk']; ?></div>
                                    </td>
                                    <td class="font-medium"><?php echo $course['sks']; ?></td>
                                    <td class="text-gray-700"><?php echo $course['dosen']; ?></td>
                                    <td>
                                        <div class="text-sm">
                                            <div class="font-medium"><?php echo $course['hari']; ?></div>
                                            <div class="text-gray-500"><?php echo formatWaktu($course['jam_mulai']); ?>-<?php echo formatWaktu($course['jam_selesai']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-gray-700"><?php echo $course['ruang']; ?></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="<?php echo $is_full ? 'text-red-600' : 'text-green-600'; ?> font-medium">
                                                <?php echo $course['terisi']; ?>/<?php echo $course['kuota']; ?>
                                            </span>
                                            <?php if ($is_full): ?>
                                                <span class="badge-soft bg-red-100 text-red-800">Penuh</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($can_select): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="add_course">
                                                <input type="hidden" name="jadwal_id" value="<?php echo $course['id']; ?>">
                                                <button type="submit" class="btn-gradient-primary px-4 py-2 text-sm">
                                                    <i class="fas fa-plus mr-1"></i> Pilih
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button disabled class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg cursor-not-allowed text-sm">
                                                <?php echo $is_full ? 'Penuh' : 'Maks SKS'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
