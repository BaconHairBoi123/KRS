-- Fix database schema to match the expected structure

-- Add missing columns to kelas table
ALTER TABLE `kelas` 
ADD COLUMN `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') DEFAULT NULL AFTER `kapasitas`,
ADD COLUMN `jam_mulai` TIME DEFAULT NULL AFTER `hari`,
ADD COLUMN `jam_selesai` TIME DEFAULT NULL AFTER `jam_mulai`,
ADD COLUMN `ruangan` VARCHAR(50) DEFAULT NULL AFTER `jam_selesai`,
ADD COLUMN `semester` VARCHAR(20) DEFAULT NULL AFTER `ruangan`,
ADD COLUMN `tahun_ajaran` VARCHAR(20) DEFAULT NULL AFTER `semester`,
ADD COLUMN `status` ENUM('aktif','nonaktif') DEFAULT 'aktif' AFTER `tahun_ajaran`;

-- Add missing columns to krs table
ALTER TABLE `krs` 
ADD COLUMN `semester` VARCHAR(20) DEFAULT NULL AFTER `status_krs`,
ADD COLUMN `tahun_ajaran` VARCHAR(20) DEFAULT NULL AFTER `semester`;

-- Update existing data with sample values
UPDATE `kelas` SET 
    `hari` = 'Senin',
    `jam_mulai` = '08:00:00',
    `jam_selesai` = '10:30:00',
    `ruangan` = 'R101',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 1;

UPDATE `kelas` SET 
    `hari` = 'Selasa',
    `jam_mulai` = '10:30:00',
    `jam_selesai` = '13:00:00',
    `ruangan` = 'R102',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 2;

UPDATE `kelas` SET 
    `hari` = 'Rabu',
    `jam_mulai` = '08:00:00',
    `jam_selesai` = '10:30:00',
    `ruangan` = 'R103',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 3;

UPDATE `kelas` SET 
    `hari` = 'Kamis',
    `jam_mulai` = '13:00:00',
    `jam_selesai` = '15:30:00',
    `ruangan` = 'Lab Komputer 1',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 4;

UPDATE `kelas` SET 
    `hari` = 'Jumat',
    `jam_mulai` = '08:00:00',
    `jam_selesai` = '10:30:00',
    `ruangan` = 'R201',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 5;

UPDATE `kelas` SET 
    `hari` = 'Senin',
    `jam_mulai` = '13:00:00',
    `jam_selesai` = '15:30:00',
    `ruangan` = 'R202',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 6;

UPDATE `kelas` SET 
    `hari` = 'Selasa',
    `jam_mulai` = '15:30:00',
    `jam_selesai` = '18:00:00',
    `ruangan` = 'R203',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 7;

UPDATE `kelas` SET 
    `hari` = 'Rabu',
    `jam_mulai` = '10:30:00',
    `jam_selesai` = '13:00:00',
    `ruangan` = 'Lab Jaringan',
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025',
    `status` = 'aktif'
WHERE `id_kelas` = 8;

-- Update KRS data with semester and tahun_ajaran
UPDATE `krs` SET 
    `semester` = 'Ganjil',
    `tahun_ajaran` = '2024/2025';

-- Change status_krs values to match expected values
ALTER TABLE `krs` MODIFY `status_krs` ENUM('pending','disetujui','ditolak','aktif','selesai','batal') DEFAULT 'pending';

-- Update existing KRS status
UPDATE `krs` SET `status_krs` = 'disetujui' WHERE `status_krs` = 'Aktif';
