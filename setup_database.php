<?php
/**
 * Database Setup Script
 * Run this file once to create the database and tables
 */

echo "=== UniConnect Database Setup ===\n\n";

// Database configuration
$host = 'localhost';
$port = '3307';
$dbname = 'uniconnect';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without database name)
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbname' created or already exists\n";
    
    // Connect to the created database
    $pdo->exec("USE $dbname");
    
    // Read and execute schema file
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Continue even if table exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Database tables created successfully\n";
    echo "✓ Default admin user created (Yusuf Kedir - email: admin@uniconnect.edu.et, password: admin123)\n\n";
    echo "=== Setup Complete! ===\n";
    echo "You can now access the application at http://localhost:5000\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nPlease ensure:\n";
    echo "1. MySQL/XAMPP is running on port 3307\n";
    echo "2. You have proper database privileges\n";
    exit(1);
}
?>
