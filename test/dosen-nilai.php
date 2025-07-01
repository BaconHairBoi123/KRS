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
    if ($_POST['action'] == 'save_nilai') {
        try {
            // Process grades data (simplified for demo)
            $kelas = $_POST['kelas'];
            $jenis_nilai = $_POST['jenis_nilai'];
            
            // In real implementation, save to database
            $success = "Nilai {$jenis_nilai} berhasil disimpan";
        } catch (Exception $e) {
            $error = "Gagal menyimpan nilai: " . $e->getMessage();
        }
    }
}

// Sample data for demo
$students = [
    ['id' => 1, 'nim' => '2021001', 'nama' => 'John Doe', 'uts' => 85, 'uas' => 88, 'tugas' => 90, 'kuis' => 87],
    ['id' => 2, 'nim' => '2021002', 'nama' => 'Jane Smith', 'uts' => 78, 'uas' => 82, 'tugas' => 85, 'kuis' => 80],
    ['id' => 3, 'nim' => '2021003', 'nama' => 'Bob Johnson', 'uts' => 92, 'uas' => 90, 'tugas' => 88, 'kuis' => 91],
    ['id' => 4, 'nim' => '2021004', 'nama' => 'Alice Brown', 'uts' => 76, 'uas' => 79, 'tugas' => 82, 'kuis' => 78],
    ['id' => 5, 'nim' => '2021005', 'nama' => 'Charlie Wilson', 'uts' => 89, 'uas' => 85, 'tugas' => 87, 'kuis' => 86]
];

$classes = [
    ['id' => 1, 'nama' => 'Pemrograman Web - Kelas A'],
    ['id' => 2, 'nama' => 'Basis Data - Kelas B'],
    ['id' => 3, 'nama' => 'Algoritma - Kelas C']
];

// Calculate final grades
foreach ($students as &$student) {
    $student['nilai_akhir'] = ($student['uts'] * 0.3) + ($student['uas'] * 0.4) + ($student['tugas'] * 0.2) + ($student['kuis'] * 0.1);
    $student['grade'] = getGrade($student['nilai_akhir']);
}

function getGrade($nilai) {
    if ($nilai >= 85) return 'A';
    if ($nilai >= 80) return 'A-';
    if ($nilai >= 75) return 'B+';
    if ($nilai >= 70) return 'B';
    if ($nilai >= 65) return 'B-';
    if ($nilai >= 60) return 'C+';
    if ($nilai >= 55) return 'C';
    if ($nilai >= 50) return 'C-';
    if ($nilai >= 45) return 'D';
    return 'E';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai - <?php echo APP_NAME; ?></title>
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
        .grade-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .grade-A { background: #10b981; color: white; }
        .grade-A- { background: #059669; color: white; }
        .grade-B { background: #3b82f6; color: white; }
        .grade-B- { background: #2563eb; color: white; }
        .grade-C { background: #f59e0b; color: white; }
        .grade-C- { background: #d97706; color: white; }
        .grade-D { background: #ef4444; color: white; }
        .grade-E { background: #991b1b; color: white; }
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

                    <a href="dosen-absensi.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-clipboard-check w-5 mr-3"></i>
                        <span>Kelola Absensi</span>
                    </a>

                    <a href="dosen-nilai.php" class="nav-link-soft active flex items-center text-white">
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
                            <h1 class="text-2xl font-bold text-gray-800">Input Nilai</h1>
                            <p class="text-gray-600">Kelola nilai UTS, UAS, tugas, dan kuis mahasiswa</p>
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

            <!-- Class Selection -->
            <div class="card mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pilih Kelas dan Jenis Nilai</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                            <select id="kelasSelect" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo $class['nama']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Nilai</label>
                            <select id="jenisNilaiSelect" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Pilih Jenis Nilai</option>
                                <option value="uts">UTS (30%)</option>
                                <option value="uas">UAS (40%)</option>
                                <option value="tugas">Tugas (20%)</option>
                                <option value="kuis">Kuis (10%)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grades Table -->
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>
                            Daftar Nilai Mahasiswa
                        </h3>
                        <div class="flex gap-2">
                            <button onclick="calculateAll()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-calculator mr-2"></i>
                                Hitung Semua
                            </button>
                            <button onclick="exportGrades()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Export
                            </button>
                        </div>
                    </div>
                    
                    <form method="POST" id="gradesForm">
                        <input type="hidden" name="action" value="save_nilai">
                        <input type="hidden" name="kelas" id="selectedKelas">
                        <input type="hidden" name="jenis_nilai" id="selectedJenisNilai">
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">No</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">NIM</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">UTS<br><small>(30%)</small></th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">UAS<br><small>(40%)</small></th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Tugas<br><small>(20%)</small></th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Kuis<br><small>(10%)</small></th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Nilai Akhir</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium text-blue-600"><?php echo $student['nim']; ?></td>
                                        <td class="py-3 px-4"><?php echo $student['nama']; ?></td>
                                        <td class="text-center py-3 px-4">
                                            <input type="number" min="0" max="100" step="0.1" 
                                                   value="<?php echo $student['uts']; ?>"
                                                   name="uts[<?php echo $student['id']; ?>]"
                                                   class="w-16 p-2 border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   onchange="calculateFinal(<?php echo $student['id']; ?>)">
                                        </td>
                                        <td class="text-center py-3 px-4">
                                            <input type="number" min="0" max="100" step="0.1" 
                                                   value="<?php echo $student['uas']; ?>"
                                                   name="uas[<?php echo $student['id']; ?>]"
                                                   class="w-16 p-2 border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   onchange="calculateFinal(<?php echo $student['id']; ?>)">
                                        </td>
                                        <td class="text-center py-3 px-4">
                                            <input type="number" min="0" max="100" step="0.1" 
                                                   value="<?php echo $student['tugas']; ?>"
                                                   name="tugas[<?php echo $student['id']; ?>]"
                                                   class="w-16 p-2 border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   onchange="calculateFinal(<?php echo $student['id']; ?>)">
                                        </td>
                                        <td class="text-center py-3 px-4">
                                            <input type="number" min="0" max="100" step="0.1" 
                                                   value="<?php echo $student['kuis']; ?>"
                                                   name="kuis[<?php echo $student['id']; ?>]"
                                                   class="w-16 p-2 border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   onchange="calculateFinal(<?php echo $student['id']; ?>)">
                                        </td>
                                        <td class="text-center py-3 px-4">
                                            <span id="final_<?php echo $student['id']; ?>" class="font-semibold text-blue-600">
                                                <?php echo number_format($student['nilai_akhir'], 1); ?>
                                            </span>
                                        </td>
                                        <td class="text-center py-3 px-4">
                                            <span id="grade_<?php echo $student['id']; ?>" class="grade-badge grade-<?php echo $student['grade']; ?>">
                                                <?php echo $student['grade']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end gap-4">
                            <button type="button" onclick="resetGrades()" 
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-undo mr-2"></i>
                                Reset
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Simpan Nilai
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateFinal(studentId) {
            const uts = parseFloat(document.querySelector(`input[name="uts[${studentId}]"]`).value) || 0;
            const uas = parseFloat(document.querySelector(`input[name="uas[${studentId}]"]`).value) || 0;
            const tugas = parseFloat(document.querySelector(`input[name="tugas[${studentId}]"]`).value) || 0;
            const kuis = parseFloat(document.querySelector(`input[name="kuis[${studentId}]"]`).value) || 0;
            
            const final = (uts * 0.3) + (uas * 0.4) + (tugas * 0.2) + (kuis * 0.1);
            const grade = getGrade(final);
            
            document.getElementById(`final_${studentId}`).textContent = final.toFixed(1);
            
            const gradeElement = document.getElementById(`grade_${studentId}`);
            gradeElement.textContent = grade;
            gradeElement.className = `grade-badge grade-${grade}`;
        }

        function getGrade(nilai) {
            if (nilai >= 85) return 'A';
            if (nilai >= 80) return 'A-';
            if (nilai >= 75) return 'B+';
            if (nilai >= 70) return 'B';
            if (nilai >= 65) return 'B-';
            if (nilai >= 60) return 'C+';
            if (nilai >= 55) return 'C';
            if (nilai >= 50) return 'C-';
            if (nilai >= 45) return 'D';
            return 'E';
        }

        function calculateAll() {
            const studentIds = [1, 2, 3, 4, 5]; // In real implementation, get from PHP
            studentIds.forEach(id => calculateFinal(id));
        }

        function resetGrades() {
            if (confirm('Yakin ingin mereset semua nilai? Data yang belum disimpan akan hilang.')) {
                location.reload();
            }
        }

        function exportGrades() {
            alert('Fitur export akan segera tersedia');
        }

        // Update hidden inputs when selects change
        document.getElementById('kelasSelect').addEventListener('change', function() {
            document.getElementById('selectedKelas').value = this.value;
        });

        document.getElementById('jenisNilaiSelect').addEventListener('change', function() {
            document.getElementById('selectedJenisNilai').value = this.value;
        });
    </script>
</body>
</html>
