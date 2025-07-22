<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

if (getUserRole() != 'mahasiswa') {
    redirect('dashboard.php');
}

$database = new Database();
$conn = $database->getConnection();
$userData = getUserData();

$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_kelas') {
        $id_kelas = $_POST['id_kelas'];
        
        try {
            // Check if already enrolled
            $check_query = "SELECT COUNT(*) FROM krs WHERE id_mahasiswa = :id_mahasiswa AND id_kelas = :id_kelas";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            $check_stmt->bindValue(':id_kelas', $id_kelas);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Anda sudah mengambil kelas ini");
            }
            
            // Check class capacity
            $capacity_query = "SELECT k.kapasitas, COUNT(krs.id_krs) as terisi 
                              FROM kelas k 
                              LEFT JOIN krs ON k.id_kelas = krs.id_kelas AND krs.status_krs = 'Aktif'
                              WHERE k.id_kelas = :id_kelas 
                              GROUP BY k.id_kelas";
            $capacity_stmt = $conn->prepare($capacity_query);
            $capacity_stmt->bindValue(':id_kelas', $id_kelas);
            $capacity_stmt->execute();
            $capacity_data = $capacity_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($capacity_data && $capacity_data['terisi'] >= $capacity_data['kapasitas']) {
                throw new Exception("Kelas sudah penuh");
            }
            
            // Insert KRS
            $insert_query = "INSERT INTO krs (id_mahasiswa, id_kelas, tanggal_ambil, status_krs) 
                            VALUES (:id_mahasiswa, :id_kelas, NOW(), 'Aktif')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            $insert_stmt->bindValue(':id_kelas', $id_kelas);
            $insert_stmt->execute();
            
            $success = "Mata kuliah berhasil ditambahkan ke KRS";
        } catch (Exception $e) {
            $error = "Gagal menambahkan mata kuliah: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'remove_kelas') {
        $id_krs = $_POST['id_krs'];
        
        try {
            $delete_query = "DELETE FROM krs WHERE id_krs = :id_krs AND id_mahasiswa = :id_mahasiswa";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindValue(':id_krs', $id_krs);
            $delete_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
            $delete_stmt->execute();
            
            $success = "Mata kuliah berhasil dihapus dari KRS";
        } catch (Exception $e) {
            $error = "Gagal menghapus mata kuliah: " . $e->getMessage();
        }
    }
}

// Get current active semester
$semester_query = "SELECT * FROM tahun_akademik WHERE status = 'Aktif' LIMIT 1";
$semester_stmt = $conn->prepare($semester_query);
$semester_stmt->execute();
$active_semester = $semester_stmt->fetch(PDO::FETCH_ASSOC);

if (!$active_semester) {
    $error = "Tidak ada semester aktif saat ini";
}

// Get student's current KRS
$krs_query = "SELECT krs.*, mk.nama_matakuliah, mk.kode_matakuliah, mk.sks, 
                     d.nama_dosen, d.gelar, kl.nama_kelas, ta.tahun_akademik, ta.semester_akademik
              FROM krs 
              JOIN kelas kl ON krs.id_kelas = kl.id_kelas
              JOIN mata_kuliah mk ON kl.id_matakuliah = mk.id_matakuliah
              JOIN dosen d ON kl.id_dosen = d.id_dosen
              JOIN tahun_akademik ta ON kl.id_tahun_akademik = ta.id_tahun_akademik
              WHERE krs.id_mahasiswa = :id_mahasiswa AND krs.status_krs = 'Aktif'
              AND ta.status = 'Aktif'";

$krs_stmt = $conn->prepare($krs_query);
$krs_stmt->bindValue(':id_mahasiswa', $_SESSION['user_id']);
$krs_stmt->execute();
$current_krs = $krs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total SKS
$total_sks = array_sum(array_column($current_krs, 'sks'));

// Get available classes
$enrolled_classes = array_column($current_krs, 'id_kelas');
$enrolled_classes_str = empty($enrolled_classes) ? '0' : implode(',', $enrolled_classes);

$available_query = "SELECT kl.*, mk.nama_matakuliah, mk.kode_matakuliah, mk.sks, mk.semester,
                           d.nama_dosen, d.gelar, ta.tahun_akademik, ta.semester_akademik,
                           COUNT(krs.id_krs) as terisi
                    FROM kelas kl
                    JOIN mata_kuliah mk ON kl.id_matakuliah = mk.id_matakuliah
                    JOIN dosen d ON kl.id_dosen = d.id_dosen
                    JOIN tahun_akademik ta ON kl.id_tahun_akademik = ta.id_tahun_akademik
                    LEFT JOIN krs ON kl.id_kelas = krs.id_kelas AND krs.status_krs = 'Aktif'
                    WHERE ta.status = 'Aktif' AND kl.id_kelas NOT IN ($enrolled_classes_str)
                    GROUP BY kl.id_kelas
                    ORDER BY mk.kode_matakuliah, kl.nama_kelas";

$available_stmt = $conn->prepare($available_query);
$available_stmt->execute();
$available_classes = $available_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Rencana Studi - Universitas Touhou Indonesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .semester-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 600;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(79, 172, 254, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4facfe;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #276749;
        }

        .alert-error {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f7fafc;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-primary {
            background: rgba(79, 172, 254, 0.1);
            color: #4facfe;
        }

        .badge-success {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }

        .badge-warning {
            background: rgba(237, 137, 54, 0.1);
            color: #ed8936;
        }

        .course-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .course-code {
            font-weight: 600;
            color: #4facfe;
        }

        .course-name {
            color: #2d3748;
        }

        .course-details {
            font-size: 0.85rem;
            color: #718096;
        }

        .capacity-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .capacity-bar {
            width: 60px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }

        .capacity-fill {
            height: 100%;
            background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
            transition: width 0.3s ease;
        }

        .capacity-full .capacity-fill {
            background: linear-gradient(90deg, #ff6b6b 0%, #ee5a52 100%);
        }

        .user-info {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            color: #e53e3e;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(229, 62, 62, 0.1);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-bar {
                flex-direction: column;
            }
            
            .user-info {
                position: static;
                margin-bottom: 20px;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- User Info -->
    <div class="user-info">
        <div class="user-avatar">
            <?php echo strtoupper(substr($userData['nama'], 0, 1)); ?>
        </div>
        <div>
            <div style="font-weight: 600; color: #2d3748;"><?php echo $userData['nama']; ?></div>
            <div style="font-size: 0.85rem; color: #718096;"><?php echo $userData['nim']; ?></div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Kartu Rencana Studi</h1>
            <p>Sistem Pengambilan Mata Kuliah - Universitas Touhou Indonesia</p>
            
            <?php if ($active_semester): ?>
                <div class="semester-info">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo $active_semester['tahun_akademik']; ?> - Semester <?php echo $active_semester['semester_akademik']; ?>
                </div>
            <?php endif; ?>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($current_krs); ?></div>
                    <div class="stat-label">Mata Kuliah Diambil</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_sks; ?></div>
                    <div class="stat-label">Total SKS</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($available_classes); ?></div>
                    <div class="stat-label">Kelas Tersedia</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Current KRS -->
        <?php if (!empty($current_krs)): ?>
        <div class="card">
            <h2><i class="fas fa-list-check"></i> Mata Kuliah yang Diambil</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Kelas</th>
                            <th>Dosen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_krs as $krs): ?>
                        <tr>
                            <td>
                                <span class="course-code"><?php echo $krs['kode_matakuliah']; ?></span>
                            </td>
                            <td>
                                <div class="course-info">
                                    <div class="course-name"><?php echo $krs['nama_matakuliah']; ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo $krs['sks']; ?> SKS</span>
                            </td>
                            <td><?php echo $krs['nama_kelas']; ?></td>
                            <td>
                                <div>
                                    <div><?php echo $krs['nama_dosen']; ?></div>
                                    <div class="course-details"><?php echo $krs['gelar']; ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-success"><?php echo $krs['status_krs']; ?></span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_kelas">
                                    <input type="hidden" name="id_krs" value="<?php echo $krs['id_krs']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Classes -->
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> Kelas yang Tersedia</h2>
            <?php if (empty($available_classes)): ?>
                <p style="text-align: center; color: #718096; padding: 40px;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                    Tidak ada kelas yang tersedia untuk semester ini.
                </p>
            <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Kelas</th>
                            <th>Dosen</th>
                            <th>Kapasitas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_classes as $class): ?>
                        <?php 
                        $is_full = $class['terisi'] >= $class['kapasitas'];
                        $capacity_percentage = ($class['terisi'] / $class['kapasitas']) * 100;
                        ?>
                        <tr>
                            <td>
                                <span class="course-code"><?php echo $class['kode_matakuliah']; ?></span>
                            </td>
                            <td>
                                <div class="course-info">
                                    <div class="course-name"><?php echo $class['nama_matakuliah']; ?></div>
                                    <div class="course-details">Semester <?php echo $class['semester']; ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo $class['sks']; ?> SKS</span>
                            </td>
                            <td><?php echo $class['nama_kelas']; ?></td>
                            <td>
                                <div>
                                    <div><?php echo $class['nama_dosen']; ?></div>
                                    <div class="course-details"><?php echo $class['gelar']; ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="capacity-info">
                                    <span><?php echo $class['terisi']; ?>/<?php echo $class['kapasitas']; ?></span>
                                    <div class="capacity-bar <?php echo $is_full ? 'capacity-full' : ''; ?>">
                                        <div class="capacity-fill" style="width: <?php echo $capacity_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($is_full): ?>
                                    <button class="btn btn-primary" disabled>
                                        <i class="fas fa-times"></i> Penuh
                                    </button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="add_kelas">
                                        <input type="hidden" name="id_kelas" value="<?php echo $class['id_kelas']; ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Ambil
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
