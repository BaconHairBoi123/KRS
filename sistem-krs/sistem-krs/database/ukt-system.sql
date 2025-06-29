-- Database untuk Sistem Pembayaran UKT
USE sistem_krs;

-- Tabel Tarif UKT berdasarkan program studi dan angkatan
CREATE TABLE ukt_tarif (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_studi VARCHAR(50) NOT NULL,
    angkatan YEAR NOT NULL,
    kelompok_ukt INT NOT NULL, -- 1-8 (kelompok UKT)
    nominal DECIMAL(12,2) NOT NULL,
    keterangan TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Periode Pembayaran UKT
CREATE TABLE ukt_periode (
    id INT PRIMARY KEY AUTO_INCREMENT,
    semester_tahun VARCHAR(10) NOT NULL, -- 2024/1, 2024/2
    nama_periode VARCHAR(50) NOT NULL, -- Semester Ganjil 2024/2025
    tanggal_mulai DATE NOT NULL,
    tanggal_akhir DATE NOT NULL,
    denda_per_hari DECIMAL(10,2) DEFAULT 0,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Tagihan UKT Mahasiswa
CREATE TABLE ukt_tagihan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mahasiswa_id INT NOT NULL,
    periode_id INT NOT NULL,
    tarif_id INT NOT NULL,
    nominal_tagihan DECIMAL(12,2) NOT NULL,
    nominal_denda DECIMAL(12,2) DEFAULT 0,
    total_tagihan DECIMAL(12,2) NOT NULL,
    virtual_account VARCHAR(20) UNIQUE NOT NULL,
    status_tagihan ENUM('belum_bayar', 'lunas', 'terlambat') DEFAULT 'belum_bayar',
    tanggal_jatuh_tempo DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES ukt_periode(id) ON DELETE CASCADE,
    FOREIGN KEY (tarif_id) REFERENCES ukt_tarif(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tagihan (mahasiswa_id, periode_id)
);

-- Tabel Pembayaran UKT
CREATE TABLE ukt_pembayaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tagihan_id INT NOT NULL,
    mahasiswa_id INT NOT NULL,
    nominal_bayar DECIMAL(12,2) NOT NULL,
    metode_pembayaran ENUM('transfer_bank', 'virtual_account', 'mobile_banking', 'atm', 'teller') NOT NULL,
    bank_pengirim VARCHAR(50),
    nomor_referensi VARCHAR(50) UNIQUE NOT NULL,
    bukti_pembayaran VARCHAR(255), -- path file bukti
    tanggal_bayar DATETIME NOT NULL,
    tanggal_verifikasi DATETIME NULL,
    status_verifikasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    catatan_verifikasi TEXT,
    verified_by INT NULL, -- admin yang verifikasi
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tagihan_id) REFERENCES ukt_tagihan(id) ON DELETE CASCADE,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES administrator(id)
);

-- Tabel Notifikasi UKT
CREATE TABLE ukt_notifikasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mahasiswa_id INT NOT NULL,
    judul VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('info', 'warning', 'urgent') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
);

-- Insert sample data
-- Tarif UKT untuk berbagai program studi dan kelompok
INSERT INTO ukt_tarif (program_studi, angkatan, kelompok_ukt, nominal, keterangan) VALUES
('Informatika', 2024, 1, 500000, 'Kelompok UKT 1 - Subsidi Penuh'),
('Informatika', 2024, 2, 1000000, 'Kelompok UKT 2 - Subsidi Tinggi'),
('Informatika', 2024, 3, 2500000, 'Kelompok UKT 3 - Subsidi Sedang'),
('Informatika', 2024, 4, 4000000, 'Kelompok UKT 4 - Subsidi Rendah'),
('Informatika', 2024, 5, 6000000, 'Kelompok UKT 5 - Tanpa Subsidi'),
('Sistem Informasi', 2024, 1, 500000, 'Kelompok UKT 1 - Subsidi Penuh'),
('Sistem Informasi', 2024, 2, 1000000, 'Kelompok UKT 2 - Subsidi Tinggi'),
('Sistem Informasi', 2024, 3, 2000000, 'Kelompok UKT 3 - Subsidi Sedang'),
('Sistem Informasi', 2024, 4, 3500000, 'Kelompok UKT 4 - Subsidi Rendah'),
('Sistem Informasi', 2024, 5, 5000000, 'Kelompok UKT 5 - Tanpa Subsidi');

-- Periode pembayaran
INSERT INTO ukt_periode (semester_tahun, nama_periode, tanggal_mulai, tanggal_akhir, denda_per_hari) VALUES
('2024/1', 'Semester Genap 2023/2024', '2024-01-01', '2024-02-15', 50000),
('2024/2', 'Semester Ganjil 2024/2025', '2024-07-01', '2024-08-15', 50000);

-- Update tabel mahasiswa untuk menambah kelompok UKT
ALTER TABLE mahasiswa ADD COLUMN kelompok_ukt INT DEFAULT 3 AFTER angkatan;

-- Update mahasiswa existing dengan kelompok UKT
UPDATE mahasiswa SET kelompok_ukt = 3 WHERE kelompok_ukt IS NULL;

-- Generate tagihan untuk mahasiswa existing
INSERT INTO ukt_tagihan (mahasiswa_id, periode_id, tarif_id, nominal_tagihan, total_tagihan, virtual_account, tanggal_jatuh_tempo)
SELECT 
    m.id,
    1, -- periode_id untuk semester genap 2023/2024
    ut.id,
    ut.nominal,
    ut.nominal,
    CONCAT('8001', LPAD(m.id, 6, '0'), LPAD(1, 4, '0')), -- format VA: 8001 + mahasiswa_id + periode_id
    '2024-02-15'
FROM mahasiswa m
JOIN ukt_tarif ut ON ut.program_studi = m.program_studi 
    AND ut.angkatan = m.angkatan 
    AND ut.kelompok_ukt = m.kelompok_ukt
WHERE ut.status = 'aktif';
