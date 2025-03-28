<?php
require_once 'Database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Register new user with email verification
    public function register($username, $email, $password, $profile_photo) {
        // Validate inputs
        if(empty($username) || empty($email) || empty($password)) {
            return "All fields are required";
        }

        // Check if email exists
        $query = "SELECT user_id FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        
        if($stmt->rowCount() > 0) {
            return "Email already exists!";
        }

        // Handle profile photo upload
        $photo_path = $this->uploadProfilePhoto($profile_photo);
        if(!$photo_path) {
            return "Invalid profile photo format (only JPG, PNG, GIF)";
        }

        // Generate verification token
        $verification_token = bin2hex(random_bytes(50));
        $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $query = "INSERT INTO {$this->table} 
                  (username, email, password_hash, profile_photo, verification_token, verification_token_expiry)
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        if($stmt->execute([$username, $email, $hashed_password, $photo_path, $verification_token, $verification_expiry])) {
            $this->sendVerificationEmail($email, $verification_token);
            return true;
        }
        return "Registration failed";
    }

    private function uploadProfilePhoto($file) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            $target_dir = "uploads/profiles/";
            if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            move_uploaded_file($file['tmp_name'], $target_dir . $filename);
            return $target_dir . $filename;
        }
        return false;
    }

    private function sendVerificationEmail($email, $token) {
        $subject = "Email Verification";
        $verification_link = "http://yourdomain.com/verify.php?token=$token";
        $message = "Please click the link to verify your email: $verification_link";
        
        mail($email, $subject, $message);
    }

    // Login with email verification check
    public function login($email, $password) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        
        if($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$user['email_verified']) {
                return "email_not_verified";
            }
            
            if(password_verify($password, $user['password_hash'])) {
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }

    // Verify email using token
    public function verifyEmail($token) {
        $query = "SELECT user_id FROM {$this->table} 
                 WHERE verification_token = ? 
                 AND verification_token_expiry > NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        
        if($stmt->rowCount() == 1) {
            $user_id = $stmt->fetchColumn();
            
            $update = "UPDATE {$this->table} 
                      SET email_verified = TRUE, 
                          verification_token = NULL,
                          verification_token_expiry = NULL
                      WHERE user_id = ?";
            
            $stmt = $this->db->prepare($update);
            return $stmt->execute([$user_id]);
        }
        return false;
    }
}
?>