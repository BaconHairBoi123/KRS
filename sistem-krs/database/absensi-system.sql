-- Sistem Absensi Mahasiswa
-- Tambahkan tabel untuk sistem absensi

-- Tabel untuk jadwal pertemuan kuliah
CREATE TABLE IF NOT EXISTS jadwal_pertemuan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    jadwal_kuliah_id INT NOT NULL,
    pertemuan_ke INT NOT NULL,
    tanggal_pertemuan DATE NOT NULL,
    waktu_mulai TIME NOT NULL,
    waktu_selesai TIME NOT NULL,
    materi VARCHAR(255),
    dosen_pengampu VARCHAR(100),
    ruangan VARCHAR(50),
    status_pertemuan ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    kode_absensi VARCHAR(10) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jadwal_kuliah_id) REFERENCES jadwal_kuliah(id) ON DELETE CASCADE,
    INDEX idx_jadwal_pertemuan (jadwal_kuliah_id, tanggal_pertemuan)
);

-- Tabel untuk record absensi mahasiswa
CREATE TABLE IF NOT EXISTS absensi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    jadwal_pertemuan_id INT NOT NULL,
    mahasiswa_id INT NOT NULL,
    status_kehadiran ENUM('hadir', 'sakit', 'izin', 'alfa', 'terlambat') NOT NULL,
    waktu_absen DATETIME,
    keterangan TEXT,
    bukti_dokumen VARCHAR(255), -- untuk surat sakit/izin
    latitude DECIMAL(10, 8), -- untuk absensi berbasis lokasi
    longitude DECIMAL(11, 8),
    ip_address VARCHAR(45),
    user_agent TEXT,
    verified_by INT, -- admin/dosen yang verifikasi
    verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jadwal_pertemuan_id) REFERENCES jadwal_pertemuan(id) ON DELETE CASCADE,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_absensi (jadwal_pertemuan_id, mahasiswa_id),
    INDEX idx_absensi_mahasiswa (mahasiswa_id),
    INDEX idx_absensi_pertemuan (jadwal_pertemuan_id),
    INDEX idx_absensi_status (status_kehadiran)
);

-- Tabel untuk pengaturan absensi
CREATE TABLE IF NOT EXISTS absensi_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    jadwal_kuliah_id INT NOT NULL,
    batas_keterlambatan INT DEFAULT 15, -- dalam menit
    radius_absensi INT DEFAULT 100, -- dalam meter
    latitude_kampus DECIMAL(10, 8),
    longitude_kampus DECIMAL(11, 8),
    require_location BOOLEAN DEFAULT FALSE,
    auto_alfa_after_minutes INT DEFAULT 30,
    min_kehadiran_persen INT DEFAULT 75,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jadwal_kuliah_id) REFERENCES jadwal_kuliah(id) ON DELETE CASCADE,
    UNIQUE KEY unique_settings (jadwal_kuliah_id)
);

-- Insert sample jadwal pertemuan
INSERT INTO jadwal_pertemuan (jadwal_kuliah_id, pertemuan_ke, tanggal_pertemuan, waktu_mulai, waktu_selesai, materi, dosen_pengampu, ruangan, kode_absensi) VALUES
-- Untuk Algoritma dan Pemrograman (jadwal_kuliah_id = 1)
(1, 1, '2024-02-05', '08:00:00', '09:40:00', 'Pengenalan Algoritma', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG001'),
(1, 2, '2024-02-12', '08:00:00', '09:40:00', 'Struktur Data Dasar', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG002'),
(1, 3, '2024-02-19', '08:00:00', '09:40:00', 'Array dan String', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG003'),
(1, 4, '2024-02-26', '08:00:00', '09:40:00', 'Sorting Algorithm', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG004'),
(1, 5, '2024-03-05', '08:00:00', '09:40:00', 'Searching Algorithm', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG005'),
(1, 6, '2024-03-12', '08:00:00', '09:40:00', 'Recursion', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG006'),
(1, 7, '2024-03-19', '08:00:00', '09:40:00', 'Dynamic Programming', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG007'),
(1, 8, '2024-03-26', '08:00:00', '09:40:00', 'UTS', 'Dr. Ahmad Fauzi', 'Lab Komputer 1', 'ALG008'),

-- Untuk Basis Data (jadwal_kuliah_id = 2)
(2, 1, '2024-02-06', '10:00:00', '11:40:00', 'Pengenalan Database', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD001'),
(2, 2, '2024-02-13', '10:00:00', '11:40:00', 'Entity Relationship Diagram', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD002'),
(2, 3, '2024-02-20', '10:00:00', '11:40:00', 'Normalisasi Database', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD003'),
(2, 4, '2024-02-27', '10:00:00', '11:40:00', 'SQL Dasar', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD004'),
(2, 5, '2024-03-06', '10:00:00', '11:40:00', 'SQL Lanjutan', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD005'),
(2, 6, '2024-03-13', '10:00:00', '11:40:00', 'Stored Procedure', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD006'),
(2, 7, '2024-03-20', '10:00:00', '11:40:00', 'Database Security', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD007'),
(2, 8, '2024-03-27', '10:00:00', '11:40:00', 'UTS', 'Prof. Siti Nurhaliza', 'Ruang 201', 'BD008');

-- Insert sample absensi data
INSERT INTO absensi (jadwal_pertemuan_id, mahasiswa_id, status_kehadiran, waktu_absen, keterangan) VALUES
-- Mahasiswa 1 (ID: 1) - Algoritma
(1, 1, 'hadir', '2024-02-05 08:05:00', 'Tepat waktu'),
(2, 1, 'hadir', '2024-02-12 08:10:00', 'Sedikit terlambat'),
(3, 1, 'sakit', '2024-02-19 08:00:00', 'Demam tinggi'),
(4, 1, 'hadir', '2024-02-26 07:55:00', 'Datang lebih awal'),
(5, 1, 'terlambat', '2024-03-05 08:20:00', 'Macet di jalan'),
(6, 1, 'hadir', '2024-03-12 08:00:00', 'Tepat waktu'),
(7, 1, 'izin', '2024-03-19 08:00:00', 'Acara keluarga'),
(8, 1, 'hadir', '2024-03-26 08:00:00', 'UTS'),

-- Mahasiswa 1 - Basis Data
(9, 1, 'hadir', '2024-02-06 10:00:00', 'Tepat waktu'),
(10, 1, 'hadir', '2024-02-13 10:05:00', 'Sedikit terlambat'),
(11, 1, 'hadir', '2024-02-20 09:55:00', 'Datang lebih awal'),
(12, 1, 'alfa', '2024-02-27 10:00:00', 'Tidak hadir tanpa keterangan'),
(13, 1, 'hadir', '2024-03-06 10:00:00', 'Tepat waktu'),
(14, 1, 'hadir', '2024-03-13 10:00:00', 'Tepat waktu'),
(15, 1, 'sakit', '2024-03-20 10:00:00', 'Flu'),
(16, 1, 'hadir', '2024-03-27 10:00:00', 'UTS'),

-- Mahasiswa 2 (ID: 2) - Sample data
(1, 2, 'hadir', '2024-02-05 08:00:00', 'Tepat waktu'),
(2, 2, 'terlambat', '2024-02-12 08:25:00', 'Bangun kesiangan'),
(3, 2, 'hadir', '2024-02-19 08:00:00', 'Tepat waktu'),
(4, 2, 'alfa', '2024-02-26 08:00:00', 'Tidak hadir'),
(5, 2, 'hadir', '2024-03-05 08:00:00', 'Tepat waktu'),
(6, 2, 'izin', '2024-03-12 08:00:00', 'Sakit keluarga'),
(7, 2, 'hadir', '2024-03-19 08:00:00', 'Tepat waktu'),
(8, 2, 'hadir', '2024-03-26 08:00:00', 'UTS');

-- Insert sample absensi settings
INSERT INTO absensi_settings (jadwal_kuliah_id, batas_keterlambatan, radius_absensi, latitude_kampus, longitude_kampus, require_location, min_kehadiran_persen) VALUES
(1, 15, 100, -6.3688, 106.8317, FALSE, 75), -- Algoritma
(2, 10, 150, -6.3688, 106.8317, FALSE, 75), -- Basis Data
(3, 15, 100, -6.3688, 106.8317, FALSE, 75), -- Kalkulus
(4, 20, 200, -6.3688, 106.8317, FALSE, 75); -- Fisika

-- Create view for absensi summary
CREATE OR REPLACE VIEW v_absensi_summary AS
SELECT 
    m.id as mahasiswa_id,
    m.nama_lengkap,
    m.nim,
    jk.id as jadwal_kuliah_id,
    mk.nama_mata_kuliah,
    mk.kode_mata_kuliah,
    COUNT(jp.id) as total_pertemuan,
    COUNT(a.id) as total_kehadiran,
    SUM(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN a.status_kehadiran = 'alfa' THEN 1 ELSE 0 END) as alfa,
    SUM(CASE WHEN a.status_kehadiran = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
    ROUND(
        (SUM(CASE WHEN a.status_kehadiran IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) / COUNT(jp.id)) * 100, 2
    ) as persentase_kehadiran
FROM mahasiswa m
JOIN krs k ON m.id = k.mahasiswa_id AND k.status = 'approved'
JOIN jadwal_kuliah jk ON k.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
LEFT JOIN jadwal_pertemuan jp ON jk.id = jp.jadwal_kuliah_id
LEFT JOIN absensi a ON (jp.id = a.jadwal_pertemuan_id AND m.id = a.mahasiswa_id)
GROUP BY m.id, jk.id;

-- Create view for daily attendance
CREATE OR REPLACE VIEW v_absensi_harian AS
SELECT 
    jp.tanggal_pertemuan,
    jp.pertemuan_ke,
    mk.nama_mata_kuliah,
    mk.kode_mata_kuliah,
    jp.waktu_mulai,
    jp.waktu_selesai,
    jp.ruangan,
    jp.dosen_pengampu,
    COUNT(k.mahasiswa_id) as total_mahasiswa,
    COUNT(a.id) as total_absen,
    SUM(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN a.status_kehadiran = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN a.status_kehadiran = 'alfa' THEN 1 ELSE 0 END) as alfa,
    SUM(CASE WHEN a.status_kehadiran = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
    ROUND((COUNT(a.id) / COUNT(k.mahasiswa_id)) * 100, 2) as persentase_absen
FROM jadwal_pertemuan jp
JOIN jadwal_kuliah jk ON jp.jadwal_kuliah_id = jk.id
JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id
LEFT JOIN krs k ON jk.id = k.jadwal_kuliah_id AND k.status = 'approved'
LEFT JOIN absensi a ON jp.id = a.jadwal_pertemuan_id
GROUP BY jp.id
ORDER BY jp.tanggal_pertemuan DESC;
