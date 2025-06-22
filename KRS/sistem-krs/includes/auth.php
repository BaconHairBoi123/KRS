<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($nim, $password) {
        // Login menggunakan NIM instead of username
        $query = "SELECT u.*, m.nama_lengkap, m.nim, m.program_studi, m.semester_aktif
                  FROM users u
                  INNER JOIN mahasiswa m ON u.id = m.user_id
                  WHERE m.nim = :nim AND u.status = 'aktif' AND u.role = 'mahasiswa'
                  
                  UNION
                  
                  SELECT u.*, d.nama_lengkap, d.nip as nim, d.program_studi, NULL as semester_aktif
                  FROM users u
                  INNER JOIN dosen d ON u.id = d.user_id
                  WHERE d.nip = :nim AND u.status = 'aktif' AND u.role = 'dosen'
                  
                  UNION
                  
                  SELECT u.*, a.nama_lengkap, a.nip as nim, NULL as program_studi, NULL as semester_aktif
                  FROM users u
                  INNER JOIN administrator a ON u.id = a.user_id
                  WHERE a.nip = :nim AND u.status = 'aktif' AND u.role = 'admin'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nim', $nim);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['nomor_induk'] = $user['nim'];
                $_SESSION['program_studi'] = $user['program_studi'];
                $_SESSION['semester_aktif'] = $user['semester_aktif'];
                
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        redirect('login.php');
    }
    
    public function register($data) {
        try {
            $this->conn->beginTransaction();
            
            // Check if email or NIM already exists
            $check_query = "SELECT COUNT(*) FROM users u 
                           LEFT JOIN mahasiswa m ON u.id = m.user_id 
                           WHERE u.email = :email OR m.nim = :nim";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindValue(':email', $data['email']);
            $check_stmt->bindValue(':nim', $data['nim']);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Email atau NIM sudah digunakan");
            }
            
            // Generate username from NIM (untuk keperluan internal)
            $username = 'mhs_' . $data['nim'];
            
            // Insert ke tabel users
            $user_query = "INSERT INTO users (username, password, email, role, status) VALUES (:username, :password, :email, :role, 'aktif')";
            $user_stmt = $this->conn->prepare($user_query);
            $user_stmt->bindValue(':username', $username);
            $user_stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $user_stmt->bindValue(':email', $data['email']);
            $user_stmt->bindValue(':role', 'mahasiswa');
            $user_stmt->execute();
            
            $user_id = $this->conn->lastInsertId();
            
            // Insert ke tabel mahasiswa
            $mahasiswa_query = "INSERT INTO mahasiswa (user_id, nim, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, no_telepon, program_studi, angkatan, semester_aktif) 
                               VALUES (:user_id, :nim, :nama_lengkap, :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :no_telepon, :program_studi, :angkatan, 1)";
            $mahasiswa_stmt = $this->conn->prepare($mahasiswa_query);
            $mahasiswa_stmt->bindValue(':user_id', $user_id);
            $mahasiswa_stmt->bindValue(':nim', $data['nim']);
            $mahasiswa_stmt->bindValue(':nama_lengkap', $data['nama_lengkap']);
            $mahasiswa_stmt->bindValue(':tempat_lahir', $data['tempat_lahir'] ?? '');
            $mahasiswa_stmt->bindValue(':tanggal_lahir', $data['tanggal_lahir'] ?: null);
            $mahasiswa_stmt->bindValue(':jenis_kelamin', $data['jenis_kelamin'] ?: null);
            $mahasiswa_stmt->bindValue(':alamat', $data['alamat'] ?? '');
            $mahasiswa_stmt->bindValue(':no_telepon', $data['no_telepon'] ?? '');
            $mahasiswa_stmt->bindValue(':program_studi', $data['program_studi']);
            $mahasiswa_stmt->bindValue(':angkatan', $data['angkatan']);
            $mahasiswa_stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }
    
    public function checkEmailExists($email) {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    public function checkNIMExists($nim) {
        $query = "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nim', $nim);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
?>
