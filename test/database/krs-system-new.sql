-- Database: sistem_krs_new
-- Struktur database berdasarkan spesifikasi PDF

DROP DATABASE IF EXISTS sistem_krs;
CREATE DATABASE sistem_krs;
USE sistem_krs;

-- =============================================
-- TABEL MASTER DATA
-- =============================================

-- 1. Tabel mahasiswa
CREATE TABLE mahasiswa (
    id_mahasiswa INT(11) PRIMARY KEY AUTO_INCREMENT,
    nim VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    alamat VARCHAR(255),
    nomor_telepon VARCHAR(15),
    email VARCHAR(100) UNIQUE NOT NULL,
    foto VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel mata_kuliah
CREATE TABLE mata_kuliah (
    id_matakuliah INT(11) PRIMARY KEY AUTO_INCREMENT,
    kode_matakuliah VARCHAR(10) UNIQUE NOT NULL,
    nama_matakuliah VARCHAR(100) NOT NULL,
    sks INT(2) NOT NULL,
    semester INT(2) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel dosen
CREATE TABLE dosen (
    id_dosen INT(11) PRIMARY KEY AUTO_INCREMENT,
    nidn VARCHAR(20) UNIQUE NOT NULL,
    nama_dosen VARCHAR(100) NOT NULL,
    gelar VARCHAR(50),
    email VARCHAR(100) UNIQUE NOT NULL,
    nomor_telepon VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabel tahun_akademik
CREATE TABLE tahun_akademik (
    id_tahun_akademik INT(11) PRIMARY KEY AUTO_INCREMENT,
    tahun_akademik VARCHAR(9) NOT NULL,
    semester_akademik ENUM('Ganjil', 'Genap', 'Pendek') NOT NULL,
    status ENUM('Aktif', 'Tidak Aktif') DEFAULT 'Tidak Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABEL TRANSAKSI
-- =============================================

-- 1. Tabel kelas
CREATE TABLE kelas (
    id_kelas INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_matakuliah INT(11) NOT NULL,
    id_dosen INT(11) NOT NULL,
    id_tahun_akademik INT(11) NOT NULL,
    nama_kelas VARCHAR(50) NOT NULL,
    kapasitas INT(4) NOT NULL DEFAULT 40,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_matakuliah) REFERENCES mata_kuliah(id_matakuliah) ON DELETE CASCADE,
    FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen) ON DELETE CASCADE,
    FOREIGN KEY (id_tahun_akademik) REFERENCES tahun_akademik(id_tahun_akademik) ON DELETE CASCADE
);

-- 2. Tabel krs (Kartu Rencana Studi)
CREATE TABLE krs (
    id_krs INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_mahasiswa INT(11) NOT NULL,
    id_kelas INT(11) NOT NULL,
    tanggal_ambil DATETIME DEFAULT CURRENT_TIMESTAMP,
    nilai_angka DECIMAL(4,2) NULL,
    nilai_huruf VARCHAR(2) NULL,
    status_krs ENUM('Aktif', 'Selesai', 'Batal') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa(id_mahasiswa) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    UNIQUE KEY unique_mahasiswa_kelas (id_mahasiswa, id_kelas)
);

-- Tabel admin untuk sistem
CREATE TABLE admin (
    id_admin INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    nama_admin VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- SAMPLE DATA
-- =============================================

-- Insert admin
INSERT INTO admin (username, nama_admin, email, password) VALUES
('admin', 'Administrator Sistem', 'admin@touhou.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert tahun akademik
INSERT INTO tahun_akademik (tahun_akademik, semester_akademik, status) VALUES
('2023/2024', 'Ganjil', 'Tidak Aktif'),
('2023/2024', 'Genap', 'Aktif'),
('2024/2025', 'Ganjil', 'Tidak Aktif');

-- Insert mata kuliah
INSERT INTO mata_kuliah (kode_matakuliah, nama_matakuliah, sks, semester, deskripsi) VALUES
('MK001', 'Algoritma dan Pemrograman', 3, 1, 'Mata kuliah dasar pemrograman dan algoritma'),
('MK002', 'Struktur Data', 3, 2, 'Mata kuliah tentang struktur data dan implementasinya'),
('MK003', 'Basis Data', 3, 3, 'Mata kuliah tentang sistem basis data relasional'),
('MK004', 'Pemrograman Web', 3, 4, 'Mata kuliah tentang pengembangan aplikasi web'),
('MK005', 'Kalkulus I', 4, 1, 'Mata kuliah matematika dasar untuk informatika'),
('MK006', 'Statistika', 3, 2, 'Mata kuliah tentang statistika dan probabilitas'),
('MK007', 'Jaringan Komputer', 3, 5, 'Mata kuliah tentang konsep dan implementasi jaringan'),
('MK008', 'Rekayasa Perangkat Lunak', 3, 6, 'Mata kuliah tentang metodologi pengembangan software');

-- Insert dosen
INSERT INTO dosen (nidn, nama_dosen, gelar, email, nomor_telepon, password) VALUES
('DOS001', 'Dr. Ahmad Wijaya', 'S.Kom., M.Kom.', 'ahmad.wijaya@touhou.ac.id', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('DOS002', 'Prof. Siti Nurhaliza', 'S.T., M.T., Ph.D.', 'siti.nurhaliza@touhou.ac.id', '081234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('DOS003', 'Dr. Budi Santoso', 'S.Mat., M.Mat.', 'budi.santoso@touhou.ac.id', '081234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('DOS004', 'Lisa Permata', 'S.Kom., M.T.', 'lisa.permata@touhou.ac.id', '081234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert mahasiswa
INSERT INTO mahasiswa (nim, nama, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon, email, password) VALUES
('2021001234', 'John Doe', '2003-05-15', 'L', 'Jl. Merdeka No. 123, Jakarta', '081234567894', 'john.doe@student.touhou.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('2021001235', 'Jane Smith', '2003-08-20', 'P', 'Jl. Sudirman No. 456, Bandung', '081234567895', 'jane.smith@student.touhou.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('2022001236', 'Ahmad Rahman', '2004-02-10', 'L', 'Jl. Diponegoro No. 789, Surabaya', '081234567896', 'ahmad.rahman@student.touhou.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert kelas untuk semester aktif
INSERT INTO kelas (id_matakuliah, id_dosen, id_tahun_akademik, nama_kelas, kapasitas, tanggal_mulai, tanggal_selesai) VALUES
-- Semester Genap 2023/2024 (id_tahun_akademik = 2)
(1, 1, 2, 'A', 40, '2024-02-01', '2024-06-30'),
(1, 1, 2, 'B', 35, '2024-02-01', '2024-06-30'),
(2, 2, 2, 'A', 35, '2024-02-01', '2024-06-30'),
(3, 4, 2, 'A', 30, '2024-02-01', '2024-06-30'),
(4, 1, 2, 'A', 30, '2024-02-01', '2024-06-30'),
(5, 3, 2, 'A', 45, '2024-02-01', '2024-06-30'),
(6, 3, 2, 'A', 40, '2024-02-01', '2024-06-30'),
(7, 2, 2, 'A', 25, '2024-02-01', '2024-06-30');

-- Insert sample KRS
INSERT INTO krs (id_mahasiswa, id_kelas, tanggal_ambil, status_krs) VALUES
(1, 1, '2024-01-15 10:00:00', 'Aktif'),
(1, 3, '2024-01-15 10:05:00', 'Aktif'),
(1, 6, '2024-01-15 10:10:00', 'Aktif'),
(2, 2, '2024-01-16 09:00:00', 'Aktif'),
(2, 4, '2024-01-16 09:05:00', 'Aktif'),
(3, 1, '2024-01-17 11:00:00', 'Aktif');
