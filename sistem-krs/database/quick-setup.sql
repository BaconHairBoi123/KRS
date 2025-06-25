-- Quick setup untuk sistem KRS
CREATE DATABASE IF NOT EXISTS sistem_krs;
USE sistem_krs;

-- Tabel Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('mahasiswa', 'dosen', 'admin') NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Mahasiswa
CREATE TABLE mahasiswa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nim VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    program_studi VARCHAR(50),
    angkatan YEAR,
    semester_aktif INT DEFAULT 1,
    ipk DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Dosen
CREATE TABLE dosen (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    fakultas VARCHAR(50),
    program_studi VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Administrator
CREATE TABLE administrator (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert demo users
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@university.ac.id', 'admin'),
('dosen1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen1@university.ac.id', 'dosen'),
('mahasiswa1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa1@university.ac.id', 'mahasiswa');

-- Insert demo data
INSERT INTO administrator (user_id, nip, nama_lengkap, jabatan) VALUES
(1, 'ADM001', 'Administrator Sistem', 'Admin IT');

INSERT INTO dosen (user_id, nip, nama_lengkap, fakultas, program_studi) VALUES
(2, 'DOS001', 'Dr. Ahmad Wijaya', 'Teknik', 'Informatika');

INSERT INTO mahasiswa (user_id, nim, nama_lengkap, program_studi, angkatan) VALUES
(3, '2021001234', 'John Doe', 'Informatika', 2021);
