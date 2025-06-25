<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'admin') {
    redirect('dashboard-soft.php');
}

$database = new Database();
$conn = $database->getConnection();

// Handle actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_tarif') {
        $tarif_id = $_POST['tarif_id'];
        $nominal = $_POST['nominal'];
        
        try {
            $query = "UPDATE ukt_tarif SET nominal = :nominal WHERE id = :tarif_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nominal', $nominal);
            $stmt->bindParam(':tarif_id', $tarif_id);
            $stmt->execute();
            
            $success = "Tarif UKT berhasil diupdate";
        } catch (Exception $e) {
            $error = "Gagal update tarif: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'generate_tagihan') {
        $periode_id = $_POST['periode_id'];
        
        try {
            // Generate tagihan untuk semua mahasiswa yang belum punya tagihan di periode ini
            $query = "INSERT INTO ukt_tagihan (mahasiswa_id, periode_id, tarif_id, nominal_tagihan, total_tagihan, virtual_account, tanggal_jatuh_tempo)
                     SELECT 
                         m.id,
                         :periode_id,
                         ut.id,
                         ut.nominal,
                         ut.nominal,
                         CONCAT('8001', LPAD(m.id, 6, '0'), LPAD(:periode_id, 4, '0')),
                         up.tanggal_akhir
                     FROM mahasiswa m
                     JOIN ukt_tarif ut ON ut.program_studi = m.program_studi 
                         AND ut.angkatan = m.angkatan 
                         AND ut.kelompok_ukt = m.kelompok_ukt
                     JOIN ukt_periode up ON up.id = :periode_id
                     WHERE ut.status = 'aktif'
                     AND NOT EXISTS (
                         SELECT 1 FROM ukt_tagihan utag 
                         WHERE utag.mahasiswa_id = m.id AND utag.periode_id = :periode_id
                     )";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':periode_id', $periode_id);
            $stmt->execute();
            
            $affected = $stmt->rowCount();
            $success = "Berhasil generate $affected tagihan baru";
        } catch (Exception $e) {
            $error = "Gagal generate tagihan: " . $e->getMessage();
        }
    }
}

// Get tarif UKT
$query = "SELECT * FROM ukt_tarif ORDER BY program_studi, angkatan, kelompok_ukt";
$stmt = $conn->prepare($query);
$stmt->execute();
$tarif_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get periode
$query = "SELECT * FROM ukt_periode ORDER BY tanggal_mulai DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$periode_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$query = "SELECT 
            COUNT(DISTINCT ut.mahasiswa_id) as total_mahasiswa,
            COUNT(CASE WHEN ut.status_tagihan = 'lunas' THEN 1 END) as lunas,
            COUNT(CASE WHEN ut.status_tagihan = 'belum_bayar' THEN 1 END) as belum_bayar,
            COUNT(CASE WHEN ut.status_tagihan = 'terlambat' THEN 1 END) as terlambat,
            SUM(CASE WHEN ut.status_tagihan = 'lunas' THEN ut.total_tagihan ELSE 0 END) as total_terkumpul
          FROM ukt_tagihan ut";

$stmt = $conn->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin UKT - <?php echo APP_NAME; ?></title>
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
                    
                    <a href="dashboard-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Keuangan</p>
                    </div>
                    
                    <a href="ukt-admin.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Kelola UKT</h1>
                            <p class="text-gray-600">Administrasi Uang Kuliah Tunggal</p>
                        </div>
                        <div class="theme-toggle-container"></div>
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

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Mahasiswa</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_mahasiswa']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Lunas</p>
                            <h3 class="text-2xl font-bold text-green-600"><?php echo $stats['lunas']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Belum Bayar</p>
                            <h3 class="text-2xl font-bold text-yellow-600"><?php echo $stats['belum_bayar']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Terlambat</p>
                            <h3 class="text-2xl font-bold text-red-600"><?php echo $stats['terlambat']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation text-red-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Terkumpul</p>
                            <h3 class="text-lg font-bold text-purple-600">Rp <?php echo number_format($stats['total_terkumpul'], 0, ',', '.'); ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Tagihan -->
            <div class="card mb-6">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Generate Tagihan UKT</h3>
                    <p class="text-sm text-gray-600">Buat tagihan UKT untuk periode tertentu</p>
                </div>
                <div class="p-6">
                    <form method="POST" class="flex items-end gap-4">
                        <input type="hidden" name="action" value="generate_tagihan">
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Periode</label>
                            <select name="periode_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">Pilih Periode</option>
                                <?php foreach ($periode_list as $periode): ?>
                                    <option value="<?php echo $periode['id']; ?>"><?php echo $periode['nama_periode']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Generate Tagihan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tarif UKT -->
            <div class="card">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Tarif UKT</h3>
                    <p class="text-sm text-gray-600">Kelola tarif UKT berdasarkan program studi dan kelompok</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Studi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Angkatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelompok</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tarif_list as $tarif): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $tarif['program_studi']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $tarif['angkatan']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Kelompok <?php echo $tarif['kelompok_ukt']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rp <?php echo number_format($tarif['nominal'], 0, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $tarif['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($tarif['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editTarif(<?php echo $tarif['id']; ?>, '<?php echo $tarif['program_studi']; ?>', <?php echo $tarif['kelompok_ukt']; ?>, <?php echo $tarif['nominal']; ?>)" 
                                            class="text-purple-600 hover:text-purple-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tarif Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Edit Tarif UKT</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_tarif">
                <input type="hidden" name="tarif_id" id="editTarifId">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Program Studi</p>
                    <p class="font-semibold text-gray-800" id="editProdi"></p>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Kelompok UKT</p>
                    <p class="font-semibold text-gray-800" id="editKelompok"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nominal (Rupiah)</label>
                    <input type="number" name="nominal" id="editNominal" required 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function editTarif(id, prodi, kelompok, nominal) {
            document.getElementById('editTarifId').value = id;
            document.getElementById('editProdi').textContent = prodi;
            document.getElementById('editKelompok').textContent = 'Kelompok ' + kelompok;
            document.getElementById('editNominal').value = nominal;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
