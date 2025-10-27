<?php
require_once 'Database.php';
class User {
    // Database connection
    protected $db;
    
    // Common properties
    protected $id;
    protected $email;
    protected $password;
    protected $roles;
    protected $is_active;
    protected $created_at;
    protected $updated_at;

    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getRoles() {
        return $this->roles;
    }

    public function getIsActive() {
        return $this->is_active;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    public function setRoles($roles) {
        $this->roles = $roles;
        return $this;
    }

    public function setIsActive($is_active) {
        $this->is_active = $is_active;
        return $this;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
        return $this;
    }

    // Common methods that all users might need
    public function authenticate($password) {
        return password_verify($password, $this->password);
    }

    public function hasRole($role) {
        return in_array($role, (array)$this->roles);
    }

    // Optional: Method to load data from array
    public function fromArray($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    // Optional: Method to convert object to array
    public function toArray() {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    // Static methods for session management
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public static function isTeacher() {
        return self::isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
    }

    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    public static function getCurrentUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }

    public static function getCurrentUserEmail() {
        return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
    }

    public static function getCurrentUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
    }

    public static function logout() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, "/");
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /kindergarden/views/auth/login.php');
            exit();
        }
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit();
        }
    }

    public static function requireTeacher() {
        self::requireLogin();
        if (!self::isTeacher()) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Teacher access required']);
            exit();
        }
    }

    // Login method
    public function login($email, $password, $remember = false) {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Get user from database with teacher active status check
            $sql = "SELECT u.*, t.full_name, t.id as teacher_id, t.is_active as teacher_is_active
                    FROM users u 
                    LEFT JOIN teachers t ON u.teacher_id = t.id 
                    WHERE u.email = :email AND u.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if user is a teacher and if teacher is inactive
            if ($user['role'] === 'teacher' && isset($user['teacher_is_active']) && $user['teacher_is_active'] == 0) {
                return ['success' => false, 'message' => 'Your account has been deactivated. Please contact the administrator.'];
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'] ?: $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['teacher_id'] = $user['teacher_id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();

            // Handle remember me
            if ($remember) {
                $this->setRememberMeToken($user['id']);
            }

            return [
                'success' => true,
                'message' => 'Login successful',
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'user_name' => $user['full_name'] ?: $user['email'],
                'user_role' => $user['role'],
                'teacher_id' => $user['teacher_id']
            ];

        } catch (PDOException $e) {
            error_log("Database error in login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        } catch (Exception $e) {
            error_log("Error in login: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    // Login with remember token
    public function loginWithRememberToken($token) {
        try {
            $sql = "SELECT u.*, t.full_name, t.id as teacher_id, t.is_active as teacher_is_active
                    FROM users u 
                    LEFT JOIN teachers t ON u.teacher_id = t.id 
                    WHERE u.remember_token = :token 
                    AND u.remember_token_expires > NOW() 
                    AND u.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if user is a teacher and if teacher is inactive
                if ($user['role'] === 'teacher' && isset($user['teacher_is_active']) && $user['teacher_is_active'] == 0) {
                    // Clear remember token since account is inactive
                    $this->clearRememberMeToken($user['id']);
                    return false;
                }
                
                // Update last activity
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
                $update_stmt = $this->db->prepare($update_sql);
                $update_stmt->bindParam(':user_id', $user['id']);
                $update_stmt->execute();
                
                return $user;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Database error in loginWithRememberToken: " . $e->getMessage());
            return false;
        }
    }

    // Set remember me token
    private function setRememberMeToken($user_id) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $sql = "UPDATE users SET remember_token = :token, remember_token_expires = :expires WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires', $expires);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                // Set cookie
                setcookie('remember_token', $token, strtotime('+30 days'), "/", "", false, true);
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Database error in setRememberMeToken: " . $e->getMessage());
            return false;
        }
    }

    // Clear remember me token
    public function clearRememberMeToken($user_id) {
        try {
            $sql = "UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, "/");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Database error in clearRememberMeToken: " . $e->getMessage());
            return false;
        }
    }
}
