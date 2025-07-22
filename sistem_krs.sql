-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 10:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_krs`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('mahasiswa','dosen','admin') DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `nama_admin`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'Administrator Sistem', 'admin@touhou.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `category` enum('template','panduan','formulir','lainnya') DEFAULT 'lainnya',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL,
  `nidn` varchar(20) NOT NULL,
  `nama_dosen` varchar(100) NOT NULL,
  `gelar` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nomor_telepon` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `alamat` varchar(255) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `bidang_keahlian` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id_dosen`, `nidn`, `nama_dosen`, `gelar`, `email`, `foto`, `nomor_telepon`, `password`, `created_at`, `alamat`, `jurusan`, `program_studi`, `bidang_keahlian`, `status`) VALUES
(1, 'DOS001', 'Dr. Ahmad Wijaya', 'S.Kom., M.Kom.', 'ahmad.wijaya@touhou.ac.id', 'uploads/photos/1_1751300572.png', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', NULL, 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(2, 'DOS002', 'Prof. Siti Nurhaliza', 'S.T., M.T., Ph.D.', 'siti.nurhaliza@touhou.ac.id', NULL, '081234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', NULL, 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(3, 'DOS003', 'Dr. Budi Santoso', 'S.Mat., M.Mat.', 'budi.santoso@touhou.ac.id', NULL, '081234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', NULL, 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(4, 'DOS004', 'Lisa Permata', 'S.Kom., M.T.', 'lisa.permata@touhou.ac.id', NULL, '081234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', NULL, 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(5, '1234567890', '1', 'S.Kom', 'paaa@gmail.com', NULL, '1', '$2y$10$A71syIKgUUSuzzOjOVO3xOlcjk38c.Uu/2TIjqsPuQpJndDyaC9o2', '2025-06-29 02:25:26', '1', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(6, '1234567891', 'wayaya', 'M.Kim', 'h@gmail.com', NULL, '999', '$2y$10$LUE5oldT97meziY4ZxAzN.EU/NE8m.8A/e0C7X9P6MXWtlSl45vQa', '2025-06-30 13:58:02', NULL, 'Akuntansi', 'D2 Administrasi Perpajakan', 'PW', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `dosen_matakuliah`
--

CREATE TABLE `dosen_matakuliah` (
  `id` int(11) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `id_matakuliah` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `id_matakuliah` int(11) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `id_tahun_akademik` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `kapasitas` int(4) NOT NULL DEFAULT 40,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `ruangan` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `id_matakuliah`, `id_dosen`, `id_tahun_akademik`, `nama_kelas`, `kapasitas`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`, `semester`, `tahun_ajaran`, `status`, `tanggal_mulai`, `tanggal_selesai`, `created_at`) VALUES
(1, 1, 1, 2, 'A', 40, 'Senin', '08:00:00', '10:30:00', 'R101', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(2, 1, 1, 2, 'B', 35, 'Selasa', '10:30:00', '13:00:00', 'R102', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(3, 2, 2, 2, 'A', 35, 'Rabu', '08:00:00', '10:30:00', 'R103', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(4, 3, 4, 2, 'A', 30, 'Kamis', '13:00:00', '15:30:00', 'Lab Komputer 1', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(5, 4, 1, 2, 'A', 30, 'Jumat', '08:00:00', '10:30:00', 'R201', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(6, 5, 3, 2, 'A', 45, 'Senin', '13:00:00', '15:30:00', 'R202', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(7, 6, 3, 2, 'A', 40, 'Selasa', '15:30:00', '18:00:00', 'R203', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(8, 7, 2, 2, 'A', 25, 'Rabu', '10:30:00', '13:00:00', 'Lab Jaringan', 'Ganjil', '2024/2025', 'aktif', '2024-02-01', '2024-06-30', '2025-06-23 14:32:24'),
(9, 9, 1, 2, 'A', 35, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(10, 9, 1, 2, 'B', 35, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(11, 10, 2, 2, 'A', 40, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(12, 11, 1, 2, 'A', 35, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(13, 12, 3, 2, 'A', 45, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(14, 13, 2, 2, 'A', 35, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(15, 14, 4, 2, 'A', 30, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(16, 14, 4, 2, 'B', 30, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19'),
(17, 15, 1, 2, 'A', 35, NULL, NULL, NULL, NULL, NULL, NULL, 'aktif', '2024-02-01', '2024-06-30', '2025-06-30 17:18:19');

-- --------------------------------------------------------

--
-- Table structure for table `krs`
--

CREATE TABLE `krs` (
  `id_krs` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `tanggal_ambil` datetime DEFAULT current_timestamp(),
  `nilai_angka` decimal(4,2) DEFAULT NULL,
  `nilai_huruf` varchar(2) DEFAULT NULL,
  `status_krs` enum('pending','disetujui','ditolak','aktif','selesai','batal') DEFAULT 'pending',
  `semester` varchar(20) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `krs`
--

INSERT INTO `krs` (`id_krs`, `id_mahasiswa`, `id_kelas`, `tanggal_ambil`, `nilai_angka`, `nilai_huruf`, `status_krs`, `semester`, `tahun_ajaran`, `created_at`) VALUES
(1, 1, 1, '2024-01-15 10:00:00', NULL, NULL, 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(2, 1, 3, '2024-01-15 10:05:00', NULL, NULL, 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(3, 1, 6, '2024-01-15 10:10:00', NULL, NULL, 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(4, 2, 2, '2024-01-16 09:00:00', 89.70, 'A', 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(5, 2, 4, '2024-01-16 09:05:00', NULL, NULL, 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(6, 3, 1, '2024-01-17 11:00:00', 71.97, 'B', 'disetujui', '1', '2024/2025', '2025-06-23 14:32:24'),
(11, 8, 1, '2025-07-22 14:47:50', NULL, NULL, 'aktif', NULL, NULL, '2025-07-22 06:47:50'),
(12, 8, 13, '2025-07-22 14:47:55', NULL, NULL, 'aktif', NULL, NULL, '2025-07-22 06:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `nomor_telepon` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jurusan` varchar(100) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `angkatan` year(4) DEFAULT NULL,
  `semester_aktif` int(11) DEFAULT 1,
  `kelompok_ukt` int(11) DEFAULT 1,
  `dosen_wali` int(11) DEFAULT NULL,
  `status` enum('aktif','nonaktif','cuti','lulus') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `nim`, `nama`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `nomor_telepon`, `email`, `foto`, `password`, `created_at`, `jurusan`, `program_studi`, `angkatan`, `semester_aktif`, `kelompok_ukt`, `dosen_wali`, `status`) VALUES
(1, '2021001234', 'John Doe', '2003-05-15', 'L', 'Jl. Merdeka No. 123, Jakarta', '081234567894', 'john.doe@student.touhou.ac.id', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', '2024', 1, 1, NULL, 'aktif'),
(2, '2021001235', 'Jane Smith', '2003-08-20', 'P', 'Jl. Sudirman No. 456, Bandung', '081234567895', 'jane.smith@student.touhou.ac.id', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', '2024', 1, 1, NULL, 'aktif'),
(3, '2022001236', 'Ahmad Rahman', '2004-02-10', 'L', 'Jl. Diponegoro No. 789, Surabaya', '081234567896', 'ahmad.rahman@student.touhou.ac.id', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', '2024', 1, 1, NULL, 'aktif'),
(4, '1', 'a', '2025-06-24', 'L', 'a', '08129', 'a@gmail.com', 'uploads/photos/4_1751311510.png', '$2y$10$pOu5ZaTFZaaxY9ofBIlql.uTNeQqUHcKtqG8BP4ByTGIaPwQiz2Yy', '2025-06-23 17:53:44', 'Teknik Informatika', 'Informatika', '2024', 1, 1, NULL, 'aktif'),
(5, '12', '12', '2025-06-25', 'L', 'b', '087', 'b@gmail.com', NULL, '$2y$10$jHzkt2tcB5Px91hmYzyDOetgim25xgpgaaPPp7J/hxPxS36jf8pQa', '2025-06-25 10:03:48', 'Teknik Informatika', 'Informatika', '2024', 1, 1, NULL, 'aktif'),
(6, '777', 'wayae', '2025-06-30', 'L', 'erpan', '911', 'f@gmail.com', NULL, '$2y$10$.nRLKPTXWzTDUjWWqz9uG.WHxyv5uUcIKMcSjTzrIMHS2tHBXC/Qm', '2025-06-30 13:45:56', 'Teknik Elektro', 'D2 Instalasi dan Pemeliharaan Kabel Bertegangan Rendah', '2025', 1, 5, NULL, 'aktif'),
(7, '2006', 'naro', '2006-06-04', 'L', 'nusa', '09876542', 'naro2006@gmail.com', NULL, '$2y$10$LfsamRoGlqVDzr2dE8YBxe90FX5kE4skXW6LyH0gaJEdlAFWKQxpO', '2025-07-22 06:26:32', 'Akuntansi', 'D3 Akuntansi', '2025', 1, 1, NULL, 'aktif'),
(8, '12345678', 'mahasiswa 1', '2025-07-22', 'L', 'test', '0821', 'www@gmail.com', NULL, '$2y$10$TSDvRz9iH/4vhFJP0HCXsui9ZBZJLBZ080xsD3xeLjTOr6gM8010C', '2025-07-22 06:46:06', 'Teknologi Informasi', 'D4 Teknologi Rekayasa Perangkat Lunak', '2025', 1, 1, NULL, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `id_matakuliah` int(11) NOT NULL,
  `kode_matakuliah` varchar(10) NOT NULL,
  `nama_matakuliah` varchar(100) NOT NULL,
  `sks` int(2) NOT NULL,
  `semester` int(2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jurusan` varchar(100) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `prasyarat` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`id_matakuliah`, `kode_matakuliah`, `nama_matakuliah`, `sks`, `semester`, `deskripsi`, `created_at`, `jurusan`, `program_studi`, `prasyarat`, `status`) VALUES
(1, 'MK001', 'Algoritma dan Pemrograman', 3, 1, 'Mata kuliah dasar pemrograman dan algoritma', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(2, 'MK002', 'Struktur Data', 3, 2, 'Mata kuliah tentang struktur data dan implementasinya', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(3, 'MK003', 'Basis Data', 3, 3, 'Mata kuliah tentang sistem basis data relasional', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(4, 'MK004', 'Pemrograman Web', 3, 4, 'Mata kuliah tentang pengembangan aplikasi web', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(5, 'MK005', 'Kalkulus I', 4, 1, 'Mata kuliah matematika dasar untuk informatika', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(6, 'MK006', 'Statistika', 3, 2, 'Mata kuliah tentang statistika dan probabilitas', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(7, 'MK007', 'Jaringan Komputer', 3, 5, 'Mata kuliah tentang konsep dan implementasi jaringan', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(8, 'MK008', 'Rekayasa Perangkat Lunak', 3, 6, 'Mata kuliah tentang metodologi pengembangan software', '2025-06-23 14:32:24', 'Teknik Informatika', 'Informatika', NULL, 'aktif'),
(9, 'TI201', 'Pemrograman Web', 3, 2, 'Mata kuliah yang mempelajari pengembangan aplikasi web menggunakan HTML, CSS, JavaScript, dan framework web', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(10, 'MTK201', 'Aljabar Linear dan Matriks', 3, 2, 'Mata kuliah matematika yang mempelajari konsep aljabar linear, matriks, dan aplikasinya dalam teknologi informasi', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(11, 'TI202', 'Sistem Operasi dan Organisasi Komputer', 4, 2, 'Mata kuliah yang mempelajari konsep sistem operasi, arsitektur komputer, dan organisasi sistem komputer', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(12, 'K3201', 'Keselamatan dan Kesehatan Kerja (K3)', 2, 2, 'Mata kuliah yang mempelajari prinsip-prinsip keselamatan dan kesehatan kerja di lingkungan teknologi informasi', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(13, 'TI203', 'Analisis Kebutuhan Perangkat Lunak', 3, 2, 'Mata kuliah yang mempelajari teknik analisis kebutuhan sistem, requirement engineering, dan dokumentasi kebutuhan perangkat lunak', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(14, 'TI204', 'Basis Data', 4, 2, 'Mata kuliah yang mempelajari konsep basis data relasional, SQL, desain database, dan implementasi sistem basis data', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif'),
(15, 'TI205', 'Pemrograman Berorientasi Objek', 4, 2, 'Mata kuliah yang mempelajari paradigma pemrograman berorientasi objek menggunakan Java atau C++', '2025-06-30 17:18:19', NULL, NULL, NULL, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id_nilai` int(11) NOT NULL,
  `id_krs` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `uts` decimal(5,2) DEFAULT NULL,
  `uas` decimal(5,2) DEFAULT NULL,
  `tugas` decimal(5,2) DEFAULT NULL,
  `kuis` decimal(5,2) DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `nilai_huruf` varchar(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`id_nilai`, `id_krs`, `id_mahasiswa`, `id_kelas`, `uts`, `uas`, `tugas`, `kuis`, `nilai_akhir`, `nilai_huruf`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 85.00, 88.00, 90.00, 87.00, 87.40, 'A-', '2025-07-22 05:43:05', '2025-07-22 05:43:05'),
(2, 2, 1, 3, 78.00, 82.00, 85.00, 80.00, 81.20, 'A-', '2025-07-22 05:43:05', '2025-07-22 05:43:05'),
(3, 4, 2, 2, 90.00, 90.00, 88.00, 91.00, 89.70, 'A', '2025-07-22 05:43:05', '2025-07-22 06:04:07'),
(4, 5, 2, 4, 76.00, 79.00, 82.00, 78.00, 78.40, 'B+', '2025-07-22 05:43:05', '2025-07-22 05:43:05'),
(10, 6, 3, 1, 39.90, 85.00, 87.00, 86.00, 71.97, 'B', '2025-07-22 06:02:19', '2025-07-22 06:02:26');

-- --------------------------------------------------------

--
-- Table structure for table `semester_settings`
--

CREATE TABLE `semester_settings` (
  `id` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `krs_mulai` date DEFAULT NULL,
  `krs_selesai` date DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'min_sks', '12', 'Minimum SKS per semester', '2025-06-29 03:28:21'),
(2, 'max_sks', '24', 'Maximum SKS per semester', '2025-06-29 03:28:21'),
(3, 'krs_open', '0', 'KRS period status (0=closed, 1=open)', '2025-06-29 03:28:21'),
(4, 'app_name', 'Sistem KRS Universitas Touhou Indonesia', 'Application name', '2025-06-29 03:28:21'),
(5, 'semester_aktif', 'Ganjil 2024/2025', 'Current active semester', '2025-06-29 03:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `tahun_akademik`
--

CREATE TABLE `tahun_akademik` (
  `id_tahun_akademik` int(11) NOT NULL,
  `tahun_akademik` varchar(9) NOT NULL,
  `semester_akademik` enum('Ganjil','Genap','Pendek') NOT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Tidak Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tahun_akademik`
--

INSERT INTO `tahun_akademik` (`id_tahun_akademik`, `tahun_akademik`, `semester_akademik`, `status`, `created_at`) VALUES
(1, '2023/2024', 'Ganjil', 'Tidak Aktif', '2025-06-23 14:32:24'),
(2, '2023/2024', 'Genap', 'Aktif', '2025-06-23 14:32:24'),
(3, '2024/2025', 'Ganjil', 'Tidak Aktif', '2025-06-23 14:32:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`),
  ADD UNIQUE KEY `nidn` (`nidn`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `dosen_matakuliah`
--
ALTER TABLE `dosen_matakuliah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dosen_matakuliah` (`id_dosen`,`id_matakuliah`),
  ADD KEY `id_matakuliah` (`id_matakuliah`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `id_matakuliah` (`id_matakuliah`),
  ADD KEY `id_dosen` (`id_dosen`),
  ADD KEY `id_tahun_akademik` (`id_tahun_akademik`);

--
-- Indexes for table `krs`
--
ALTER TABLE `krs`
  ADD PRIMARY KEY (`id_krs`),
  ADD UNIQUE KEY `unique_mahasiswa_kelas` (`id_mahasiswa`,`id_kelas`),
  ADD UNIQUE KEY `unique_mahasiswa_kelas_krs` (`id_mahasiswa`,`id_kelas`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dosen_wali` (`dosen_wali`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`id_matakuliah`),
  ADD UNIQUE KEY `kode_matakuliah` (`kode_matakuliah`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD UNIQUE KEY `unique_mahasiswa_kelas` (`id_mahasiswa`,`id_kelas`),
  ADD KEY `id_krs` (`id_krs`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `semester_settings`
--
ALTER TABLE `semester_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tahun_akademik`
--
ALTER TABLE `tahun_akademik`
  ADD PRIMARY KEY (`id_tahun_akademik`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dosen_matakuliah`
--
ALTER TABLE `dosen_matakuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `krs`
--
ALTER TABLE `krs`
  MODIFY `id_krs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  MODIFY `id_matakuliah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `semester_settings`
--
ALTER TABLE `semester_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tahun_akademik`
--
ALTER TABLE `tahun_akademik`
  MODIFY `id_tahun_akademik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

--
-- Constraints for table `dosen_matakuliah`
--
ALTER TABLE `dosen_matakuliah`
  ADD CONSTRAINT `dosen_matakuliah_ibfk_1` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE,
  ADD CONSTRAINT `dosen_matakuliah_ibfk_2` FOREIGN KEY (`id_matakuliah`) REFERENCES `mata_kuliah` (`id_matakuliah`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`id_matakuliah`) REFERENCES `mata_kuliah` (`id_matakuliah`) ON DELETE CASCADE,
  ADD CONSTRAINT `kelas_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE,
  ADD CONSTRAINT `kelas_ibfk_3` FOREIGN KEY (`id_tahun_akademik`) REFERENCES `tahun_akademik` (`id_tahun_akademik`) ON DELETE CASCADE;

--
-- Constraints for table `krs`
--
ALTER TABLE `krs`
  ADD CONSTRAINT `krs_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `krs_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`dosen_wali`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL;

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`id_krs`) REFERENCES `krs` (`id_krs`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_ibfk_3` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
