-- Script update sederhana - jalankan satu per satu di phpMyAdmin
-- Skip baris yang error "Duplicate column name"

-- 1. Cek struktur tabel dulu
DESCRIBE mahasiswa;

-- 2. Tambah kolom satu per satu (skip jika error)
-- Jika tempat_lahir belum ada:
ALTER TABLE mahasiswa ADD COLUMN tempat_lahir VARCHAR(50) AFTER nama_lengkap;

-- Jika tanggal_lahir belum ada:
ALTER TABLE mahasiswa ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir;

-- Jika jenis_kelamin belum ada:
ALTER TABLE mahasiswa ADD COLUMN jenis_kelamin ENUM('L', 'P') AFTER tanggal_lahir;

-- Jika alamat belum ada:
ALTER TABLE mahasiswa ADD COLUMN alamat TEXT AFTER jenis_kelamin;

-- Jika no_telepon belum ada:
ALTER TABLE mahasiswa ADD COLUMN no_telepon VARCHAR(15) AFTER alamat;

-- 3. Update data existing
UPDATE mahasiswa SET 
    tempat_lahir = COALESCE(tempat_lahir, 'Jakarta'),
    tanggal_lahir = COALESCE(tanggal_lahir, '2000-01-01'),
    jenis_kelamin = COALESCE(jenis_kelamin, 'L'),
    alamat = COALESCE(alamat, 'Jakarta, Indonesia'),
    no_telepon = COALESCE(no_telepon, '081234567890')
WHERE id IS NOT NULL;

-- 4. Cek hasil akhir
DESCRIBE mahasiswa;
SELECT * FROM mahasiswa LIMIT 3;
