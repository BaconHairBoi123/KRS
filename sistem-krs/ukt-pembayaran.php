<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard-soft.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get mahasiswa ID
$query = "SELECT id, kelompok_ukt FROM mahasiswa WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
$mahasiswa_id = $mahasiswa['id'];

// Handle payment submission
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'bayar_ukt') {
    $tagihan_id = $_POST['tagihan_id'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $bank_pengirim = $_POST['bank_pengirim'] ?? '';
    
    try {
        // Generate nomor referensi
        $nomor_referensi = 'UKT' . date('Ymd') . rand(1000, 9999);
        
        // Get tagihan info
        $query = "SELECT * FROM ukt_tagihan WHERE id = :tagihan_id AND mahasiswa_id = :mahasiswa_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':tagihan_id', $tagihan_id);
        $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
        $stmt->execute();
        $tagihan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tagihan) {
            // Insert pembayaran
            $query = "INSERT INTO ukt_pembayaran (tagihan_id, mahasiswa_id, nominal_bayar, metode_pembayaran, bank_pengirim, nomor_referensi, tanggal_bayar, status_verifikasi) 
                     VALUES (:tagihan_id, :mahasiswa_id, :nominal_bayar, :metode_pembayaran, :bank_pengirim, :nomor_referensi, NOW(), 'verified')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tagihan_id', $tagihan_id);
            $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $stmt->bindParam(':nominal_bayar', $tagihan['total_tagihan']);
            $stmt->bindParam(':metode_pembayaran', $metode_pembayaran);
            $stmt->bindParam(':bank_pengirim', $bank_pengirim);
            $stmt->bindParam(':nomor_referensi', $nomor_referensi);
            $stmt->execute();
            
            // Update status tagihan
            $query = "UPDATE ukt_tagihan SET status_tagihan = 'lunas' WHERE id = :tagihan_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tagihan_id', $tagihan_id);
            $stmt->execute();
            
            // Add notification
            $query = "INSERT INTO ukt_notifikasi (mahasiswa_id, judul, pesan, tipe) 
                     VALUES (:mahasiswa_id, 'Pembayaran UKT Berhasil', 'Pembayaran UKT Anda telah berhasil diverifikasi dengan nomor referensi: $nomor_referensi', 'info')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
            $stmt->execute();
            
            $success = "Pembayaran UKT berhasil! Nomor referensi: $nomor_referensi";
        }
    } catch (Exception $e) {
        $error = "Gagal memproses pembayaran: " . $e->getMessage();
    }
}

// Get current tagihan
$query = "SELECT ut.*, up.nama_periode, up.tanggal_akhir, utr.nominal as tarif_nominal, utr.kelompok_ukt,
                 CASE 
                     WHEN ut.status_tagihan = 'lunas' THEN 'Lunas'
                     WHEN CURDATE() > ut.tanggal_jatuh_tempo THEN 'Terlambat'
                     ELSE 'Belum Bayar'
                 END as status_display,
                 DATEDIFF(ut.tanggal_jatuh_tempo, CURDATE()) as hari_tersisa
          FROM ukt_tagihan ut
          JOIN ukt_periode up ON ut.periode_id = up.id
          JOIN ukt_tarif utr ON ut.tarif_id = utr.id
          WHERE ut.mahasiswa_id = :mahasiswa_id
          ORDER BY up.tanggal_akhir DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$tagihan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment history
$query = "SELECT up.*, ut.virtual_account, uper.nama_periode
          FROM ukt_pembayaran up
          JOIN ukt_tagihan ut ON up.tagihan_id = ut.id
          JOIN ukt_periode uper ON ut.periode_id = uper.id
          WHERE up.mahasiswa_id = :mahasiswa_id
          ORDER BY up.tanggal_bayar DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
$stmt->execute();
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran UKT - <?php echo APP_NAME; ?></title>
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
        .payment-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .payment-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-lunas { background: #d4edda; color: #155724; }
        .status-belum-bayar { background: #fff3cd; color: #856404; }
        .status-terlambat { background: #f8d7da; color: #721c24; }
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
                    
                    <a href="ukt-pembayaran.php" class="nav-link-soft active flex items-center text-white">
                        <i class="fas fa-credit-card w-5 mr-3"></i>
                        <span>Pembayaran UKT</span>
                    </a>
                    
                    <a href="ukt-riwayat.php" class="nav-link-soft flex items-center text-gray-700 hover:text-gray-900">
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
                            <h1 class="text-2xl font-bold text-gray-800">Pembayaran UKT</h1>
                            <p class="text-gray-600">Kelola pembayaran Uang Kuliah Tunggal Anda</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="theme-toggle-container"></div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Kelompok UKT</p>
                                <p class="text-lg font-bold text-purple-600"><?php echo $mahasiswa['kelompok_ukt']; ?></p>
                            </div>
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

            <!-- Tagihan UKT -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <?php foreach ($tagihan_list as $tagihan): ?>
                <div class="payment-card p-6 relative">
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold"><?php echo $tagihan['nama_periode']; ?></h3>
                                <p class="text-sm opacity-90">Virtual Account: <?php echo $tagihan['virtual_account']; ?></p>
                            </div>
                            <span class="status-badge <?php echo 'status-' . strtolower(str_replace(' ', '-', $tagihan['status_display'])); ?>">
                                <?php echo $tagihan['status_display']; ?>
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-sm opacity-90">Total Tagihan</p>
                            <p class="text-2xl font-bold">Rp <?php echo number_format($tagihan['total_tagihan'], 0, ',', '.'); ?></p>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm opacity-90">Jatuh Tempo</p>
                                <p class="font-semibold"><?php echo date('d M Y', strtotime($tagihan['tanggal_jatuh_tempo'])); ?></p>
                                <?php if ($tagihan['hari_tersisa'] > 0 && $tagihan['status_tagihan'] != 'lunas'): ?>
                                    <p class="text-xs opacity-75"><?php echo $tagihan['hari_tersisa']; ?> hari lagi</p>
                                <?php elseif ($tagihan['hari_tersisa'] < 0 && $tagihan['status_tagihan'] != 'lunas'): ?>
                                    <p class="text-xs text-red-200">Terlambat <?php echo abs($tagihan['hari_tersisa']); ?> hari</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($tagihan['status_tagihan'] != 'lunas'): ?>
                                <button onclick="openPaymentModal(<?php echo $tagihan['id']; ?>, '<?php echo $tagihan['nama_periode']; ?>', <?php echo $tagihan['total_tagihan']; ?>)" 
                                        class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Bayar Sekarang
                                </button>
                            <?php else: ?>
                                <div class="bg-green-500 text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-check mr-2"></i>
                                    Lunas
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Payments -->
            <?php if (!empty($payment_history)): ?>
            <div class="card">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Pembayaran Terbaru</h3>
                    <p class="text-sm text-gray-600">5 pembayaran terakhir</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach (array_slice($payment_history, 0, 5) as $payment): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo $payment['nama_periode']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo date('d M Y H:i', strtotime($payment['tanggal_bayar'])); ?></p>
                                    <p class="text-xs text-gray-500">Ref: <?php echo $payment['nomor_referensi']; ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">Rp <?php echo number_format($payment['nominal_bayar'], 0, ',', '.'); ?></p>
                                <p class="text-sm text-green-600"><?php echo ucfirst($payment['metode_pembayaran']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="ukt-riwayat.php" class="text-purple-600 hover:text-purple-800 font-semibold">
                            Lihat Semua Riwayat <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Pembayaran UKT</h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="paymentForm">
                <input type="hidden" name="action" value="bayar_ukt">
                <input type="hidden" name="tagihan_id" id="modalTagihanId">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Periode</p>
                    <p class="font-semibold text-gray-800" id="modalPeriode"></p>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Total Pembayaran</p>
                    <p class="text-2xl font-bold text-purple-600" id="modalNominal"></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Metode Pembayaran</label>
                    <select name="metode_pembayaran" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Pilih Metode Pembayaran</option>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="virtual_account">Virtual Account</option>
                        <option value="mobile_banking">Mobile Banking</option>
                        <option value="atm">ATM</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bank Pengirim (Opsional)</label>
                    <select name="bank_pengirim" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Pilih Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="CIMB Niaga">CIMB Niaga</option>
                        <option value="Danamon">Danamon</option>
                        <option value="Permata">Permata</option>
                    </select>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-info-circle text-yellow-600 mr-2 mt-0.5"></i>
                        <div class="text-sm text-yellow-800">
                            <p class="font-semibold">Simulasi Pembayaran</p>
                            <p>Ini adalah simulasi pembayaran untuk demo. Pembayaran akan langsung diverifikasi.</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closePaymentModal()" 
                            class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>
                        Bayar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/theme-toggle.js"></script>
    <script>
        function openPaymentModal(tagihanId, periode, nominal) {
            document.getElementById('modalTagihanId').value = tagihanId;
            document.getElementById('modalPeriode').textContent = periode;
            document.getElementById('modalNominal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(nominal);
            document.getElementById('paymentModal').classList.remove('hidden');
            document.getElementById('paymentModal').classList.add('flex');
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>
