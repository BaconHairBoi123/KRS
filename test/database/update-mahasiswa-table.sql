-- Update tabel mahasiswa dengan pengecekan kolom
-- Script ini aman dijalankan berulang kali

-- Cek dan tambah kolom tempat_lahir jika belum ada
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mahasiswa' 
AND COLUMN_NAME = 'tempat_lahir';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE mahasiswa ADD COLUMN tempat_lahir VARCHAR(50) AFTER nama_lengkap', 
    'SELECT "Column tempat_lahir already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom tanggal_lahir jika belum ada
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mahasiswa' 
AND COLUMN_NAME = 'tanggal_lahir';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE mahasiswa ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir', 
    'SELECT "Column tanggal_lahir already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom jenis_kelamin jika belum ada
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mahasiswa' 
AND COLUMN_NAME = 'jenis_kelamin';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE mahasiswa ADD COLUMN jenis_kelamin ENUM(\'L\', \'P\') AFTER tanggal_lahir', 
    'SELECT "Column jenis_kelamin already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom alamat jika belum ada
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mahasiswa' 
AND COLUMN_NAME = 'alamat';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE mahasiswa ADD COLUMN alamat TEXT AFTER jenis_kelamin', 
    'SELECT "Column alamat already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom no_telepon jika belum ada
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mahasiswa' 
AND COLUMN_NAME = 'no_telepon';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE mahasiswa ADD COLUMN no_telepon VARCHAR(15) AFTER alamat', 
    'SELECT "Column no_telepon already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update data existing jika ada dan field masih NULL
UPDATE mahasiswa SET 
    tempat_lahir = COALESCE(tempat_lahir, 'Jakarta'),
    tanggal_lahir = COALESCE(tanggal_lahir, '2000-01-01'),
    jenis_kelamin = COALESCE(jenis_kelamin, 'L'),
    alamat = COALESCE(alamat, 'Jakarta, Indonesia'),
    no_telepon = COALESCE(no_telepon, '081234567890')
WHERE id IS NOT NULL;

-- Tampilkan struktur tabel final
DESCRIBE mahasiswa;
