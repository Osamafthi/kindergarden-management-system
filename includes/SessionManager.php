<?php
// File: includes/SessionManager.php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

class SessionManager {
    private $db;
    private $user;
    
    public function __construct($database) {
        // Get the PDO connection from the Database object
        $this->db = $database->connect();
        $this->user = new User($this->db); // Pass PDO connection, not Database object
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for remember me auto-login
        $this->checkRememberMe();
        
        // Check session timeout
        $this->checkSessionTimeout();
    }
    
    private function checkRememberMe() {
        // If user is not logged in but has remember token
        if (!User::isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $userData = $this->user->loginWithRememberToken($_COOKIE['remember_token']);
            
            if ($userData) {
                // Set session data
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_email'] = $userData['email'];
                $_SESSION['user_name'] = $userData['full_name'] ?: $userData['email'];
                $_SESSION['user_role'] = $userData['role'];
                $_SESSION['teacher_id'] = $userData['teacher_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
            } else {
                // Invalid or expired token, clear cookie
                setcookie('remember_token', '', time() - 3600, "/");
            }
        }
    }
    
    private function checkSessionTimeout() {
        if (User::isLoggedIn() && $this->isSessionExpired()) {
            $this->logout();
        }
    }
    
    public function extendSession() {
        if (User::isLoggedIn()) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    public function isSessionExpired($timeout = 36000) { // 10 hours for teachers
        if (User::isLoggedIn()&& User::isTeacher() && isset($_SESSION['last_activity'])) {
            return (time() - $_SESSION['last_activity']) > $timeout;
        }
        return false;
    }
      
    public function isSessionExpiredAdmin($timeout = 2592000) { // 30 days (1 month) for admins
        if (User::isLoggedIn()&& User::isAdmin() && isset($_SESSION['last_activity'])) {
            return (time() - $_SESSION['last_activity']) > $timeout;
        }
        return false;
    }

    public function logout() {
        // Clear remember me token if user is logged in
        if (User::isLoggedIn() && isset($_SESSION['user_id'])) {
            $this->user->clearRememberMeToken($_SESSION['user_id']);
        }
        
        // Use User class logout method
        User::logout();
    }
    
    public function regenerateSessionId() {
        if (User::isLoggedIn()) {
            session_regenerate_id(true);
        }
    }
    
    public function getSessionInfo() {
        if (!User::isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => User::getCurrentUserId(),
            'user_email' => User::getCurrentUserEmail(),
            'user_name' => User::getCurrentUserName(),
            'user_role' => User::getCurrentUserRole(),
            'login_time' => isset($_SESSION['login_time']) ? $_SESSION['login_time'] : null,
            'last_activity' => isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] : null,
            'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0
        ];
    }
    
    public function updateLastActivity() {
        if (User::isLoggedIn()) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    public function isAdminSession() {
        return User::isAdmin();
    }
    
    public function isTeacherSession() {
        return User::isTeacher();
    }
    
    public function requireLogin() {
        User::requireLogin();
    }
    
    public function requireAdmin() {
        User::requireAdmin();
    }
    
    public function requireTeacher() {
        User::requireTeacher();
    }
    
    public function setSessionData($key, $value) {
        if (User::isLoggedIn()) {
            $_SESSION[$key] = $value;
        }
    }
    
    public function getSessionData($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public function clearSessionData($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public function destroySession() {
        $this->logout();
    }
    
    public function getSessionLifetime() {
        return ini_get('session.gc_maxlifetime');
    }
    
    public function setSessionLifetime($lifetime) {
        ini_set('session.gc_maxlifetime', $lifetime);
    }
    
    public function getSessionCookieParams() {
        return session_get_cookie_params();
    }
    
    public function setSessionCookieParams($lifetime, $path, $domain, $secure, $httponly) {
        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    }
}
