<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

// Only allow admin or dosen
if (!in_array(getUserRole(), ['admin', 'dosen'])) {
    header('Location: dashboard-soft.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        $jadwal_pertemuan_id = $_POST['jadwal_pertemuan_id'];
        $mahasiswa_id = $_POST['mahasiswa_id'];
        $status_kehadiran = $_POST['status_kehadiran'];
        $keterangan = $_POST['keterangan'] ?? '';
        
        // Check if absensi already exists
        $check_query = "SELECT id FROM absensi WHERE jadwal_pertemuan_id = :jadwal_pertemuan_id AND mahasiswa_id = :mahasiswa_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':jadwal_pertemuan_id', $jadwal_pertemuan_id);
        $check_stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn()) {
            // Update existing record
            $update_query = "UPDATE absensi SET 
                            status_kehadiran = :status_kehadiran,
                            keterangan = :keterangan,
                            waktu_absen = NOW(),
                            verified_by = :verified_by,
                            verified_at = NOW(),
                            updated_at = NOW()
                            WHERE jadwal_pertemuan_id = :jadwal_pertemuan_id AND mahasiswa_id = :mahasiswa_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':status_kehadiran', $status_kehadiran);
            $update_stmt->bindParam(':keterangan', $keterangan);
            $update_stmt->bindParam(':verified_by', $_SESSION['user_id']);
            $update_stmt->bindParam(':jadwal_pertemuan_id', $jadwal_pertemuan_id);
            $update_stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $update_stmt->execute();
            
            $message = "Absensi berhasil diperbarui!";
        } else {
            // Insert new record
            $insert_query = "INSERT INTO absensi (jadwal_pertemuan_id, mahasiswa_id, status_kehadiran, keterangan, waktu_absen, verified_by, verified_at) 
                            VALUES (:jadwal_pertemuan_id, :mahasiswa_id, :status_kehadiran, :keterangan, NOW(), :verified_by, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindParam(':jadwal_pertemuan_id', $jadwal_pertemuan_id);
            $insert_stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $insert_stmt->bindParam(':status_kehadiran', $status_kehadiran);
            $insert_stmt->bindParam(':keterangan', $keterangan);
            $insert_stmt->bindParam(':verified_by', $_SESSION['user_id']);
            $insert_stmt->execute();
            
            $message = "Absensi berhasil disimpan!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get jadwal pertemuan for today
$today = date('Y-m-d');
$pertemuan_query = "SELECT 
    jp.id,
    jp.pertemuan_ke,
    jp.tanggal_pertemuan,
    jp.waktu_mulai,
    jp.waktu_selesai,
    jp.materi,
    jp.ruangan,
    mk.nama_mata_kuliah,
    mk.kode_mata_kuliah,
    jp.kode_absensi
FROM jadwal_pertemuan jp
JOIN jadwal_kuliah jk ON jp.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
WHERE jp.tanggal_pertemuan = :today
ORDER BY jp.waktu_mulai";

$pertemuan_stmt = $conn->prepare($pertemuan_query);
$pertemuan_stmt->bindParam(':today', $today);
$pertemuan_stmt->execute();
$pertemuan_list = $pertemuan_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected pertemuan details
$selected_pertemuan = null;
$mahasiswa_list = [];
$absensi_data = [];

if (isset($_GET['pertemuan_id'])) {
    $pertemuan_id = $_GET['pertemuan_id'];
    
    // Get pertemuan details
    $detail_query = "SELECT 
        jp.*,
        mk.nama_mata_kuliah,
        mk.kode_mata_kuliah
    FROM jadwal_pertemuan jp
    JOIN jadwal_kuliah jk ON jp.jadwal_kuliah_id = jk.id
    JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
    WHERE jp.id = :pertemuan_id";
    
    $detail_stmt = $conn->prepare($detail_query);
    $detail_stmt->bindParam(':pertemuan_id', $pertemuan_id);
    $detail_stmt->execute();
    $selected_pertemuan = $detail_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_pertemuan) {
        // Get mahasiswa list for this jadwal
        $mahasiswa_query = "SELECT 
            m.id,
            m.nama_lengkap,
            m.nim,
            COALESCE(a.status_kehadiran, 'belum_absen') as status_kehadiran,
            a.waktu_absen,
            a.keterangan
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a ON (a.jadwal_pertemuan_id = :pertemuan_id AND a.mahasiswa_id = m.id)
        WHERE k.jadwal_kuliah_id = :jadwal_kuliah_id AND k.status = 'approved'
        ORDER BY m.nama_lengkap";
        
        $mahasiswa_stmt = $conn->prepare($mahasiswa_query);
        $mahasiswa_stmt->bindParam(':pertemuan_id', $pertemuan_id);
        $mahasiswa_stmt->bindParam(':jadwal_kuliah_id', $selected_pertemuan['jadwal_kuliah_id']);
        $mahasiswa_stmt->execute();
        $mahasiswa_list = $mahasiswa_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Absensi - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme-toggle.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(310deg, #f0f2f5 0%, #fcfcfc 100%);
            font-family: 'Open Sans', sans-serif;
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05);
            border: 0;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-hadir { background: #d1fae5; color: #065f46; }
        .status-sakit { background: #fef3c7; color: #92400e; }
        .status-izin { background: #dbeafe; color: #1e40af; }
        .status-alfa { background: #fee2e2; color: #991b1b; }
        .status-terlambat { background: #fed7aa; color: #9a3412; }
        .status-belum_absen { background: #f3f4f6; color: #374151; }
        
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37 0%, #98ec2d 100%); }
        .bg-gradient-info { background: linear-gradient(310deg, #2152ff 0%, #21d4fd 100%); }
        .bg-gradient-warning { background: linear-gradient(310deg, #f53939 0%, #fbcf33 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-4">
        <!-- Header -->
        <div class="card mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Input Absensi</h1>
                        <p class="text-gray-600">Kelola kehadiran mahasiswa</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="theme-toggle-container"></div>
                        <a href="dashboard-soft.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Pilih Pertemuan -->
        <div class="card mb-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Pilih Pertemuan Hari Ini</h3>
                
                <?php if (empty($pertemuan_list)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">Tidak ada pertemuan yang dijadwalkan hari ini.</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($pertemuan_list as $pertemuan): ?>
                    <a href="?pertemuan_id=<?php echo $pertemuan['id']; ?>" 
                       class="block p-4 border border-gray-200 rounded-lg hover:border-purple-500 hover:shadow-md transition-all <?php echo (isset($_GET['pertemuan_id']) && $_GET['pertemuan_id'] == $pertemuan['id']) ? 'border-purple-500 bg-purple-50' : ''; ?>">
                        <div class="font-semibold text-gray-800"><?php echo $pertemuan['nama_mata_kuliah']; ?></div>
                        <div class="text-sm text-gray-600"><?php echo $pertemuan['kode_mata_kuliah']; ?></div>
                        <div class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-clock mr-1"></i>
                            <?php echo date('H:i', strtotime($pertemuan['waktu_mulai'])); ?> - 
                            <?php echo date('H:i', strtotime($pertemuan['waktu_selesai'])); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo $pertemuan['ruangan']; ?>
                        </div>
                        <div class="text-xs text-purple-600 mt-2 font-medium">
                            Pertemuan ke-<?php echo $pertemuan['pertemuan_ke']; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daftar Mahasiswa -->
        <?php if ($selected_pertemuan && !empty($mahasiswa_list)): ?>
        <div class="card">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Kehadiran</h3>
                        <p class="text-sm text-gray-600">
                            <?php echo $selected_pertemuan['nama_mata_kuliah']; ?> - 
                            Pertemuan ke-<?php echo $selected_pertemuan['pertemuan_ke']; ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="markAllPresent()" class="px-4 py-2 bg-gradient-success text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-check-double mr-2"></i>Semua Hadir
                        </button>
                        <button onclick="saveAllAbsensi()" class="px-4 py-2 bg-gradient-primary text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-save mr-2"></i>Simpan Semua
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">No</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">NIM</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama Mahasiswa</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Status Kehadiran</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Keterangan</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mahasiswa_list as $index => $mahasiswa): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50" data-mahasiswa-id="<?php echo $mahasiswa['id']; ?>">
                                <td class="py-3 px-4"><?php echo $index + 1; ?></td>
                                <td class="py-3 px-4 font-medium"><?php echo $mahasiswa['nim']; ?></td>
                                <td class="py-3 px-4"><?php echo $mahasiswa['nama_lengkap']; ?></td>
                                <td class="text-center py-3 px-4">
                                    <select class="status-select px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                            data-mahasiswa-id="<?php echo $mahasiswa['id']; ?>">
                                        <option value="belum_absen" <?php echo $mahasiswa['status_kehadiran'] == 'belum_absen' ? 'selected' : ''; ?>>Belum Absen</option>
                                        <option value="hadir" <?php echo $mahasiswa['status_kehadiran'] == 'hadir' ? 'selected' : ''; ?>>Hadir</option>
                                        <option value="sakit" <?php echo $mahasiswa['status_kehadiran'] == 'sakit' ? 'selected' : ''; ?>>Sakit</option>
                                        <option value="izin" <?php echo $mahasiswa['status_kehadiran'] == 'izin' ? 'selected' : ''; ?>>Izin</option>
                                        <option value="alfa" <?php echo $mahasiswa['status_kehadiran'] == 'alfa' ? 'selected' : ''; ?>>Alfa</option>
                                        <option value="terlambat" <?php echo $mahasiswa['status_kehadiran'] == 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" 
                                           class="keterangan-input w-full px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                           placeholder="Keterangan (opsional)"
                                           value="<?php echo htmlspecialchars($mahasiswa['keterangan'] ?? ''); ?>"
                                           data-mahasiswa-id="<?php echo $mahasiswa['id']; ?>">
                                </td>
                                <td class="text-center py-3 px-4">
                                    <button onclick="saveIndividualAbsensi(<?php echo $mahasiswa['id']; ?>)" 
                                            class="px-3 py-1 bg-gradient-primary text-white rounded-lg hover:shadow-lg transition-all text-sm">
                                        <i class="fas fa-save mr-1"></i>Simpan
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function markAllPresent() {
            const selects = document.querySelectorAll('.status-select');
            selects.forEach(select => {
                select.value = 'hadir';
            });
        }

        function saveIndividualAbsensi(mahasiswaId) {
            const row = document.querySelector(`tr[data-mahasiswa-id="${mahasiswaId}"]`);
            const status = row.querySelector('.status-select').value;
            const keterangan = row.querySelector('.keterangan-input').value;
            
            if (status === 'belum_absen') {
                alert('Silakan pilih status kehadiran terlebih dahulu!');
                return;
            }
            
            const formData = new FormData();
            formData.append('jadwal_pertemuan_id', '<?php echo $_GET['pertemuan_id'] ?? ''; ?>');
            formData.append('mahasiswa_id', mahasiswaId);
            formData.append('status_kehadiran', status);
            formData.append('keterangan', keterangan);
            
            fetch('absensi-input.php?pertemuan_id=<?php echo $_GET['pertemuan_id'] ?? ''; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert('Absensi berhasil disimpan!');
                location.reload();
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }

        function saveAllAbsensi() {
            const rows = document.querySelectorAll('tbody tr');
            let promises = [];
            
            rows.forEach(row => {
                const mahasiswaId = row.dataset.mahasiswaId;
                const status = row.querySelector('.status-select').value;
                const keterangan = row.querySelector('.keterangan-input').value;
                
                if (status !== 'belum_absen') {
                    const formData = new FormData();
                    formData.append('jadwal_pertemuan_id', '<?php echo $_GET['pertemuan_id'] ?? ''; ?>');
                    formData.append('mahasiswa_id', mahasiswaId);
                    formData.append('status_kehadiran', status);
                    formData.append('keterangan', keterangan);
                    
                    promises.push(
                        fetch('absensi-input.php?pertemuan_id=<?php echo $_GET['pertemuan_id'] ?? ''; ?>', {
                            method: 'POST',
                            body: formData
                        })
                    );
                }
            });
            
            if (promises.length === 0) {
                alert('Tidak ada data absensi yang akan disimpan!');
                return;
            }
            
            Promise.all(promises)
                .then(() => {
                    alert('Semua absensi berhasil disimpan!');
                    location.reload();
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
    </script>
</body>
</html>
