<?php
// File: includes/create_admin_user.php
// This script creates an admin user for testing purposes

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Check if admin user already exists
    $check_sql = "SELECT id FROM users WHERE email = 'admin@kindergarten.com'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo "Admin user already exists!\n";
        exit();
    }
    
    // Create admin user
    $email = 'admin@kindergarten.com';
    $password = 'admin123'; // Change this in production!
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'admin';
    
    $sql = "INSERT INTO users (email, password, role, is_active, created_at) 
            VALUES (:email, :password, :role, 1, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        $user_id = $conn->lastInsertId();
        echo "Admin user created successfully!\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        echo "User ID: $user_id\n";
        echo "\nIMPORTANT: Change the password in production!\n";
    } else {
        echo "Failed to create admin user.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
