<?php
session_start();

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function login($nim, $password) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Multi-role login query
            $query = "SELECT u.*, m.nama, m.nim, 'mahasiswa' as role_type FROM users u 
                      INNER JOIN mahasiswa m ON u.id = m.id_mahasiswa 
                      WHERE m.nim = :nim
                      
                      UNION
                      
                      SELECT u.*, d.nama_dosen as nama, d.nidn as nim, 'dosen' as role_type FROM users u
                      INNER JOIN dosen d ON u.id = d.id_dosen 
                      WHERE d.nidn = :nim
                      
                      UNION
                      
                      SELECT u.*, 'Admin' as nama, u.username as nim, 'admin' as role_type FROM users u
                      WHERE u.username = :nim AND u.role = 'admin'";
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':nim', $nim);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nim'] = $user['nim'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role_type'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    // Other methods can be added here
}
</merged_code>
