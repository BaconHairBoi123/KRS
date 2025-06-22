<?php
require_once 'config/config.php';
requireLogin();

// Get user data based on role
$database = new Database();
$conn = $database->getConnection();

$stats = [];
if (getUserRole() == 'mahasiswa') {
    // Get mahasiswa stats
    $query = "SELECT m.*, 
                     COUNT(k.id) as total_mk,
                     SUM(mk.sks) as total_sks
              FROM mahasiswa m
              LEFT JOIN krs k ON m.id = k.mahasiswa_id AND k.status = 'approved'
              LEFT JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
              LEFT JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
              WHERE m.user_id = :user_id
              GROUP BY m.id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $mahasiswa_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_sks' => $mahasiswa_data['total_sks'] ?? 0,
        'total_mk' => $mahasiswa_data['total_mk'] ?? 0,
        'ipk' => $mahasiswa_data['ipk'] ?? 0,
        'semester' => $mahasiswa_data['semester_aktif'] ?? 1
    ];
}

// Get announcements
$query = "SELECT * FROM (
    SELECT 'Pengumuman' as type, 'Batas Akhir Pengisian KRS' as title, 
           'Pengisian KRS semester genap berakhir pada 15 Januari 2024' as description,
           '2 hari lagi' as deadline, 1 as urgent
    UNION ALL
    SELECT 'Pengumuman' as type, 'Pembayaran UKT Semester Genap' as title,
           'Jangan lupa melakukan pembayaran UKT sebelum batas waktu' as description,
           '5 hari lagi' as deadline, 0 as urgent
    UNION ALL  
    SELECT 'Pengumuman' as type, 'Jadwal Ujian Tengah Semester' as title,
           'Jadwal UTS telah dirilis, silakan cek di menu jadwal' as description,
           '1 minggu lagi' as deadline, 0 as urgent
) as announcements";

$stmt = $conn->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="flex h-screen">
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
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-blue-600 bg-blue-50 border-r-2 border-blue-600">
                    <i class="fas fa-home w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <?php if (getUserRole() == 'mahasiswa'): ?>
                <a href="krs.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-book w-5"></i>
                    <span class="ml-3">Pengisian KRS</span>
                </a>
                <a href="jadwal.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-calendar w-5"></i>
                    <span class="ml-3">Jadwal Kuliah</span>
                </a>
                <a href="transkrip.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-file-alt w-5"></i>
                    <span class="ml-3">Transkrip Nilai</span>
                </a>
                <?php endif; ?>
                
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                </div>
                <a href="profil.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-user w-5"></i>
                    <span class="ml-3">Profil</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-4">
                <div class="flex items-center gap-3 bg-gray-100 p-3 rounded-lg">
                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo $_SESSION['nama_lengkap']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $_SESSION['nomor_induk']; ?></p>
                    </div>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Dashboard KRS</h1>
                        <p class="text-sm text-gray-500">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="flex items-center gap-2 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50">
                            <i class="fas fa-bell"></i>
                            Notifikasi
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (getUserRole() == 'mahasiswa'): ?>
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total SKS Diambil</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_sks']; ?></p>
                                <p class="text-sm text-gray-500">SKS</p>
                            </div>
                            <div class="p-3 bg-blue-600 rounded-lg">
                                <i class="fas fa-book text-white"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Mata Kuliah Terdaftar</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_mk']; ?></p>
                                <p class="text-sm text-gray-500">MK</p>
                            </div>
                            <div class="p-3 bg-green-600 rounded-lg">
                                <i class="fas fa-calendar text-white"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">IPK Sementara</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['ipk'], 2); ?></p>
                                <p class="text-sm text-green-600">+0.12</p>
                            </div>
                            <div class="p-3 bg-purple-600 rounded-lg">
                                <i class="fas fa-graduation-cap text-white"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Semester Aktif</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['semester']; ?></p>
                                <p class="text-sm text-gray-500">Semester</p>
                            </div>
                            <div class="p-3 bg-orange-600 rounded-lg">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Quick Actions -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Aksi Cepat</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <?php if (getUserRole() == 'mahasiswa'): ?>
                            <a href="krs.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                                <div class="p-2 bg-blue-600 rounded-lg mb-2">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                                <span class="text-xs">Isi KRS</span>
                            </a>
                            <a href="jadwal.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                                <div class="p-2 bg-green-600 rounded-lg mb-2">
                                    <i class="fas fa-calendar text-white"></i>
                                </div>
                                <span class="text-xs">Lihat Jadwal</span>
                            </a>
                            <a href="transkrip.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                                <div class="p-2 bg-purple-600 rounded-lg mb-2">
                                    <i class="fas fa-file-alt text-white"></i>
                                </div>
                                <span class="text-xs">Transkrip</span>
                            </a>
                            <?php endif; ?>
                            <a href="profil.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                                <div class="p-2 bg-orange-600 rounded-lg mb-2">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <span class="text-xs">Profil</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Announcements -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Pengumuman Akademik</h3>
                        <div class="space-y-4">
                            <?php foreach ($announcements as $announcement): ?>
                            <div class="p-4 rounded-lg border-l-4 <?php echo $announcement['urgent'] ? 'border-red-500 bg-red-50' : 'border-blue-500 bg-blue-50'; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900"><?php echo $announcement['title']; ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo $announcement['description']; ?></p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $announcement['urgent'] ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $announcement['deadline']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
