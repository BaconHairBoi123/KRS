-- Script untuk mengecek dan update tabel mahasiswa
-- Jalankan satu per satu di phpMyAdmin

-- 1. Cek struktur tabel mahasiswa yang ada
DESCRIBE mahasiswa;

-- 2. Cek kolom yang sudah ada
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sistem_krs' 
AND TABLE_NAME = 'mahasiswa';

-- 3. Tambahkan kolom hanya jika belum ada
-- Jalankan satu per satu dan skip jika error "Duplicate column name"

-- Tambah tempat_lahir jika belum ada
ALTER TABLE mahasiswa ADD COLUMN tempat_lahir VARCHAR(50) AFTER nama_lengkap;

-- Tambah tanggal_lahir jika belum ada  
ALTER TABLE mahasiswa ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir;

-- Tambah jenis_kelamin jika belum ada
ALTER TABLE mahasiswa ADD COLUMN jenis_kelamin ENUM('L', 'P') AFTER tanggal_lahir;

-- Tambah alamat jika belum ada
ALTER TABLE mahasiswa ADD COLUMN alamat TEXT AFTER jenis_kelamin;

-- Tambah no_telepon jika belum ada
ALTER TABLE mahasiswa ADD COLUMN no_telepon VARCHAR(15) AFTER alamat;
