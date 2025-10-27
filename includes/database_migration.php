<?php
// File: includes/database_migration.php
// This script adds remember me functionality to the users table

require_once __DIR__ . '/../classes/Database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Add remember me fields to users table
    $sql = "ALTER TABLE users 
            ADD COLUMN remember_token VARCHAR(64) NULL,
            ADD COLUMN remember_token_expires DATETIME NULL,
            ADD COLUMN last_login DATETIME NULL";
    
    $conn->exec($sql);
    
    echo "Database migration completed successfully!\n";
    echo "Added fields: remember_token, remember_token_expires, last_login\n";
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Fields already exist in the users table.\n";
    } else {
        echo "Migration error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
