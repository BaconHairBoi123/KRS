-- Script aman untuk update tabel mahasiswa
-- Menggunakan IF NOT EXISTS logic

-- Buat procedure untuk menambah kolom jika belum ada
DELIMITER $$

CREATE PROCEDURE AddColumnIfNotExists(
    IN table_name VARCHAR(100),
    IN column_name VARCHAR(100), 
    IN column_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = table_name
    AND COLUMN_NAME = column_name;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', table_name, ' ADD COLUMN ', column_name, ' ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Gunakan procedure untuk menambah kolom
CALL AddColumnIfNotExists('mahasiswa', 'tempat_lahir', 'VARCHAR(50) AFTER nama_lengkap');
CALL AddColumnIfNotExists('mahasiswa', 'tanggal_lahir', 'DATE AFTER tempat_lahir');
CALL AddColumnIfNotExists('mahasiswa', 'jenis_kelamin', 'ENUM(\'L\', \'P\') AFTER tanggal_lahir');
CALL AddColumnIfNotExists('mahasiswa', 'alamat', 'TEXT AFTER jenis_kelamin');
CALL AddColumnIfNotExists('mahasiswa', 'no_telepon', 'VARCHAR(15) AFTER alamat');

-- Hapus procedure setelah selesai
DROP PROCEDURE AddColumnIfNotExists;

-- Update data existing jika ada dan field masih NULL
UPDATE mahasiswa SET 
    tempat_lahir = COALESCE(tempat_lahir, 'Jakarta'),
    tanggal_lahir = COALESCE(tanggal_lahir, '2000-01-01'),
    jenis_kelamin = COALESCE(jenis_kelamin, 'L'),
    alamat = COALESCE(alamat, 'Jakarta, Indonesia'),
    no_telepon = COALESCE(no_telepon, '081234567890')
WHERE tempat_lahir IS NULL OR tanggal_lahir IS NULL OR jenis_kelamin IS NULL;
