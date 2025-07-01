-- Menambahkan kolom foto ke tabel mahasiswa
ALTER TABLE mahasiswa ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER email;

-- Menambahkan kolom foto ke tabel dosen  
ALTER TABLE dosen ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER email;

-- Menambahkan kolom foto ke tabel admin (jika ada)
-- ALTER TABLE admin ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER email;

-- Update struktur kelas jika belum ada kolom yang diperlukan
ALTER TABLE kelas 
ADD COLUMN IF NOT EXISTS hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') DEFAULT 'Senin',
ADD COLUMN IF NOT EXISTS jam_mulai TIME DEFAULT '08:00:00',
ADD COLUMN IF NOT EXISTS jam_selesai TIME DEFAULT '10:00:00',
ADD COLUMN IF NOT EXISTS ruangan VARCHAR(50) DEFAULT 'TBA',
ADD COLUMN IF NOT EXISTS semester INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(20) DEFAULT '2024/2025',
ADD COLUMN IF NOT EXISTS status ENUM('aktif','nonaktif') DEFAULT 'aktif';

-- Update struktur krs jika belum ada kolom yang diperlukan
ALTER TABLE krs
ADD COLUMN IF NOT EXISTS semester INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS tahun_ajaran VARCHAR(20) DEFAULT '2024/2025';

-- Update data sample untuk kelas
UPDATE kelas SET 
    hari = 'Senin', 
    jam_mulai = '08:00:00', 
    jam_selesai = '10:00:00', 
    ruangan = 'R101',
    semester = 1,
    tahun_ajaran = '2024/2025',
    status = 'aktif'
WHERE hari IS NULL OR hari = '';

-- Update data sample untuk krs
UPDATE krs SET 
    semester = 1,
    tahun_ajaran = '2024/2025'
WHERE semester IS NULL OR semester = 0;
