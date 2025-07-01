<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'dosen') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_absensi') {
        try {
            // Process attendance data (simplified for demo)
            $tanggal = $_POST['tanggal'];
            $pertemuan = $_POST['pertemuan'];
            $materi = $_POST['materi'];
            
            // In real implementation, save to database
            $success = "Absensi berhasil disimpan untuk pertemuan ke-{$pertemuan}";
        } catch (Exception $e) {
            $error = "Gagal menyimpan absensi: " . $e->getMessage();
        }
    }
}

// Sample data for demo
$students = [
    ['id' => 1, 'nim' => '2021001', 'nama' => 'John Doe'],
    ['id' => 2, 'nim' => '2021002', 'nama' => 'Jane Smith'],
    ['id' => 3, 'nim' => '2021003', 'nama' => 'Bob Johnson'],
    ['id' => 4, 'nim' => '2021004', 'nama' => 'Alice Brown'],
    ['id' => 5, 'nim' => '2021005', 'nama' => 'Charlie Wilson']
];

$classes = [
    ['id' => 1, 'nama' => 'Pemrograman Web - Kelas A'],
    ['id' => 2, 'nama' => 'Basis Data - Kelas B'],
    ['id' => 3, 'nama' => 'Algoritma - Kelas C']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Absensi - <?php echo APP_NAME; ?></title>
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
        .attendance-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .attendance-btn.hadir {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        .attendance-btn.tidak-hadir {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }
        .attendance-btn.izin {
            background: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }
        .attendance-btn:not(.hadir):not(.tidak-hadir):not(.izin) {
            background: white;
            border-color: #d1d5db;
            color: #6b7280;
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
                    
                    <a href="dosen-mahasiswa.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Daftar Mahasiswa</span>
                    </a>

                    <a href="dosen-jadwal.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Mengajar</span>
                    </a>

                    <a href="dosen-absensi.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Kelola Absensi</h1>
                            <p class="text-gray-600">Input dan kelola kehadiran mahasiswa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Attendance Form -->
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">
                        <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>
                        Input Absensi
                    </h3>
                    
                    <form method="POST" id="attendanceForm">
                        <input type="hidden" name="action" value="save_absensi">
                        
                        <!-- Form Header -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                                <select name="kelas" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo $class['nama']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                                <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pertemuan Ke-</label>
                                <input type="number" name="pertemuan" min="1" max="16" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Materi Perkuliahan</label>
                            <textarea name="materi" rows="3" placeholder="Masukkan materi yang diajarkan..."
                                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                        </div>

                        <!-- Attendance Legend -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Keterangan:</h4>
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="attendance-btn hadir">
                                        <i class="fas fa-check text-sm"></i>
                                    </div>
                                    <span class="text-sm text-gray-700">Hadir</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="attendance-btn tidak-hadir">
                                        <i class="fas fa-times text-sm"></i>
                                    </div>
                                    <span class="text-sm text-gray-700">Tidak Hadir</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="attendance-btn izin">
                                        <i class="fas fa-exclamation text-sm"></i>
                                    </div>
                                    <span class="text-sm text-gray-700">Izin/Sakit</span>
                                </div>
                            </div>
                        </div>

                        <!-- Students List -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">No</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">NIM</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama Mahasiswa</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Kehadiran</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium text-blue-600"><?php echo $student['nim']; ?></td>
                                        <td class="py-3 px-4"><?php echo $student['nama']; ?></td>
                                        <td class="text-center py-3 px-4">
                                            <div class="flex justify-center gap-2">
                                                <button type="button" class="attendance-btn" 
                                                        onclick="setAttendance(<?php echo $student['id']; ?>, 'hadir', this)">
                                                    <i class="fas fa-check text-sm"></i>
                                                </button>
                                                <button type="button" class="attendance-btn" 
                                                        onclick="setAttendance(<?php echo $student['id']; ?>, 'tidak-hadir', this)">
                                                    <i class="fas fa-times text-sm"></i>
                                                </button>
                                                <button type="button" class="attendance-btn" 
                                                        onclick="setAttendance(<?php echo $student['id']; ?>, 'izin', this)">
                                                    <i class="fas fa-exclamation text-sm"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="attendance[<?php echo $student['id']; ?>]" value="">
                                        </td>
                                        <td class="py-3 px-4">
                                            <input type="text" name="keterangan[<?php echo $student['id']; ?>]" 
                                                   placeholder="Keterangan (opsional)" 
                                                   class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end gap-4">
                            <button type="button" onclick="resetForm()" 
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-undo mr-2"></i>
                                Reset
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Simpan Absensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setAttendance(studentId, status, button) {
            // Remove active class from all buttons in the same row
            const row = button.closest('tr');
            const buttons = row.querySelectorAll('.attendance-btn');
            buttons.forEach(btn => {
                btn.classList.remove('hadir', 'tidak-hadir', 'izin');
            });
            
            // Add active class to clicked button
            button.classList.add(status);
            
            // Set hidden input value
            const hiddenInput = row.querySelector(`input[name="attendance[${studentId}]"]`);
            hiddenInput.value = status;
        }

        function resetForm() {
            if (confirm('Yakin ingin mereset form? Semua data yang belum disimpan akan hilang.')) {
                document.getElementById('attendanceForm').reset();
                
                // Reset attendance buttons
                const buttons = document.querySelectorAll('.attendance-btn');
                buttons.forEach(btn => {
                    btn.classList.remove('hadir', 'tidak-hadir', 'izin');
                });
            }
        }

        // Form validation
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const attendanceInputs = document.querySelectorAll('input[name^="attendance["]');
            let hasAttendance = false;
            
            attendanceInputs.forEach(input => {
                if (input.value !== '') {
                    hasAttendance = true;
                }
            });
            
            if (!hasAttendance) {
                e.preventDefault();
                alert('Harap isi minimal satu kehadiran mahasiswa!');
                return false;
            }
        });
    </script>
</body>
</html>
