<?php
// Quick script to create admin user or convert existing user to admin
// Run this once to set up your admin account

$host = "localhost";
$db = "smartlib";
$dbUser = "root";
$dbPass = "";

$conn = new mysqli($host, $dbUser, $dbPass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Method 1: Create new admin user
function createAdminUser($conn, $name, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if role column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    
    if ($result->num_rows > 0) {
        // Role column exists
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
    } else {
        // Role column doesn't exist, add it first
        $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
    }
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
    } else {
        echo "❌ Error creating admin: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
}

// Method 2: Convert existing user to admin
function makeUserAdmin($conn, $email) {
    // Check if role column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    
    if ($result->num_rows == 0) {
        // Add role column if it doesn't exist
        $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
    }
    
    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
    $stmt->bind_param("s", $email);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "✅ User $email is now an admin!<br>";
    } else {
        echo "❌ User $email not found or already admin.<br>";
    }
    
    $stmt->close();
}

// USAGE EXAMPLES (uncomment the method you want to use):

// Example 1: Create new admin user
// createAdminUser($conn, "Admin User", "admin@smartlib.com", "admin123");

// Example 2: Convert existing user to admin
// makeUserAdmin($conn, "existing@user.com");

echo "<h3>Admin Setup Script</h3>";
echo "<p>Uncomment the appropriate function call in this script to:</p>";
echo "<ol>";
echo "<li><strong>Create new admin:</strong> createAdminUser(conn, 'Name', 'email', 'password')</li>";
echo "<li><strong>Make existing user admin:</strong> makeUserAdmin(conn, 'email')</li>";
echo "</ol>";

$conn->close();
?>
