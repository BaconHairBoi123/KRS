<?php
require_once __DIR__ . '/config/config.php';
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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get payment history with pagination
$query = "SELECT up.*, ut.virtual_account, uper.nama_periode, ut.nominal_tagihan
          FROM ukt_pembayaran up
          JOIN ukt_tagihan ut ON up.tagihan_id = ut.id
          JOIN ukt_periode uper ON ut.periode_id = uper.id
          WHERE up.mahasiswa_id = :mahasiswa_id
          ORDER BY up.tanggal_bayar DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$query = "SELECT COUNT(*) FROM ukt_pembayaran WHERE mahasiswa_id = :mahasiswa_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$total_payments = $stmt->fetchColumn();
$total_pages = ceil($total_payments / $limit);

// Get summary statistics
$query = "SELECT 
            COUNT(*) as total_pembayaran,
            SUM(nominal_bayar) as total_dibayar,
            MIN(tanggal_bayar) as pembayaran_pertama,
            MAX(tanggal_bayar) as pembayaran_terakhir
          FROM ukt_pembayaran 
          WHERE mahasiswa_id = :mahasiswa_id AND status_verifikasi = 'verified'";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran UKT - <?php echo APP_NAME; ?></title>
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
                    
                    <a href="krs-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Pengisian KRS</span>
                    </a>
                    
                    <a href="jadwal-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Jadwal Kuliah</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Keuangan</p>
                    </div>
                    
                    <a href="ukt-pembayaran.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-credit-card w-5 mr-3"></i>
                        <span>Pembayaran UKT</span>
                    </a>
                    
                    <a href="ukt-riwayat.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-history w-5 mr-3"></i>
                        <span>Riwayat Pembayaran</span>
                    </a>

                    <div class="px-3 py-2 mt-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Akademik</p>
                    </div>
                    
                    <a href="profil-soft.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-user w-5 mr-3"></i>
                        <span>Profil</span>
                    </a>
                </nav>

                <!-- User Info -->
                <div class="absolute bottom-4 left-4 right-4">
                    <div class="bg-white bg-opacity-50 rounded-xl p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $_SESSION['nama_lengkap']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $_SESSION['nomor_induk']; ?></p>
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
                            <h1 class="text-2xl font-bold text-gray-800">Riwayat Pembayaran UKT</h1>
                            <p class="text-gray-600">Lihat semua riwayat pembayaran Uang Kuliah Tunggal</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-print"></i>
                                <span>Cetak</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <?php if ($summary['total_pembayaran'] > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Pembayaran</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $summary['total_pembayaran']; ?></h3>
                            <p class="text-xs text-gray-500">Transaksi</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-receipt text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Dibayar</p>
                            <h3 class="text-lg font-bold text-gray-800">Rp <?php echo number_format($summary['total_dibayar'], 0, ',', '.'); ?></h3>
                            <p class="text-xs text-gray-500">Rupiah</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Pembayaran Pertama</p>
                            <h3 class="text-sm font-bold text-gray-800"><?php echo date('d M Y', strtotime($summary['pembayaran_pertama'])); ?></h3>
                            <p class="text-xs text-gray-500">Tanggal</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Pembayaran Terakhir</p>
                            <h3 class="text-sm font-bold text-gray-800"><?php echo date('d M Y', strtotime($summary['pembayaran_terakhir'])); ?></h3>
                            <p class="text-xs text-gray-500">Tanggal</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History Table -->
            <div class="card">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Riwayat Pembayaran</h3>
                            <p class="text-sm text-gray-600">Menampilkan <?php echo count($payment_history); ?> dari <?php echo $total_payments; ?> pembayaran</p>
                        </div>
                        <a href="ukt-pembayaran.php" class="text-purple-600 hover:text-purple-800 font-semibold">
                            <i class="fas fa-plus mr-1"></i>
                            Bayar UKT
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($payment_history)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Bayar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referensi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($payment_history as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo $payment['nama_periode']; ?></div>
                                        <div class="text-sm text-gray-500">VA: <?php echo $payment['virtual_account']; ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('d M Y', strtotime($payment['tanggal_bayar'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($payment['tanggal_bayar'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rp <?php echo number_format($payment['nominal_bayar'], 0, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $payment['metode_pembayaran'])); ?></div>
                                    <?php if ($payment['bank_pengirim']): ?>
                                        <div class="text-sm text-gray-500"><?php echo $payment['bank_pengirim']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono text-gray-900"><?php echo $payment['nomor_referensi']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($payment['status_verifikasi']) {
                                        case 'verified':
                                            $status_class = 'bg-green-100 text-green-800';
                                            $status_text = 'Terverifikasi';
                                            break;
                                        case 'pending':
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                            $status_text = 'Pending';
                                            break;
                                        case 'rejected':
                                            $status_class = 'bg-red-100 text-red-800';
                                            $status_text = 'Ditolak';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="showPaymentDetail('<?php echo $payment['nomor_referensi']; ?>', '<?php echo $payment['nama_periode']; ?>', '<?php echo number_format($payment['nominal_bayar'], 0, ',', '.'); ?>', '<?php echo date('d M Y H:i', strtotime($payment['tanggal_bayar'])); ?>', '<?php echo ucfirst(str_replace('_', ' ', $payment['metode_pembayaran'])); ?>', '<?php echo $payment['bank_pengirim']; ?>')" 
                                            class="text-purple-600 hover:text-purple-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> sampai <?php echo min($page * $limit, $total_payments); ?> dari <?php echo $total_payments; ?> hasil
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-2 text-sm border rounded-md <?php echo $i == $page ? 'bg-purple-600 text-white border-purple-600' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="p-12 text-center">
                    <i class="fas fa-receipt text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Belum Ada Riwayat Pembayaran</h3>
                    <p class="text-gray-500 mb-6">Anda belum melakukan pembayaran UKT</p>
                    <a href="ukt-pembayaran.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>
                        Bayar UKT Sekarang
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Detail Pembayaran</h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600">Nomor Referensi</p>
                    <p class="font-mono font-semibold text-gray-800" id="detailRef"></p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Periode</p>
                    <p class="font-semibold text-gray-800" id="detailPeriode"></p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Nominal</p>
                    <p class="text-xl font-bold text-purple-600" id="detailNominal"></p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Tanggal Pembayaran</p>
                    <p class="font-semibold text-gray-800" id="detailTanggal"></p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Metode Pembayaran</p>
                    <p class="font-semibold text-gray-800" id="detailMetode"></p>
                </div>
                
                <div id="detailBankContainer" class="hidden">
                    <p class="text-sm text-gray-600">Bank</p>
                    <p class="font-semibold text-gray-800" id="detailBank"></p>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <button onclick="closeDetailModal()" 
                        class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Tutup
                </button>
                <button onclick="printReceipt()" 
                        class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>
                    Cetak Bukti
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function showPaymentDetail(ref, periode, nominal, tanggal, metode, bank) {
            document.getElementById('detailRef').textContent = ref;
            document.getElementById('detailPeriode').textContent = periode;
            document.getElementById('detailNominal').textContent = 'Rp ' + nominal;
            document.getElementById('detailTanggal').textContent = tanggal;
            document.getElementById('detailMetode').textContent = metode;
            
            if (bank) {
                document.getElementById('detailBank').textContent = bank;
                document.getElementById('detailBankContainer').classList.remove('hidden');
            } else {
                document.getElementById('detailBankContainer').classList.add('hidden');
            }
            
            document.getElementById('detailModal').classList.remove('hidden');
            document.getElementById('detailModal').classList.add('flex');
        }
        
        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
            document.getElementById('detailModal').classList.remove('flex');
        }
        
        function printReceipt() {
            // Simple print functionality
            window.print();
        }
        
        // Close modal when clicking outside
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailModal();
            }
        });
    </script>
</body>
</html>
