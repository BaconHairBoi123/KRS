<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'dosen') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

// Get dosen's classes and students
$classes_query = "SELECT DISTINCT k.id_kelas, k.nama_kelas, mk.nama_matakuliah, mk.kode_matakuliah
                  FROM kelas k
                  LEFT JOIN mata_kuliah mk ON k.id_matakuliah = mk.id_matakuliah
                  WHERE k.id_dosen = :dosen_id
                  ORDER BY k.nama_kelas";

$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bindValue(':dosen_id', $_SESSION['user_id']);
$classes_stmt->execute();
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students for selected class
$selected_class = $_GET['kelas'] ?? '';
$students = [];

if ($selected_class) {
    $students_query = "SELECT m.*, krs.tanggal_ambil, krs.status_krs
                       FROM mahasiswa m
                       JOIN krs ON m.id_mahasiswa = krs.id_mahasiswa
                       WHERE krs.id_kelas = :kelas_id AND krs.status_krs = 'Aktif'
                       ORDER BY m.nama";
    
    $students_stmt = $conn->prepare($students_query);
    $students_stmt->bindValue(':kelas_id', $selected_class);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Mahasiswa - <?php echo APP_NAME; ?></title>
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
        .user-info-box {
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca 0%, #ff0080 100%); }
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
                        <h2 class="text-lg font-bold text-gray-800">Portal Dosen</h2>
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
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>
                    
                    <a href="dosen-mahasiswa.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Daftar Mahasiswa</span>
                    </a>

                    <a href="dosen-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Mengajar</span>
                    </a>

                    <a href="dosen-absensi.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-check w-5 mr-3"></i>
                        <span>Kelola Absensi</span>
                    </a>

                    <a href="dosen-nilai.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-star w-5 mr-3"></i>
                        <span>Input Nilai</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Laporan</p>
                    </div>

                    <a href="dosen-laporan.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan Akademik</span>
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
                                <i class="fas fa-chalkboard-teacher text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $userData['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500">Dosen</p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Daftar Mahasiswa</h1>
                            <p class="text-gray-600">Kelola mahasiswa di kelas Anda</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Selection -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pilih Kelas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($classes as $class): ?>
                        <a href="?kelas=<?php echo $class['id_kelas']; ?>" 
                           class="p-4 border-2 rounded-lg transition-all duration-200 <?php echo $selected_class == $class['id_kelas'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'; ?>">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chalkboard text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo $class['nama_kelas']; ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo $class['kode_matakuliah']; ?> - <?php echo $class['nama_matakuliah']; ?></p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Students List -->
            <?php if ($selected_class && !empty($students)): ?>
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-users text-blue-500 mr-2"></i>
                            Daftar Mahasiswa (<?php echo count($students); ?> mahasiswa)
                        </h3>
                        <button onclick="exportData()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Export Excel
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">No</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">NIM</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">No. Telepon</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4"><?php echo $index + 1; ?></td>
                                    <td class="py-3 px-4 font-medium text-blue-600"><?php echo $student['nim']; ?></td>
                                    <td class="py-3 px-4"><?php echo $student['nama']; ?></td>
                                    <td class="py-3 px-4"><?php echo $student['email']; ?></td>
                                    <td class="py-3 px-4"><?php echo $student['nomor_telepon'] ?: '-'; ?></td>
                                    <td class="text-center py-3 px-4">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                            <?php echo ucfirst($student['status_krs']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <button onclick="viewDetail('<?php echo $student['id_mahasiswa']; ?>')" 
                                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif ($selected_class && empty($students)): ?>
            <div class="card">
                <div class="p-6 text-center">
                    <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">Tidak Ada Mahasiswa</h4>
                    <p class="text-gray-500">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Detail Mahasiswa</h3>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="studentDetail">
                        <!-- Student detail will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewDetail(studentId) {
            // Show modal
            document.getElementById('studentModal').classList.remove('hidden');
            
            // Load student detail (simplified for demo)
            document.getElementById('studentDetail').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIM</label>
                            <p class="mt-1 text-sm text-gray-900">2021001</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama</label>
                            <p class="mt-1 text-sm text-gray-900">John Doe</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">john@example.com</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No. Telepon</label>
                            <p class="mt-1 text-sm text-gray-900">08123456789</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                            <p class="mt-1 text-sm text-gray-900">01 Januari 2000</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <p class="mt-1 text-sm text-gray-900">Laki-laki</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <p class="mt-1 text-sm text-gray-900">Jl. Contoh No. 123, Jakarta</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Riwayat Akademik</label>
                        <div class="mt-2 bg-gray-50 p-3 rounded">
                            <p class="text-sm text-gray-700">IPK: 3.75</p>
                            <p class="text-sm text-gray-700">SKS Lulus: 120</p>
                            <p class="text-sm text-gray-700">Semester: 6</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function closeModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        function exportData() {
            alert('Fitur export akan segera tersedia');
        }

        // Close modal when clicking outside
        document.getElementById('studentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
