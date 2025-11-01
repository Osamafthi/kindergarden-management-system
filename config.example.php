<?php
/**
 * Database Configuration Template
 * 
 * Copy this file to config.php and update with your actual database credentials.
 * NEVER commit config.php to version control!
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// XAMPP Socket Path (for macOS)
// Update this path based on your XAMPP installation
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

// Application Settings
define('APP_NAME', 'Kindergarten Management System');
define('APP_URL', 'http://localhost/kindergarden');

// Security Settings
// Generate a secure random key for session encryption
define('APP_KEY', 'your-secret-app-key-here');
// Define base paths that work on both systems
define('BASE_PATH', dirname(__DIR__)); // Project root
define('UPLOAD_DIR', BASE_PATH . '/assets/uploads/modules/');

