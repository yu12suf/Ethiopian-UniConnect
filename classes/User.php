<?php
/**
 * User Class - Handles user authentication, registration, and profile management
 * Demonstrates: OOP, password hashing, session/cookie management
 */

class User {
    private $db;
    private $userId;
    private $userData;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Register new user with validation
     */
    public function register($data) {
        // Validate university email
        if (!$this->isUniversityEmail($data['email'])) {
            return ['success' => false, 'message' => 'Please use a valid university email (.edu.et)'];
        }
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Insert user
        $sql = "INSERT INTO users (full_name, email, password, department, phone, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['full_name'],
            $data['email'],
            $hashedPassword,
            $data['department'],
            $data['phone']
        ];
        
        $userId = $this->db->prepare($sql);
        if ($userId->execute($params)) {
            return ['success' => true, 'message' => 'Registration successful'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Login user with session and cookie support
     */
    public function login($email, $password, $remember = false) {
        $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $hashedToken = password_hash($token, PASSWORD_BCRYPT);
                
                // Store token in database
                $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$hashedToken, $user['id']]);
                
                // Set cookie for 30 days
                setcookie('remember_token', $token, time() + COOKIE_LIFETIME, '/');
                setcookie('user_id', $user['id'], time() + COOKIE_LIFETIME, '/');
            }
            
            // Update last login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['id']]);
            
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
            return $this->loginWithCookie();
        }
        
        return false;
    }
    
    /**
     * Login using remember me cookie
     */
    private function loginWithCookie() {
        $userId = $_COOKIE['user_id'];
        $token = $_COOKIE['remember_token'];
        
        $sql = "SELECT * FROM users WHERE id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($token, $user['remember_token'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear session
        session_destroy();
        
        // Clear cookies
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
        }
    }
    
    /**
     * Validate university email
     */
    private function isUniversityEmail($email) {
        return preg_match('/@.*\.edu\.et$/i', $email);
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get user data by ID
     */
    public function getUserById($userId) {
        $sql = "SELECT id, full_name, email, department, phone, profile_image, created_at FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $sql = "UPDATE users SET full_name = ?, department = ?, phone = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$data['full_name'], $data['department'], $data['phone'], $userId]);
    }
    
    /**
     * Get current user ID from session
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>
