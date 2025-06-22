<?php
require_once 'config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard.php');
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar (same as dashboard) -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Sistem KRS</h2>
                        <p class="text-sm text-gray-500">Universitas Indonesia</p>
                    </div>
                </div>
            </div>
            
            <nav class="mt-4">
                <div class="px-4 py-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
                </div>
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-home w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="krs.php" class="flex items-center px-4 py-2 text-blue-600 bg-blue-50 border-r-2 border-blue-600">
                    <i class="fas fa-book w-5"></i>
                    <span class="ml-3">Pengisian KRS</span>
                </a>
                <a href="jadwal.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-calendar w-5"></i>
                    <span class="ml-3">Jadwal Kuliah</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Pengisian KRS</h1>
                        <p class="text-sm text-gray-500">Semester Genap 2023/2024</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            Total SKS: <?php echo $total_sks; ?>/24
                        </span>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 <?php echo empty($selected_courses) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo empty($selected_courses) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save mr-2"></i>
                            Simpan KRS
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Selected Courses -->
                <?php if (!empty($selected_courses)): ?>
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Mata Kuliah Terpilih</h3>
                        <p class="text-sm text-gray-500">Daftar mata kuliah yang akan diambil semester ini</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($selected_courses as $course): ?>
                            <div class="flex items-center justify-between p-3 border rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-sm"><?php echo $course['kode_mk']; ?></span>
                                        <span class="font-medium"><?php echo $course['nama_mk']; ?></span>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm"><?php echo $course['sks']; ?> SKS</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?php echo $course['dosen']; ?> • 
                                        <?php echo $course['hari']; ?> <?php echo formatWaktu($course['jam_mulai']); ?>-<?php echo formatWaktu($course['jam_selesai']); ?> • 
                                        <?php echo $course['ruang']; ?>
                                    </p>
                                </div>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="remove_course">
                                    <input type="hidden" name="krs_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="px-3 py-1 text-red-600 border border-red-600 rounded hover:bg-red-50"
                                            onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Available Courses -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Mata Kuliah Tersedia</h3>
                        <p class="text-sm text-gray-500">Pilih mata kuliah yang ingin diambil</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Kuliah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKS</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ruang</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kuota</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($available_courses as $course): ?>
                                <?php 
                                $can_select = ($total_sks + $course['sks'] <= 24) && ($course['terisi'] < $course['kuota']);
                                $is_full = $course['terisi'] >= $course['kuota'];
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-sm"><?php echo $course['kode_mk']; ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900"><?php echo $course['nama_mk']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $course['sks']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $course['dosen']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $course['hari']; ?><br>
                                        <?php echo formatWaktu($course['jam_mulai']); ?>-<?php echo formatWaktu($course['jam_selesai']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $course['ruang']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="<?php echo $is_full ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo $course['terisi']; ?>/<?php echo $course['kuota']; ?>
                                            </span>
                                            <?php if ($is_full): ?>
                                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Penuh</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($can_select): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="add_course">
                                                <input type="hidden" name="jadwal_id" value="<?php echo $course['id']; ?>">
                                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                    <i class="fas fa-plus"></i> Pilih
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button disabled class="px-3 py-1 bg-gray-300 text-gray-500 rounded cursor-not-allowed">
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
            </main>
        </div>
    </div>
</body>
</html>
