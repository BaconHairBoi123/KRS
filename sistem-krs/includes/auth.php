<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function login($nim, $password) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check mahasiswa table
            $query = "SELECT id_mahasiswa, nim, nama, email, password, 'mahasiswa' as role_type 
                     FROM mahasiswa WHERE nim = :nim";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':nim', $nim);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_mahasiswa'];
                $_SESSION['nim'] = $user['nim'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_type'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            
            // Check dosen table if mahasiswa login failed
            $query = "SELECT id_dosen, nidn as nim, nama_dosen as nama, email, password, 'dosen' as role_type 
                     FROM dosen WHERE nidn = :nim";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':nim', $nim);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_dosen'];
                $_SESSION['nim'] = $user['nim'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_type'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            
            // Check admin table if exists (you might need to create this)
            try {
                $query = "SELECT id_admin, username as nim, nama_admin as nama, email, password, 'admin' as role_type 
                         FROM admin WHERE username = :nim";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':nim', $nim);
                $stmt->execute();
                
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id_admin'];
                    $_SESSION['nim'] = $user['nim'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role_type'];
                    $_SESSION['logged_in'] = true;
                    
                    return true;
                }
            } catch (Exception $e) {
                // Admin table might not exist, ignore this error
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function register($data) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            if (!$conn) {
                error_log("Database connection failed");
                return false;
            }
            
            // Check if NIM or email already exists in mahasiswa table
            $checkQuery = "SELECT nim, email FROM mahasiswa WHERE nim = :nim OR email = :email";
            $checkStmt = $conn->prepare($checkQuery);
            
            if (!$checkStmt) {
                error_log("Failed to prepare check query: " . implode(", ", $conn->errorInfo()));
                return false;
            }
            
            $checkStmt->bindValue(':nim', $data['nim']);
            $checkStmt->bindValue(':email', $data['email']);
            
            if (!$checkStmt->execute()) {
                error_log("Failed to execute check query: " . implode(", ", $checkStmt->errorInfo()));
                return false;
            }
            
            $existingData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingData) {
                error_log("Data already exists - NIM: " . $existingData['nim'] . ", Email: " . $existingData['email']);
                return false;
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert directly into mahasiswa table
            $insertQuery = "INSERT INTO mahasiswa (nim, nama, email, password, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon) 
                           VALUES (:nim, :nama, :email, :password, :tanggal_lahir, :jenis_kelamin, :alamat, :nomor_telepon)";
            
            $insertStmt = $conn->prepare($insertQuery);
            
            if (!$insertStmt) {
                error_log("Failed to prepare insert query: " . implode(", ", $conn->errorInfo()));
                return false;
            }
            
            $insertStmt->bindValue(':nim', $data['nim']);
            $insertStmt->bindValue(':nama', $data['nama']);
            $insertStmt->bindValue(':email', $data['email']);
            $insertStmt->bindValue(':password', $hashedPassword);
            $insertStmt->bindValue(':tanggal_lahir', $data['tanggal_lahir'] ?: null);
            $insertStmt->bindValue(':jenis_kelamin', $data['jenis_kelamin'] ?: null);
            $insertStmt->bindValue(':alamat', $data['alamat'] ?: null);
            $insertStmt->bindValue(':nomor_telepon', $data['nomor_telepon'] ?: null);
            
            if (!$insertStmt->execute()) {
                error_log("Failed to insert mahasiswa: " . implode(", ", $insertStmt->errorInfo()));
                return false;
            }
            
            error_log("Registration successful for NIM: " . $data['nim']);
            return true;
            
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
                'nim' => $_SESSION['nim'],
                'nama' => $_SESSION['nama'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
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