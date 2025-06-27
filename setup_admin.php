<?php
// Admin Setup Script
require_once 'config/db.php';

echo "<h1>Admin Setup</h1>";

try {
    // Create admin table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Admin table created successfully</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating admin table: " . $conn->error . "</div>";
    }
    
    // Check if admin already exists
    $check_admin = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
    $admin_email = 'bamenorhu8@gmail.com';
    $check_admin->bind_param('s', $admin_email);
    $check_admin->execute();
    $result = $check_admin->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div style='color: orange;'>⚠️ Admin user already exists</div>";
    } else {
        // Create admin user
        $admin_password = '123';
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $admin_name = 'System Administrator';
        
        $stmt = $conn->prepare("INSERT INTO admin_users (email, password_hash, full_name, role) VALUES (?, ?, ?, 'super_admin')");
        $stmt->bind_param('sss', $admin_email, $password_hash, $admin_name);
        
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✅ Admin user created successfully</div>";
            echo "<div style='color: blue;'>📧 Email: $admin_email</div>";
            echo "<div style='color: blue;'>🔑 Password: $admin_password</div>";
        } else {
            echo "<div style='color: red;'>❌ Error creating admin user: " . $stmt->error . "</div>";
        }
        
        $stmt->close();
    }
    
    $check_admin->close();
    
    // Create sessions table for login management
    $sql = "CREATE TABLE IF NOT EXISTS admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Admin sessions table created successfully</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating sessions table: " . $conn->error . "</div>";
    }
    
    echo "<h2>Admin Setup Complete!</h2>";
    echo "<div style='color: green; font-weight: bold;'>✅ Admin authentication system is ready</div>";
    echo "<p><a href='login.php'>🔐 Go to Admin Login</a></p>";
    echo "<p><a href='index.html'>🏠 Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h1, h2 {
    color: #333;
    text-align: center;
}

div {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
}

a {
    display: inline-block;
    margin: 10px 5px;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

a:hover {
    background-color: #0056b3;
}
</style>
