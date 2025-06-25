-- Database: sistem_krs
CREATE DATABASE IF NOT EXISTS sistem_krs;
USE sistem_krs;

-- Tabel Users (untuk authentication)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('mahasiswa', 'dosen', 'admin') NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Mahasiswa
CREATE TABLE mahasiswa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nim VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    alamat TEXT,
    no_telepon VARCHAR(15),
    program_studi VARCHAR(50),
    angkatan YEAR,
    semester_aktif INT DEFAULT 1,
    ipk DECIMAL(3,2) DEFAULT 0.00,
    total_sks INT DEFAULT 0,
    status_mahasiswa ENUM('aktif', 'cuti', 'lulus', 'dropout') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Dosen
CREATE TABLE dosen (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    alamat TEXT,
    no_telepon VARCHAR(15),
    fakultas VARCHAR(50),
    program_studi VARCHAR(50),
    jabatan VARCHAR(50),
    pendidikan_terakhir VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Administrator
CREATE TABLE administrator (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50),
    bagian VARCHAR(50),
    no_telepon VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Mata Kuliah
CREATE TABLE mata_kuliah (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_mk VARCHAR(10) UNIQUE NOT NULL,
    nama_mk VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    semester INT NOT NULL,
    jenis ENUM('wajib', 'pilihan') DEFAULT 'wajib',
    program_studi VARCHAR(50),
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Jadwal Kuliah
CREATE TABLE jadwal_kuliah (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mata_kuliah_id INT NOT NULL,
    dosen_id INT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruang VARCHAR(20) NOT NULL,
    kuota INT DEFAULT 40,
    semester_tahun VARCHAR(10) NOT NULL, -- contoh: 2024/1
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE CASCADE
);

-- Tabel KRS (Kartu Rencana Studi)
CREATE TABLE krs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mahasiswa_id INT NOT NULL,
    jadwal_kuliah_id INT NOT NULL,
    semester_tahun VARCHAR(10) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_approve TIMESTAMP NULL,
    approved_by INT NULL,
    catatan TEXT,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_kuliah_id) REFERENCES jadwal_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES administrator(id),
    UNIQUE KEY unique_krs (mahasiswa_id, jadwal_kuliah_id, semester_tahun)
);

-- Tabel Nilai
CREATE TABLE nilai (
    id INT PRIMARY KEY AUTO_INCREMENT,
    krs_id INT NOT NULL,
    nilai_angka DECIMAL(5,2),
    nilai_huruf ENUM('A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'E'),
    bobot DECIMAL(3,2),
    semester_tahun VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (krs_id) REFERENCES krs(id) ON DELETE CASCADE
);

-- Tabel Prasyarat Mata Kuliah
CREATE TABLE prasyarat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mata_kuliah_id INT NOT NULL,
    prasyarat_mk_id INT NOT NULL,
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (prasyarat_mk_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE
);

-- Insert sample data
-- Users
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@university.ac.id', 'admin'),
('dosen1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ahmad.wijaya@university.ac.id', 'dosen'),
('mahasiswa1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@student.university.ac.id', 'mahasiswa');

-- Administrator
INSERT INTO administrator (user_id, nip, nama_lengkap, jabatan, bagian, no_telepon) VALUES
(1, 'ADM001', 'Administrator Sistem', 'Admin IT', 'Sistem Informasi', '081234567890');

-- Dosen
INSERT INTO dosen (user_id, nip, nama_lengkap, jenis_kelamin, fakultas, program_studi, jabatan, pendidikan_terakhir) VALUES
(2, 'DOS001', 'Dr. Ahmad Wijaya', 'L', 'Teknik', 'Informatika', 'Lektor', 'S3 Ilmu Komputer');

-- Mahasiswa
INSERT INTO mahasiswa (user_id, nim, nama_lengkap, jenis_kelamin, program_studi, angkatan, semester_aktif) VALUES
(3, '2021001234', 'John Doe', 'L', 'Informatika', 2021, 5);

-- Mata Kuliah
INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester, jenis, program_studi, deskripsi) VALUES
('CS101', 'Algoritma dan Pemrograman', 3, 1, 'wajib', 'Informatika', 'Mata kuliah dasar pemrograman'),
('CS201', 'Struktur Data', 3, 3, 'wajib', 'Informatika', 'Mata kuliah struktur data dan algoritma'),
('MTK101', 'Kalkulus I', 4, 1, 'wajib', 'Informatika', 'Matematika dasar untuk informatika'),
('CS301', 'Basis Data', 3, 5, 'wajib', 'Informatika', 'Sistem basis data relasional'),
('CS302', 'Rekayasa Perangkat Lunak', 3, 5, 'wajib', 'Informatika', 'Metodologi pengembangan software');

-- Jadwal Kuliah
INSERT INTO jadwal_kuliah (mata_kuliah_id, dosen_id, hari, jam_mulai, jam_selesai, ruang, kuota, semester_tahun) VALUES
(1, 1, 'Senin', '08:00:00', '10:30:00', 'Lab Komputer 1', 40, '2024/1'),
(2, 1, 'Rabu', '10:30:00', '13:00:00', 'Lab Komputer 2', 35, '2024/1'),
(3, 1, 'Selasa', '08:00:00', '11:30:00', 'Ruang 201', 50, '2024/1'),
(4, 1, 'Kamis', '13:00:00', '15:30:00', 'Lab Database', 30, '2024/1'),
(5, 1, 'Jumat', '08:00:00', '10:30:00', 'Ruang 301', 35, '2024/1');
