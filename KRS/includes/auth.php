<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($nim, $password) {
        try {
            // Check mahasiswa
            $query = "SELECT id_mahasiswa as id, nim, nama, email, 'mahasiswa' as role, password 
                     FROM mahasiswa WHERE nim = :nim";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':nim', $nim);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found in mahasiswa, check dosen
            if (!$user) {
                $query = "SELECT id_dosen as id, nidn as nim, nama_dosen as nama, email, 'dosen' as role, password 
                         FROM dosen WHERE nidn = :nim";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':nim', $nim);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // If not found in dosen, check admin
            if (!$user) {
                $query = "SELECT id_admin as id, username as nim, nama_admin as nama, email, 'admin' as role, password 
                         FROM admin WHERE username = :nim";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':nim', $nim);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nim'] = $user['nim'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return ['success' => true, 'role' => $user['role']];
            } else {
                return ['success' => false, 'message' => 'NIM atau password salah!'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }
    
    public function register($data) {
        try {
            $this->conn->beginTransaction();
            
            // Check if NIM already exists
            if ($this->checkNIMExists($data['nim'])) {
                throw new Exception("NIM sudah terdaftar");
            }
            
            // Check if email already exists
            if ($this->checkEmailExists($data['email'])) {
                throw new Exception("Email sudah terdaftar");
            }
            
            // Insert mahasiswa
            $query = "INSERT INTO mahasiswa (nim, nama, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon, email, password) 
                     VALUES (:nim, :nama, :tanggal_lahir, :jenis_kelamin, :alamat, :nomor_telepon, :email, :password)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':nim', $data['nim']);
            $stmt->bindValue(':nama', $data['nama']);
            $stmt->bindValue(':tanggal_lahir', $data['tanggal_lahir'] ?: null);
            $stmt->bindValue(':jenis_kelamin', $data['jenis_kelamin'] ?: null);
            $stmt->bindValue(':alamat', $data['alamat'] ?: null);
            $stmt->bindValue(':nomor_telepon', $data['nomor_telepon'] ?: null);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Registrasi berhasil'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function checkNIMExists($nim) {
        $query = "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim 
                 UNION ALL 
                 SELECT COUNT(*) FROM dosen WHERE nidn = :nim
                 UNION ALL
                 SELECT COUNT(*) FROM admin WHERE username = :nim";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nim', $nim);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_sum($results) > 0;
    }
    
    private function checkEmailExists($email) {
        $query = "SELECT COUNT(*) FROM mahasiswa WHERE email = :email 
                 UNION ALL 
                 SELECT COUNT(*) FROM dosen WHERE email = :email
                 UNION ALL
                 SELECT COUNT(*) FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_sum($results) > 0;
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getRole() {
        session_start();
        return $_SESSION['role'] ?? null;
    }
    
    public function getUserData() {
        session_start();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'nim' => $_SESSION['nim'] ?? null,
            'nama' => $_SESSION['nama'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
}

// Helper functions
function requireLogin() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserRole() {
    $auth = new Auth();
    return $auth->getRole();
}

function getUserData() {
    $auth = new Auth();
    return $auth->getUserData();
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>
