<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login($identifier, $password) {
        try {
            // Try mahasiswa first
            $query = "SELECT id_mahasiswa as id, nim as identifier, nama, password, 'mahasiswa' as role, status 
                     FROM mahasiswa WHERE nim = :identifier AND status = 'aktif'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setSession($user);
                return true;
            }
            
            // Try dosen
            $query = "SELECT id_dosen as id, nidn as identifier, nama_dosen as nama, password, 'dosen' as role, status 
                     FROM dosen WHERE nidn = :identifier AND status = 'aktif'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setSession($user);
                return true;
            }
            
            // Try admin (assuming admin uses nim/username)
            $query = "SELECT id_admin as id, username as identifier, nama as nama, password, 'admin' as role, 'aktif' as status 
                     FROM admin WHERE username = :identifier";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setSession($user);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['identifier'] = $user['identifier'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['logged_in'] = true;
    }

    public function register($data) {
        try {
            // Check if NIM or email already exists
            $check_query = "SELECT nim, email FROM mahasiswa WHERE nim = :nim OR email = :email";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':nim', $data['nim']);
            $check_stmt->bindParam(':email', $data['email']);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                return false;
            }
            
            // Insert new mahasiswa
            $insert_query = "INSERT INTO mahasiswa (nim, nama, email, password, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon, jurusan, program_studi, angkatan, semester_aktif, kelompok_ukt, status) 
                           VALUES (:nim, :nama, :email, :password, :tanggal_lahir, :jenis_kelamin, :alamat, :nomor_telepon, :jurusan, :program_studi, :angkatan, :semester_aktif, :kelompok_ukt, 'aktif')";
            
            $insert_stmt = $this->db->prepare($insert_query);
            $insert_stmt->bindParam(':nim', $data['nim']);
            $insert_stmt->bindParam(':nama', $data['nama']);
            $insert_stmt->bindParam(':email', $data['email']);
            $insert_stmt->bindParam(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $insert_stmt->bindParam(':tanggal_lahir', $data['tanggal_lahir']);
            $insert_stmt->bindParam(':jenis_kelamin', $data['jenis_kelamin']);
            $insert_stmt->bindParam(':alamat', $data['alamat']);
            $insert_stmt->bindParam(':nomor_telepon', $data['nomor_telepon']);
            $insert_stmt->bindParam(':jurusan', $data['jurusan']);
            $insert_stmt->bindParam(':program_studi', $data['program_studi']);
            $insert_stmt->bindParam(':angkatan', $data['angkatan']);
            $insert_stmt->bindParam(':semester_aktif', $data['semester_aktif']);
            $insert_stmt->bindParam(':kelompok_ukt', $data['kelompok_ukt']);
            
            return $insert_stmt->execute();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'identifier' => $_SESSION['identifier'],
                'nama' => $_SESSION['nama'],
                'role' => $_SESSION['role'],
                'status' => $_SESSION['status']
            ];
        }
        return null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireRole($role) {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: dashboard.php');
            exit();
        }
    }
}
?>
</merged_code>
